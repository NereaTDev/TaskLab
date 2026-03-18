<?php

namespace App\Http\Controllers;

use App\Models\GithubConnection;
use App\Services\GithubContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GithubConnectionController extends Controller
{
    /**
     * Step 1 — Redirect the user to GitHub's OAuth authorization page.
     */
    public function redirect(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $state = Str::random(40);
        session(['github_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.github.client_id'),
            'redirect_uri' => config('services.github.redirect'),
            'scope'     => 'repo',          // read access to private + public repos
            'state'     => $state,
        ]);

        return redirect('https://github.com/login/oauth/authorize?' . $query);
    }

    /**
     * Step 2 — GitHub redirects back here with a code. Exchange it for a token
     * and store it in the session, then redirect to the repo picker.
     */
    public function callback(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        // CSRF state check
        if ($request->get('state') !== session('github_oauth_state')) {
            return redirect()->route('settings.index')
                ->with('github_error', 'Estado OAuth inválido. Inténtalo de nuevo.');
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('settings.index')
                ->with('github_error', 'No se recibió código de autorización de GitHub.');
        }

        // Exchange code for access token
        $response = Http::withHeaders(['Accept' => 'application/json'])
            ->post('https://github.com/login/oauth/access_token', [
                'client_id'     => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'code'          => $code,
                'redirect_uri'  => config('services.github.redirect'),
            ]);

        $token = $response->json('access_token');

        if (! $token) {
            $desc = $response->json('error_description', 'Error desconocido');
            return redirect()->route('settings.index')
                ->with('github_error', "GitHub rechazó la autorización: {$desc}");
        }

        // Store token in session for the repo picker step
        session([
            'github_pending_token' => $token,
            'github_oauth_state'   => null,
        ]);

        return redirect()->route('settings.index', ['github_pick' => '1']);
    }

    /**
     * Return the list of repos accessible with the pending token (AJAX).
     */
    public function repos(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $token = session('github_pending_token');
        abort_unless($token, 400);

        $repos = collect();
        $page  = 1;

        // Fetch all pages (GitHub paginates at 100)
        while (true) {
            $res = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->timeout(15)
                ->get('https://api.github.com/user/repos', [
                    'per_page' => 100,
                    'page'     => $page,
                    'sort'     => 'pushed',
                    'affiliation' => 'owner,collaborator,organization_member',
                ]);

            if (! $res->ok()) {
                break;
            }

            $batch = $res->json();
            if (empty($batch)) {
                break;
            }

            $repos = $repos->merge(collect($batch)->map(fn ($r) => [
                'full_name'    => $r['full_name'],
                'owner'        => $r['owner']['login'],
                'name'         => $r['name'],
                'private'      => $r['private'],
                'default_branch' => $r['default_branch'],
                'description'  => $r['description'],
            ]));

            if (count($batch) < 100) {
                break;
            }
            $page++;
        }

        return response()->json($repos->values());
    }

    /**
     * Step 3 — The user has picked a repo. Save the connection and sync.
     */
    public function store(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $token = session('github_pending_token');
        abort_unless($token, 400, 'No hay token OAuth pendiente. Vuelve a conectar.');

        $validated = $request->validate([
            'owner'  => ['required', 'string', 'max:255'],
            'repo'   => ['required', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:100'],
        ]);

        // Deactivate any existing connection
        GithubConnection::where('active', true)->update(['active' => false]);

        $conn = GithubConnection::create([
            'owner'  => $validated['owner'],
            'repo'   => $validated['repo'],
            'token'  => $token,
            'branch' => $validated['branch'] ?: 'main',
            'active' => true,
        ]);

        // Clear pending token from session
        session()->forget('github_pending_token');

        // Auto-sync file tree
        try {
            $count = app(GithubContextService::class)->syncFileTree($conn);
            return redirect()->route('settings.index')
                ->with('status', "Repositorio {$conn->full_name} conectado. {$count} archivos indexados.");
        } catch (\Throwable $e) {
            return redirect()->route('settings.index')
                ->with('status', 'Repositorio guardado, pero no se pudo sincronizar: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect the active connection.
     */
    public function destroy(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        GithubConnection::where('active', true)->delete();
        session()->forget('github_pending_token');

        return redirect()->route('settings.index')->with('status', 'Repositorio desconectado.');
    }

    /**
     * Update the project memory manually (SuperAdmin editing the markdown).
     */
    public function updateProjectMemory(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $conn = GithubConnection::active();
        abort_unless($conn, 404);

        $validated = $request->validate([
            'project_memory' => ['nullable', 'string', 'max:50000'],
        ]);

        $conn->update(['project_memory' => $validated['project_memory'] ?: null]);

        return redirect()->route('settings.index')->with('status', 'Memoria del proyecto actualizada.');
    }

    /**
     * Regenerate the project memory from the repo documentation.
     */
    public function regenerateProjectMemory(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $conn = GithubConnection::active();
        abort_unless($conn, 404);

        try {
            app(GithubContextService::class)->syncProjectMemory($conn);
            $conn->refresh();
            $status = $conn->project_memory
                ? 'Memoria del proyecto regenerada correctamente.'
                : 'No se encontraron archivos de documentación en el repositorio.';
        } catch (\Throwable $e) {
            $status = 'Error al regenerar la memoria: ' . $e->getMessage();
        }

        return redirect()->route('settings.index')->with('status', $status);
    }

    /**
     * Update the site URL for the active connection.
     */
    public function updateSiteUrl(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $conn = GithubConnection::active();
        abort_unless($conn, 404);

        $validated = $request->validate([
            'site_url' => ['nullable', 'url', 'max:255'],
        ]);

        $conn->update(['site_url' => $validated['site_url'] ?: null]);

        return redirect()->route('settings.index')->with('status', 'URL de producción actualizada.');
    }

    /**
     * Re-sync the file tree for the active connection.
     */
    public function sync(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $conn = GithubConnection::active();
        abort_unless($conn, 404);

        try {
            $count = app(GithubContextService::class)->syncFileTree($conn);
            return redirect()->route('settings.index')
                ->with('status', "Árbol sincronizado. {$count} archivos indexados.");
        } catch (\Throwable $e) {
            return redirect()->route('settings.index')
                ->with('status', 'Error al sincronizar: ' . $e->getMessage());
        }
    }
}

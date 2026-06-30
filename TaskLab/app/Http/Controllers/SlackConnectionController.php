<?php

namespace App\Http\Controllers;

use App\Models\SlackConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SlackConnectionController extends Controller
{
    /**
     * Step 1 — Redirige al usuario a la página de autorización de Slack.
     */
    public function redirect(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $state = Str::random(40);
        session([
            'slack_oauth_state' => $state,
            'slack_oauth_from'  => $request->query('from', 'settings'), // 'onboarding' | 'settings'
        ]);

        $query = http_build_query([
            'client_id'    => config('services.slack.client_id'),
            'scope'        => config('services.slack.scopes'),
            'redirect_uri' => config('services.slack.redirect'),
            'state'        => $state,
        ]);

        return redirect('https://slack.com/oauth/v2/authorize?' . $query);
    }

    /**
     * Step 2 — Slack redirige aquí con el código. Lo intercambiamos por el bot token.
     */
    public function callback(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $from = session('slack_oauth_from', 'settings');

        if ($request->get('state') !== session('slack_oauth_state')) {
            return $this->redirectAfterOAuth($from, error: 'Estado OAuth inválido. Inténtalo de nuevo.');
        }

        if ($request->has('error')) {
            return $this->redirectAfterOAuth($from, error: 'Autorización cancelada.');
        }

        $code = $request->get('code');
        if (! $code) {
            return $this->redirectAfterOAuth($from, error: 'No se recibió código de autorización de Slack.');
        }

        // Intercambiar código por tokens
        $response = Http::post('https://slack.com/api/oauth.v2.access', [
            'client_id'     => config('services.slack.client_id'),
            'client_secret' => config('services.slack.client_secret'),
            'code'          => $code,
            'redirect_uri'  => config('services.slack.redirect'),
        ]);

        if (! $response->ok() || ! $response->json('ok')) {
            $error = $response->json('error', 'unknown_error');
            return $this->redirectAfterOAuth($from, error: "Slack rechazó la autorización: {$error}");
        }

        $workspaceId   = $response->json('team.id');
        $workspaceName = $response->json('team.name');
        $botToken      = $response->json('access_token'); // xoxb-...
        $botUserId     = $response->json('bot_user_id');

        // Guardar/actualizar la conexión para este workspace
        SlackConnection::updateOrCreate(
            ['workspace_id' => $workspaceId],
            [
                'workspace_name' => $workspaceName,
                'bot_token'      => $botToken,
                'signing_secret' => null, // se usa SLACK_SIGNING_SECRET del env (app-level)
                'active'         => true,
            ]
        );

        session()->forget(['slack_oauth_state', 'slack_oauth_from']);

        return $this->redirectAfterOAuth($from, success: "Slack conectado al workspace \"{$workspaceName}\" correctamente.");
    }

    public function destroy(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        SlackConnection::where('active', true)->update(['active' => false]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Slack desconectado.');
    }

    private function redirectAfterOAuth(string $from, ?string $success = null, ?string $error = null)
    {
        $route  = $from === 'onboarding' ? 'onboarding.show' : 'settings.index';
        $params = $from === 'onboarding' ? ['step' => 3] : [];

        if ($error) {
            return redirect()->route($route, $params)->with('slack_error', $error);
        }

        return redirect()->route($route, $params)->with('slack_success', $success);
    }
}

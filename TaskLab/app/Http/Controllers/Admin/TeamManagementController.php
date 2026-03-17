<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamManagementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $teams = Team::withCount('users')->with('users')->orderBy('name')->get();

        // Usuarios sin equipo (excluye superadmin y requesters de Discord/Teams)
        $pendingUsers = User::where('is_super_admin', false)
            ->where(fn ($q) => $q->whereNull('user_type')->orWhere('user_type', '!=', 'requester'))
            ->whereDoesntHave('teams')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.teams.index', compact('teams', 'pendingUsers'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Team::create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', "Equipo \"{$validated['name']}\" creado.");
    }

    public function destroy(Request $request, Team $team)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $team->delete();

        return back()->with('success', "Equipo eliminado.");
    }

    public function assignUser(Request $request, Team $team)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', 'in:admin,member'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Sync: el usuario solo pertenece a UN equipo — sacar de otros primero
        $user->teams()->detach();

        $team->users()->attach($user->id, ['role' => $validated['role']]);

        // Si se asigna como admin de equipo, marcar flag is_admin
        if ($validated['role'] === 'admin') {
            $user->update(['is_admin' => true]);
        }

        return back()->with('success', "{$user->name} asignado al equipo como {$validated['role']}.");
    }

    public function removeUser(Request $request, Team $team, User $user)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $team->users()->detach($user->id);

        return back()->with('success', "{$user->name} eliminado del equipo.");
    }

    public function updateUserRole(Request $request, Team $team, User $user)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'role' => ['required', 'in:admin,member'],
        ]);

        $team->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        if ($validated['role'] === 'admin') {
            $user->update(['is_admin' => true]);
        } else {
            // Solo quitar is_admin si no es admin en ningún otro equipo
            $isAdminElsewhere = $user->teams()
                ->where('teams.id', '!=', $team->id)
                ->wherePivot('role', 'admin')
                ->exists();

            if (! $isAdminElsewhere) {
                $user->update(['is_admin' => false]);
            }
        }

        return back()->with('success', "Rol actualizado.");
    }
}

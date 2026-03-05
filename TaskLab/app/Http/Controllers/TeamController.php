<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Gate: solo admins de área o super admins pueden ver la página de equipo por ahora
        abort_unless($user && ($user->isSuperAdmin() || $user->isAreaAdmin()), 403);

        $group = $request->get('group'); // null | department (en el futuro: area, dev_type...)

        // Filtros de equipo (de momento solo se usan para la vista "listado")
        $filters = [
            'department' => $request->get('department'),
            'user_type'  => $request->get('user_type'),
            'dev_type'   => $request->get('dev_type'),
            'area'       => $request->get('area'),
        ];

        // El super admin no se muestra en la vista de equipo
        $query = User::query()
            ->where('is_super_admin', false)
            ->with('developerProfile');

        // Por ahora mantenemos filtros clásicos solo cuando no estamos en modo "group" especial
        if (! $group) {
            if ($filters['department']) {
                $query->where('department', $filters['department']);
            }

            if ($filters['user_type']) {
                $query->where('user_type', $filters['user_type']);
            }

            if ($filters['dev_type']) {
                $query->whereHas('developerProfile', function ($q) use ($filters) {
                    $q->where('type', $filters['dev_type']);
                });
            }

            if ($filters['area']) {
                $query->whereHas('developerProfile', function ($q) use ($filters) {
                    $q->whereJsonContains('areas', $filters['area']);
                });
            }
        }

        $teamMembers = $query
            ->orderBy('name')
            ->get();

        // Opciones para filtros (siguen basadas en datos reales por ahora)
        $departments = User::query()
            ->where('is_super_admin', false)
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->values();

        $userTypes = collect(['requester', 'developer']);

        $devTypes = collect(['frontend', 'backend', 'fullstack']);

        $areaOptions = collect(['web', 'plataforma', 'frontierz', 'dashboard_empresas']);

        // Agrupaciones especiales para vistas tipo tablero
        $groupedDepartments = collect();

        if ($group === 'department') {
            // Departamentos fijos para el tablero de equipo.
            // Más adelante esto podría venir de BD/config gestionado por Super Admin.
            $fixedLabels = ['Tech', 'Learning', 'Ventas', 'Sin departamento'];
            $labelMap = [
                'tech'     => 'Tech',
                'learning' => 'Learning',
                'ventas'   => 'Ventas',
            ];

            // Inicializamos todas las columnas como colecciones vacías
            foreach ($fixedLabels as $label) {
                $groupedDepartments[$label] = collect();
            }

            // Asignamos cada miembro a una de las columnas fijas
            foreach ($teamMembers as $member) {
                $raw = $member->department;
                $key = $raw ? strtolower($raw) : null;
                $label = $key && isset($labelMap[$key]) ? $labelMap[$key] : 'Sin departamento';

                $groupedDepartments[$label]->push($member);
            }
        }

        return view('team.index', [
            'teamMembers'        => $teamMembers,
            'filters'            => $filters,
            'departments'        => $departments,
            'userTypes'          => $userTypes,
            'devTypes'           => $devTypes,
            'areaOptions'        => $areaOptions,
            'group'              => $group,
            'groupedDepartments' => $groupedDepartments,
        ]);
    }

    public function reassignDepartment(Request $request)
    {
        $current = $request->user();

        // Solo el Super Admin puede mover personas entre departamentos por ahora
        abort_unless($current && $current->isSuperAdmin(), 403);

        $validated = $request->validate([
            'user_id'    => ['required', 'integer', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = User::where('is_super_admin', false)->findOrFail($validated['user_id']);

        $department = $validated['department'];
        if ($department === 'Sin departamento') {
            $department = null;
        }

        $user->department = $department;
        $user->save();

        return response()->json([
            'status'     => 'ok',
            'user_id'    => $user->id,
            'department' => $user->department,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CategoryType;
use App\Models\CategoryValue;
use App\Models\User;
use App\Models\UserCategoryAssignment;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Gate: solo admins de área o super admins pueden ver la página de equipo por ahora
        abort_unless($user && ($user->isSuperAdmin() || $user->isAreaAdmin()), 403);

        // group: null | 'category:slug'
        $group = $request->get('group');

        // Filtros de equipo (de momento solo se usan para la vista "lista")
        $filters = [
            'department' => $request->get('department'),
            'user_type'  => $request->get('user_type'),
            'dev_type'   => $request->get('dev_type'),
            'area'       => $request->get('area'),
        ];

        // El super admin no se muestra en la vista de equipo
        $query = User::query()
            ->where('is_super_admin', false)
            ->with(['developerProfile', 'categoryValues.type', 'categoryValues.parent']);

        // Filtros clásicos solo cuando no estamos en modo tablero especial
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

        // Opciones para filtros (lista)
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

        // Tipos de categoría (botones dinámicos de vista)
        $categoryTypes = CategoryType::orderBy('name')->get();

        // Agrupaciones especiales para vistas tipo tablero
        $categoryBoardType = null;
        $categoryColumns = collect();
        $categoryAssignCounts = [];

        if ($group && str_starts_with($group, 'category:')) {
            $slug = substr($group, \strlen('category:'));

            $categoryBoardType = CategoryType::where('slug', $slug)->first();

            if ($categoryBoardType) {
                $topValues = CategoryValue::where('category_type_id', $categoryBoardType->id)
                    ->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();

                $childrenByParent = CategoryValue::where('category_type_id', $categoryBoardType->id)
                    ->whereNotNull('parent_id')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get()
                    ->groupBy('parent_id');

                $allValues = CategoryValue::where('category_type_id', $categoryBoardType->id)->get();
                $valuesById = $allValues->keyBy('id');

                $assignmentsByUser = UserCategoryAssignment::whereIn('user_id', $teamMembers->pluck('id'))
                    ->whereIn('category_value_id', $allValues->pluck('id'))
                    ->get()
                    ->groupBy('user_id');

                $categoryAssignCounts = $assignmentsByUser->map->count()->toArray();

                // Columna especial para "Sin valor" de ese tipo
                $categoryColumns['__none__'] = [
                    'label'            => 'Sin ' . strtolower($categoryBoardType->name),
                    'top'              => null,
                    'users_for_parent' => collect(),
                    'children'         => collect(),
                ];

                foreach ($topValues as $top) {
                    $children = $childrenByParent->get($top->id, collect());

                    $categoryColumns[$top->id] = [
                        'label'            => $top->name,
                        'top'              => $top,
                        'users_for_parent' => collect(),
                        'children'         => $children->mapWithKeys(function ($child) {
                            return [
                                $child->id => [
                                    'value' => $child,
                                    'users' => collect(),
                                ],
                            ];
                        }),
                    ];
                }

                // Asignar usuarios a columnas/secciones (soportar múltiples asignaciones por tipo)
                foreach ($teamMembers as $member) {
                    $userAssigns = $assignmentsByUser->get($member->id);

                    if (! $userAssigns || $userAssigns->isEmpty()) {
                        $categoryColumns['__none__']['users_for_parent']->push($member);
                        continue;
                    }

                    $assignedSomewhere = false;

                    foreach ($userAssigns as $assign) {
                        $value = $valuesById->get($assign->category_value_id);

                        if (! $value) {
                            continue;
                        }

                        $topValue = $value->parent_id ? $valuesById->get($value->parent_id) : $value;

                        if (! $topValue || ! isset($categoryColumns[$topValue->id])) {
                            continue;
                        }

                        if ($value->parent_id && isset($categoryColumns[$topValue->id]['children'][$value->id])) {
                            $categoryColumns[$topValue->id]['children'][$value->id]['users']->push($member);
                        } else {
                            $categoryColumns[$topValue->id]['users_for_parent']->push($member);
                        }

                        $assignedSomewhere = true;
                    }

                    if (! $assignedSomewhere) {
                        $categoryColumns['__none__']['users_for_parent']->push($member);
                    }
                }
            }
        }

        return view('team.index', [
            'teamMembers'          => $teamMembers,
            'filters'              => $filters,
            'departments'          => $departments,
            'userTypes'            => $userTypes,
            'devTypes'             => $devTypes,
            'areaOptions'          => $areaOptions,
            'group'                => $group,
            'categoryTypes'        => $categoryTypes,
            'categoryBoardType'    => $categoryBoardType,
            'categoryColumns'      => $categoryColumns,
            'categoryAssignCounts' => $categoryAssignCounts,
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

    public function reassignCategory(Request $request)
    {
        $current = $request->user();

        // Solo el Super Admin puede mover/clonar/eliminar asignaciones de categorías por ahora
        abort_unless($current && $current->isSuperAdmin(), 403);

        $validated = $request->validate([
            'user_id'           => ['required', 'integer', 'exists:users,id'],
            'category_type_slug'=> ['required', 'string', 'exists:category_types,slug'],
            'category_value_id' => ['nullable', 'integer', 'exists:category_values,id'],
            'clone'             => ['nullable', 'boolean'],
            'delete_single'     => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = User::where('is_super_admin', false)->findOrFail($validated['user_id']);

        $type         = CategoryType::where('slug', $validated['category_type_slug'])->firstOrFail();
        $clone        = (bool) ($validated['clone'] ?? false);
        $deleteSingle = (bool) ($validated['delete_single'] ?? false);

        // Si no se especifica category_value_id, eliminar todas las asignaciones de este tipo
        if (! $validated['category_value_id']) {
            $typeValueIds = CategoryValue::where('category_type_id', $type->id)->pluck('id');

            UserCategoryAssignment::where('user_id', $user->id)
                ->whereIn('category_value_id', $typeValueIds)
                ->delete();

            return response()->json([
                'status'  => 'ok',
                'user_id' => $user->id,
                'value'   => null,
            ]);
        }

        $value = CategoryValue::findOrFail($validated['category_value_id']);

        abort_unless($value->category_type_id === $type->id, 400);

        // Eliminar solo la asignación concreta (cuando hay varias asignaciones para el tipo)
        if ($deleteSingle) {
            UserCategoryAssignment::where('user_id', $user->id)
                ->where('category_value_id', $value->id)
                ->delete();

            return response()->json([
                'status'  => 'ok',
                'user_id' => $user->id,
                'value'   => null,
            ]);
        }

        $typeValueIds = CategoryValue::where('category_type_id', $type->id)->pluck('id');

        if ($clone) {
            // Añadir sin borrar otras asignaciones del mismo tipo
            UserCategoryAssignment::firstOrCreate([
                'user_id'          => $user->id,
                'category_value_id'=> $value->id,
            ]);
        } else {
            // Mover: eliminar asignaciones previas de este tipo y crear una nueva
            UserCategoryAssignment::where('user_id', $user->id)
                ->whereIn('category_value_id', $typeValueIds)
                ->delete();

            UserCategoryAssignment::create([
                'user_id'          => $user->id,
                'category_value_id'=> $value->id,
            ]);
        }

        return response()->json([
            'status'  => 'ok',
            'user_id' => $user->id,
            'value'   => $value->name,
        ]);
    }
}

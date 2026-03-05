<?php

namespace App\Http\Controllers;

use App\Models\CategoryType;
use App\Models\CategoryValue;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Solo el Super Admin puede acceder a Configuración
        abort_unless($user && $user->isSuperAdmin(), 403);

        $activeTypeSlug = $request->get('type');

        // Cargamos todos los tipos de categoría (para la lista lateral)
        $categoryTypes = CategoryType::orderBy('name')->get();

        // Determinamos el tipo activo (focus en la UI)
        $activeType = null;
        if ($categoryTypes->isNotEmpty()) {
            $activeType = $activeTypeSlug
                ? $categoryTypes->firstWhere('slug', $activeTypeSlug)
                : $categoryTypes->first();
        }

        // Para el tipo activo, cargamos valores + subcategorías
        $activeTypeWithValues = null;
        if ($activeType) {
            $activeTypeWithValues = CategoryType::with([
                'values' => function ($q) {
                    $q->whereNull('parent_id')->orderBy('sort_order')->orderBy('name');
                },
                'values.children' => function ($q) {
                    $q->orderBy('sort_order')->orderBy('name');
                },
            ])->find($activeType->id);
        }

        return view('settings.index', [
            'categoryTypes'        => $categoryTypes,
            'activeType'          => $activeType,
            'activeTypeWithValues'=> $activeTypeWithValues,
        ]);
    }

    // ---- Departamentos legacy (podemos dejar la lógica por si se usa en el futuro) ----

    public function storeDepartment(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        Department::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('settings.index')->with('status', 'Departamento creado.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,'.$department->id],
        ]);

        $department->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('settings.index')->with('status', 'Departamento actualizado.');
    }

    public function destroyDepartment(Request $request, Department $department)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $department->delete();

        return redirect()->route('settings.index')->with('status', 'Departamento eliminado.');
    }

    // ---- Categorías genéricas (CategoryType / CategoryValue) ----

    public function storeCategoryType(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:category_types,name'],
        ]);

        $type = CategoryType::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('settings.index', ['type' => $type->slug])->with('status', 'Tipo de categoría creado.');
    }

    public function destroyCategoryType(Request $request, CategoryType $categoryType)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $categoryType->delete();

        return redirect()->route('settings.index')->with('status', 'Tipo de categoría eliminado.');
    }

    public function storeCategoryValue(Request $request, CategoryType $categoryType)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:category_values,id'],
        ]);

        CategoryValue::create([
            'category_type_id' => $categoryType->id,
            'name'             => $validated['name'],
            'slug'             => Str::slug($validated['name']),
            'parent_id'        => $validated['parent_id'] ?? null,
            'sort_order'       => 0,
        ]);

        return redirect()->route('settings.index', ['type' => $categoryType->slug])->with('status', 'Valor de categoría creado.');
    }

    public function updateCategoryValue(Request $request, CategoryValue $categoryValue)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $categoryValue->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        $type = $categoryValue->type;

        return redirect()->route('settings.index', ['type' => $type?->slug])->with('status', 'Valor de categoría actualizado.');
    }

    public function destroyCategoryValue(Request $request, CategoryValue $categoryValue)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $type = $categoryValue->type;
        $categoryValue->delete();

        return redirect()->route('settings.index', ['type' => $type?->slug])->with('status', 'Valor de categoría eliminado.');
    }
}

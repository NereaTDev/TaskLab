<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Solo el Super Admin puede acceder a Configuración
        abort_unless($user && $user->isSuperAdmin(), 403);

        $departments = Department::orderBy('name')->get();

        return view('settings.index', compact('departments'));
    }

    public function storeDepartment(Request $request)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        Department::create([
            'name' => $validated['name'],
            'slug' => \Str::slug($validated['name']),
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
            'slug' => \Str::slug($validated['name']),
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
}

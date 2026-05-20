<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proyectos\StoreRequerimientoRequest;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\User;

class RequerimientoController extends Controller
{
    public function index(Project $proyecto)
    {
        $proyecto->load(['phases', 'requirements.assignedTo']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);
        return view('requerimientos.index', compact('proyecto', 'usuarios'));
    }

    public function store(StoreRequerimientoRequest $request, Project $proyecto)
    {
        $proyecto->requirements()->create(
            $request->validated() + ['created_by' => auth()->id()]
        );

        return back()->with('success', 'Requerimiento agregado.');
    }

    public function update(Project $proyecto, Requirement $requerimiento)
    {
        $data = request()->validate([
            'status'   => ['required', 'in:' . implode(',', Requirement::STATUSES)],
            'priority' => ['nullable', 'in:' . implode(',', Requirement::PRIORITIES)],
        ]);

        $requerimiento->update($data);
        return back()->with('success', 'Requerimiento actualizado.');
    }

    public function destroy(Project $proyecto, Requirement $requerimiento)
    {
        $requerimiento->delete();
        return back()->with('success', 'Requerimiento eliminado.');
    }
}

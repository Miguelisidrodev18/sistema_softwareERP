<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proyectos\StoreProyectoRequest;
use App\Http\Requests\Proyectos\UpdateProyectoRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;

class ProyectoController extends Controller
{
    public function index()
    {
        $clientes = Client::activos()->orderBy('razon_social')->get(['id', 'razon_social', 'nombre_comercial']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);
        return view('proyectos.index', compact('clientes', 'usuarios'));
    }

    public function create()
    {
        $clientes = Client::activos()->orderBy('razon_social')->get(['id', 'razon_social', 'nombre_comercial']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);
        return view('proyectos.create', compact('clientes', 'usuarios'));
    }

    public function store(StoreProyectoRequest $request)
    {
        $proyecto = Project::create($request->safe()->except('phases') + ['created_by' => auth()->id()]);

        // Crear fases si vienen en el form
        if ($request->filled('phases')) {
            foreach ($request->input('phases') as $i => $fase) {
                if (!empty($fase['name'])) {
                    $proyecto->phases()->create([
                        'name'  => $fase['name'],
                        'order' => $i,
                    ]);
                }
            }
        }

        return redirect()
            ->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto creado correctamente.');
    }

    public function show(Project $proyecto)
    {
        $proyecto->load(['client', 'responsible', 'phases.requirements', 'requirements']);
        return view('proyectos.show', compact('proyecto'));
    }

    public function edit(Project $proyecto)
    {
        $clientes = Client::activos()->orderBy('razon_social')->get(['id', 'razon_social', 'nombre_comercial']);
        $usuarios = User::orderBy('name')->get(['id', 'name']);
        $proyecto->load('phases');
        return view('proyectos.edit', compact('proyecto', 'clientes', 'usuarios'));
    }

    public function update(UpdateProyectoRequest $request, Project $proyecto)
    {
        $proyecto->update($request->validated());

        return redirect()
            ->route('proyectos.show', $proyecto)
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    public function destroy(Project $proyecto)
    {
        $proyecto->delete();
        return redirect()->route('proyectos.index')->with('success', 'Proyecto eliminado.');
    }

    // ── Actualizar progreso de una fase ──────────────────────────────
    public function updatePhase(Project $proyecto, ProjectPhase $fase)
    {
        $fase->update([
            'progress' => request()->validate(['progress' => 'required|integer|min:0|max:100'])['progress'],
            'status'   => request()->input('status', $fase->status),
        ]);
        $proyecto->recalcularProgreso();

        return back()->with('success', 'Fase actualizada.');
    }
}

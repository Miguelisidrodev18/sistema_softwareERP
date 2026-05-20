<?php

use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Proyectos\DailyReportController;
use App\Http\Controllers\Proyectos\ProyectoController;
use App\Http\Controllers\Proyectos\RequerimientoController;
use App\Http\Controllers\Proyectos\SprintController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Clientes ────────────────────────────────────────────────────────
// IMPORTANTE: rutas con segmento fijo (create) ANTES de wildcards ({cliente})
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/clientes', [ClienteController::class, 'index'])
        ->middleware('permission:clientes.ver')
        ->name('clientes.index');

    // create ANTES de {cliente} para que no lo capture el wildcard
    Route::get('/clientes/create', [ClienteController::class, 'create'])
        ->middleware('permission:clientes.crear')
        ->name('clientes.create');

    Route::post('/clientes', [ClienteController::class, 'store'])
        ->middleware('permission:clientes.crear')
        ->name('clientes.store');

    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])
        ->middleware('permission:clientes.ver')
        ->name('clientes.show');

    Route::get('/clientes/{cliente}/edit', [ClienteController::class, 'edit'])
        ->middleware('permission:clientes.editar')
        ->name('clientes.edit');

    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:clientes.editar')
        ->name('clientes.update');

    Route::patch('/clientes/{cliente}', [ClienteController::class, 'update'])
        ->middleware('permission:clientes.editar');

    Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])
        ->middleware('permission:clientes.eliminar')
        ->name('clientes.destroy');

});

// ── Módulos en construcción (placeholders por sprint) ───────────────
Route::middleware(['auth', 'verified'])->group(function () {
    $proximamente = fn(string $modulo, int $sprint) =>
        view('proximamente', compact('modulo', 'sprint'));

    // Proyectos
    Route::get('/proyectos', [ProyectoController::class, 'index'])
        ->middleware('permission:proyectos.ver|proyectos.ver_asignados')
        ->name('proyectos.index');
    Route::get('/proyectos/create', [ProyectoController::class, 'create'])
        ->middleware('permission:proyectos.crear')->name('proyectos.create');
    Route::post('/proyectos', [ProyectoController::class, 'store'])
        ->middleware('permission:proyectos.crear')->name('proyectos.store');
    Route::get('/proyectos/{proyecto}', [ProyectoController::class, 'show'])
        ->middleware('permission:proyectos.ver|proyectos.ver_asignados')->name('proyectos.show');
    Route::get('/proyectos/{proyecto}/edit', [ProyectoController::class, 'edit'])
        ->middleware('permission:proyectos.editar')->name('proyectos.edit');
    Route::put('/proyectos/{proyecto}', [ProyectoController::class, 'update'])
        ->middleware('permission:proyectos.editar')->name('proyectos.update');
    Route::patch('/proyectos/{proyecto}', [ProyectoController::class, 'update'])
        ->middleware('permission:proyectos.editar');
    Route::delete('/proyectos/{proyecto}', [ProyectoController::class, 'destroy'])
        ->middleware('permission:proyectos.eliminar')->name('proyectos.destroy');
    Route::patch('/proyectos/{proyecto}/fases/{fase}', [ProyectoController::class, 'updatePhase'])
        ->middleware('permission:proyectos.editar')->name('proyectos.fases.update');
    Route::patch('/proyectos/{proyecto}/checklist/{index}', [ProyectoController::class, 'toggleChecklist'])
        ->middleware('permission:proyectos.editar')->name('proyectos.checklist.toggle');
    Route::patch('/proyectos/{proyecto}/notas', [ProyectoController::class, 'updateNotas'])
        ->middleware('permission:proyectos.editar')->name('proyectos.notas.update');

    // Requerimientos (anidados bajo proyecto)
    Route::get('/proyectos/{proyecto}/requerimientos', [RequerimientoController::class, 'index'])
        ->middleware('permission:requerimientos.ver')->name('requerimientos.index');
    Route::post('/proyectos/{proyecto}/requerimientos', [RequerimientoController::class, 'store'])
        ->middleware('permission:requerimientos.crear')->name('requerimientos.store');
    Route::patch('/proyectos/{proyecto}/requerimientos/{requerimiento}', [RequerimientoController::class, 'update'])
        ->middleware('permission:requerimientos.editar')->name('requerimientos.update');
    Route::delete('/proyectos/{proyecto}/requerimientos/{requerimiento}', [RequerimientoController::class, 'destroy'])
        ->middleware('permission:requerimientos.editar')->name('requerimientos.destroy');

    Route::get('/requerimientos', fn() => redirect()->route('proyectos.index'))->name('requerimientos.global');

    // Sprints (anidados bajo proyecto)
    Route::get('/proyectos/{proyecto}/sprints', [SprintController::class, 'index'])
        ->middleware('permission:sprints.ver')->name('sprints.index');
    Route::post('/proyectos/{proyecto}/sprints', [SprintController::class, 'store'])
        ->middleware('permission:sprints.gestionar')->name('sprints.store');
    Route::get('/proyectos/{proyecto}/sprints/{sprint}', [SprintController::class, 'show'])
        ->middleware('permission:sprints.ver')->name('sprints.show');
    Route::patch('/proyectos/{proyecto}/sprints/{sprint}', [SprintController::class, 'update'])
        ->middleware('permission:sprints.gestionar')->name('sprints.update');

    // Daily reports (anidados bajo sprint)
    Route::post('/proyectos/{proyecto}/sprints/{sprint}/daily', [DailyReportController::class, 'store'])
        ->middleware('permission:sprints.daily')->name('sprints.daily.store');

    // Asignar requirement a sprint
    Route::patch('/proyectos/{proyecto}/requerimientos/{requerimiento}/sprint', function (
        \App\Models\Project $proyecto,
        \App\Models\Requirement $requerimiento,
        Request $request
    ) {
        $requerimiento->update(['sprint_id' => $request->input('sprint_id')]);
        return back()->with('success', 'Tarea actualizada.');
    })->middleware('permission:sprints.gestionar')->name('requerimientos.asignar-sprint');
    Route::get('/ventas',           fn() => $proximamente('Cotizaciones', 3))->name('cotizaciones.index');
    Route::get('/facturacion',      fn() => $proximamente('Facturación SUNAT', 4))->name('facturacion.index');
    Route::get('/caja',             fn() => $proximamente('Caja', 5))->name('caja.index');
    Route::get('/entregas',         fn() => $proximamente('Entregas', 5))->name('entregas.index');
    Route::get('/reportes',         fn() => $proximamente('Reportes', 6))->name('reportes.index');
    Route::get('/configuracion', [ConfigController::class, 'index'])
        ->middleware('permission:configuracion.ver')
        ->name('configuracion.index');
    Route::put('/configuracion', [ConfigController::class, 'update'])
        ->middleware('permission:configuracion.editar')
        ->name('configuracion.update');
    Route::get('/admin/usuarios',   fn() => $proximamente('Usuarios', 1))->name('usuarios.index');
});

// ── Proxy consulta DNI / RUC (token queda server-side) ──────────────
Route::middleware(['auth'])->get('/api/consulta-documento', function (Request $request) {
    $tipo   = strtoupper($request->input('tipo', ''));
    $numero = preg_replace('/\D/', '', $request->input('numero', ''));

    if (!in_array($tipo, ['DNI', 'RUC']) || empty($numero)) {
        return response()->json(['error' => 'Parámetros inválidos'], 422);
    }

    $endpoint = $tipo === 'RUC'
        ? config('services.apis_net_pe.url') . '/ruc?numero=' . $numero
        : config('services.apis_net_pe.url') . '/dni?numero=' . $numero;

    $token = config('services.apis_net_pe.token');

    $req = Http::timeout(8)->acceptJson();
    if ($token) {
        $req = $req->withToken($token);
    }

    $response = $req->get($endpoint);

    if ($response->status() === 404) {
        return response()->json(['error' => 'No encontrado'], 404);
    }

    if ($response->failed()) {
        return response()->json(['error' => 'Error al consultar la API'], 502);
    }

    return response()->json($response->json());
});

require __DIR__.'/auth.php';

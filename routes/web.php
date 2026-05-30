<?php

use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RolController;
use App\Http\Controllers\Planilla\PlanillaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Caja\CajaController;
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Entregas\EntregaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Proyectos\DailyReportController;
use App\Http\Controllers\Proyectos\ProyectoController;
use App\Http\Controllers\Proyectos\RequerimientoController;
use App\Http\Controllers\Proyectos\SprintController;
use App\Http\Controllers\Facturacion\FacturaController;
use App\Http\Controllers\Ventas\CotizacionController;
use App\Http\Controllers\Ventas\QuotePaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
})->name('landing');

Route::get('/dashboard', DashboardController::class)
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
        $requerimiento->update(['sprint_id' => $request->input('sprint_id') ?: null]);
        return back()->with('success', 'Tarea actualizada.');
    })->middleware('permission:sprints.gestionar')->name('requerimientos.asignar-sprint');
    // ── Cotizaciones ────────────────────────────────────────────────
    Route::get('/ventas',                    [CotizacionController::class, 'index'])->middleware('permission:cotizaciones.ver')->name('cotizaciones.index');
    Route::get('/ventas/create',             [CotizacionController::class, 'create'])->middleware('permission:cotizaciones.crear')->name('cotizaciones.create');
    Route::post('/ventas',                   [CotizacionController::class, 'store'])->middleware('permission:cotizaciones.crear')->name('cotizaciones.store');
    Route::get('/ventas/{cotizacion}',       [CotizacionController::class, 'show'])->middleware('permission:cotizaciones.ver')->name('cotizaciones.show');
    Route::get('/ventas/{cotizacion}/edit',  [CotizacionController::class, 'edit'])->middleware('permission:cotizaciones.editar')->name('cotizaciones.edit');
    Route::put('/ventas/{cotizacion}',       [CotizacionController::class, 'update'])->middleware('permission:cotizaciones.editar')->name('cotizaciones.update');
    Route::delete('/ventas/{cotizacion}',    [CotizacionController::class, 'destroy'])->middleware('permission:cotizaciones.eliminar')->name('cotizaciones.destroy');
    Route::get('/ventas/{cotizacion}/pdf',   [CotizacionController::class, 'pdf'])->middleware('permission:cotizaciones.pdf')->name('cotizaciones.pdf');
    Route::patch('/ventas/{cotizacion}/estado', [CotizacionController::class, 'cambiarEstado'])->middleware('permission:cotizaciones.aprobar')->name('cotizaciones.estado');

    // ── Plan de cobros (cuotas) ──────────────────────────────────────
    Route::middleware('permission:cotizaciones.editar')->group(function () {
        Route::post('/ventas/{cotizacion}/pagos/plan',          [QuotePaymentController::class, 'generarPlan'])->name('cotizaciones.pagos.plan');
        Route::post('/ventas/{cotizacion}/pagos',               [QuotePaymentController::class, 'store'])->name('cotizaciones.pagos.store');
        Route::patch('/ventas/{cotizacion}/pagos/{pago}',       [QuotePaymentController::class, 'update'])->name('cotizaciones.pagos.update');
        Route::patch('/ventas/{cotizacion}/pagos/{pago}/pagar', [QuotePaymentController::class, 'marcarPagado'])->name('cotizaciones.pagos.pagar');
        Route::patch('/ventas/{cotizacion}/pagos/{pago}/revertir', [QuotePaymentController::class, 'desmarcarPagado'])->name('cotizaciones.pagos.revertir');
        Route::delete('/ventas/{cotizacion}/pagos/{pago}',      [QuotePaymentController::class, 'destroy'])->name('cotizaciones.pagos.destroy');
    });
    // ── Facturación SUNAT ────────────────────────────────────────────
    Route::get('/facturacion',                       [FacturaController::class, 'index'])->middleware('permission:facturacion.ver')->name('facturacion.index');
    Route::get('/facturacion/create',                [FacturaController::class, 'create'])->middleware('permission:facturacion.emitir')->name('facturacion.create');
    Route::post('/facturacion',                      [FacturaController::class, 'store'])->middleware('permission:facturacion.emitir')->name('facturacion.store');
    Route::get('/facturacion/{factura}',             [FacturaController::class, 'show'])->middleware('permission:facturacion.ver')->name('facturacion.show');
    Route::delete('/facturacion/{factura}',          [FacturaController::class, 'destroy'])->middleware('permission:facturacion.anular')->name('facturacion.destroy');
    Route::post('/facturacion/{factura}/enviar',     [FacturaController::class, 'enviar'])->middleware('permission:facturacion.emitir')->name('facturacion.enviar');
    Route::get('/facturacion/{factura}/pdf',         [FacturaController::class, 'descargarPdf'])->middleware('permission:facturacion.ver')->name('facturacion.pdf');
    Route::get('/facturacion/{factura}/xml',         [FacturaController::class, 'descargarXml'])->middleware('permission:facturacion.ver')->name('facturacion.xml');
    Route::get('/facturacion/{factura}/cdr',         [FacturaController::class, 'descargarCdr'])->middleware('permission:facturacion.ver')->name('facturacion.cdr');
    // ── Caja ────────────────────────────────────────────────────────────
    Route::get('/caja',                    [CajaController::class, 'index'])->middleware('permission:caja.ver')->name('caja.index');
    Route::get('/caja/create',             [CajaController::class, 'create'])->middleware('permission:caja.crear')->name('caja.create');
    Route::post('/caja',                   [CajaController::class, 'store'])->middleware('permission:caja.crear')->name('caja.store');
    Route::get('/caja/{movimiento}',       [CajaController::class, 'show'])->middleware('permission:caja.ver')->name('caja.show');
    Route::get('/caja/{movimiento}/edit',  [CajaController::class, 'edit'])->middleware('permission:caja.editar')->name('caja.edit');
    Route::put('/caja/{movimiento}',       [CajaController::class, 'update'])->middleware('permission:caja.editar')->name('caja.update');
    Route::delete('/caja/{movimiento}',    [CajaController::class, 'destroy'])->middleware('permission:caja.eliminar')->name('caja.destroy');

    // ── Entregas ─────────────────────────────────────────────────────────
    Route::get('/entregas',                  [EntregaController::class, 'index'])->middleware('permission:entregas.ver')->name('entregas.index');
    Route::get('/entregas/create',           [EntregaController::class, 'create'])->middleware('permission:entregas.crear')->name('entregas.create');
    Route::post('/entregas',                 [EntregaController::class, 'store'])->middleware('permission:entregas.crear')->name('entregas.store');
    Route::get('/entregas/{entrega}',        [EntregaController::class, 'show'])->middleware('permission:entregas.ver')->name('entregas.show');
    Route::get('/entregas/{entrega}/edit',   [EntregaController::class, 'edit'])->middleware('permission:entregas.editar')->name('entregas.edit');
    Route::put('/entregas/{entrega}',        [EntregaController::class, 'update'])->middleware('permission:entregas.editar')->name('entregas.update');
    Route::delete('/entregas/{entrega}',     [EntregaController::class, 'destroy'])->middleware('permission:entregas.eliminar')->name('entregas.destroy');
    Route::get('/entregas/{entrega}/acta',   [EntregaController::class, 'acta'])->middleware('permission:entregas.ver')->name('entregas.acta');
    Route::get('/reportes',         fn() => $proximamente('Reportes', 6))->name('reportes.index');

    // ── Planilla ─────────────────────────────────────────────────────────
    Route::get('/planilla',                    [PlanillaController::class, 'index'])->middleware('permission:planilla.ver')->name('planilla.index');
    Route::get('/planilla/create',             [PlanillaController::class, 'create'])->middleware('permission:planilla.crear')->name('planilla.create');
    Route::post('/planilla',                   [PlanillaController::class, 'store'])->middleware('permission:planilla.crear')->name('planilla.store');
    Route::get('/planilla/{planilla}',         [PlanillaController::class, 'show'])->middleware('permission:planilla.ver')->name('planilla.show');
    Route::patch('/planilla/{planilla}/pagar', [PlanillaController::class, 'pagar'])->middleware('permission:planilla.pagar')->name('planilla.pagar');
    Route::patch('/planilla/{planilla}/revertir', [PlanillaController::class, 'revertir'])->middleware('permission:planilla.pagar')->name('planilla.revertir');
    Route::delete('/planilla/{planilla}',      [PlanillaController::class, 'destroy'])->middleware('permission:planilla.eliminar')->name('planilla.destroy');
    Route::get('/configuracion', [ConfigController::class, 'index'])
        ->middleware('permission:configuracion.ver')
        ->name('configuracion.index');
    Route::put('/configuracion', [ConfigController::class, 'update'])
        ->middleware('permission:configuracion.editar')
        ->name('configuracion.update');
    // ── Usuarios (admin) ────────────────────────────────────────────────
    Route::get('/admin/usuarios',              [UsuarioController::class, 'index'])->middleware('permission:usuarios.ver')->name('usuarios.index');
    Route::get('/admin/usuarios/create',       [UsuarioController::class, 'create'])->middleware('permission:usuarios.crear')->name('usuarios.create');
    Route::post('/admin/usuarios',             [UsuarioController::class, 'store'])->middleware('permission:usuarios.crear')->name('usuarios.store');
    Route::get('/admin/usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])->middleware('permission:usuarios.editar')->name('usuarios.edit');
    Route::put('/admin/usuarios/{usuario}',    [UsuarioController::class, 'update'])->middleware('permission:usuarios.editar')->name('usuarios.update');
    Route::delete('/admin/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->middleware('permission:usuarios.eliminar')->name('usuarios.destroy');
    Route::patch('/admin/usuarios/{usuario}/toggle', [UsuarioController::class, 'toggleActivo'])->middleware('permission:usuarios.editar')->name('usuarios.toggle');
    Route::patch('/admin/usuarios/{usuario}/reset-password', [UsuarioController::class, 'resetPassword'])->middleware('permission:usuarios.editar')->name('usuarios.reset-password');

    // ── Roles ────────────────────────────────────────────────────────────
    Route::get('/admin/roles',               [RolController::class, 'index'])->middleware('permission:usuarios.ver')->name('roles.index');
    Route::get('/admin/roles/create',        [RolController::class, 'create'])->middleware('permission:usuarios.crear')->name('roles.create');
    Route::post('/admin/roles',              [RolController::class, 'store'])->middleware('permission:usuarios.crear')->name('roles.store');
    Route::get('/admin/roles/{rol}/edit',    [RolController::class, 'edit'])->middleware('permission:usuarios.editar')->name('roles.edit');
    Route::put('/admin/roles/{rol}',         [RolController::class, 'update'])->middleware('permission:usuarios.editar')->name('roles.update');
    Route::delete('/admin/roles/{rol}',      [RolController::class, 'destroy'])->middleware('permission:usuarios.eliminar')->name('roles.destroy');
});

// ── Estado de la API SUNAT externa ──────────────────────────────────
Route::middleware(['auth'])->get('/api/sunat-api-status', function () {
    $service = app(\App\Services\SunatService::class);
    if (!$service->estaConfigurada()) {
        return response()->json(['connected' => false, 'error' => 'SUNAT_API_TOKEN no configurado en .env']);
    }
    $ok = $service->ping();
    return response()->json([
        'connected'  => $ok,
        'company_id' => config('services.sunat_api.company_id'),
        'branch_id'  => config('services.sunat_api.branch_id'),
        'error'      => $ok ? null : 'La API no responde. Verifica que esté corriendo en ' . config('services.sunat_api.url'),
    ]);
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

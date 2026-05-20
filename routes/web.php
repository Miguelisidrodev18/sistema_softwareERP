<?php

use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\ProfileController;
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

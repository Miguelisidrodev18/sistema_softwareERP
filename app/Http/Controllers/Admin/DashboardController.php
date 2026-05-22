<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectDelivery;
use App\Models\Quote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $now       = Carbon::now();
        $mesActual = $now->month;
        $anioActual = $now->year;
        $mesAnterior = $now->copy()->subMonth();

        // ── KPIs principales (caché 5 min) ────────────────────────────
        $kpis = Cache::remember("dashboard_kpis_{$anioActual}_{$mesActual}", 300, function () use ($now, $mesActual, $anioActual, $mesAnterior) {
            $ingMes  = CashMovement::ingresos()->whereYear('fecha', $anioActual)->whereMonth('fecha', $mesActual)->sum('monto');
            $egrMes  = CashMovement::egresos()->whereYear('fecha', $anioActual)->whereMonth('fecha', $mesActual)->sum('monto');
            $ingAnt  = CashMovement::ingresos()->whereYear('fecha', $mesAnterior->year)->whereMonth('fecha', $mesAnterior->month)->sum('monto');
            $egrAnt  = CashMovement::egresos()->whereYear('fecha', $mesAnterior->year)->whereMonth('fecha', $mesAnterior->month)->sum('monto');

            $saldoTotal = CashMovement::sum(DB::raw("CASE WHEN tipo='ingreso' THEN monto ELSE -monto END"));

            return [
                'ingresos_mes'       => (float) $ingMes,
                'egresos_mes'        => (float) $egrMes,
                'saldo_total'        => (float) $saldoTotal,
                'ingresos_var'       => $ingAnt > 0 ? round((($ingMes - $ingAnt) / $ingAnt) * 100, 1) : null,
                'egresos_var'        => $egrAnt > 0 ? round((($egrMes - $egrAnt) / $egrAnt) * 100, 1) : null,
                'proyectos_activos'  => Project::where('status', 'en_curso')->count(),
                'proyectos_total'    => Project::count(),
                'facturas_mes'       => Invoice::where('estado_sunat', 'aceptado')
                                               ->whereYear('fecha_emision', $anioActual)
                                               ->whereMonth('fecha_emision', $mesActual)
                                               ->count(),
                'facturado_mes'      => (float) Invoice::where('estado_sunat', 'aceptado')
                                               ->whereYear('fecha_emision', $anioActual)
                                               ->whereMonth('fecha_emision', $mesActual)
                                               ->sum('total'),
                'clientes_total'     => Client::count(),
                'cotiz_pendientes'   => Quote::where('status', 'enviado')->count(),
            ];
        });

        // ── Chart: Flujo de caja últimos 6 meses ──────────────────────
        $flujoCaja = Cache::remember("dashboard_flujo_{$anioActual}_{$mesActual}", 300, function () use ($now) {
            $meses = collect(range(5, 0))->map(fn($i) => $now->copy()->subMonths($i));

            return $meses->map(function (Carbon $m) {
                $ing = CashMovement::ingresos()->whereYear('fecha', $m->year)->whereMonth('fecha', $m->month)->sum('monto');
                $egr = CashMovement::egresos()->whereYear('fecha', $m->year)->whereMonth('fecha', $m->month)->sum('monto');
                return [
                    'mes'      => $m->translatedFormat('M Y'),
                    'ingresos' => (float) $ing,
                    'egresos'  => (float) $egr,
                ];
            })->values();
        });

        // ── Chart: Facturación mensual últimos 6 meses ─────────────────
        $facturacion6m = Cache::remember("dashboard_fact_{$anioActual}_{$mesActual}", 300, function () use ($now) {
            $meses = collect(range(5, 0))->map(fn($i) => $now->copy()->subMonths($i));

            return $meses->map(function (Carbon $m) {
                $total = Invoice::where('estado_sunat', 'aceptado')
                    ->whereYear('fecha_emision', $m->year)
                    ->whereMonth('fecha_emision', $m->month)
                    ->sum('total');
                return [
                    'mes'   => $m->translatedFormat('M Y'),
                    'total' => (float) $total,
                ];
            })->values();
        });

        // ── Chart: Estado de proyectos ─────────────────────────────────
        $estadoProyectos = Cache::remember("dashboard_proyectos_{$anioActual}_{$mesActual}", 300, function () {
            return Project::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');
        });

        // ── Paneles ────────────────────────────────────────────────────
        $proyectosActivos = Project::with(['client', 'phases'])
            ->where('status', 'en_curso')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $ultimasFacturas = Invoice::with('client')
            ->whereIn('estado_sunat', ['aceptado', 'pendiente', 'enviando'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $movimientosRecientes = CashMovement::with('client')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $cotizacionesPendientes = Quote::with('client')
            ->where('status', 'enviado')
            ->orderByDesc('fecha_emision')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'kpis',
            'flujoCaja',
            'facturacion6m',
            'estadoProyectos',
            'proyectosActivos',
            'ultimasFacturas',
            'movimientosRecientes',
            'cotizacionesPendientes',
        ));
    }
}

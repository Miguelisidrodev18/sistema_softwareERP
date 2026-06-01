<?php

namespace App\Http\Controllers\Asistencias;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\EmpresaConfig;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AsistenciaController extends Controller
{
    // ── Vista para marcar entrada / salida ────────────────────────────
    public function marcar()
    {
        $hoy        = today();
        $userId     = auth()->id();
        $registros  = Asistencia::where('user_id', $userId)->whereDate('fecha', $hoy)->orderBy('hora')->get();
        $entrada    = $registros->firstWhere('tipo', 'entrada');
        $salida     = $registros->firstWhere('tipo', 'salida');
        $config     = EmpresaConfig::config();

        return view('asistencias.marcar', compact('entrada', 'salida', 'config'));
    }

    // ── Procesar registro (POST desde marcar) ─────────────────────────
    public function registrar(Request $request)
    {
        $request->validate([
            'latitud'  => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $config = EmpresaConfig::config();

        if (!$config->tieneGeoConfigurado()) {
            return back()->with('error', 'El administrador aún no configuró la ubicación de la oficina.');
        }

        $distancia = Asistencia::haversine(
            (float) $config->asistencia_latitud,
            (float) $config->asistencia_longitud,
            (float) $request->latitud,
            (float) $request->longitud,
        );

        $radio = $config->radioMetros();

        if ($distancia > $radio) {
            return back()->with('error',
                'Estás a ' . round($distancia) . ' metros de la oficina. Debes estar dentro de los ' . $radio . ' metros permitidos.'
            );
        }

        $hoy    = today();
        $userId = auth()->id();

        $tieneEntrada = Asistencia::where('user_id', $userId)->whereDate('fecha', $hoy)->where('tipo', 'entrada')->exists();
        $tieneSalida  = Asistencia::where('user_id', $userId)->whereDate('fecha', $hoy)->where('tipo', 'salida')->exists();

        if ($tieneSalida) {
            return back()->with('error', 'Ya completaste tu registro de asistencia de hoy.');
        }

        $tipo = $tieneEntrada ? 'salida' : 'entrada';

        Asistencia::create([
            'user_id'           => $userId,
            'tipo'              => $tipo,
            'fecha'             => $hoy,
            'hora'              => now()->format('H:i:s'),
            'latitud'           => $request->latitud,
            'longitud'          => $request->longitud,
            'distancia_metros'  => round($distancia, 1),
            'radio_configurado' => $radio,
            'ip_address'        => $request->ip(),
        ]);

        return back()->with('success', ucfirst($tipo) . ' registrada correctamente a las ' . now()->format('H:i') . '.');
    }

    // ── Reporte mensual ───────────────────────────────────────────────
    public function index(Request $request)
    {
        $authUser  = auth()->user();
        $esGestor  = $authUser->hasPermissionTo('asistencias.gestionar');

        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes',  now()->month);

        $usuarioId     = $esGestor ? (int) $request->input('usuario_id', $authUser->id) : $authUser->id;
        $usuarioFiltro = User::findOrFail($usuarioId);

        $usuarios = $esGestor
            ? User::orderBy('name')->get(['id', 'name'])
            : collect();

        // Registros del mes agrupados por fecha
        $registros = Asistencia::where('user_id', $usuarioId)
            ->delMes($anio, $mes)
            ->orderBy('hora')
            ->get()
            ->groupBy(fn($r) => $r->fecha->format('Y-m-d'));

        // Estadísticas
        $diasPresentes  = $registros->count();
        $diasCompletos  = $registros->filter(
            fn($g) => $g->firstWhere('tipo', 'entrada') && $g->firstWhere('tipo', 'salida')
        )->count();

        $minutosTotal = $registros->sum(function ($grupo) {
            $ent = $grupo->firstWhere('tipo', 'entrada');
            $sal = $grupo->firstWhere('tipo', 'salida');
            if ($ent && $sal) {
                return Carbon::parse($ent->hora)->diffInMinutes(Carbon::parse($sal->hora));
            }
            return 0;
        });

        $horasTotal = intdiv($minutosTotal, 60);
        $minsExtra  = $minutosTotal % 60;

        // Días laborables del mes (lunes a sábado)
        $primerDia    = Carbon::create($anio, $mes, 1);
        $diasDelMes   = $primerDia->daysInMonth;
        $diasLaborales = collect(range(1, $diasDelMes))->filter(
            fn($d) => Carbon::create($anio, $mes, $d)->dayOfWeek !== Carbon::SUNDAY
        )->count();

        // Hoy: resumen del equipo (solo gestor)
        $hoyEquipo = null;
        if ($esGestor) {
            $hoyEquipo = User::with(['asistenciasHoy' => fn($q) => $q->orderBy('hora')])
                ->whereHas('roles')
                ->orderBy('name')
                ->get();
        }

        return view('asistencias.index', compact(
            'registros', 'esGestor', 'anio', 'mes', 'primerDia', 'diasDelMes',
            'usuarioFiltro', 'usuarios',
            'diasPresentes', 'diasCompletos', 'diasLaborales',
            'horasTotal', 'minsExtra', 'hoyEquipo'
        ));
    }

    // ── Config geo (admin) ────────────────────────────────────────────
    public function configGeo()
    {
        abort_unless(auth()->user()->hasPermissionTo('asistencias.gestionar'), 403);
        $config = EmpresaConfig::config();
        return view('asistencias.config', compact('config'));
    }

    public function guardarConfigGeo(Request $request)
    {
        abort_unless(auth()->user()->hasPermissionTo('asistencias.gestionar'), 403);

        $request->validate([
            'asistencia_latitud'      => ['required', 'numeric', 'between:-90,90'],
            'asistencia_longitud'     => ['required', 'numeric', 'between:-180,180'],
            'asistencia_radio_metros' => ['required', 'integer', 'min:5', 'max:500'],
        ]);

        $config = EmpresaConfig::config();
        $config->fill($request->only(['asistencia_latitud', 'asistencia_longitud', 'asistencia_radio_metros']));
        $config->save();

        return back()->with('success', 'Configuración de geolocalización guardada.');
    }
}

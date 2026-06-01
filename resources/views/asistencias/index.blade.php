<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white">
                    {{ $esGestor ? 'Control de Asistencias' : 'Mi Reporte de Asistencias' }}
                </h1>
                <p class="text-sm text-slate-400 mt-0.5">
                    {{ $esGestor ? $usuarioFiltro->name : 'Reporte mensual' }}
                    · {{ \Carbon\Carbon::create($anio, $mes, 1)->isoFormat('MMMM YYYY') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @can('asistencias.registrar')
                <a href="{{ route('asistencias.justificar') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
                          border border-amber-500/40 text-amber-400 hover:bg-amber-500/10 transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    Justificar ausencia
                </a>
                <a href="{{ route('asistencias.marcar') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold
                          bg-emerald-500 hover:bg-emerald-400 text-white transition-colors duration-150">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                    </svg>
                    Marcar asistencia
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    {{-- Filtros --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
        @if($esGestor)
        <div>
            <label class="block text-xs text-slate-500 mb-1">Empleado</label>
            <select name="usuario_id" onchange="this.form.submit()"
                    class="bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-sky-500">
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" @selected($u->id == $usuarioFiltro->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <label class="block text-xs text-slate-500 mb-1">Mes</label>
            <select name="mes" onchange="this.form.submit()"
                    class="bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-sky-500">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" @selected($m == $mes)>
                        {{ \Carbon\Carbon::create(2000,$m,1)->isoFormat('MMMM') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-500 mb-1">Año</label>
            <select name="anio" onchange="this.form.submit()"
                    class="bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-sky-500">
                @foreach(range(now()->year, 2025) as $a)
                    <option value="{{ $a }}" @selected($a == $anio)>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        @if($esGestor)
            <input type="hidden" name="usuario_id" value="{{ $usuarioFiltro->id }}">
        @endif
    </form>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Días presentes</p>
            <p class="text-2xl font-bold text-white">{{ $diasPresentes }}</p>
            <p class="text-xs text-slate-600">de {{ $diasLaborales }} laborables</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Días completos</p>
            <p class="text-2xl font-bold text-emerald-400">{{ $diasCompletos }}</p>
            <p class="text-xs text-slate-600">entrada + salida</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Horas trabajadas</p>
            <p class="text-2xl font-bold text-sky-400">{{ $horasTotal }}h {{ $minsExtra }}m</p>
            <p class="text-xs text-slate-600">total del mes</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Faltas</p>
            <p class="text-2xl font-bold text-red-400">{{ max(0, $diasLaborales - $diasPresentes) }}</p>
            <p class="text-xs text-slate-600">días sin asistencia</p>
        </div>
    </div>

    {{-- Hoy: resumen equipo (solo admin) --}}
    @if($esGestor && $hoyEquipo)
    <div class="card overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-slate-800/60 flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-300">Asistencia de hoy — {{ now()->format('d/m/Y') }}</p>
        </div>
        <div class="divide-y divide-slate-800/40">
            @foreach($hoyEquipo as $emp)
            @php
                $asistHoy = $emp->asistenciasHoy;
                $entHoy = $asistHoy->firstWhere('tipo','entrada');
                $salHoy = $asistHoy->firstWhere('tipo','salida');
            @endphp
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-500 to-cyan-500
                            flex items-center justify-center text-xs font-bold text-white uppercase flex-shrink-0">
                    {{ substr($emp->name, 0, 2) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white font-medium truncate">{{ $emp->name }}</p>
                    <p class="text-xs text-slate-500">{{ $emp->rolLabel() }}</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-mono">
                    <span class="{{ $entHoy ? 'text-emerald-400' : 'text-slate-600' }}">
                        ↑ {{ $entHoy ? substr($entHoy->hora,0,5) : '--:--' }}
                    </span>
                    <span class="{{ $salHoy ? 'text-sky-400' : 'text-slate-600' }}">
                        ↓ {{ $salHoy ? substr($salHoy->hora,0,5) : '--:--' }}
                    </span>
                    @if(!$entHoy)
                        <span class="px-2 py-0.5 rounded-md bg-red-500/15 text-red-400 font-sans">Sin registro</span>
                    @elseif($salHoy)
                        <span class="px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 font-sans">Completo</span>
                    @else
                        <span class="px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-400 font-sans">Solo entrada</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Justificaciones pendientes (solo gestor) --}}
    @if($esGestor && $justPendientes->isNotEmpty())
    <div class="card overflow-hidden mb-6" x-data="{}">
        <div class="px-5 py-3 border-b border-slate-800/60 flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            <p class="text-sm font-semibold text-amber-400">Justificaciones pendientes ({{ $justPendientes->count() }})</p>
        </div>
        <div class="divide-y divide-slate-800/40">
            @foreach($justPendientes as $just)
            <div class="px-5 py-4" x-data="{ accion: null }">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-white font-semibold text-sm">{{ $just->empleado->name }}</span>
                            <span class="text-xs text-slate-500">·</span>
                            <span class="text-xs font-mono text-slate-400">
                                {{ $just->fecha->isoFormat('ddd D MMM') }} — {{ ucfirst($just->tipo) }} a las {{ substr($just->hora_justificada,0,5) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-300 leading-relaxed">{{ $just->motivo }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="accion = accion === 'aprobar' ? null : 'aprobar'"
                                :class="accion==='aprobar' ? 'bg-emerald-500/20 border-emerald-500 text-emerald-400' : 'border-slate-700 text-slate-400 hover:border-slate-500'"
                                class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all">
                            Aprobar
                        </button>
                        <button @click="accion = accion === 'rechazar' ? null : 'rechazar'"
                                :class="accion==='rechazar' ? 'bg-red-500/20 border-red-500 text-red-400' : 'border-slate-700 text-slate-400 hover:border-slate-500'"
                                class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition-all">
                            Rechazar
                        </button>
                    </div>
                </div>

                <form x-show="accion === 'aprobar'" x-transition
                      method="POST" action="{{ route('asistencias.justificacion.aprobar', $just) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <input type="text" name="respuesta_admin" placeholder="Nota opcional..."
                           class="input-dark flex-1 text-sm py-1.5">
                    <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-white transition-colors">
                        Confirmar aprobación
                    </button>
                </form>

                <form x-show="accion === 'rechazar'" x-transition
                      method="POST" action="{{ route('asistencias.justificacion.rechazar', $just) }}" class="flex gap-2">
                    @csrf @method('PATCH')
                    <input type="text" name="respuesta_admin" placeholder="Motivo del rechazo (requerido)..."
                           class="input-dark flex-1 text-sm py-1.5" required>
                    <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-bold bg-red-500 hover:bg-red-400 text-white transition-colors">
                        Confirmar rechazo
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tabla mensual --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-800/60">
            <p class="text-sm font-semibold text-slate-300">
                Detalle — {{ \Carbon\Carbon::create($anio, $mes, 1)->isoFormat('MMMM YYYY') }}
            </p>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800/40">
                    <th class="th-cell">Día</th>
                    <th class="th-cell">Entrada</th>
                    <th class="th-cell">Salida</th>
                    <th class="th-cell">Horas</th>
                    <th class="th-cell">Distancia</th>
                    <th class="th-cell">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/30">
                @for($d = 1; $d <= $diasDelMes; $d++)
                    @php
                        $fecha = \Carbon\Carbon::create($anio, $mes, $d);
                        $key   = $fecha->format('Y-m-d');
                        $grupo = $registros->get($key);
                        $esDomingo = $fecha->dayOfWeek === \Carbon\Carbon::SUNDAY;
                        $esFuturo  = $fecha->isFuture();
                        $esHoy     = $fecha->isToday();
                        $ent = $grupo?->firstWhere('tipo','entrada');
                        $sal = $grupo?->firstWhere('tipo','salida');
                        $minutos = ($ent && $sal)
                            ? \Carbon\Carbon::parse($ent->hora)->diffInMinutes(\Carbon\Carbon::parse($sal->hora))
                            : null;
                        $justEnt = $justificaciones->get($key . '_entrada');
                        $justSal = $justificaciones->get($key . '_salida');
                    @endphp
                    <tr class="{{ $esDomingo ? 'opacity-40' : ($esHoy ? 'bg-sky-500/5' : 'hover:bg-slate-800/20') }} transition-colors">
                        <td class="td-cell font-mono text-slate-400">
                            {{ $fecha->isoFormat('ddd D') }}
                            @if($esHoy)<span class="ml-1 text-xs text-sky-400">hoy</span>@endif
                        </td>
                        <td class="td-cell font-mono {{ $ent ? 'text-emerald-400' : 'text-slate-600' }}">
                            {{ $ent ? substr($ent->hora,0,5) : '—' }}
                            @if(!$ent && $justEnt)
                                @php $c = $justEnt->estadoColor() @endphp
                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-{{ $c }}-500/20 text-{{ $c }}-400 font-sans">
                                    just.{{ substr($justEnt->estado,0,3) }}
                                </span>
                            @endif
                        </td>
                        <td class="td-cell font-mono {{ $sal ? 'text-sky-400' : 'text-slate-600' }}">
                            {{ $sal ? substr($sal->hora,0,5) : '—' }}
                            @if(!$sal && $justSal)
                                @php $c = $justSal->estadoColor() @endphp
                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-{{ $c }}-500/20 text-{{ $c }}-400 font-sans">
                                    just.{{ substr($justSal->estado,0,3) }}
                                </span>
                            @endif
                        </td>
                        <td class="td-cell font-mono text-slate-300">
                            @if($minutos !== null)
                                {{ intdiv($minutos,60) }}h {{ $minutos%60 }}m
                            @else —
                            @endif
                        </td>
                        <td class="td-cell text-slate-400 text-xs">
                            {{ $ent ? round($ent->distancia_metros) . 'm' : '—' }}
                        </td>
                        <td class="td-cell">
                            @if($esDomingo)
                                <span class="text-xs text-slate-600">Domingo</span>
                            @elseif($esFuturo)
                                <span class="text-xs text-slate-700">—</span>
                            @elseif($ent && $sal)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400">✓ Completo</span>
                            @elseif($ent)
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-400">Solo entrada</span>
                                    @if(!$justSal && !$esGestor && !$esFuturo)
                                    <a href="{{ route('asistencias.justificar', ['fecha'=>$key,'tipo'=>'salida']) }}"
                                       class="text-[10px] text-slate-500 hover:text-amber-400 transition-colors underline">justificar</a>
                                    @endif
                                </div>
                            @else
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-red-500/15 text-red-400">Falta</span>
                                    @if(!$justEnt && !$esGestor && !$esFuturo)
                                    <a href="{{ route('asistencias.justificar', ['fecha'=>$key,'tipo'=>'entrada']) }}"
                                       class="text-[10px] text-slate-500 hover:text-amber-400 transition-colors underline">justificar</a>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    @if($esGestor)
    <div class="mt-4 text-right">
        <a href="{{ route('asistencias.config') }}"
           class="text-xs text-slate-500 hover:text-slate-300 transition-colors">
            ⚙ Configurar ubicación de oficina
        </a>
    </div>
    @endif
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('cotizaciones.index') }}" class="text-slate-600 hover:text-slate-400">Cotizaciones</a>
            <span class="text-slate-700">/</span>
            <span class="text-white font-semibold font-mono">{{ $cotizacion->numero }}</span>
        </div>
    </x-slot>

    @php
        $siguientes = collect(\App\Models\Quote::ESTADOS)
            ->reject(fn($s) => $s === $cotizacion->status)
            ->values();
    @endphp

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 mb-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-3 mb-1 flex-wrap">
                    <h1 class="text-xl font-bold font-mono text-white">{{ $cotizacion->numero }}</h1>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $cotizacion->statusBadgeClass() }}">
                        {{ $cotizacion->statusLabel() }}
                    </span>
                    @if($cotizacion->estaVencida())
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-500/10 text-red-400 ring-1 ring-red-500/20">
                        Vencida
                    </span>
                    @endif
                </div>
                <p class="text-slate-400 font-semibold">{{ $cotizacion->client->razon_social }}</p>
                @if($cotizacion->project)
                <p class="text-xs text-slate-600 mt-0.5">Proyecto: {{ $cotizacion->project->name }}</p>
                @endif
                <div class="flex items-center gap-4 mt-2 text-xs text-slate-600 font-mono flex-wrap">
                    <span>Emitida: {{ $cotizacion->fecha_emision->format('d/m/Y') }}</span>
                    @if($cotizacion->fecha_vencimiento)
                    <span class="{{ $cotizacion->estaVencida() ? 'text-red-400' : '' }}">
                        Vence: {{ $cotizacion->fecha_vencimiento->format('d/m/Y') }}
                    </span>
                    @endif
                    @if($cotizacion->sent_at)
                    <span class="text-sky-400">Enviada: {{ $cotizacion->sent_at->format('d/m/Y') }}</span>
                    @endif
                </div>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                @can('cotizaciones.pdf')
                <a href="{{ route('cotizaciones.pdf', $cotizacion) }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium
                          bg-slate-800 text-slate-300 border border-slate-700/60
                          hover:border-sky-500/30 hover:text-sky-400 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    Ver PDF
                </a>
                @endcan

                @can('cotizaciones.editar')
                @if(in_array($cotizacion->status, ['borrador', 'enviado']))
                <a href="{{ route('cotizaciones.edit', $cotizacion) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium
                          bg-slate-800 text-slate-300 border border-slate-700/60
                          hover:border-sky-500/30 hover:text-sky-400 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>
                    </svg>
                    Editar
                </a>
                @endif
                @endcan

                @can('cotizaciones.aprobar')
                <div x-data="{ menuEstado: false }" class="relative">
                    <button @click="menuEstado = !menuEstado"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium
                                   bg-sky-500 text-white hover:bg-sky-400 transition-all">
                        Cambiar estado
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                    <div x-show="menuEstado" @click.outside="menuEstado = false"
                         class="absolute right-0 top-11 z-20 bg-slate-800 border border-slate-700/60 rounded-xl overflow-hidden shadow-xl w-40"
                         style="display:none">
                        @foreach(\App\Models\Quote::ESTADO_LABELS as $s => $label)
                        @if($s !== $cotizacion->status)
                        <form method="POST" action="{{ route('cotizaciones.estado', $cotizacion) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $s }}">
                            <button type="submit" class="w-full text-left px-3 py-2.5 text-xs text-slate-300 hover:bg-slate-700/60 transition-colors">
                                {{ $label }}
                            </button>
                        </form>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── Tabla de ítems ───────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-800/60">
                    <h3 class="text-sm font-semibold text-white">Ítems</h3>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-800/40">
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-600">Descripción</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-slate-600">Cant.</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-slate-600 hidden sm:table-cell">P. Unit.</th>
                            <th class="text-right px-4 py-3 text-xs font-medium text-slate-600 hidden sm:table-cell">Dto %</th>
                            <th class="text-right px-6 py-3 text-xs font-medium text-slate-600">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @foreach($cotizacion->items as $item)
                        <tr>
                            <td class="px-6 py-3.5">
                                <p class="text-xs font-medium text-white">{{ $item->descripcion }}</p>
                                <p class="text-[10px] text-slate-600 mt-0.5">{{ $item->unidad }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-right text-xs font-mono text-slate-400">{{ number_format($item->cantidad, 2) }}</td>
                            <td class="px-4 py-3.5 text-right text-xs font-mono text-slate-400 hidden sm:table-cell">{{ $cotizacion->monedaSimbolo() }} {{ number_format($item->precio_unitario, 2) }}</td>
                            <td class="px-4 py-3.5 text-right text-xs font-mono text-slate-600 hidden sm:table-cell">{{ $item->descuento > 0 ? $item->descuento.'%' : '—' }}</td>
                            <td class="px-6 py-3.5 text-right text-xs font-semibold font-mono text-white">{{ $cotizacion->monedaSimbolo() }} {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-700/60 bg-slate-800/30">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-xs text-slate-500 text-right hidden sm:table-cell">Subtotal</td>
                            <td class="px-6 py-3 text-right text-xs font-mono font-semibold text-white">{{ $cotizacion->monedaSimbolo() }} {{ number_format($cotizacion->subtotal, 2) }}</td>
                        </tr>
                        @if($cotizacion->igv > 0)
                        <tr>
                            <td colspan="4" class="px-6 py-2 text-xs text-slate-500 text-right hidden sm:table-cell">IGV ({{ $config?->igv_porcentaje ?? 18 }}%)</td>
                            <td class="px-6 py-2 text-right text-xs font-mono text-slate-400">{{ $cotizacion->monedaSimbolo() }} {{ number_format($cotizacion->igv, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-sm font-bold text-white text-right hidden sm:table-cell">TOTAL</td>
                            <td class="px-6 py-3 text-right text-base font-bold font-mono text-sky-400">
                                {{ $cotizacion->monedaSimbolo() }} {{ number_format($cotizacion->total, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Notas --}}
            @if($cotizacion->notas || $cotizacion->terminos)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($cotizacion->notas)
                <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Notas</h4>
                    <p class="text-xs text-slate-400 leading-relaxed whitespace-pre-wrap">{{ $cotizacion->notas }}</p>
                </div>
                @endif
                @if($cotizacion->terminos)
                <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5">
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Términos</h4>
                    <p class="text-xs text-slate-400 leading-relaxed font-mono whitespace-pre-wrap">{{ $cotizacion->terminos }}</p>
                </div>
                @endif
            </div>
            @endif
        </div>

        {{-- ── Panel lateral ────────────────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Resumen --}}
            <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5 space-y-3">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Resumen</h3>
                <div>
                    <p class="text-[10px] text-slate-600">Moneda</p>
                    <p class="text-sm font-semibold text-white mt-0.5">{{ $cotizacion->moneda }}</p>
                </div>
                <div>
                    <p class="text-[10px] text-slate-600">Total</p>
                    <p class="text-xl font-bold font-mono text-sky-400 mt-0.5">
                        {{ $cotizacion->monedaSimbolo() }} {{ number_format($cotizacion->total, 2) }}
                    </p>
                    @if($cotizacion->incluye_igv)
                    <p class="text-[10px] text-slate-600 mt-0.5">Incluye IGV</p>
                    @else
                    <p class="text-[10px] text-slate-600 mt-0.5">Sin IGV</p>
                    @endif
                </div>
                <div>
                    <p class="text-[10px] text-slate-600">Creada por</p>
                    <p class="text-sm text-white mt-0.5">{{ $cotizacion->createdBy->name }}</p>
                    <p class="text-[10px] text-slate-600 font-mono">{{ $cotizacion->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            {{-- Zona peligrosa --}}
            @can('cotizaciones.eliminar')
            <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Zona peligrosa</h3>
                <form method="POST" action="{{ route('cotizaciones.destroy', $cotizacion) }}"
                      x-data @submit.prevent="if(confirm('¿Eliminar la cotización {{ $cotizacion->numero }}?')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="w-full py-2 rounded-xl text-xs font-medium text-red-400
                                   border border-red-500/20 hover:bg-red-500/10 transition-colors">
                        Eliminar cotización
                    </button>
                </form>
            </div>
            @endcan
        </div>
    </div>

</x-app-layout>

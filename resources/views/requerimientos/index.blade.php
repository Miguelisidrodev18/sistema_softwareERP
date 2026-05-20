<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('proyectos.index') }}" class="text-slate-600 hover:text-slate-400 font-mono">Proyectos</a>
            <span class="text-slate-700">/</span>
            <a href="{{ route('proyectos.show', $proyecto) }}" class="text-slate-600 hover:text-slate-400 truncate max-w-[160px]">{{ $proyecto->name }}</a>
            <span class="text-slate-700">/</span>
            <span class="text-white font-semibold">Requerimientos</span>
        </div>
    </x-slot>

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-6" x-data="{ modalAgregar: false }">
        <div>
            <h2 class="text-xl font-bold text-white">Requerimientos</h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ $proyecto->name }}</p>
        </div>
        @can('requerimientos.crear')
        <button @click="modalAgregar = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold
                       bg-gradient-to-r from-sky-500 to-cyan-500 text-white
                       shadow-[0_0_18px_rgba(14,165,233,0.35)] hover:shadow-[0_0_28px_rgba(14,165,233,0.55)]
                       transition-all active:scale-[0.98]">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nuevo requerimiento
        </button>
        @endcan

        {{-- Modal agregar --}}
        @can('requerimientos.crear')
        <template x-teleport="body">
            <div x-show="modalAgregar"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 overflow-y-auto"
                 style="display:none">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="modalAgregar = false"></div>
                <div class="relative min-h-full flex items-start justify-center p-4 pt-12">
                    <div class="w-full max-w-lg bg-slate-900 border border-slate-700/60 rounded-2xl shadow-2xl"
                         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         @click.stop>
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800/80">
                            <h3 class="text-base font-bold text-white">Nuevo requerimiento</h3>
                            <button @click="modalAgregar = false" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-500 hover:text-white hover:bg-slate-800 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('requerimientos.store', $proyecto) }}" class="p-6 space-y-4">
                            @csrf
                            <input type="hidden" name="project_id" value="{{ $proyecto->id }}">

                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Título <span class="text-red-400">*</span></label>
                                <input type="text" name="title" class="input-dark" placeholder="Descripción breve del requerimiento" required>
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Tipo</label>
                                    <select name="type" class="input-dark">
                                        <option value="funcional">Funcional</option>
                                        <option value="tecnico">Técnico</option>
                                        <option value="negocio">Negocio</option>
                                        <option value="ux_ui">UX/UI</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Prioridad</label>
                                    <select name="priority" class="input-dark">
                                        <option value="media">Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="critica">Crítica</option>
                                        <option value="baja">Baja</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Estado</label>
                                    <select name="status" class="input-dark">
                                        <option value="pendiente">Pendiente</option>
                                        <option value="en_progreso">En progreso</option>
                                    </select>
                                </div>
                            </div>

                            @if($proyecto->phases->isNotEmpty())
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Fase (opcional)</label>
                                <select name="phase_id" class="input-dark">
                                    <option value="">Sin fase</option>
                                    @foreach($proyecto->phases as $fase)
                                    <option value="{{ $fase->id }}">{{ $fase->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Asignar a</label>
                                <select name="assigned_to" class="input-dark">
                                    <option value="">Sin asignar</option>
                                    @foreach($usuarios as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Descripción</label>
                                <textarea name="description" rows="2" class="input-dark resize-none" placeholder="Detalles adicionales..."></textarea>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="modalAgregar = false" class="px-4 py-2.5 text-sm text-slate-400 hover:text-white border border-slate-700/60 rounded-xl hover:bg-slate-800 transition-colors">Cancelar</button>
                                <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-cyan-500 hover:from-sky-400 hover:to-cyan-400 transition-all active:scale-[0.98]">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
        @endcan
    </div>

    {{-- Kanban por estado --}}
    @php
        $columnas = [
            'pendiente'   => ['label' => 'Pendiente',    'color' => 'slate'],
            'en_progreso' => ['label' => 'En progreso',  'color' => 'sky'],
            'en_revision' => ['label' => 'En revisión',  'color' => 'violet'],
            'completado'  => ['label' => 'Completado',   'color' => 'emerald'],
            'rechazado'   => ['label' => 'Rechazado',    'color' => 'red'],
        ];
        $requerimientos = $proyecto->requirements->load('assignedTo', 'phase');
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 items-start">
        @foreach($columnas as $estado => $col)
        @php
            $items = $requerimientos->where('status', $estado);
            $colorMap = [
                'slate'   => ['header' => 'bg-slate-800 text-slate-400', 'border' => 'border-slate-700/40'],
                'sky'     => ['header' => 'bg-sky-500/10 text-sky-400',  'border' => 'border-sky-500/20'],
                'violet'  => ['header' => 'bg-violet-500/10 text-violet-400', 'border' => 'border-violet-500/20'],
                'emerald' => ['header' => 'bg-emerald-500/10 text-emerald-400', 'border' => 'border-emerald-500/20'],
                'red'     => ['header' => 'bg-red-500/10 text-red-400', 'border' => 'border-red-500/20'],
            ];
            $c = $colorMap[$col['color']];
        @endphp
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden">

            {{-- Cabecera columna --}}
            <div class="flex items-center justify-between px-4 py-3 {{ $c['header'] }} border-b {{ $c['border'] }}">
                <span class="text-xs font-semibold">{{ $col['label'] }}</span>
                <span class="text-xs font-mono bg-slate-900/40 px-1.5 py-0.5 rounded-md">{{ $items->count() }}</span>
            </div>

            {{-- Cards --}}
            <div class="p-2 space-y-2 min-h-[80px]">
                @forelse($items as $req)
                <div class="bg-slate-800/60 border border-slate-700/40 rounded-xl p-3 hover:border-slate-600/60 transition-colors"
                     x-data="{ menu: false }">

                    {{-- Badges --}}
                    <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md {{ $req->priorityBadge() }} capitalize">
                            {{ $req->priority }}
                        </span>
                        <span class="text-[10px] text-slate-600 bg-slate-800 px-1.5 py-0.5 rounded-md">
                            {{ $req->typeLabel() }}
                        </span>
                    </div>

                    <p class="text-xs font-medium text-white leading-snug mb-2">{{ $req->title }}</p>

                    @if($req->assignedTo)
                    <p class="text-[10px] text-slate-500">↪ {{ $req->assignedTo->name }}</p>
                    @endif
                    @if($req->phase)
                    <p class="text-[10px] text-slate-600">Fase: {{ $req->phase->name }}</p>
                    @endif

                    {{-- Cambiar estado rápido --}}
                    @can('requerimientos.editar')
                    <div class="relative mt-2 pt-2 border-t border-slate-700/40" x-data="{ open: false }">
                        <button @click="open = !open" class="text-[10px] text-slate-600 hover:text-sky-400 transition-colors flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/></svg>
                            Mover
                        </button>
                        <div x-show="open" @click.outside="open = false"
                             class="absolute left-0 bottom-6 z-20 bg-slate-800 border border-slate-700/60 rounded-xl overflow-hidden shadow-xl w-36"
                             style="display:none">
                            @foreach($columnas as $s => $cl)
                            @if($s !== $estado)
                            <form method="POST" action="{{ route('requerimientos.update', [$proyecto, $req]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $s }}">
                                <button type="submit" class="w-full text-left px-3 py-2 text-xs text-slate-300 hover:bg-slate-700/60 transition-colors">
                                    {{ $cl['label'] }}
                                </button>
                            </form>
                            @endif
                            @endforeach
                            <div class="border-t border-slate-700/40">
                                <form method="POST" action="{{ route('requerimientos.destroy', [$proyecto, $req]) }}"
                                      x-data @submit.prevent="if(confirm('¿Eliminar este requerimiento?')) $el.submit()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full text-left px-3 py-2 text-xs text-red-400 hover:bg-red-500/10 transition-colors">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
                @empty
                <p class="text-[11px] text-slate-700 text-center py-4">Sin items</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

</x-app-layout>

<div>
    @php
    $columnas = [
        'pendiente'   => ['label' => 'Por hacer',    'color' => 'slate'],
        'en_progreso' => ['label' => 'En progreso',  'color' => 'sky'],
        'en_revision' => ['label' => 'En revisión',  'color' => 'violet'],
        'completado'  => ['label' => 'Hecho ✓',      'color' => 'emerald'],
    ];
    $colorMap = [
        'slate'   => ['header' => 'bg-slate-800 text-slate-400',         'border' => 'border-slate-700/40'],
        'sky'     => ['header' => 'bg-sky-500/10 text-sky-400',           'border' => 'border-sky-500/20'],
        'violet'  => ['header' => 'bg-violet-500/10 text-violet-400',     'border' => 'border-violet-500/20'],
        'emerald' => ['header' => 'bg-emerald-500/10 text-emerald-400',   'border' => 'border-emerald-500/20'],
    ];
    @endphp

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($columnas as $estado => $col)
        @php
            $items = $tareas[$estado] ?? collect();
            $c = $colorMap[$col['color']];
        @endphp
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl overflow-hidden flex flex-col">

            <div class="flex items-center justify-between px-4 py-3 {{ $c['header'] }} border-b {{ $c['border'] }} flex-shrink-0">
                <span class="text-xs font-semibold">{{ $col['label'] }}</span>
                <span class="text-xs font-mono bg-slate-900/40 px-1.5 py-0.5 rounded-md">{{ $items->count() }}</span>
            </div>

            <div class="p-2 space-y-2 flex-1 min-h-[120px]">
                @forelse($items as $req)
                <div class="bg-slate-800/60 border border-slate-700/40 rounded-xl p-3 hover:border-slate-600/60 transition-colors">

                    {{-- Story points + prioridad --}}
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md {{ $req->priorityBadge() }} capitalize">
                                {{ $req->priority }}
                            </span>
                            <span class="text-[10px] text-slate-600 bg-slate-800 px-1.5 py-0.5 rounded-md">
                                {{ $req->typeLabel() }}
                            </span>
                        </div>
                        @if($req->story_points)
                        <span class="text-[11px] font-bold font-mono text-sky-400 bg-sky-500/10 px-1.5 py-0.5 rounded-md">
                            {{ $req->story_points }}pt
                        </span>
                        @endif
                    </div>

                    <p class="text-xs font-medium text-white leading-snug mb-2">{{ $req->title }}</p>

                    @if($req->assignedTo)
                    <div class="flex items-center gap-1.5">
                        <div class="w-4 h-4 rounded-full bg-sky-500/20 flex items-center justify-center text-[8px] font-bold text-sky-400 uppercase flex-shrink-0">
                            {{ substr($req->assignedTo->name, 0, 1) }}
                        </div>
                        <p class="text-[10px] text-slate-500 truncate">{{ $req->assignedTo->name }}</p>
                    </div>
                    @endif

                    {{-- Mover tarea --}}
                    <div class="relative mt-2 pt-2 border-t border-slate-700/40" x-data="{ open: false }">
                        <button @click="open = !open" class="text-[10px] text-slate-600 hover:text-sky-400 transition-colors flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                            </svg>
                            Mover a
                        </button>
                        <div x-show="open" @click.outside="open = false"
                             class="absolute left-0 bottom-6 z-20 bg-slate-800 border border-slate-700/60 rounded-xl overflow-hidden shadow-xl w-36"
                             style="display:none">
                            @foreach($columnas as $s => $cl)
                            @if($s !== $estado)
                            <button wire:click="moverTarea({{ $req->id }}, '{{ $s }}')" @click="open = false"
                                    class="w-full text-left px-3 py-2 text-xs text-slate-300 hover:bg-slate-700/60 transition-colors">
                                {{ $cl['label'] }}
                            </button>
                            @endif
                            @endforeach
                            @can('sprints.gestionar')
                            <div class="border-t border-slate-700/40">
                                <button wire:click="quitarDelSprint({{ $req->id }})" @click="open = false"
                                        class="w-full text-left px-3 py-2 text-xs text-amber-400 hover:bg-amber-500/10 transition-colors">
                                    → Backlog
                                </button>
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-[11px] text-slate-700 text-center py-4">Vacío</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>

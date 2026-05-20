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

    @php
        $columnas = [
            'pendiente'   => ['label' => 'Pendiente',    'color' => 'slate'],
            'en_progreso' => ['label' => 'En progreso',  'color' => 'sky'],
            'en_revision' => ['label' => 'En revisión',  'color' => 'violet'],
            'completado'  => ['label' => 'Completado',   'color' => 'emerald'],
            'rechazado'   => ['label' => 'Rechazado',    'color' => 'red'],
        ];
        $requerimientos = $proyecto->requirements->load('assignedTo', 'phase');
        $totalReqs      = $requerimientos->count();
        $completados    = $requerimientos->where('status', 'completado')->count();
        $totalPtsGlobal = $requerimientos->sum('story_points');
    @endphp

    {{-- ══ Wrapper principal con todo el estado Alpine ══════════════ --}}
    <div x-data="{
        {{-- Modal crear --}}
        modalAgregar: {{ $errors->any() ? 'true' : 'false' }},

        {{-- Filtros --}}
        filtro: { q: '', prioridad: '', tipo: '', asignado: '' },
        visible(priority, type, assignedId, title) {
            const f = this.filtro;
            if (f.prioridad && f.prioridad !== priority) return false;
            if (f.tipo     && f.tipo     !== type)     return false;
            if (f.asignado && String(assignedId) !== f.asignado) return false;
            if (f.q && !title.toLowerCase().includes(f.q.toLowerCase())) return false;
            return true;
        },
        get hayFiltros() {
            const f = this.filtro;
            return !!(f.q || f.prioridad || f.tipo || f.asignado);
        },
        limpiar() { this.filtro = { q: '', prioridad: '', tipo: '', asignado: '' }; },

        {{-- Modal editar --}}
        edit: null,
        abrirEditar(data) { this.edit = { ...data }; },
        cerrarEditar()    { this.edit = null; },
        get editUrl() {
            return this.edit
                ? '/proyectos/{{ $proyecto->id }}/requerimientos/' + this.edit.id
                : '#';
        },

        {{-- Drag & drop --}}
        over: null,
        drag(e, id, from) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('id', String(id));
            e.dataTransfer.setData('from', from);
            e.currentTarget.style.opacity = '0.4';
        },
        endDrag(e) {
            e.currentTarget.style.opacity = '';
            this.over = null;
        },
        enter(status) { this.over = status; },
        leave(e) {
            if (!e.currentTarget.contains(e.relatedTarget)) this.over = null;
        },
        drop(e, status) {
            this.over = null;
            const id   = e.dataTransfer.getData('id');
            const from = e.dataTransfer.getData('from');
            if (!id || from === status) return;
            const form = document.getElementById('dd-form');
            form.action = '/proyectos/{{ $proyecto->id }}/requerimientos/' + id;
            document.getElementById('dd-status').value = status;
            form.submit();
        }
    }">

    {{-- ── Encabezado ─────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-xl font-bold text-white">Requerimientos</h2>
            <p class="text-sm text-slate-500 mt-0.5">{{ $proyecto->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Stats globales --}}
            <div class="hidden sm:flex items-center gap-4 text-xs text-slate-600 font-mono mr-1">
                <span>
                    <span class="text-slate-400 font-semibold">{{ $completados }}/{{ $totalReqs }}</span>
                    completados
                </span>
                @if($totalPtsGlobal)
                <span>
                    <span class="text-sky-400 font-semibold">{{ $totalPtsGlobal }}</span> pts totales
                </span>
                @endif
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
                Nuevo
            </button>
            @endcan
        </div>
    </div>

    {{-- ── Barra de filtros ────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl px-4 py-3 mb-5
                flex items-center gap-2.5 flex-wrap">

        {{-- Búsqueda --}}
        <div class="relative flex-1 min-w-[180px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500 pointer-events-none"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" x-model="filtro.q"
                   placeholder="Buscar por título..."
                   class="input-dark pl-8 py-2 text-xs w-full">
        </div>

        {{-- Prioridad --}}
        <select x-model="filtro.prioridad" class="input-dark py-2 text-xs min-w-[110px]">
            <option value="">Prioridad</option>
            <option value="critica">🔴 Crítica</option>
            <option value="alta">🟡 Alta</option>
            <option value="media">🔵 Media</option>
            <option value="baja">⚪ Baja</option>
        </select>

        {{-- Tipo --}}
        <select x-model="filtro.tipo" class="input-dark py-2 text-xs min-w-[110px]">
            <option value="">Tipo</option>
            <option value="funcional">Funcional</option>
            <option value="tecnico">Técnico</option>
            <option value="negocio">Negocio</option>
            <option value="ux_ui">UX/UI</option>
        </select>

        {{-- Asignado --}}
        <select x-model="filtro.asignado" class="input-dark py-2 text-xs min-w-[130px]">
            <option value="">Asignado a</option>
            @foreach($usuarios as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>

        {{-- Limpiar --}}
        <button x-show="hayFiltros" @click="limpiar()"
                class="flex items-center gap-1 text-xs text-slate-500 hover:text-sky-400 transition-colors px-2 py-2 rounded-lg hover:bg-sky-500/5"
                style="display:none">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
            Limpiar
        </button>
    </div>

    {{-- ── Modal: Crear requerimiento ────────────────────────────── --}}
    @can('requerimientos.crear')
    <template x-teleport="body">
        <div x-show="modalAgregar"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="modalAgregar = false"></div>
            <div class="relative min-h-full flex items-start justify-center p-4 pt-12">
                <div class="w-full max-w-lg bg-slate-900 border border-slate-700/60 rounded-2xl shadow-2xl"
                     x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     @click.stop>
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800/80">
                        <h3 class="text-base font-bold text-white">Nuevo requerimiento</h3>
                        <button @click="modalAgregar = false" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-500 hover:text-white hover:bg-slate-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('requerimientos.store', $proyecto) }}" class="p-6 space-y-4">
                        @csrf
                        <input type="hidden" name="project_id" value="{{ $proyecto->id }}">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Título <span class="text-red-400">*</span></label>
                            <input type="text" name="title" class="input-dark" placeholder="Descripción breve del requerimiento" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Descripción</label>
                            <textarea name="description" rows="2" class="input-dark resize-none" placeholder="Detalles adicionales, criterios de aceptación..."></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
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
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Estado inicial</label>
                                <select name="status" class="input-dark">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en_progreso">En progreso</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Story points</label>
                                <select name="story_points" class="input-dark">
                                    <option value="">— sin estimar</option>
                                    @foreach([1,2,3,5,8,13,21] as $pts)
                                    <option value="{{ $pts }}">{{ $pts }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Asignar a</label>
                            <select name="assigned_to" class="input-dark">
                                <option value="">Sin asignar</option>
                                @foreach($usuarios as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($proyecto->phases->isNotEmpty())
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Fase</label>
                            <select name="phase_id" class="input-dark">
                                <option value="">Sin fase</option>
                                @foreach($proyecto->phases as $fase)
                                <option value="{{ $fase->id }}">{{ $fase->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="modalAgregar = false"
                                    class="px-4 py-2.5 text-sm text-slate-400 hover:text-white border border-slate-700/60 rounded-xl hover:bg-slate-800 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-cyan-500 hover:from-sky-400 hover:to-cyan-400 transition-all active:scale-[0.98]">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
    @endcan

    {{-- ── Modal: Editar requerimiento ────────────────────────────── --}}
    @can('requerimientos.editar')
    <template x-teleport="body">
        <div x-show="edit !== null"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto" style="display:none">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="cerrarEditar()"></div>
            <div class="relative min-h-full flex items-start justify-center p-4 pt-12">
                <div class="w-full max-w-lg bg-slate-900 border border-slate-700/60 rounded-2xl shadow-2xl"
                     x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     @click.stop>
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800/80">
                        <h3 class="text-base font-bold text-white">Editar requerimiento</h3>
                        <button @click="cerrarEditar()" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-500 hover:text-white hover:bg-slate-800 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" :action="editUrl" class="p-6 space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Título <span class="text-red-400">*</span></label>
                            <input type="text" name="title" :value="edit?.title" class="input-dark" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Descripción</label>
                            <textarea name="description" rows="3" class="input-dark resize-none"
                                      x-text="edit?.description" x-init="$watch('edit', v => { if (v) $el.value = v.description || '' })"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Tipo</label>
                                <select name="type" class="input-dark"
                                        x-init="$watch('edit', v => { if (v) $el.value = v.type })">
                                    <option value="funcional">Funcional</option>
                                    <option value="tecnico">Técnico</option>
                                    <option value="negocio">Negocio</option>
                                    <option value="ux_ui">UX/UI</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Prioridad</label>
                                <select name="priority" class="input-dark"
                                        x-init="$watch('edit', v => { if (v) $el.value = v.priority })">
                                    <option value="critica">Crítica</option>
                                    <option value="alta">Alta</option>
                                    <option value="media">Media</option>
                                    <option value="baja">Baja</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Estado</label>
                                <select name="status" class="input-dark"
                                        x-init="$watch('edit', v => { if (v) $el.value = v.status })">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="en_progreso">En progreso</option>
                                    <option value="en_revision">En revisión</option>
                                    <option value="completado">Completado</option>
                                    <option value="rechazado">Rechazado</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Story points</label>
                                <select name="story_points" class="input-dark"
                                        x-init="$watch('edit', v => { if (v) $el.value = v.story_points ?? '' })">
                                    <option value="">— sin estimar</option>
                                    @foreach([1,2,3,5,8,13,21] as $pts)
                                    <option value="{{ $pts }}">{{ $pts }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Asignar a</label>
                            <select name="assigned_to" class="input-dark"
                                    x-init="$watch('edit', v => { if (v) $el.value = v.assigned_to ?? '' })">
                                <option value="">Sin asignar</option>
                                @foreach($usuarios as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($proyecto->phases->isNotEmpty())
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Fase</label>
                            <select name="phase_id" class="input-dark"
                                    x-init="$watch('edit', v => { if (v) $el.value = v.phase_id ?? '' })">
                                <option value="">Sin fase</option>
                                @foreach($proyecto->phases as $fase)
                                <option value="{{ $fase->id }}">{{ $fase->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="cerrarEditar()"
                                    class="px-4 py-2.5 text-sm text-slate-400 hover:text-white border border-slate-700/60 rounded-xl hover:bg-slate-800 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-cyan-500 hover:from-sky-400 hover:to-cyan-400 transition-all active:scale-[0.98]">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
    @endcan

    {{-- ── Form oculto drag & drop ────────────────────────────────── --}}
    <form id="dd-form" method="POST" style="display:none">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" id="dd-status">
    </form>

    {{-- ── Kanban ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 items-start">
        @foreach($columnas as $estado => $col)
        @php
            $items = $requerimientos->where('status', $estado);
            $colorMap = [
                'slate'   => ['header' => 'bg-slate-800 text-slate-400',              'border' => 'border-slate-700/40'],
                'sky'     => ['header' => 'bg-sky-500/10 text-sky-400',               'border' => 'border-sky-500/20'],
                'violet'  => ['header' => 'bg-violet-500/10 text-violet-400',         'border' => 'border-violet-500/20'],
                'emerald' => ['header' => 'bg-emerald-500/10 text-emerald-400',       'border' => 'border-emerald-500/20'],
                'red'     => ['header' => 'bg-red-500/10 text-red-400',               'border' => 'border-red-500/20'],
            ];
            $c       = $colorMap[$col['color']];
            $colPts  = $items->sum('story_points');
        @endphp

        <div class="bg-slate-900 border rounded-2xl transition-colors duration-150"
             :class="over === '{{ $estado }}' ? 'border-sky-500/40' : 'border-slate-800/60'">

            {{-- Cabecera --}}
            <div class="flex items-center justify-between px-4 py-3 {{ $c['header'] }} border-b {{ $c['border'] }} rounded-t-2xl">
                <span class="text-xs font-semibold">{{ $col['label'] }}</span>
                <div class="flex items-center gap-2">
                    @if($colPts > 0)
                    <span class="text-[10px] font-mono text-slate-600" title="Story points en esta columna">{{ $colPts }}pt</span>
                    @endif
                    <span class="text-xs font-mono bg-slate-900/40 px-1.5 py-0.5 rounded-md">{{ $items->count() }}</span>
                </div>
            </div>

            {{-- Drop zone --}}
            <div class="p-2 space-y-2 min-h-[80px] rounded-b-2xl transition-colors duration-150"
                 :class="over === '{{ $estado }}' ? 'bg-sky-500/5' : ''"
                 @dragover.prevent
                 @dragenter="enter('{{ $estado }}')"
                 @dragleave="leave($event)"
                 @drop.prevent="drop($event, '{{ $estado }}')">

                @forelse($items as $req)
                <div x-show="visible(
                        '{{ $req->priority }}',
                        '{{ $req->type }}',
                        {{ $req->assigned_to ?? 'null' }},
                        {{ Js::from($req->title) }}
                     )"
                     class="bg-slate-800/60 border border-slate-700/40 rounded-xl p-3
                            hover:border-slate-600/60 transition-colors cursor-grab active:cursor-grabbing select-none group"
                     draggable="true"
                     @dragstart="drag($event, {{ $req->id }}, '{{ $req->status }}')"
                     @dragend="endDrag($event)">

                    {{-- Fila superior: badges + story points + edit --}}
                    <div class="flex items-start justify-between gap-1.5 mb-2">
                        <div class="flex items-center gap-1.5 flex-wrap min-w-0">
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md {{ $req->priorityBadge() }} capitalize flex-shrink-0">
                                {{ $req->priority }}
                            </span>
                            <span class="text-[10px] text-slate-600 bg-slate-800 px-1.5 py-0.5 rounded-md flex-shrink-0">
                                {{ $req->typeLabel() }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            @if($req->story_points)
                            <span class="text-[11px] font-bold font-mono text-sky-400 bg-sky-500/10 px-1.5 py-0.5 rounded-md">
                                {{ $req->story_points }}pt
                            </span>
                            @endif
                            @can('requerimientos.editar')
                            <button type="button"
                                    @click.stop="abrirEditar({{ Js::from([
                                        'id'           => $req->id,
                                        'title'        => $req->title,
                                        'description'  => $req->description ?? '',
                                        'type'         => $req->type,
                                        'priority'     => $req->priority,
                                        'status'       => $req->status,
                                        'assigned_to'  => $req->assigned_to,
                                        'story_points' => $req->story_points,
                                        'phase_id'     => $req->phase_id,
                                    ]) }})"
                                    class="p-1 rounded-md text-slate-700 hover:text-sky-400 hover:bg-sky-500/10
                                           opacity-0 group-hover:opacity-100 transition-all duration-150"
                                    title="Editar">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                </svg>
                            </button>
                            @endcan
                        </div>
                    </div>

                    {{-- Título --}}
                    <p class="text-xs font-medium text-white leading-snug mb-1.5">{{ $req->title }}</p>

                    {{-- Descripción (snippet) --}}
                    @if($req->description)
                    <p class="text-[10px] text-slate-600 leading-relaxed mb-2 line-clamp-2">{{ $req->description }}</p>
                    @endif

                    {{-- Footer: asignado + mover --}}
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-700/40">
                        {{-- Asignado --}}
                        <div class="flex items-center gap-1.5 min-w-0">
                            @if($req->assignedTo)
                            <div class="w-4 h-4 rounded-full bg-sky-500/20 flex items-center justify-center
                                        text-[8px] font-bold text-sky-400 uppercase flex-shrink-0">
                                {{ substr($req->assignedTo->name, 0, 1) }}
                            </div>
                            <p class="text-[10px] text-slate-500 truncate">{{ $req->assignedTo->name }}</p>
                            @else
                            <p class="text-[10px] text-slate-700">Sin asignar</p>
                            @endif
                        </div>

                        {{-- Menú mover --}}
                        @can('requerimientos.editar')
                        <div class="relative flex-shrink-0" x-data="{ open: false }">
                            <button type="button" @click.stop="open = !open"
                                    class="text-[10px] text-slate-600 hover:text-sky-400 transition-colors flex items-center gap-0.5">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15 12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                                </svg>
                                Mover
                            </button>
                            <div x-show="open" @click.outside="open = false"
                                 class="absolute right-0 bottom-7 z-30 bg-slate-800 border border-slate-700/60
                                        rounded-xl overflow-hidden shadow-xl w-36"
                                 style="display:none">
                                @foreach($columnas as $s => $cl)
                                @if($s !== $estado)
                                <form method="POST" action="{{ route('requerimientos.update', [$proyecto, $req]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $s }}">
                                    <button type="submit"
                                            class="w-full text-left px-3 py-2 text-xs text-slate-300 hover:bg-slate-700/60 transition-colors">
                                        {{ $cl['label'] }}
                                    </button>
                                </form>
                                @endif
                                @endforeach
                                <div class="border-t border-slate-700/40">
                                    <form method="POST" action="{{ route('requerimientos.destroy', [$proyecto, $req]) }}"
                                          x-data @submit.prevent="if(confirm('¿Eliminar este requerimiento?')) $el.submit()">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="w-full text-left px-3 py-2 text-xs text-red-400 hover:bg-red-500/10 transition-colors">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>
                @empty
                <p class="text-[11px] text-slate-700 text-center py-6">Sin items</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    </div>{{-- fin wrapper x-data --}}

</x-app-layout>

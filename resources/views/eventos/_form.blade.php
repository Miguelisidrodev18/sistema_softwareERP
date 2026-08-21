{{--
    Partial compartido por create.blade.php y edit.blade.php
    Variables esperadas: $evento (opcional, para edición), $usuarios
--}}
@include('eventos._leaflet-assets')

<div
    x-data="ubicacionPicker({{ $evento->latitud ?? 'null' }}, {{ $evento->longitud ?? 'null' }})"
    class="space-y-6"
>

    {{-- ── Datos del evento ───────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-5 flex items-center gap-2">
            <span class="w-5 h-5 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold">1</span>
            Datos del evento
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-400 mb-1.5">
                    Nombre del evento <span class="text-red-400">*</span>
                </label>
                <input
                    type="text"
                    name="nombre"
                    class="input-dark @error('nombre') error @enderror"
                    placeholder="Ej. Feria Tecnológica Huancayo 2026"
                    value="{{ old('nombre', $evento->nombre ?? '') }}"
                >
                @error('nombre')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Descripción</label>
                <textarea
                    name="descripcion"
                    rows="2"
                    class="input-dark"
                    placeholder="Objetivo de la campaña o feria (opcional)"
                >{{ old('descripcion', $evento->descripcion ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Fecha de inicio <span class="text-red-400">*</span></label>
                <input
                    type="date"
                    name="fecha_inicio"
                    class="input-dark @error('fecha_inicio') error @enderror"
                    value="{{ old('fecha_inicio', isset($evento) ? $evento->fecha_inicio->format('Y-m-d') : '') }}"
                >
                @error('fecha_inicio')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Fecha de fin</label>
                <input
                    type="date"
                    name="fecha_fin"
                    class="input-dark @error('fecha_fin') error @enderror"
                    value="{{ old('fecha_fin', isset($evento) && $evento->fecha_fin ? $evento->fecha_fin->format('Y-m-d') : '') }}"
                >
                @error('fecha_fin')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Estado</label>
                <select name="estado" class="input-dark">
                    @foreach(\App\Models\Event::ESTADOS as $val)
                    <option value="{{ $val }}" {{ old('estado', $evento->estado ?? 'planificado') === $val ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $val)) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Responsable</label>
                <select name="responsable_id" class="input-dark">
                    <option value="">— Sin asignar —</option>
                    @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" {{ (string) old('responsable_id', $evento->responsable_id ?? '') === (string) $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                    @endforeach
                </select>
            </div>

        </div>
    </div>

    {{-- ── Ubicación del evento ───────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-5 flex items-center gap-2">
            <span class="w-5 h-5 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold">2</span>
            Ubicación
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Lugar / Local</label>
                <input
                    type="text"
                    name="lugar"
                    class="input-dark"
                    placeholder="Ej. Cámara de Comercio de Huancayo"
                    value="{{ old('lugar', $evento->lugar ?? '') }}"
                >
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Dirección</label>
                <input
                    type="text"
                    name="direccion"
                    class="input-dark"
                    placeholder="Referencia de la dirección"
                    value="{{ old('direccion', $evento->direccion ?? '') }}"
                >
            </div>
        </div>

        @include('eventos._mapa-picker', ['mapId' => 'mapa-evento'])
    </div>

</div>

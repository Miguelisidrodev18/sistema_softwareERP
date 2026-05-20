{{-- Partial compartido: create y edit --}}
<div
    x-data="{
        fases: @json(old('phases', isset($proyecto) ? $proyecto->phases->map(fn($f) => ['name' => $f->name])->toArray() : [])),
        agregarFase() { this.fases.push({ name: '' }); },
        quitarFase(i) { this.fases.splice(i, 1); }
    }"
    class="space-y-6"
>

    {{-- Datos principales --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-5 flex items-center gap-2">
            <span class="w-5 h-5 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold">1</span>
            Información del proyecto
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Nombre del proyecto <span class="text-red-400">*</span></label>
                <input type="text" name="name" class="input-dark @error('name') error @enderror"
                       placeholder="Ej. Sistema de ventas para Empresa XYZ"
                       value="{{ old('name', $proyecto->name ?? '') }}">
                @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Cliente <span class="text-red-400">*</span></label>
                <select name="client_id" class="input-dark @error('client_id') error @enderror">
                    <option value="">Selecciona un cliente</option>
                    @foreach($clientes as $c)
                    <option value="{{ $c->id }}" {{ old('client_id', $proyecto->client_id ?? '') == $c->id ? 'selected' : '' }}>
                        {{ $c->razon_social }}
                    </option>
                    @endforeach
                </select>
                @error('client_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Responsable</label>
                <select name="responsible_user_id" class="input-dark">
                    <option value="">Sin asignar</option>
                    @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" {{ old('responsible_user_id', $proyecto->responsible_user_id ?? '') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Estado</label>
                <select name="status" class="input-dark">
                    @foreach(['planificado' => 'Planificado','en_curso' => 'En curso','pausado' => 'Pausado','en_revision' => 'En revisión','entregado' => 'Entregado','cancelado' => 'Cancelado'] as $val => $label)
                    <option value="{{ $val }}" {{ old('status', $proyecto->status ?? 'planificado') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Fecha de inicio</label>
                <input type="date" name="start_date" class="input-dark font-mono"
                       value="{{ old('start_date', isset($proyecto) ? $proyecto->start_date?->format('Y-m-d') : '') }}">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Fecha de entrega</label>
                <input type="date" name="end_date" class="input-dark font-mono"
                       value="{{ old('end_date', isset($proyecto) ? $proyecto->end_date?->format('Y-m-d') : '') }}">
                @error('end_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Descripción</label>
                <textarea name="description" rows="3"
                          class="input-dark resize-none"
                          placeholder="Describe el alcance del proyecto...">{{ old('description', $proyecto->description ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Fases (solo en create) --}}
    @unless(isset($proyecto))
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <span class="w-5 h-5 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold">2</span>
                Fases del proyecto
                <span class="text-xs text-slate-600 font-normal">(opcional)</span>
            </h3>
            <button type="button" @click="agregarFase()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           text-sky-400 bg-sky-500/10 border border-sky-500/20 hover:bg-sky-500/20 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Agregar fase
            </button>
        </div>

        <div class="space-y-2" x-show="fases.length > 0">
            <template x-for="(fase, i) in fases" :key="i">
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-md bg-slate-800 flex items-center justify-center text-xs font-mono text-slate-500 flex-shrink-0"
                         x-text="i + 1"></div>
                    <input type="text" :name="`phases[${i}][name]`" x-model="fase.name"
                           class="input-dark flex-1" placeholder="Nombre de la fase">
                    <button type="button" @click="quitarFase(i)"
                            class="p-1.5 rounded-lg text-slate-600 hover:text-red-400 hover:bg-red-500/10 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <p x-show="fases.length === 0" class="text-xs text-slate-600 text-center py-4">
            Sin fases — puedes agregarlas después desde el detalle del proyecto
        </p>
    </div>
    @endunless

</div>

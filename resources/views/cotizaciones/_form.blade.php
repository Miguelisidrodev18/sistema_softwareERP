{{-- Partial compartido: create y edit --}}
@php
    $igvPct      = $config?->igv_porcentaje ?? 18;
    $termDefault = $config ? "Validez de la cotización: 30 días calendario.\nPrecios en {$config->moneda}." : '';
    // Items para Alpine (edit mode pre-populate)
    $itemsInit = isset($cotizacion)
        ? $cotizacion->items->map(fn($i) => [
            'descripcion'     => $i->descripcion,
            'cantidad'        => (float)$i->cantidad,
            'unidad'          => $i->unidad,
            'precio_unitario' => (float)$i->precio_unitario,
            'descuento'       => (float)$i->descuento,
        ])->values()->toArray()
        : [['descripcion' => '', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 0, 'descuento' => 0]];
@endphp

<div class="space-y-6"
     x-data="{
        igvPct: {{ $igvPct }},
        incluyeIgv: {{ old('incluye_igv', isset($cotizacion) ? ($cotizacion->incluye_igv ? 'true' : 'false') : 'true') }},
        items: {{ Js::from($itemsInit) }},
        unidades: {{ Js::from(\App\Models\Quote::UNIDADES) }},

        addItem() {
            this.items.push({ descripcion: '', cantidad: 1, unidad: 'servicio', precio_unitario: 0, descuento: 0 });
        },
        removeItem(i) {
            if (this.items.length > 1) this.items.splice(i, 1);
        },
        subtotalItem(item) {
            const bruto = parseFloat(item.cantidad || 0) * parseFloat(item.precio_unitario || 0);
            const desc  = parseFloat(item.descuento || 0);
            return bruto * (1 - desc / 100);
        },
        get subtotal() {
            return this.items.reduce((s, i) => s + this.subtotalItem(i), 0);
        },
        get igv() {
            return this.incluyeIgv ? this.subtotal * this.igvPct / 100 : 0;
        },
        get total() {
            return this.subtotal + this.igv;
        },
        fmt(n) {
            return new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0);
        }
     }">

    {{-- ── Sección 1: Datos principales ─────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-5 flex items-center gap-2">
            <span class="w-5 h-5 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold">1</span>
            Información general
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Cliente --}}
            <div
                x-data="{
                    items:      {{ Js::from($clientes->map(fn($c) => ['id' => $c->id, 'label' => $c->razon_social . ($c->nombre_comercial ? ' — '.$c->nombre_comercial : ''), 'sub' => $c->numero_documento])) }},
                    search: '',
                    selectedId: {{ old('client_id', isset($cotizacion) ? $cotizacion->client_id : 'null') }},
                    open: false,
                    get filtered() {
                        const q = this.search.toLowerCase();
                        if (!q) return this.items.slice(0, 10);
                        return this.items.filter(i => i.label.toLowerCase().includes(q) || (i.sub && i.sub.includes(q))).slice(0, 10);
                    },
                    select(item) { this.selectedId = item.id; this.search = item.label; this.open = false; },
                    clear()      { this.selectedId = null; this.search = ''; this.$refs.input.focus(); },
                    init() {
                        if (this.selectedId) {
                            const found = this.items.find(i => i.id == this.selectedId);
                            if (found) this.search = found.label;
                        }
                    }
                }"
                class="relative sm:col-span-2"
            >
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Cliente <span class="text-red-400">*</span></label>
                <input type="hidden" name="client_id" :value="selectedId">
                <div class="relative">
                    <input x-ref="input" type="text" x-model="search"
                           @focus="open = true" @input="open = true; selectedId = null"
                           @keydown.escape="open = false" @click.outside="open = false"
                           placeholder="Escribe el nombre o RUC del cliente..."
                           autocomplete="off"
                           class="input-dark pr-8 @error('client_id') error @enderror">
                    <button x-show="selectedId" type="button" @click="clear()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div x-show="open && filtered.length > 0"
                     class="absolute z-50 left-0 right-0 mt-1 bg-slate-800 border border-slate-700/60 rounded-xl overflow-hidden shadow-xl max-h-52 overflow-y-auto"
                     style="display:none">
                    <template x-for="item in filtered" :key="item.id">
                        <button type="button" @click="select(item)"
                                class="w-full text-left px-4 py-2.5 hover:bg-slate-700/60 transition-colors border-b border-slate-700/40 last:border-0">
                            <p class="text-sm text-white truncate" x-text="item.label"></p>
                            <p class="text-[10px] text-slate-500 font-mono mt-0.5" x-text="item.sub"></p>
                        </button>
                    </template>
                </div>
                @error('client_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Proyecto (opcional) --}}
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Proyecto <span class="text-slate-600 font-normal">— opcional</span></label>
                <select name="project_id" class="input-dark">
                    <option value="">Sin proyecto asociado</option>
                    @foreach($proyectos as $p)
                    <option value="{{ $p->id }}" {{ old('project_id', $cotizacion->project_id ?? '') == $p->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Moneda --}}
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Moneda</label>
                <select name="moneda" class="input-dark">
                    <option value="PEN" {{ old('moneda', $cotizacion->moneda ?? 'PEN') === 'PEN' ? 'selected' : '' }}>S/ Soles (PEN)</option>
                    <option value="USD" {{ old('moneda', $cotizacion->moneda ?? 'PEN') === 'USD' ? 'selected' : '' }}>$ Dólares (USD)</option>
                </select>
            </div>

            {{-- Fecha emisión --}}
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Fecha de emisión</label>
                <input type="date" name="fecha_emision" class="input-dark font-mono"
                       value="{{ old('fecha_emision', isset($cotizacion) ? $cotizacion->fecha_emision->format('Y-m-d') : now()->format('Y-m-d')) }}">
            </div>

            {{-- Fecha vencimiento --}}
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Válida hasta <span class="text-slate-600 font-normal">— opcional</span></label>
                <input type="date" name="fecha_vencimiento" class="input-dark font-mono"
                       value="{{ old('fecha_vencimiento', isset($cotizacion) ? $cotizacion->fecha_vencimiento?->format('Y-m-d') : '') }}">
            </div>

            {{-- IGV --}}
            <div class="sm:col-span-2 flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="incluye_igv" value="0">
                    <input type="checkbox" name="incluye_igv" value="1"
                           x-model="incluyeIgv"
                           class="sr-only peer"
                           {{ old('incluye_igv', isset($cotizacion) ? $cotizacion->incluye_igv : true) ? 'checked' : '' }}>
                    <div class="w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer
                                peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all
                                peer-checked:bg-sky-500"></div>
                </label>
                <span class="text-xs text-slate-400">
                    Aplicar IGV (<span x-text="igvPct"></span>%)
                    <span class="text-slate-600 ml-1">— según configuración de empresa</span>
                </span>
            </div>
        </div>
    </div>

    {{-- ── Sección 2: Items ──────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                <span class="w-5 h-5 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold">2</span>
                Ítems / Servicios
            </h3>
            <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                           text-sky-400 bg-sky-500/10 border border-sky-500/20 hover:bg-sky-500/20 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Agregar ítem
            </button>
        </div>

        {{-- Cabecera tabla --}}
        <div class="hidden sm:grid grid-cols-12 gap-2 mb-2 px-1">
            <div class="col-span-5 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">Descripción</div>
            <div class="col-span-2 text-[10px] font-semibold text-slate-600 uppercase tracking-wider text-center">Cant.</div>
            <div class="col-span-2 text-[10px] font-semibold text-slate-600 uppercase tracking-wider text-right">Precio unit.</div>
            <div class="col-span-1 text-[10px] font-semibold text-slate-600 uppercase tracking-wider text-center">Dto %</div>
            <div class="col-span-2 text-[10px] font-semibold text-slate-600 uppercase tracking-wider text-right">Subtotal</div>
        </div>

        {{-- Items --}}
        <div class="space-y-2">
            <template x-for="(item, index) in items" :key="index">
                <div class="grid grid-cols-12 gap-2 items-start bg-slate-800/40 border border-slate-700/40 rounded-xl p-3">

                    {{-- Hidden inputs --}}
                    <input type="hidden" :name="`items[${index}][descripcion]`"    :value="item.descripcion">
                    <input type="hidden" :name="`items[${index}][cantidad]`"       :value="item.cantidad">
                    <input type="hidden" :name="`items[${index}][unidad]`"         :value="item.unidad">
                    <input type="hidden" :name="`items[${index}][precio_unitario]`" :value="item.precio_unitario">
                    <input type="hidden" :name="`items[${index}][descuento]`"      :value="item.descuento">

                    {{-- Descripción + unidad --}}
                    <div class="col-span-12 sm:col-span-5 space-y-1.5">
                        <input type="text" x-model="item.descripcion"
                               placeholder="Descripción del servicio..."
                               class="w-full bg-slate-800 border border-slate-700/40 rounded-lg px-3 py-2 text-xs text-white
                                      placeholder-slate-600 focus:outline-none focus:border-sky-500/60 transition-colors">
                        <select x-model="item.unidad"
                                class="w-full bg-slate-800 border border-slate-700/40 rounded-lg px-3 py-1.5 text-xs text-slate-400
                                       focus:outline-none focus:border-sky-500/60 transition-colors">
                            <template x-for="u in unidades" :key="u">
                                <option :value="u" x-text="u" :selected="item.unidad === u"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Cantidad --}}
                    <div class="col-span-4 sm:col-span-2">
                        <input type="number" x-model.number="item.cantidad"
                               min="0.01" step="0.01" placeholder="1"
                               class="w-full bg-slate-800 border border-slate-700/40 rounded-lg px-3 py-2 text-xs text-white text-center
                                      focus:outline-none focus:border-sky-500/60 transition-colors">
                    </div>

                    {{-- Precio unitario --}}
                    <div class="col-span-4 sm:col-span-2">
                        <input type="number" x-model.number="item.precio_unitario"
                               min="0" step="0.01" placeholder="0.00"
                               class="w-full bg-slate-800 border border-slate-700/40 rounded-lg px-3 py-2 text-xs text-white text-right
                                      focus:outline-none focus:border-sky-500/60 transition-colors">
                    </div>

                    {{-- Descuento --}}
                    <div class="col-span-2 sm:col-span-1">
                        <input type="number" x-model.number="item.descuento"
                               min="0" max="100" step="1" placeholder="0"
                               class="w-full bg-slate-800 border border-slate-700/40 rounded-lg px-3 py-2 text-xs text-white text-center
                                      focus:outline-none focus:border-sky-500/60 transition-colors">
                    </div>

                    {{-- Subtotal + eliminar --}}
                    <div class="col-span-2 sm:col-span-2 flex items-center justify-end gap-2">
                        <p class="text-xs font-semibold font-mono text-white text-right" x-text="fmt(subtotalItem(item))"></p>
                        <button type="button" @click="removeItem(index)"
                                x-show="items.length > 1"
                                class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded-md
                                       text-slate-600 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Totales --}}
        <div class="mt-5 pt-4 border-t border-slate-800/60 space-y-2 max-w-xs ml-auto">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>Subtotal</span>
                <span class="font-mono font-semibold text-white" x-text="fmt(subtotal)"></span>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-400" x-show="incluyeIgv">
                <span>IGV (<span x-text="igvPct"></span>%)</span>
                <span class="font-mono text-slate-300" x-text="fmt(igv)"></span>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-slate-700/40">
                <span class="text-sm font-semibold text-white">Total</span>
                <span class="text-base font-bold font-mono text-sky-400" x-text="fmt(total)"></span>
            </div>
        </div>
    </div>

    {{-- ── Sección 3: Notas y términos ──────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-5 flex items-center gap-2">
            <span class="w-5 h-5 rounded-md bg-sky-500/20 flex items-center justify-center text-sky-400 text-xs font-bold">3</span>
            Notas y términos
            <span class="text-xs font-normal text-slate-600">— opcionales</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Notas para el cliente</label>
                <textarea name="notas" rows="4" class="input-dark resize-none text-xs"
                          placeholder="Agradecimientos, aclaraciones, condiciones especiales...">{{ old('notas', $cotizacion->notas ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Términos y condiciones</label>
                <textarea name="terminos" rows="4" class="input-dark resize-none text-xs font-mono"
                          placeholder="Validez, forma de pago, garantías...">{{ old('terminos', $cotizacion->terminos ?? $termDefault) }}</textarea>
            </div>
        </div>
    </div>

</div>

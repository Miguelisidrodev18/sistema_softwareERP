<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('clientes.index') }}" class="text-slate-600 hover:text-slate-400 transition-colors font-mono">Clientes</a>
            <span class="text-slate-700">/</span>
            <span class="text-white font-semibold truncate max-w-[240px]">{{ $cliente->razon_social }}</span>
        </div>
    </x-slot>

    {{-- Header card ─────────────────────────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6 mb-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">

            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-sky-500/10 border border-sky-500/20
                            flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-xl font-bold text-white">{{ $cliente->razon_social }}</h1>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold capitalize
                                     {{ $cliente->estadoBadgeClass() }}">
                            {{ $cliente->estado }}
                        </span>
                    </div>
                    @if($cliente->nombre_comercial)
                    <p class="text-sm text-slate-500 mt-0.5">{{ $cliente->nombre_comercial }}</p>
                    @endif
                    <p class="text-xs font-mono text-slate-600 mt-1">
                        {{ $cliente->tipo_documento }} · {{ $cliente->numero_documento }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                @can('clientes.editar')
                <a href="{{ route('clientes.edit', $cliente) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium
                          bg-slate-800 text-slate-300 border border-slate-700/60
                          hover:border-sky-500/30 hover:text-sky-400 transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                    </svg>
                    Editar
                </a>
                @endcan
                @can('clientes.eliminar')
                <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                      x-data
                      @submit.prevent="if(confirm('¿Eliminar a {{ addslashes($cliente->razon_social) }}? Esta acción no se puede deshacer.')) $el.submit()">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium
                                   bg-slate-800 text-red-400 border border-slate-700/60
                                   hover:border-red-500/30 hover:bg-red-500/10 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                        Eliminar
                    </button>
                </form>
                @endcan
            </div>

        </div>
    </div>

    {{-- Info grid ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">

        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5 space-y-4">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Contacto</h3>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-600">Email</p>
                    <p class="text-sm text-white">{{ $cliente->email ?? '—' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-600">Teléfono</p>
                    <p class="text-sm text-white font-mono">{{ $cliente->telefono ?? '—' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-600">Dirección</p>
                    <p class="text-sm text-white">{{ $cliente->direccion ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5 space-y-4">
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sistema</h3>
            <div>
                <p class="text-xs text-slate-600">Registrado por</p>
                <p class="text-sm text-white mt-0.5">{{ $cliente->createdBy->name ?? 'Sistema' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-600">Fecha de registro</p>
                <p class="text-sm text-white font-mono mt-0.5">{{ $cliente->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-600">Última actualización</p>
                <p class="text-sm text-white font-mono mt-0.5">{{ $cliente->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>

    {{-- Historial (placeholder para sprints futuros) ─────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Historial</h3>
        <div class="grid grid-cols-3 gap-4">
            @foreach(['Proyectos', 'Cotizaciones', 'Facturas'] as $seccion)
            <div class="bg-slate-800/40 rounded-xl p-4 text-center border border-slate-700/30">
                <p class="text-2xl font-bold text-slate-600 font-mono">0</p>
                <p class="text-xs text-slate-600 mt-1">{{ $seccion }}</p>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-700 mt-4 text-center">
            El historial completo estará disponible al avanzar los sprints de Proyectos y Facturación.
        </p>
    </div>

</x-app-layout>

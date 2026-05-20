<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-600 font-mono">Inicio</span>
            <span class="text-slate-700">/</span>
            <h1 class="text-sm font-semibold text-white">Dashboard</h1>
        </div>
    </x-slot>

    {{-- ── KPI Cards ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        {{-- Ingresos del mes --}}
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5 hover:border-sky-500/20 transition-colors duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                    </svg>
                </div>
                <span class="text-xs text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-lg font-mono">+0%</span>
            </div>
            <p class="text-2xl font-bold text-white font-mono">S/ 0.00</p>
            <p class="text-xs text-slate-500 mt-1">Ingresos del mes</p>
        </div>

        {{-- Proyectos activos --}}
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5 hover:border-sky-500/20 transition-colors duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/>
                    </svg>
                </div>
                <span class="text-xs text-slate-600 bg-slate-800 px-2 py-0.5 rounded-lg font-mono">activos</span>
            </div>
            <p class="text-2xl font-bold text-white font-mono">0</p>
            <p class="text-xs text-slate-500 mt-1">Proyectos en curso</p>
        </div>

        {{-- Facturas emitidas --}}
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5 hover:border-sky-500/20 transition-colors duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185ZM9.75 9h.008v.008H9.75V9Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm4.125 4.5h.008v.008h-.008V13.5Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
                </div>
                <span class="text-xs text-slate-600 bg-slate-800 px-2 py-0.5 rounded-lg font-mono">mes</span>
            </div>
            <p class="text-2xl font-bold text-white font-mono">0</p>
            <p class="text-xs text-slate-500 mt-1">Comprobantes emitidos</p>
        </div>

        {{-- Saldo de caja --}}
        <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-5 hover:border-sky-500/20 transition-colors duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <span class="text-xs text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-lg font-mono">disponible</span>
            </div>
            <p class="text-2xl font-bold text-white font-mono">S/ 0.00</p>
            <p class="text-xs text-slate-500 mt-1">Saldo en caja</p>
        </div>

    </div>

    {{-- ── Bienvenida / Estado del sprint ─────────────────────────── --}}
    <div class="bg-slate-900 border border-slate-800/60 rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></div>
            <h2 class="text-sm font-semibold text-white">Sistema en construcción — Sprint 1</h2>
        </div>
        <p class="text-sm text-slate-400 leading-relaxed mb-4">
            Bienvenido a <span class="text-white font-medium">Estelar ERP</span>.
            El layout principal y la autenticación están listos. Los módulos se irán activando por sprint.
        </p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs font-mono">
            @foreach([
                ['✓ Auth + Roles', 'emerald'],
                ['✓ Sidebar dinámico', 'emerald'],
                ['· Clientes', 'slate'],
                ['· Proyectos', 'slate'],
                ['· Cotizaciones', 'slate'],
                ['· Facturación SUNAT', 'slate'],
            ] as [$label, $color])
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg
                        {{ $color === 'emerald' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-600' }}">
                {{ $label }}
            </div>
            @endforeach
        </div>
    </div>

</x-app-layout>

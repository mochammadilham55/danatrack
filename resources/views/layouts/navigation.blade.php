@php
    $tautanNavigasi = [
        ['route' => 'dashboard', 'pola' => 'dashboard', 'label' => 'Dashboard', 'ikon' => '📊'],
        ['route' => 'categories.index', 'pola' => 'categories.*', 'label' => 'Kategori', 'ikon' => '🏷️'],
        ['route' => 'transactions.index', 'pola' => 'transactions.*', 'label' => 'Transaksi', 'ikon' => '💸'],
        ['route' => 'reports.index', 'pola' => 'reports.*', 'label' => 'Laporan', 'ikon' => '📈'],
    ];
@endphp

<!-- Sidebar (desktop) -->
<aside class="w-64 bg-slate-900 text-white hidden lg:flex flex-col fixed inset-y-0 left-0 z-30">
    <a href="{{ route('dashboard') }}" class="p-6 flex items-center gap-2 font-extrabold text-lg shrink-0">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">D</div>
        DanaTrack
    </a>

    <nav class="flex-1 px-4 space-y-1 text-sm font-medium overflow-y-auto">
        @foreach ($tautanNavigasi as $tautan)
            <a href="{{ route($tautan['route']) }}"
               class="flex items-center gap-3 px-4 py-3 rounded-2xl transition
                   {{ request()->routeIs($tautan['pola'])
                        ? 'bg-white/10 text-white'
                        : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                <span>{{ $tautan['ikon'] }}</span>
                {{ $tautan['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="p-4 border-t border-white/10" x-data="{ open: false }" @click.outside="open = false">
        <button @click="open = !open" class="w-full flex items-center gap-3 text-left">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-xs font-bold shrink-0">
                {{ \Illuminate\Support\Str::of(Auth::user()->name)->substr(0, 2)->upper() }}
            </div>
            <div class="flex-1 overflow-hidden">
                <p class="text-sm font-semibold truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-400">Lihat profil</p>
            </div>
        </button>

        <div x-show="open"
             x-transition
             style="display: none;"
             class="mt-2 bg-slate-800 rounded-2xl overflow-hidden text-sm">
            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-slate-200 hover:bg-white/5">Profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-start block px-4 py-2.5 text-rose-400 hover:bg-white/5">Keluar</button>
            </form>
        </div>
    </div>
</aside>

<!-- Top bar (mobile) -->
<header class="lg:hidden bg-slate-900 text-white sticky top-0 z-30" x-data="{ open: false }">
    <div class="flex items-center justify-between px-4 h-16">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-extrabold">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-sm">D</div>
            DanaTrack
        </a>
        <button @click="open = !open" class="p-2 rounded-lg hover:bg-white/10">
            <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div x-show="open" x-transition style="display: none;" class="px-4 pb-4 space-y-1 text-sm font-medium">
        @foreach ($tautanNavigasi as $tautan)
            <a href="{{ route($tautan['route']) }}"
               class="flex items-center gap-3 px-4 py-3 rounded-2xl
                   {{ request()->routeIs($tautan['pola']) ? 'bg-white/10 text-white' : 'text-slate-400' }}">
                <span>{{ $tautan['ikon'] }}</span>
                {{ $tautan['label'] }}
            </a>
        @endforeach
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-400">👤 Profil</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-start flex items-center gap-3 px-4 py-3 rounded-2xl text-rose-400">🚪 Keluar</button>
        </form>
    </div>
</header>

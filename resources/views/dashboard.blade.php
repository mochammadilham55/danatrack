<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold text-slate-900">
            Halo, {{ Auth::user()->name }} 👋
        </h2>
        <p class="text-slate-500 text-sm mt-0.5">Berikut ringkasan keuanganmu.</p>
    </x-slot>

    <div class="space-y-6">

        {{-- Filter periode --}}
        <div class="bg-white rounded-3xl shadow-sm p-5">
            <form method="GET" action="{{ route('dashboard') }}"
                class="flex flex-col md:flex-row md:items-end gap-4">

                <div>
                    <label for="periode" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wide">
                        Periode
                    </label>

                    <select name="periode" id="periode"
                            class="rounded-full border-2 border-slate-100 text-sm px-4 py-2 font-medium bg-slate-50 focus:border-indigo-400 focus:ring-0">
                        <option value="hari_ini" @selected($periode === 'hari_ini')>
                            Hari Ini
                        </option>
                        <option value="bulan_ini" @selected($periode === 'bulan_ini')>
                            Bulan Ini
                        </option>
                        <option value="tahun_ini" @selected($periode === 'tahun_ini')>
                            Tahun Ini
                        </option>
                        <option value="rentang" @selected($periode === 'rentang')>
                            Rentang Tanggal
                        </option>
                    </select>
                </div>

                <div>
                    <label for="dari" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wide">
                        Dari
                    </label>
                    <input type="date" name="dari" id="dari"
                        value="{{ request('dari') }}"
                        class="rounded-full border-2 border-slate-100 text-sm px-4 py-2 bg-slate-50 focus:border-indigo-400 focus:ring-0">
                </div>

                <div>
                    <label for="sampai" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wide">
                        Sampai
                    </label>
                    <input type="date" name="sampai" id="sampai"
                        value="{{ request('sampai') }}"
                        class="rounded-full border-2 border-slate-100 text-sm px-4 py-2 bg-slate-50 focus:border-indigo-400 focus:ring-0">
                </div>

                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full text-sm font-bold shadow-lg shadow-indigo-200">
                    Terapkan
                </button>
            </form>
        </div>

        {{-- Kartu statistik --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 bg-gradient-to-br from-emerald-500 to-teal-600">
                <p class="text-sm opacity-90">Total Pemasukan</p>
                <h3 class="text-3xl font-extrabold mt-2">
                    Rp{{ number_format($totalPemasukan, 0, ',', '.') }}
                </h3>
            </div>

            <div class="rounded-3xl p-6 text-white shadow-lg shadow-rose-200 bg-gradient-to-br from-rose-500 to-orange-500">
                <p class="text-sm opacity-90">Total Pengeluaran</p>
                <h3 class="text-3xl font-extrabold mt-2">
                    Rp{{ number_format($totalPengeluaran, 0, ',', '.') }}
                </h3>
            </div>

            <div class="rounded-3xl p-6 text-white shadow-lg shadow-indigo-200 bg-gradient-to-br from-indigo-500 to-purple-600">
                <p class="text-sm opacity-90">Saldo</p>
                <h3 class="text-3xl font-extrabold mt-2">
                    Rp{{ number_format($saldo, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {{-- Grafik --}}
            <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm p-6">
                <h3 class="font-bold text-slate-800 mb-4">Grafik 6 Bulan Terakhir</h3>
                <div style="height: 260px;">
                    <canvas id="grafikKeuangan"></canvas>
                </div>
            </div>

            {{-- Kategori pengeluaran terbesar --}}
            <div class="bg-white rounded-3xl shadow-sm p-6">
                <h3 class="font-bold text-slate-800 mb-4">Kategori Pengeluaran Terbesar</h3>

                @if ($kategoriPengeluaranTerbesar)
                    <div class="flex items-center gap-3 bg-amber-50 rounded-2xl p-4">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold"
                              style="background-color: {{ $kategoriPengeluaranTerbesar->category?->color ?? '#64748b' }}">
                            {{ \Illuminate\Support\Str::of($kategoriPengeluaranTerbesar->category?->name ?? '-')->substr(0, 1)->upper() }}
                        </span>
                        <div>
                            <p class="font-bold text-slate-800">
                                {{ $kategoriPengeluaranTerbesar->category?->name ?? 'Tanpa Kategori' }}
                            </p>
                            <p class="text-sm text-slate-500">
                                Rp{{ number_format($kategoriPengeluaranTerbesar->total, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-slate-400">Belum ada data pengeluaran pada periode ini.</p>
                @endif
            </div>
        </div>

        {{-- Transaksi terbaru --}}
        <div class="bg-white rounded-3xl shadow-sm overflow-hidden">
            <div class="p-6 pb-2">
                <h3 class="font-bold text-slate-800">Transaksi Terbaru</h3>
            </div>

            @if ($transaksiTerbaru->isEmpty())
                <p class="text-center text-slate-400 py-8">Belum ada transaksi pada periode ini.</p>
            @else
                <div class="divide-y divide-slate-50">
                    @foreach ($transaksiTerbaru as $transaksi)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold text-white"
                                      style="background-color: {{ $transaksi->category?->color ?? '#94a3b8' }}">
                                    {{ \Illuminate\Support\Str::of($transaksi->category?->name ?? '?')->substr(0, 1)->upper() }}
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm">{{ $transaksi->description }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ $transaksi->transaction_date->translatedFormat('d M') }} ·
                                        {{ $transaksi->category?->name ?? 'Tanpa kategori' }}
                                    </p>
                                </div>
                            </div>
                            <span class="font-bold text-sm {{ $transaksi->type === 'income' ? 'text-emerald-600' : 'text-rose-500' }}">
                                {{ $transaksi->type === 'income' ? '+' : '-' }}Rp{{ number_format($transaksi->amount, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const labelsGrafik = @json($labelsGrafik);
        const dataPemasukan = @json($dataPemasukan);
        const dataPengeluaran = @json($dataPengeluaran);

        const canvas = document.getElementById('grafikKeuangan');

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labelsGrafik,
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: dataPemasukan,
                        backgroundColor: '#10b981',
                        borderRadius: 8
                    },
                    {
                        label: 'Pengeluaran',
                        data: dataPengeluaran,
                        backgroundColor: '#f43f5e',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp' +
                                    new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                }
            }
        });
    </script>
@endpush

</x-app-layout>

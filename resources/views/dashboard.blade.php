<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard DanaTrack
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Filter periode --}}
            <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
                <form method="GET" action="{{ route('dashboard') }}"
                    class="flex flex-col md:flex-row md:items-end gap-4">

                    <div>
                        <label for="periode" class="block text-sm font-medium text-gray-700 mb-1">
                            Periode
                        </label>

                        <select name="periode" id="periode"
                                class="border-gray-300 rounded-md shadow-sm">
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
                        <label for="dari" class="block text-sm font-medium text-gray-700 mb-1">
                            Dari
                        </label>
                        <input type="date" name="dari" id="dari"
                            value="{{ request('dari') }}"
                            class="border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label for="sampai" class="block text-sm font-medium text-gray-700 mb-1">
                            Sampai
                        </label>
                        <input type="date" name="sampai" id="sampai"
                            value="{{ request('sampai') }}"
                            class="border-gray-300 rounded-md shadow-sm">
                    </div>

                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Terapkan
                    </button>

                    <a href="{{ route('dashboard') }}"
                    class="px-4 py-2 text-gray-600 hover:text-gray-900">
                        Reset
                    </a>
                </form>
            </div>

            {{-- Ringkasan keuangan --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Total Pemasukan</p>
                    <h3 class="text-2xl font-bold text-green-600 mt-2">
                        Rp{{ number_format($totalPemasukan, 0, ',', '.') }}
                    </h3>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Total Pengeluaran</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-2">
                        Rp{{ number_format($totalPengeluaran, 0, ',', '.') }}
                    </h3>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Saldo</p>
                    <h3 class="text-2xl font-bold text-blue-600 mt-2">
                        Rp{{ number_format($saldo, 0, ',', '.') }}
                    </h3>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Pengeluaran Terbesar</p>

                    @if ($kategoriPengeluaranTerbesar)
                        <h3 class="text-lg font-bold text-gray-800 mt-2">
                            {{ $kategoriPengeluaranTerbesar->category?->name ?? 'Tanpa Kategori' }}
                        </h3>

                        <p class="text-red-600 font-semibold mt-1">
                            Rp{{ number_format($kategoriPengeluaranTerbesar->total, 0, ',', '.') }}
                        </p>
                    @else
                        <p class="text-gray-500 mt-2">Belum ada pengeluaran.</p>
                    @endif
                </div>

            </div>

            {{-- Grafik pemasukan dan pengeluaran --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Grafik Keuangan 6 Bulan Terakhir
                </h3>

                <div class="relative h-80">
                    <canvas id="grafikKeuangan"></canvas>
                </div>
            </div>

            {{-- Transaksi terbaru --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Transaksi Terbaru
                    </h3>

                    <a href="{{ route('transactions.index') }}"
                       class="text-sm text-indigo-600 hover:text-indigo-800">
                        Lihat Semua
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Tanggal
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Keterangan
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Kategori
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Jenis
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Nominal
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($transaksiTerbaru as $transaksi)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ \Carbon\Carbon::parse($transaksi->transaction_date)->format('d/m/Y') }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        {{ $transaksi->description }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $transaksi->category?->name ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($transaksi->type === 'income')
                                            <span class="text-green-600 font-medium">
                                                Pemasukan
                                            </span>
                                        @else
                                            <span class="text-red-600 font-medium">
                                                Pengeluaran
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium
                                        {{ $transaksi->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaksi->type === 'income' ? '+' : '-' }}Rp{{ number_format($transaksi->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        Belum ada transaksi.
                                        <a href="{{ route('transactions.create') }}"
                                           class="text-indigo-600 hover:underline">
                                            Tambah transaksi pertama
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

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
                        backgroundColor: '#16a34a',
                        borderRadius: 5
                    },
                    {
                        label: 'Pengeluaran',
                        data: dataPengeluaran,
                        backgroundColor: '#dc2626',
                        borderRadius: 5
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

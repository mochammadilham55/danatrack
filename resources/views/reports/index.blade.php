<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Laporan Keuangan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Filter tanggal --}}
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <form method="GET" action="{{ route('reports.index') }}"
                      class="flex flex-col md:flex-row md:items-end gap-4">

                    <div>
                        <label for="dari" class="block text-sm font-medium text-gray-700 mb-1">
                            Dari tanggal
                        </label>
                        <input type="date" name="dari" id="dari"
                               value="{{ request('dari', $tanggalAwal->format('Y-m-d')) }}"
                               class="border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label for="sampai" class="block text-sm font-medium text-gray-700 mb-1">
                            Sampai tanggal
                        </label>
                        <input type="date" name="sampai" id="sampai"
                               value="{{ request('sampai', $tanggalAkhir->format('Y-m-d')) }}"
                               class="border-gray-300 rounded-md shadow-sm">
                    </div>

                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Tampilkan
                    </button>

                    <a href="{{ route('reports.index') }}"
                       class="px-4 py-2 text-gray-600 hover:text-gray-900">
                        Reset
                    </a>
                </form>
            </div>

            {{-- Ringkasan laporan --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
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
                    <p class="text-sm text-gray-500">Saldo Bersih Periode</p>
                    <h3 class="text-2xl font-bold text-blue-600 mt-2">
                        Rp{{ number_format($saldoBersih, 0, ',', '.') }}
                    </h3>
                </div>
            </div>

            {{-- Rekap kategori --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Rekap Pengeluaran per Kategori
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Kategori
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Total Pengeluaran
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($rekapKategori as $kategori)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        {{ $kategori->nama_kategori ?? 'Tanpa Kategori' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right text-red-600 font-medium">
                                        Rp{{ number_format($kategori->total, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-6 text-center text-gray-500">
                                        Belum ada pengeluaran pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Daftar transaksi --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Rincian Transaksi
                    </h3>
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

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($daftarTransaksi as $transaksi)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ \Carbon\Carbon::parse($transaksi->transaction_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-800">
                                        {{ $transaksi->description }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $transaksi->category?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        {{ $transaksi->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium
                                        {{ $transaksi->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaksi->type === 'income' ? '+' : '-' }}Rp{{ number_format($transaksi->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                                        Tidak ada transaksi pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

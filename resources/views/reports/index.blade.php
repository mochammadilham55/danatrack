<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold text-slate-900">
            Laporan Keuangan
        </h2>
    </x-slot>

    <div class="space-y-6">

        {{-- Filter tanggal --}}
        <div class="bg-white rounded-3xl shadow-sm p-5">
            <form method="GET" action="{{ route('reports.index') }}"
                  class="flex flex-col md:flex-row md:items-end gap-4">

                <div>
                    <label for="dari" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wide">
                        Dari tanggal
                    </label>
                    <input type="date" name="dari" id="dari"
                           value="{{ request('dari', $tanggalAwal->format('Y-m-d')) }}"
                           class="rounded-full border-2 border-slate-100 text-sm px-4 py-2 bg-slate-50 focus:border-indigo-400 focus:ring-0">
                </div>

                <div>
                    <label for="sampai" class="block text-xs font-semibold text-slate-500 mb-1 uppercase tracking-wide">
                        Sampai tanggal
                    </label>
                    <input type="date" name="sampai" id="sampai"
                           value="{{ request('sampai', $tanggalAkhir->format('Y-m-d')) }}"
                           class="rounded-full border-2 border-slate-100 text-sm px-4 py-2 bg-slate-50 focus:border-indigo-400 focus:ring-0">
                </div>

                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full text-sm font-bold shadow-lg shadow-indigo-200">
                    Tampilkan
                </button>

                <a href="{{ route('reports.index') }}"
                   class="px-3 py-2.5 text-slate-500 text-sm font-semibold hover:text-slate-800">
                    Reset
                </a>

                <a href="{{ route('reports.exportExcel', [
                        'dari' => $tanggalAwal->format('Y-m-d'),
                        'sampai' => $tanggalAkhir->format('Y-m-d'),
                   ]) }}"
                   class="md:ms-auto px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-full text-sm font-bold shadow-lg shadow-emerald-200">
                    ⬇ Unduh Excel
                </a>
            </form>
        </div>

        {{-- Ringkasan laporan --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div class="rounded-3xl p-6 text-white shadow-lg shadow-emerald-200 bg-gradient-to-br from-emerald-500 to-teal-600">
                <p class="text-sm opacity-90">Total Pemasukan</p>
                <h3 class="text-2xl font-extrabold mt-2">
                    Rp{{ number_format($totalPemasukan, 0, ',', '.') }}
                </h3>
            </div>

            <div class="rounded-3xl p-6 text-white shadow-lg shadow-rose-200 bg-gradient-to-br from-rose-500 to-orange-500">
                <p class="text-sm opacity-90">Total Pengeluaran</p>
                <h3 class="text-2xl font-extrabold mt-2">
                    Rp{{ number_format($totalPengeluaran, 0, ',', '.') }}
                </h3>
            </div>

            <div class="rounded-3xl p-6 text-white shadow-lg shadow-indigo-200 bg-gradient-to-br from-indigo-500 to-purple-600">
                <p class="text-sm opacity-90">Saldo Bersih Periode</p>
                <h3 class="text-2xl font-extrabold mt-2">
                    Rp{{ number_format($saldoBersih, 0, ',', '.') }}
                </h3>
            </div>
        </div>

        {{-- Rekap kategori --}}
        <div class="bg-white rounded-3xl shadow-sm overflow-hidden">
            <div class="p-6 pb-4">
                <h3 class="font-bold text-slate-800">
                    Rekap Pengeluaran per Kategori
                </h3>
            </div>

            @if ($rekapKategori->isEmpty())
                <p class="text-center text-slate-400 py-8">Belum ada pengeluaran pada periode ini.</p>
            @else
                <div class="px-6 pb-6 space-y-3">
                    @foreach ($rekapKategori as $kategori)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">
                                {{ $kategori->nama_kategori ?? 'Tanpa Kategori' }}
                            </span>
                            <span class="text-sm font-bold text-rose-500">
                                Rp{{ number_format($kategori->total, 0, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Daftar transaksi --}}
        <div class="bg-white rounded-3xl shadow-sm overflow-hidden">
            <div class="p-6 pb-4">
                <h3 class="font-bold text-slate-800">
                    Rincian Transaksi
                </h3>
            </div>

            @if ($daftarTransaksi->isEmpty())
                <p class="text-center text-slate-400 py-8">Tidak ada transaksi pada periode ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                            <tr>
                                <th class="px-6 py-3 text-left">Tanggal</th>
                                <th class="px-6 py-3 text-left">Keterangan</th>
                                <th class="px-6 py-3 text-left">Kategori</th>
                                <th class="px-6 py-3 text-left">Jenis</th>
                                <th class="px-6 py-3 text-right">Nominal</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-50">
                            @foreach ($daftarTransaksi as $transaksi)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                        {{ \Carbon\Carbon::parse($transaksi->transaction_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-800">
                                        {{ $transaksi->description }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">
                                        {{ $transaksi->category?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $transaksi->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-bold
                                        {{ $transaksi->type === 'income' ? 'text-emerald-600' : 'text-rose-500' }}">
                                        {{ $transaksi->type === 'income' ? '+' : '-' }}Rp{{ number_format($transaksi->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

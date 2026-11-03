<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Transaksi
            </h2>

            <a href="{{ route('transactions.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                + Tambah Transaksi
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            @if ($jumlahMenunggu > 0)
                <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded-md">
                    Anda memiliki {{ $jumlahMenunggu }} transaksi yang menunggu persetujuan.
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="p-6">
                    @if ($transactions->isEmpty())
                        <p class="text-center text-gray-500 py-8">
                            Belum ada transaksi. Silakan tambahkan transaksi baru.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="px-4 py-3">Tanggal</th>
                                        <th class="px-4 py-3">Deskripsi</th>
                                        <th class="px-4 py-3">Kategori</th>
                                        <th class="px-4 py-3">Jenis</th>
                                        <th class="px-4 py-3">Nominal</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($transactions as $transaction)
                                        <tr class="border-b">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                {{ $transaction->transaction_date->format('d-m-Y') }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="font-medium">
                                                    {{ $transaction->description }}
                                                </div>

                                                @if ($transaction->merchant)
                                                    <div class="text-gray-500 text-xs">
                                                        {{ $transaction->merchant }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3">
                                                {{ $transaction->category?->name ?? 'Belum dikategorikan' }}
                                            </td>

                                            <td class="px-4 py-3">
                                                @if ($transaction->type === 'expense')
                                                    <span class="text-red-600">Pengeluaran</span>
                                                @else
                                                    <span class="text-green-600">Pemasukan</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 whitespace-nowrap font-medium">
                                                Rp{{ number_format((float) $transaction->amount, 0, ',', '.') }}
                                            </td>

                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if ($transaction->status === 'pending')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                        Menunggu
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                        Disetujui
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if ($transaction->status === 'pending')
                                                        <form action="{{ route('transactions.approve', $transaction) }}"
                                                              method="POST">
                                                            @csrf

                                                            <button type="submit"
                                                                    class="text-green-600 hover:underline">
                                                                Setujui
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <a href="{{ route('transactions.edit', $transaction) }}"
                                                       class="text-indigo-600 hover:underline">
                                                        Edit
                                                    </a>

                                                    <form action="{{ route('transactions.destroy', $transaction) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('{{ $transaction->status === 'pending' ? 'Yakin ingin menolak transaksi ini?' : 'Yakin ingin menghapus transaksi ini?' }}')">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit"
                                                                class="text-red-600 hover:underline">
                                                            {{ $transaction->status === 'pending' ? 'Tolak' : 'Hapus' }}
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $transactions->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

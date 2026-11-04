<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-extrabold text-slate-900">
                Transaksi
            </h2>

            <a href="{{ route('transactions.create') }}"
               class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full text-sm font-bold shadow-lg shadow-indigo-200">
                + Tambah Transaksi
            </a>
        </div>
    </x-slot>

    <div class="space-y-5">

        @if (session('success'))
            <div class="p-4 bg-emerald-100 text-emerald-800 rounded-2xl text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-100 text-rose-800 rounded-2xl text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if ($jumlahMenunggu > 0)
            <div class="p-4 bg-amber-100 text-amber-800 rounded-2xl text-sm font-medium">
                🔔 Anda memiliki {{ $jumlahMenunggu }} transaksi yang menunggu persetujuan.
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-sm overflow-hidden">
            @if ($transactions->isEmpty())
                <p class="text-center text-slate-400 py-12">
                    Belum ada transaksi. Silakan tambahkan transaksi baru.
                </p>
            @else
                <div class="divide-y divide-slate-50">
                    @foreach ($transactions as $transaction)
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-11 h-11 rounded-2xl flex items-center justify-center text-sm font-bold text-white shrink-0"
                                      style="background-color: {{ $transaction->category?->color ?? '#94a3b8' }}">
                                    {{ \Illuminate\Support\Str::of($transaction->category?->name ?? '?')->substr(0, 1)->upper() }}
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm">
                                        {{ $transaction->description }}
                                        @if ($transaction->merchant)
                                            <span class="text-slate-400 font-normal">· {{ $transaction->merchant }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ $transaction->transaction_date->format('d/m/Y') }} ·
                                        {{ $transaction->category?->name ?? 'Belum dikategorikan' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 sm:gap-5 pl-14 sm:pl-0">
                                <span class="font-bold text-sm {{ $transaction->type === 'expense' ? 'text-rose-500' : 'text-emerald-600' }}">
                                    Rp{{ number_format((float) $transaction->amount, 0, ',', '.') }}
                                </span>

                                @if ($transaction->status === 'pending')
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                                        Menunggu
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                        Disetujui
                                    </span>
                                @endif

                                <div class="flex items-center gap-3 text-xs font-bold">
                                    @if ($transaction->status === 'pending')
                                        <form action="{{ route('transactions.approve', $transaction) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-emerald-600 hover:underline">
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

                                        <button type="submit" class="text-rose-500 hover:underline">
                                            {{ $transaction->status === 'pending' ? 'Tolak' : 'Hapus' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-6 py-4 border-t border-slate-50">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>

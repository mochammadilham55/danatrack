<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold text-slate-900">
            Edit Transaksi
        </h2>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white rounded-3xl shadow-sm p-7">

            <form action="{{ route('transactions.update', $transaction) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="transaction_date" value="Tanggal Transaksi" />

                    <input type="datetime-local"
                           name="transaction_date"
                           id="transaction_date"
                           value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d\TH:i')) }}"
                           required
                           class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">

                    @error('transaction_date')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="type" value="Jenis Transaksi" />

                        <select name="type" id="type" required
                                class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">
                            <option value="expense"
                                @selected(old('type', $transaction->type) === 'expense')>
                                Pengeluaran
                            </option>
                            <option value="income"
                                @selected(old('type', $transaction->type) === 'income')>
                                Pemasukan
                            </option>
                        </select>

                        @error('type')
                            <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-input-label for="category_id" value="Kategori" />

                        <select name="category_id" id="category_id"
                                class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">
                            <option value="">Tanpa kategori</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                        data-type="{{ $category->type }}"
                                        @selected((string) old('category_id', $transaction->category_id) === (string) $category->id)>
                                    {{ $category->name }}
                                    ({{ $category->type === 'expense' ? 'Pengeluaran' : 'Pemasukan' }})
                                </option>
                            @endforeach
                        </select>

                        @error('category_id')
                            <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <x-input-label for="amount" value="Nominal (Rp)" />

                    <input type="number"
                           name="amount"
                           id="amount"
                           min="1"
                           step="1"
                           value="{{ old('amount', $transaction->amount) }}"
                           required
                           class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">

                    @error('amount')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label for="description" value="Deskripsi" />

                    <input type="text"
                           name="description"
                           id="description"
                           value="{{ old('description', $transaction->description) }}"
                           required
                           maxlength="255"
                           class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">

                    @error('description')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label for="merchant" value="Tempat / Merchant (opsional)" />

                    <input type="text"
                           name="merchant"
                           id="merchant"
                           value="{{ old('merchant', $transaction->merchant) }}"
                           maxlength="150"
                           class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">

                    @error('merchant')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <x-primary-button>Update</x-primary-button>
                    <a href="{{ route('transactions.index') }}">
                        <x-secondary-button type="button">Batal</x-secondary-button>
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script>
        const typeSelect = document.getElementById('type');
        const categorySelect = document.getElementById('category_id');

        function filterCategories() {
            const selectedType = typeSelect.value;

            Array.from(categorySelect.options).forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                option.hidden = option.dataset.type !== selectedType;
            });

            const selectedOption = categorySelect.selectedOptions[0];

            if (selectedOption && selectedOption.dataset.type &&
                selectedOption.dataset.type !== selectedType) {
                categorySelect.value = '';
            }
        }

        typeSelect.addEventListener('change', filterCategories);
        filterCategories();
    </script>
</x-app-layout>

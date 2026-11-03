<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Transaksi
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                <form action="{{ route('transactions.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="transaction_date" class="block text-sm font-medium text-gray-700">
                            Tanggal Transaksi
                        </label>

                        <input type="datetime-local"
                               name="transaction_date"
                               id="transaction_date"
                               value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                        @error('transaction_date')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="type" class="block text-sm font-medium text-gray-700">
                            Jenis Transaksi
                        </label>

                        <select name="type" id="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Pilih jenis</option>
                            <option value="expense" @selected(old('type') === 'expense')>
                                Pengeluaran
                            </option>
                            <option value="income" @selected(old('type') === 'income')>
                                Pemasukan
                            </option>
                        </select>

                        @error('type')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="category_id" class="block text-sm font-medium text-gray-700">
                            Kategori
                        </label>

                        <select name="category_id" id="category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Tanpa kategori</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                        data-type="{{ $category->type }}"
                                        @selected((string) old('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                    ({{ $category->type === 'expense' ? 'Pengeluaran' : 'Pemasukan' }})
                                </option>
                            @endforeach
                        </select>

                        @error('category_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="amount" class="block text-sm font-medium text-gray-700">
                            Nominal (Rp)
                        </label>

                        <input type="number"
                               name="amount"
                               id="amount"
                               min="1"
                               step="1"
                               value="{{ old('amount') }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                        @error('amount')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            Deskripsi
                        </label>

                        <input type="text"
                               name="description"
                               id="description"
                               value="{{ old('description') }}"
                               required
                               maxlength="255"
                               placeholder="Contoh: Makan siang"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                        @error('description')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="merchant" class="block text-sm font-medium text-gray-700">
                            Tempat / Merchant (opsional)
                        </label>

                        <input type="text"
                               name="merchant"
                               id="merchant"
                               value="{{ old('merchant') }}"
                               maxlength="150"
                               placeholder="Contoh: Warung Bu Siti"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                        @error('merchant')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Simpan
                        </button>

                        <a href="{{ route('transactions.index') }}"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Batal
                        </a>
                    </div>
                </form>

            </div>
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

                option.hidden = selectedType !== '' &&
                    option.dataset.type !== selectedType;
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

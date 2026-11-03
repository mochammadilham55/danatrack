<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Kategori
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Kategori
                        </label>

                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name') }}"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="type" class="block text-sm font-medium text-gray-700">
                            Jenis Kategori
                        </label>

                        <select name="type"
                                id="type"
                                required
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

                    <div class="mb-6">
                        <label for="color" class="block text-sm font-medium text-gray-700">
                            Warna Kategori
                        </label>

                        <input type="color"
                               name="color"
                               id="color"
                               value="{{ old('color', '#64748b') }}"
                               class="mt-1 h-10 w-20 rounded border-gray-300">

                        @error('color')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Simpan
                        </button>

                        <a href="{{ route('categories.index') }}"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                            Batal
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>

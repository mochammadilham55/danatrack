<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold text-slate-900">
            Edit Kategori
        </h2>
    </x-slot>

    <div class="max-w-xl">
        <div class="bg-white rounded-3xl shadow-sm p-7">

            <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Nama Kategori" />

                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $category->name) }}"
                           required
                           class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">

                    @error('name')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label for="type" value="Jenis Kategori" />

                    <select name="type"
                            id="type"
                            required
                            class="w-full rounded-2xl border-2 border-slate-100 focus:border-indigo-400 focus:ring-0 bg-slate-50 px-4 py-3 text-sm">
                        <option value="expense"
                            @selected(old('type', $category->type) === 'expense')>
                            Pengeluaran
                        </option>

                        <option value="income"
                            @selected(old('type', $category->type) === 'income')>
                            Pemasukan
                        </option>
                    </select>

                    @error('type')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-input-label for="color" value="Warna Kategori" />

                    <div class="flex items-center gap-3">
                        <input type="color"
                               name="color"
                               id="color"
                               value="{{ old('color', $category->color ?? '#64748b') }}"
                               class="h-11 w-16 rounded-xl border border-slate-200">
                        <span class="text-sm text-slate-400">Pilih warna penanda kategori ini</span>
                    </div>

                    @error('color')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <x-primary-button>Update</x-primary-button>
                    <a href="{{ route('categories.index') }}">
                        <x-secondary-button type="button">Batal</x-secondary-button>
                    </a>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-extrabold text-slate-900">
                Kategori
            </h2>

            <a href="{{ route('categories.create') }}"
               class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-full text-sm font-bold shadow-lg shadow-indigo-200">
                + Tambah Kategori
            </a>
        </div>
    </x-slot>

    <div class="space-y-5">

        @if (session('success'))
            <div class="p-4 bg-emerald-100 text-emerald-800 rounded-2xl text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if ($categories->isEmpty())
            <div class="bg-white rounded-3xl shadow-sm p-12 text-center text-slate-400">
                Belum ada kategori. Silakan tambahkan kategori baru.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($categories as $category)
                    <div class="bg-white rounded-3xl shadow-sm p-5 flex items-center gap-4">
                        <span class="w-12 h-12 rounded-2xl flex items-center justify-center text-white text-lg font-bold shrink-0"
                              style="background-color: {{ $category->color }}">
                            {{ \Illuminate\Support\Str::of($category->name)->substr(0, 1)->upper() }}
                        </span>

                        <div class="flex-1 overflow-hidden">
                            <p class="font-bold text-slate-800 truncate">{{ $category->name }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $category->type === 'expense' ? 'Pengeluaran' : 'Pemasukan' }}
                                · {{ $category->color }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 text-xs font-bold shrink-0">
                            <a href="{{ route('categories.edit', $category) }}" class="text-indigo-600 hover:underline">
                                Edit
                            </a>

                            <form action="{{ route('categories.destroy', $category) }}"
                                  method="POST"
                                  onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="text-rose-500 hover:underline">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-app-layout>

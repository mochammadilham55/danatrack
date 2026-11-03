<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Kategori
            </h2>

            <a href="{{ route('categories.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                + Tambah Kategori
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

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="p-6">

                    @if ($categories->isEmpty())
                        <p class="text-gray-500 text-center py-8">
                            Belum ada kategori. Silakan tambahkan kategori baru.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="px-4 py-3">No</th>
                                        <th class="px-4 py-3">Nama Kategori</th>
                                        <th class="px-4 py-3">Jenis</th>
                                        <th class="px-4 py-3">Warna</th>
                                        <th class="px-4 py-3">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr class="border-b">
                                            <td class="px-4 py-3">
                                                {{ $loop->iteration }}
                                            </td>

                                            <td class="px-4 py-3 font-medium">
                                                {{ $category->name }}
                                            </td>

                                            <td class="px-4 py-3">
                                                {{ $category->type === 'expense'
                                                    ? 'Pengeluaran'
                                                    : 'Pemasukan' }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <span class="inline-block w-5 h-5 rounded-full border"
                                                      style="background-color: {{ $category->color }}">
                                                </span>
                                                {{ $category->color }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('categories.edit', $category) }}"
                                                       class="text-indigo-600 hover:underline">
                                                        Edit
                                                    </a>

                                                    <form action="{{ route('categories.destroy', $category) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit"
                                                                class="text-red-600 hover:underline">
                                                            Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories')
                    ->where('user_id', auth()->id())
                    ->where('type', $request->type),
            ],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Category::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'color' => $validated['color'] ?? '#64748b',
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        $this->authorizeCategory($category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeCategory($category);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories')
                    ->where('user_id', auth()->id())
                    ->where('type', $request->type)
                    ->ignore($category->id),
            ],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'color' => $validated['color'] ?? '#64748b',
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        $this->authorizeCategory($category);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    private function authorizeCategory(Category $category): void
    {
        abort_unless($category->user_id === auth()->id(), 403);
    }
}

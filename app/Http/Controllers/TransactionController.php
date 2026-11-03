<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->with('category')
            ->orderByDesc('transaction_date')
            ->paginate(10);

        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('transactions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'category_id' => ['nullable', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'merchant' => ['nullable', 'string', 'max:150'],
        ]);

        $this->validateCategory(
            $validated['category_id'] ?? null,
            $validated['type']
        );

        Transaction::create([
            'user_id' => auth()->id(),
            'category_id' => $validated['category_id'] ?? null,
            'transaction_date' => $validated['transaction_date'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'merchant' => $validated['merchant'] ?? null,
            'source' => 'website',
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function edit(Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);

        $categories = Category::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('transactions.edit', compact('transaction', 'categories'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['expense', 'income'])],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999999'],
            'category_id' => ['nullable', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'merchant' => ['nullable', 'string', 'max:150'],
        ]);

        $this->validateCategory(
            $validated['category_id'] ?? null,
            $validated['type']
        );

        $transaction->update([
            'category_id' => $validated['category_id'] ?? null,
            'transaction_date' => $validated['transaction_date'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'merchant' => $validated['merchant'] ?? null,
        ]);

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorizeTransaction($transaction);

        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }

    private function authorizeTransaction(Transaction $transaction): void
    {
        abort_unless(
            $transaction->user_id === auth()->id(),
            403
        );
    }

    private function validateCategory(?int $categoryId, string $type): void
    {
        if ($categoryId === null) {
            return;
        }

        $category = Category::where('user_id', auth()->id())
            ->where('id', $categoryId)
            ->where('type', $type)
            ->first();

        if (!$category) {
            abort(
                response()->json([
                    'message' => 'Kategori tidak valid atau tidak sesuai jenis transaksi.',
                ], 422)
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $tanggalAwal = $request->input('dari')
            ? Carbon::parse($request->input('dari'))->startOfDay()
            : now()->startOfMonth();

        $tanggalAkhir = $request->input('sampai')
            ? Carbon::parse($request->input('sampai'))->endOfDay()
            : now()->endOfMonth();

        $transaksi = Transaction::where('user_id', auth()->id())
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$tanggalAwal, $tanggalAkhir]);

        $totalPemasukan = (clone $transaksi)
            ->where('type', 'income')
            ->sum('amount');

        $totalPengeluaran = (clone $transaksi)
            ->where('type', 'expense')
            ->sum('amount');

        $saldoBersih = $totalPemasukan - $totalPengeluaran;

        $rekapKategori = Transaction::query()
            ->where('transactions.user_id', $userId)
            ->where('transactions.status', 'approved')
            ->whereBetween('transactions.transaction_date', [
                $tanggalAwal,
                $tanggalAkhir
            ])
            ->where('transactions.type', 'expense')
            ->leftJoin(
                'categories',
                'transactions.category_id',
                '=',
                'categories.id'
            )
            ->select(
                'categories.name as nama_kategori',
                DB::raw('SUM(transactions.amount) as total')
            )
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        $daftarTransaksi = (clone $transaksi)
            ->with('category')
            ->orderBy('transaction_date')
            ->get();

        return view('reports.index', compact(
            'tanggalAwal',
            'tanggalAkhir',
            'totalPemasukan',
            'totalPengeluaran',
            'saldoBersih',
            'rekapKategori',
            'daftarTransaksi'
        ));
    }
}

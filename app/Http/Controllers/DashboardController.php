<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $periode = $request->input('periode', 'bulan_ini');
        $tanggalAwal = null;
        $tanggalAkhir = null;

        switch ($periode) {
            case 'hari_ini':
                $tanggalAwal = now()->startOfDay();
                $tanggalAkhir = now()->endOfDay();
                break;

            case 'tahun_ini':
                $tanggalAwal = now()->startOfYear();
                $tanggalAkhir = now()->endOfYear();
                break;

            case 'rentang':
                $request->validate([
                    'dari' => ['required', 'date'],
                    'sampai' => ['required', 'date', 'after_or_equal:dari'],
                ]);

                $tanggalAwal = Carbon::parse($request->input('dari'))->startOfDay();
                $tanggalAkhir = Carbon::parse($request->input('sampai'))->endOfDay();
                break;

            case 'bulan_ini':
            default:
                $periode = 'bulan_ini';
                $tanggalAwal = now()->startOfMonth();
                $tanggalAkhir = now()->endOfMonth();
                break;
        }

        $transaksiPeriode = Transaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$tanggalAwal, $tanggalAkhir]);

        $totalPemasukan = (clone $transaksiPeriode)
            ->where('type', 'income')
            ->sum('amount');

        $totalPengeluaran = (clone $transaksiPeriode)
            ->where('type', 'expense')
            ->sum('amount');

        $saldo = $totalPemasukan - $totalPengeluaran;

        $transaksiTerbaru = (clone $transaksiPeriode)
            ->with('category')
            ->latest('transaction_date')
            ->take(5)
            ->get();

        $kategoriPengeluaranTerbesar = (clone $transaksiPeriode)
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as total')
            ->with('category')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->first();

        // Grafik 6 bulan terakhir
        $awalPeriode = now()->startOfMonth()->subMonths(5);
        $akhirPeriode = now()->endOfMonth();

        // Diambil per transaksi (bukan agregat SQL) agar query ini portable
        // lintas database (MySQL di produksi, SQLite di test suite).
        $dataBulanan = Transaction::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$awalPeriode, $akhirPeriode])
            ->get(['transaction_date', 'type', 'amount']);

        $labelsGrafik = [];
        $dataPemasukan = [];
        $dataPengeluaran = [];

        for ($i = 0; $i < 6; $i++) {
            $bulan = $awalPeriode->copy()->addMonths($i);

            $labelsGrafik[] = $bulan->translatedFormat('M Y');

            $transaksiBulanIni = $dataBulanan->filter(
                fn ($transaksi) => $transaksi->transaction_date->year === $bulan->year
                    && $transaksi->transaction_date->month === $bulan->month
            );

            $dataPemasukan[] = (float) $transaksiBulanIni
                ->where('type', 'income')
                ->sum('amount');

            $dataPengeluaran[] = (float) $transaksiBulanIni
                ->where('type', 'expense')
                ->sum('amount');
        }

        return view('dashboard', compact(
            'periode',
            'tanggalAwal',
            'tanggalAkhir',
            'totalPemasukan',
            'totalPengeluaran',
            'saldo',
            'transaksiTerbaru',
            'kategoriPengeluaranTerbesar',
            'labelsGrafik',
            'dataPemasukan',
            'dataPengeluaran'
        ));
    }
}

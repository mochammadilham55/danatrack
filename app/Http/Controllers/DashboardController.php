<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
   public function index(\Illuminate\Http\Request $request)
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

                    $tanggalAwal = \Carbon\Carbon::parse($request->input('dari'))->startOfDay();
                    $tanggalAkhir = \Carbon\Carbon::parse($request->input('sampai'))->endOfDay();
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

            $dataBulanan = Transaction::where('user_id', $userId)
                ->where('status', 'approved')
                ->whereBetween('transaction_date', [$awalPeriode, $akhirPeriode])
                ->selectRaw('YEAR(transaction_date) as tahun, MONTH(transaction_date) as bulan, type, SUM(amount) as total')
                ->groupBy('tahun', 'bulan', 'type')
                ->get();

            $labelsGrafik = [];
            $dataPemasukan = [];
            $dataPengeluaran = [];

            for ($i = 0; $i < 6; $i++) {
                $bulan = $awalPeriode->copy()->addMonths($i);

                $labelsGrafik[] = $bulan->translatedFormat('M Y');

                $dataPemasukan[] = (float) $dataBulanan
                    ->where('tahun', $bulan->year)
                    ->where('bulan', $bulan->month)
                    ->where('type', 'income')
                    ->sum('total');

                $dataPengeluaran[] = (float) $dataBulanan
                    ->where('tahun', $bulan->year)
                    ->where('bulan', $bulan->month)
                    ->where('type', 'expense')
                    ->sum('total');
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

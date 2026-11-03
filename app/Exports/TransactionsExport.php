<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Ekspor Excel untuk halaman Laporan. Kolomnya sengaja mengikuti tabel
 * "Rincian Transaksi" di reports/index.blade.php, plus beberapa kolom
 * tambahan (merchant, sumber, nomor referensi) yang berguna di file
 * tapi tidak perlu ditampilkan di halaman web.
 */
class TransactionsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private Collection $transaksi) {}

    public function collection(): Collection
    {
        return $this->transaksi;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Jenis',
            'Nominal',
            'Kategori',
            'Keterangan',
            'Merchant',
            'Sumber',
            'No. Referensi',
        ];
    }

    /**
     * @param  Transaction  $transaksi
     */
    public function map(mixed $transaksi): array
    {
        return [
            $transaksi->transaction_date->format('d/m/Y H:i'),
            $transaksi->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            (float) $transaksi->amount,
            $transaksi->category?->name ?? 'Tanpa Kategori',
            $transaksi->description,
            $transaksi->merchant,
            match ($transaksi->source) {
                'website' => 'Website',
                'email' => 'Email',
                'telegram' => 'Telegram',
                default => $transaksi->source,
            },
            $transaksi->reference_number,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export(): void
    {
        $response = $this->get(route('reports.exportExcel'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_download_excel_export_for_default_period(): void
    {
        Excel::fake();

        $user = User::factory()->create();
        $category = Category::factory()->for($user)->expense()->create(['name' => 'Makanan']);

        Transaction::factory()->for($user)->expense()->create([
            'category_id' => $category->id,
            'transaction_date' => now(),
            'description' => 'Makan siang',
            'amount' => 25000,
        ]);

        $response = $this->actingAs($user)->get(route('reports.exportExcel'));

        $response->assertOk();
        Excel::assertDownloaded(
            'laporan-danatrack_'.now()->startOfMonth()->format('Y-m-d').'_'.now()->endOfMonth()->format('Y-m-d').'.xlsx'
        );
    }

    public function test_export_respects_the_same_date_filter_as_the_report_page(): void
    {
        Excel::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.exportExcel', [
            'dari' => '2026-01-01',
            'sampai' => '2026-01-31',
        ]));

        $response->assertOk();
        Excel::assertDownloaded('laporan-danatrack_2026-01-01_2026-01-31.xlsx');
    }

    public function test_export_only_includes_the_authenticated_users_approved_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Transaction::factory()->for($user)->create([
            'description' => 'Punya saya',
            'status' => 'approved',
            'transaction_date' => now(),
        ]);
        Transaction::factory()->for($user)->pending()->create([
            'description' => 'Masih pending',
            'transaction_date' => now(),
        ]);
        Transaction::factory()->for($otherUser)->create([
            'description' => 'Punya orang lain',
            'status' => 'approved',
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.exportExcel'));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        // Baca ulang isi file xlsx yang benar-benar di-generate (tanpa
        // Excel::fake()) langsung lewat PhpSpreadsheet, untuk memastikan
        // isi datanya benar -- bukan cuma nama file atau status HTTP-nya.
        $path = storage_path('app/pengujian-export.xlsx');
        file_put_contents($path, $response->streamedContent());

        $baris = IOFactory::load($path)
            ->getActiveSheet()
            ->toArray();

        unlink($path);

        // Kolom ke-4 (index 0-based) = Keterangan, sesuai urutan di
        // TransactionsExport::map().
        $deskripsi = collect($baris)->pluck(4);

        $this->assertTrue($deskripsi->contains('Punya saya'));
        $this->assertFalse($deskripsi->contains('Masih pending'));
        $this->assertFalse($deskripsi->contains('Punya orang lain'));
    }
}

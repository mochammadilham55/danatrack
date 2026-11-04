<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Kategori umum untuk pencatatan keuangan pribadi. Dibuatkan untuk
 * setiap user yang sudah ada di database.
 *
 * Aman dijalankan berulang kali: memakai firstOrCreate berdasarkan
 * (user_id, name, type) -- sama persis dengan unique constraint di
 * migration categories -- jadi tidak akan membuat duplikat, dan tidak
 * menimpa warna kategori yang mungkin sudah diubah manual oleh user.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $kategoriPengeluaran = [
            'Makanan & Minuman' => '#f59e0b',
            'Transportasi' => '#3b82f6',
            'Belanja' => '#8b5cf6',
            'Hiburan' => '#ec4899',
            'Kesehatan' => '#10b981',
            'Pendidikan' => '#06b6d4',
            'Tagihan & Utilitas' => '#ef4444',
            'Rumah Tangga' => '#84cc16',
            'Donasi & Sedekah' => '#14b8a6',
            'Lainnya' => '#64748b',
        ];

        $kategoriPemasukan = [
            'Gaji' => '#22c55e',
            'Bonus' => '#eab308',
            'Investasi' => '#6366f1',
            'Hadiah' => '#f97316',
            'Lainnya' => '#94a3b8',
        ];

        User::all()->each(function (User $user) use ($kategoriPengeluaran, $kategoriPemasukan) {
            foreach ($kategoriPengeluaran as $nama => $warna) {
                Category::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $nama, 'type' => 'expense'],
                    ['color' => $warna]
                );
            }

            foreach ($kategoriPemasukan as $nama => $warna) {
                Category::firstOrCreate(
                    ['user_id' => $user->id, 'name' => $nama, 'type' => 'income'],
                    ['color' => $warna]
                );
            }
        });
    }
}

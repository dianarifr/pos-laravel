<?php

namespace Database\Seeders;

use App\Models\JenisPengeluaran;
use Illuminate\Database\Seeder;

class JenisPengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = [
            [
                'nama' => 'Operasional & Utilitas',
                'deskripsi' => 'Listrik, air PDAM, WiFi toko, dan iuran kebersihan.',
            ],
            [
                'nama' => 'Gaji & Uang Makan',
                'deskripsi' => 'Gaji karyawan, bonus harian, dan uang makan kasir.',
            ],
            [
                'nama' => 'Perlengkapan & ATK',
                'deskripsi' => 'Kertas roll thermal kasir, kantong kresek, lakban, dan nota manual.',
            ],
            [
                'nama' => 'Maintenance & Kebersihan',
                'deskripsi' => 'Servis AC toko, perbaikan printer kasir, sabun pel, dan cairan disinfektan.',
            ],
            [
                'nama' => 'Pemasaran & Promosi',
                'deskripsi' => 'Iklan media sosial, cetak banner spanduk promo, dan brosur.',
            ],
            [
                'nama' => 'Biaya Tak Terduga',
                'deskripsi' => 'Pengeluaran darurat atau kebutuhan mendadak lainnya.',
            ],
        ];

        foreach ($kategoriList as $kategori) {
            JenisPengeluaran::updateOrCreate(
                ['nama' => $kategori['nama']],
                ['deskripsi' => $kategori['deskripsi']]
            );
        }
    }
}
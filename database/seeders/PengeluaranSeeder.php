<?php

namespace Database\Seeders;

use App\Models\JenisPengeluaran;
use App\Models\Pengeluaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $userId = $user?->id ?? 1;

        $operasional = JenisPengeluaran::where('nama', 'Operasional & Utilitas')->first();
        $gaji = JenisPengeluaran::where('nama', 'Gaji & Uang Makan')->first();
        $atk = JenisPengeluaran::where('nama', 'Perlengkapan & ATK')->first();
        $maintenance = JenisPengeluaran::where('nama', 'Maintenance & Kebersihan')->first();
        $promosi = JenisPengeluaran::where('nama', 'Pemasaran & Promosi')->first();
        $lainnya = JenisPengeluaran::where('nama', 'Biaya Tak Terduga')->first();

        $daftarPengeluaran = [
            // Hari ini
            [
                'jenis_pengeluaran_id' => $atk?->id,
                'nama_pengeluaran' => 'Beli Roll Kertas Thermal Kasir (5 Roll)',
                'nominal' => 45000,
                'tanggal' => Carbon::today()->toDateString(),
                'catatan' => 'Beli di toko ATK seberang jalan.',
            ],
            [
                'jenis_pengeluaran_id' => $gaji?->id,
                'nama_pengeluaran' => 'Uang Makan Kasir Siang & Sore',
                'nominal' => 50000,
                'tanggal' => Carbon::today()->toDateString(),
                'catatan' => '2 porsi shift siang.',
            ],

            // Kemarin (H-1)
            [
                'jenis_pengeluaran_id' => $atk?->id,
                'nama_pengeluaran' => 'Kantong Kresek Sedang & Besar (3 Pak)',
                'nominal' => 65000,
                'tanggal' => Carbon::yesterday()->toDateString(),
                'catatan' => 'Stok kemasan penjualan barang.',
            ],

            // H-2
            [
                'jenis_pengeluaran_id' => $maintenance?->id,
                'nama_pengeluaran' => 'Isi Ulang Sabun Cuci Tangan & Pembersih Lantai',
                'nominal' => 38000,
                'tanggal' => Carbon::now()->subDays(2)->toDateString(),
                'catatan' => 'Kebutuhan kebersihan toko.',
            ],

            // H-3
            [
                'jenis_pengeluaran_id' => $promosi?->id,
                'nama_pengeluaran' => 'Iklan Instagram Story Promo Akhir Pekan',
                'nominal' => 100000,
                'tanggal' => Carbon::now()->subDays(3)->toDateString(),
                'catatan' => 'Jangkauan target wilayah sekitar toko.',
            ],

            // H-4
            [
                'jenis_pengeluaran_id' => $lainnya?->id,
                'nama_pengeluaran' => 'Galon Air Mineral Toko (2 Galon)',
                'nominal' => 40000,
                'tanggal' => Carbon::now()->subDays(4)->toDateString(),
                'catatan' => 'Konsumsi toko.',
            ],

            // H-5
            [
                'jenis_pengeluaran_id' => $operasional?->id,
                'nama_pengeluaran' => 'Token Listrik Toko',
                'nominal' => 200000,
                'tanggal' => Carbon::now()->subDays(5)->toDateString(),
                'catatan' => 'Beli token 200rb via m-Banking.',
            ],

            // H-6
            [
                'jenis_pengeluaran_id' => $maintenance?->id,
                'nama_pengeluaran' => 'Servis Ringan & Cuci AC Toko',
                'nominal' => 85000,
                'tanggal' => Carbon::now()->subDays(6)->toDateString(),
                'catatan' => 'Teknisi langganan.',
            ],

            // H-7
            [
                'jenis_pengeluaran_id' => $operasional?->id,
                'nama_pengeluaran' => 'Tagihan WiFi Indihome Toko',
                'nominal' => 350000,
                'tanggal' => Carbon::now()->subDays(7)->toDateString(),
                'catatan' => 'Tagihan internet bulanan kasir & POS.',
            ],
        ];

        foreach ($daftarPengeluaran as $item) {
            Pengeluaran::create([
                'jenis_pengeluaran_id' => $item['jenis_pengeluaran_id'],
                'user_id' => $userId,
                'nama_pengeluaran' => $item['nama_pengeluaran'],
                'nominal' => $item['nominal'],
                'tanggal' => $item['tanggal'],
                'catatan' => $item['catatan'],
                'created_at' => Carbon::parse($item['tanggal'])->setTime(rand(9, 17), rand(10, 59)),
                'updated_at' => Carbon::parse($item['tanggal'])->setTime(rand(9, 17), rand(10, 59)),
            ]);
        }
    }
}
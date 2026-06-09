<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'nama_toko'   => 'UD. SINTA RAHAYU',
            'alamat_toko' => 'JL. BY PASS SERING SUMBAWA BESAR',
            'no_hp_toko'  => '081259169467',
            'email_toko'  => 'toko@example.com',
            'pesan_faktur' => 'BARANG SUDAH DIBELI TIDAK DI KEMBALIKAN / DI TUKAR',
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

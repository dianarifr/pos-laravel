<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'nama_toko'   => 'POS Retail Store',
            'alamat_toko' => 'Jl. Raya No. 1, Jakarta',
            'no_hp_toko'  => '021-12345678',
            'email_toko'  => 'toko@example.com',
            'pesan_faktur' => 'Barang yang sudah dibeli tidak dapat ditukar',
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

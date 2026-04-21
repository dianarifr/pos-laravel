<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'nama'   => 'Budi Santoso',
                'no_hp'  => '081234567890',
                'alamat' => 'Jl. Melati No. 5, Jakarta Selatan',
            ],
            [
                'nama'   => 'Siti Rahayu',
                'no_hp'  => '082345678901',
                'alamat' => 'Jl. Mawar No. 12, Bandung',
            ],
            [
                'nama'   => 'Agus Permadi',
                'no_hp'  => '083456789012',
                'alamat' => 'Jl. Kenanga No. 3, Surabaya',
            ],
            [
                'nama'   => 'Dewi Lestari',
                'no_hp'  => '084567890123',
                'alamat' => 'Jl. Anggrek No. 8, Yogyakarta',
            ],
            [
                'nama'   => 'Hendra Wijaya',
                'no_hp'  => '085678901234',
                'alamat' => 'Jl. Dahlia No. 20, Semarang',
            ],
            [
                'nama'   => 'Umum',
                'no_hp'  => null,
                'alamat' => null,
            ],
        ];

        foreach ($customers as $data) {
            Customer::firstOrCreate(['nama' => $data['nama']], $data);
        }
    }
}

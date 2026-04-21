<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'nama'   => 'CV. Maju Bersama',
                'no_hp'  => '021-88887777',
                'alamat' => 'Jl. Industri No. 1, Kawasan Pulogadung, Jakarta Timur',
            ],
            [
                'nama'   => 'PT. Sumber Makmur',
                'no_hp'  => '022-55556666',
                'alamat' => 'Jl. Raya Bandung No. 45, Bandung',
            ],
            [
                'nama'   => 'UD. Berkah Jaya',
                'no_hp'  => '031-33334444',
                'alamat' => 'Jl. Pahlawan No. 17, Surabaya',
            ],
            [
                'nama'   => 'PT. Nusantara Distribusi',
                'no_hp'  => '024-22223333',
                'alamat' => 'Jl. Pemuda No. 88, Semarang',
            ],
            [
                'nama'   => 'CV. Agro Sejahtera',
                'no_hp'  => '0274-11112222',
                'alamat' => 'Jl. Kaliurang Km. 10, Yogyakarta',
            ],
        ];

        foreach ($suppliers as $data) {
            Supplier::firstOrCreate(['nama' => $data['nama']], $data);
        }
    }
}

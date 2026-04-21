<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class KategoriUnitSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = ['Makanan', 'Minuman', 'Elektronik', 'Pakaian', 'Peralatan Rumah'];
        foreach ($kategoris as $nama) {
            Kategori::firstOrCreate(['nama' => $nama]);
        }

        $units = ['Pcs', 'Box', 'Kg', 'Liter', 'Lusin', 'Pack', 'Karton'];
        foreach ($units as $nama) {
            Unit::firstOrCreate(['nama' => $nama]);
        }
    }
}

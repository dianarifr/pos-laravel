<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        $makanan    = Kategori::firstWhere('nama', 'Makanan');
        $minuman    = Kategori::firstWhere('nama', 'Minuman');
        $elektronik = Kategori::firstWhere('nama', 'Elektronik');
        $pakaian    = Kategori::firstWhere('nama', 'Pakaian');
        $peralatan  = Kategori::firstWhere('nama', 'Peralatan Rumah');

        $pcs    = Unit::firstWhere('nama', 'Pcs');
        $box    = Unit::firstWhere('nama', 'Box');
        $kg     = Unit::firstWhere('nama', 'Kg');
        $liter  = Unit::firstWhere('nama', 'Liter');
        $pack   = Unit::firstWhere('nama', 'Pack');

        $barangs = [
            [
                'kategori_id'  => $makanan?->id,
                'unit_id'      => $kg?->id,
                'sku'          => 'MKN-001',
                'nama_barang'  => 'Beras Premium',
                'harga_beli'   => 12000,
                'harga_jual'   => 14000,
                'stok_minimal' => 10,
            ],
            [
                'kategori_id'  => $makanan?->id,
                'unit_id'      => $pcs?->id,
                'sku'          => 'MKN-002',
                'nama_barang'  => 'Mie Instan Goreng',
                'harga_beli'   => 2500,
                'harga_jual'   => 3500,
                'stok_minimal' => 50,
            ],
            [
                'kategori_id'  => $makanan?->id,
                'unit_id'      => $pack?->id,
                'sku'          => 'MKN-003',
                'nama_barang'  => 'Gula Pasir',
                'harga_beli'   => 13000,
                'harga_jual'   => 15000,
                'stok_minimal' => 20,
            ],
            [
                'kategori_id'  => $minuman?->id,
                'unit_id'      => $liter?->id,
                'sku'          => 'MNM-001',
                'nama_barang'  => 'Air Mineral Galon',
                'harga_beli'   => 18000,
                'harga_jual'   => 22000,
                'stok_minimal' => 5,
            ],
            [
                'kategori_id'  => $minuman?->id,
                'unit_id'      => $box?->id,
                'sku'          => 'MNM-002',
                'nama_barang'  => 'Teh Kotak 200ml',
                'harga_beli'   => 28000,
                'harga_jual'   => 35000,
                'stok_minimal' => 10,
            ],
            [
                'kategori_id'  => $minuman?->id,
                'unit_id'      => $pcs?->id,
                'sku'          => 'MNM-003',
                'nama_barang'  => 'Kopi Sachet',
                'harga_beli'   => 1500,
                'harga_jual'   => 2500,
                'stok_minimal' => 100,
            ],
            [
                'kategori_id'  => $elektronik?->id,
                'unit_id'      => $pcs?->id,
                'sku'          => 'ELK-001',
                'nama_barang'  => 'Lampu LED 10W',
                'harga_beli'   => 15000,
                'harga_jual'   => 25000,
                'stok_minimal' => 15,
            ],
            [
                'kategori_id'  => $elektronik?->id,
                'unit_id'      => $pcs?->id,
                'sku'          => 'ELK-002',
                'nama_barang'  => 'Baterai AA',
                'harga_beli'   => 8000,
                'harga_jual'   => 12000,
                'stok_minimal' => 30,
            ],
            [
                'kategori_id'  => $pakaian?->id,
                'unit_id'      => $pcs?->id,
                'sku'          => 'PKN-001',
                'nama_barang'  => 'Kaos Polos Putih',
                'harga_beli'   => 35000,
                'harga_jual'   => 55000,
                'stok_minimal' => 20,
            ],
            [
                'kategori_id'  => $peralatan?->id,
                'unit_id'      => $pcs?->id,
                'sku'          => 'PRL-001',
                'nama_barang'  => 'Sapu Ijuk',
                'harga_beli'   => 20000,
                'harga_jual'   => 30000,
                'stok_minimal' => 10,
            ],
        ];

        foreach ($barangs as $data) {
            Barang::firstOrCreate(['sku' => $data['sku']], $data);
        }
    }
}

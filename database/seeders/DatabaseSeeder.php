<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleUserSeeder::class,
            GudangUserSeeder::class,
            KategoriUnitSeeder::class,
            SettingSeeder::class,
            BarangSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            PembelianSeeder::class,
            PenjualanSeeder::class,
            StokSeeder::class,
        ]);
    }
}

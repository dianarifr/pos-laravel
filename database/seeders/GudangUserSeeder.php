<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class GudangUserSeeder extends Seeder
{
    public function run(): void
    {
        $gudangRole = Role::firstOrCreate(['name' => 'Gudang']);

        $gudang = User::firstOrCreate(
            ['email' => 'gudang@pos.com'],
            [
                'name'     => 'Petugas Gudang',
                'password' => Hash::make('password'),
                'address'  => 'Gudang Utama',
            ]
        );

        $gudang->syncRoles([$gudangRole]);
    }
}

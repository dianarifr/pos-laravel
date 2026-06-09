<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $kasirRole = Role::firstOrCreate(['name' => 'Kasir']);
        $gudangRole = Role::firstOrCreate(['name' => 'Gudang']);

        $admin = User::firstOrCreate(
            ['email' => 'dian@mail.com'],
            [
                'name'     => 'Dian',
                'password' => Hash::make('ubahsaya'),
                'address'  => 'Jl. Admin No. 1',
            ]
        );
        $admin->assignRole($adminRole);

        $kasir = User::updateOrCreate(
            ['email' => 'kasir@mail.com'],
            [
                'name'     => 'Kasir POS',
                'password' => Hash::make('ubahsaya'),
                'address'  => 'Counter Kasir',
            ]
        );
        $kasir->syncRoles([$kasirRole]);

        $gudang = User::updateOrCreate(
            ['email' => 'gudang@mail.com'],
            [
                'name'     => 'Gudang POS',
                'password' => Hash::make('ubahsaya'),
                'address'  => 'Area Gudang',
            ]
        );
        $gudang->syncRoles([$gudangRole]);
    }
}

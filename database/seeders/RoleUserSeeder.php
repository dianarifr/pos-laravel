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
        $ownerRole = Role::firstOrCreate(['name' => 'Owner']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);


        $owner = User::updateOrCreate(
            ['email' => 'owner'],
            [
                'name'     => 'Owner POS',
                'password' => Hash::make('ubahsaya'),
                'address'  => 'JL. BY PASS SERING SUMBAWA BESAR',
            ]
        );
        $owner->syncRoles([$ownerRole]);

        $admin = User::firstOrCreate(
            ['email' => 'admin'],
            [
                'name'     => 'Admin POS',
                'password' => Hash::make('ubahsaya'),
                'address'  => 'JL. BY PASS SERING SUMBAWA BESAR',
            ]
        );
        $admin->syncRoles($adminRole);
    }
}

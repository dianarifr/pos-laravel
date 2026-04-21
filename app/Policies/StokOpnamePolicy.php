<?php

namespace App\Policies;

use App\Models\StokOpname;
use App\Models\User;

class StokOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Gudang']);
    }

    public function view(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasAnyRole(['Admin', 'Gudang']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Gudang']);
    }

    public function update(User $user, StokOpname $stokOpname): bool
    {
        // Tidak bisa edit opname yang sudah divalidasi
        if ($stokOpname->isValidated()) {
            return false;
        }

        return $user->hasAnyRole(['Admin', 'Gudang']);
    }

    public function delete(User $user, StokOpname $stokOpname): bool
    {
        // Hanya Admin yang bisa hapus, dan hanya jika masih draft
        return $user->hasRole('Admin') && $stokOpname->isDraft();
    }

    public function validate(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasRole('Admin') && $stokOpname->isDraft();
    }

    public function rollback(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasRole('Admin') && $stokOpname->isValidated();
    }
}

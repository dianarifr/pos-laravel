<?php

namespace App\Policies;

use App\Models\StokOpname;
use App\Models\User;

class StokOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, StokOpname $stokOpname): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasAnyRole(['Owner'])  && !$stokOpname->isValidated();
    }

    public function delete(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasAnyRole(['Owner']) && $stokOpname->isDraft();
    }

    public function validate(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasAnyRole(['Owner']) && $stokOpname->isDraft();
    }

    public function rollback(User $user, StokOpname $stokOpname): bool
    {
        return $user->hasAnyRole(['Owner']) && $stokOpname->isValidated();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Models\Contracts\HasAvatar;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_picture
            ? Storage::url($this->profile_picture)
            : null;
    }

    const ROLE_ADMIN = 'Admin';
    const ROLE_KASIR = 'Kasir';
    const ROLE_GUDANG = 'Gudang';

    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_KASIR, self::ROLE_GUDANG]);
    }
}

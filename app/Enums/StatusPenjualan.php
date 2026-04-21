<?php

namespace App\Enums;

enum StatusPenjualan: string
{
    case Lunas     = 'lunas';
    case BelumLunas = 'belum_lunas';

    public function label(): string
    {
        return match ($this) {
            self::Lunas     => 'Lunas',
            self::BelumLunas => 'Belum Lunas',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Lunas     => 'success',
            self::BelumLunas => 'danger',
        };
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\StatsPenjualanHariIni;
use App\Filament\Widgets\TrendOmsetChart;
use App\Filament\Widgets\ProdukTerlarisHariIni;
use App\Filament\Widgets\ProdukStokKritis;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        // Jika yang login Owner, tampilkan semua widget rahasia dapur
        if (auth()->user()?->hasRole('Owner')) {
            return [
                StatsPenjualanHariIni::class,
                TrendOmsetChart::class,
                ProdukTerlarisHariIni::class,
                ProdukStokKritis::class,
            ];
        }

        return [];
    }
}
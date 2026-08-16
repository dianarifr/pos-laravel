<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use App\Filament\Widgets\StatsPenjualanHariIni;
use App\Filament\Widgets\TrendOmsetChart;
use App\Filament\Widgets\ProdukTerlarisHariIni;
use App\Filament\Widgets\ProdukStokKritis;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    // ⚡ KUNCI: Gunakan method ini untuk mematikan session filter bawaan
    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Mulai Tanggal')
                            ->default(now()->subDays(6)->toDateString())
                            ->native(false)
                            ->maxDate(now()),

                        DatePicker::make('endDate')
                            ->label('Sampai Tanggal')
                            ->default(now()->toDateString())
                            ->native(false)
                            ->maxDate(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public function getWidgets(): array
    {
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
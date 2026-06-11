<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TrendOmsetChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Omset 7 Hari Terakhir';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '10s';

    protected static bool $isLazy = true;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $dataOmset = [];
        $labelTanggal = [];

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::now()->subDays($i);

            $omsetHariItu = Penjualan::whereDate('created_at', $tanggal)->sum('total_harga');

            $dataOmset[] = $omsetHariItu;
            $labelTanggal[] = $tanggal->translatedFormat('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omset Toko (Rp)',
                    'data' => $dataOmset,
                    'fill' => 'start',
                    'tension' => 0.3,
                    'borderColor' => '#fbbf24',
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
                ],
            ],
            'labels' => $labelTanggal,
        ];
    }
}
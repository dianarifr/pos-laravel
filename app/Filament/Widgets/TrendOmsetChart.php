<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters; // ⚡ 1. Import Trait ini

class TrendOmsetChart extends ChartWidget
{
    use InteractsWithPageFilters; // ⚡ 2. Pasang Trait ini

    protected static ?string $heading = 'Tren Omset & Profit Bersih';

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
        // ⚡ 3. Ambil nilai tanggal dari filter Dashboard di atas
        $startDate = !empty($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $endDate = !empty($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : Carbon::now()->endOfDay();

        // Buat loop tanggal dinamis sesuai rentang yang dipilih
        $period = CarbonPeriod::create($startDate, $endDate);

        $dataOmset = [];
        $dataProfit = [];
        $labelTanggal = [];

        foreach ($period as $date) {
            // Hitung Omset
            $omsetHariItu = Penjualan::whereDate('created_at', $date)->sum('total_harga');

            // Hitung Estimasi Profit Bersih
            $detailHariItu = PenjualanDetail::whereHas('penjualan', function ($query) use ($date) {
                $query->whereDate('created_at', $date);
            })->get();

            $profitHariItu = $detailHariItu->sum(function ($detail) {
                return ($detail->harga_jual - $detail->harga_beli) * $detail->qty;
            });

            $dataOmset[] = $omsetHariItu;
            $dataProfit[] = $profitHariItu;
            $labelTanggal[] = $date->translatedFormat('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omset (Rp)',
                    'data' => $dataOmset,
                    'fill' => 'start',
                    'tension' => 0.3,
                    'borderColor' => '#fbbf24',
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)',
                ],
                [
                    'label' => 'Profit Bersih (Rp)',
                    'data' => $dataProfit,
                    'fill' => 'start',
                    'tension' => 0.3,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $labelTanggal,
        ];
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters; // ⚡ 1. Import Trait Filter
use Carbon\Carbon;

class StatsPenjualanHariIni extends BaseWidget
{
    use InteractsWithPageFilters; // ⚡ 2. Pasang Trait Filter

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '10s';

    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    // Formasi 3 kolom (2 baris x 3 kartu)
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        // ⚡ 3. Tangkap nilai tanggal dari filter Dashboard (default 7 hari terakhir jika kosong)
        $startDate = !empty($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : Carbon::now()->subDays(6)->startOfDay();

        $endDate = !empty($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : Carbon::now()->endOfDay();

        // ⚡ 4. Gunakan whereBetween untuk mengecek periode
        $trxLunas = Penjualan::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'lunas');

        $countLunas = $trxLunas->count();
        $nominalLunas = $trxLunas->sum('total_harga');

        $trxBelumLunas = Penjualan::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'belum_lunas');

        $countBelumLunas = $trxBelumLunas->count();
        $nominalBelumLunas = $trxBelumLunas->sum('total_harga');

        $totalNominalSemua = $nominalLunas + $nominalBelumLunas;

        $jumlahBarangTerjual = PenjualanDetail::whereHas('penjualan', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })->sum('qty');

        $detailTransaksi = PenjualanDetail::whereHas('penjualan', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })->get();

        $totalProfitBersih = $detailTransaksi->sum(function ($detail) {
            return ($detail->harga_jual - $detail->harga_beli) * $detail->qty;
        });

        $countTrxVoid = Penjualan::onlyTrashed()
            ->whereBetween('deleted_at', [$startDate, $endDate])
            ->count();

        // Format label periode untuk deskripsi
        $periodeLabel = $startDate->isSameDay($endDate)
            ? $startDate->translatedFormat('d M Y')
            : $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M Y');

        return [
            Stat::make('Transaksi Lunas', $countLunas . ' Transaksi')
                ->description('Total: Rp ' . number_format($nominalLunas, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Transaksi Belum Lunas', $countBelumLunas . ' Transaksi')
                ->description('Total: Rp ' . number_format($nominalBelumLunas, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Omset', 'Rp ' . number_format($totalNominalSemua, 0, ',', '.'))
                ->description('Periode: ' . $periodeLabel)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Estimasi Profit Bersih', 'Rp ' . number_format($totalProfitBersih, 0, ',', '.'))
                ->description('Omset dikurangi Harga Beli saat transaksi')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('emerald'),

            Stat::make('Barang Terjual', number_format($jumlahBarangTerjual, 0, ',', '.') . ' Pcs')
                ->description('Total unit keluar periode ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Transaksi Dibatalkan / Void', $countTrxVoid . ' Transaksi')
                ->description($countTrxVoid > 0 ? 'Periksa riwayat hapus nota!' : 'Aman, tidak ada pembatalan')
                ->descriptionIcon('heroicon-m-trash')
                ->color($countTrxVoid > 0 ? 'danger' : 'gray'),
        ];
    }
}
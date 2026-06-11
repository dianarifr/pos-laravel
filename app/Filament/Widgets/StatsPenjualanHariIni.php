<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsPenjualanHariIni extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '10s';

    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $hariIni = Carbon::today();

        $trxLunas = Penjualan::whereDate('created_at', $hariIni)
            ->where('status', 'lunas');

        $countLunas = $trxLunas->count();
        $nominalLunas = $trxLunas->sum('total_harga');

        $trxBelumLunas = Penjualan::whereDate('created_at', $hariIni)
            ->where('status', 'belum_lunas');

        $countBelumLunas = $trxBelumLunas->count();
        $nominalBelumLunas = $trxBelumLunas->sum('total_harga');

        $totalNominalSemua = $nominalLunas + $nominalBelumLunas;

        $jumlahBarangTerjual = PenjualanDetail::whereHas('penjualan', function ($query) use ($hariIni) {
                $query->whereDate('created_at', $hariIni);
            })->sum('qty');

        $detailTransaksiHariIni = PenjualanDetail::whereHas('penjualan', function ($query) use ($hariIni) {
                $query->whereDate('created_at', $hariIni);
            })->with('barang')->get();

        $totalProfitBersih = $detailTransaksiHariIni->sum(function ($detail) {
            return ($detail->harga_jual - $detail->harga_beli) * $detail->qty;
        });

        $countTrxVoid = Penjualan::onlyTrashed()
            ->whereDate('deleted_at', $hariIni)
            ->count();

        return [
            Stat::make('Transaksi Lunas (Hari Ini)', $countLunas . ' Transaksi')
                ->description('Total: Rp ' . number_format($nominalLunas, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Transaksi Belum Lunas (Hari Ini)', $countBelumLunas . ' Transaksi')
                ->description('Total: Rp ' . number_format($nominalBelumLunas, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Omset Hari Ini', 'Rp ' . number_format($totalNominalSemua, 0, ',', '.'))
                ->description('Gabungan Lunas & Belum Lunas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Estimasi Profit Bersih', 'Rp ' . number_format($totalProfitBersih, 0, ',', '.'))
                ->description('Omset dikurangi Harga Beli saat transaksi')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('emerald'),

            Stat::make('Barang Terjual', number_format($jumlahBarangTerjual, 0, ',', '.') . ' Pcs')
                ->description('Total produk keluar hari ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Transaksi Dibatalkan / Void', $countTrxVoid . ' Transaksi')
                ->description($countTrxVoid > 0 ? 'Periksa riwayat hapus nota!' : 'Aman, tidak ada pembatalan')
                ->descriptionIcon('heroicon-m-trash')
                ->color($countTrxVoid > 0 ? 'danger' : 'gray'),
        ];
    }
}
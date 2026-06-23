<?php

namespace App\Filament\Widgets;

use App\Models\Barang;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProdukTerlarisHariIni extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = '10 Produk Terlaris Hari Ini';

    protected static ?string $pollingInterval = '10s'; // Live update tiap 10 detik

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Barang::query()
                    ->withSum(['penjualanDetails as total_terjual' => function ($query) {
                        $query->whereHas('penjualan', function ($q) {
                            $q->whereDate('created_at', Carbon::today());
                        });
                    }], 'qty')
                    ->whereHas('penjualanDetails', function ($query) {
                        $query->whereHas('penjualan', function ($q) {
                            $q->whereDate('created_at', Carbon::today());
                        });
                    })
                    ->orderByDesc('total_terjual')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Produk'),

                Tables\Columns\TextColumn::make('total_terjual')
                    ->label('Terjual')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Pcs'),

                Tables\Columns\TextColumn::make('stok')
                    ->label('Sisa Stok')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Pcs'),
            ]);
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\Barang;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProdukStokKritis extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = '10 Produk Perlu Restock (Stok Kritis)';

    protected static ?string $pollingInterval = '10s';

    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Barang::query()
                    ->whereColumn('stok', '<', 'stok_minimal')
                    ->orderBy('stok', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Produk'),

                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok Saat Ini')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Pcs'),

                Tables\Columns\TextColumn::make('stok_minimal')
                    ->label('Batas Minimal')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.') . ' Pcs'),
            ]);
    }
}
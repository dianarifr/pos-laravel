<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StokLogResource\Pages;
use App\Models\Barang;
use App\Models\StokLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StokLogResource extends Resource
{
    protected static ?string $model = StokLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Inventori';

    protected static ?string $navigationLabel = 'Log Pergerakan Stok';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('barang.sku')
                    ->label('SKU')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('tipe')
                    ->label('Tipe')
                    ->colors([
                        'success' => 'in',
                        'danger'  => 'out',
                        'warning' => 'opname',
                    ])
                    ->formatStateUsing(fn(string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->alignCenter()
                    ->color(fn(StokLog $record): string => match ($record->tipe) {
                        'in'     => 'success',
                        'out'    => 'danger',
                        default  => 'warning',
                    })
                    ->formatStateUsing(
                        fn(int $state, StokLog $record): string => ($record->tipe === 'out' ? '-' : ($record->tipe === 'in' ? '+' : '±')) . $state
                    ),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Petugas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(60)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'in'     => 'Masuk (IN)',
                        'out'    => 'Keluar (OUT)',
                        'opname' => 'Opname',
                    ]),

                Tables\Filters\SelectFilter::make('barang_id')
                    ->label('Barang')
                    ->options(Barang::query()->pluck('nama_barang', 'id'))
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStokLogs::route('/'),
        ];
    }
}

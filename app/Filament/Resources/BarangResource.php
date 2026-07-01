<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangResource\Pages;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Barang';

    protected static ?string $modelLabel = 'Barang';

    protected static ?string $pluralModelLabel = 'Barang';

    protected static ?string $slug = 'barang';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Barang')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),

                        Forms\Components\TextInput::make('nama_barang')
                            ->label('Nama Barang')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->options(Kategori::pluck('nama', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('unit_id')
                            ->label('Satuan')
                            ->options(Unit::pluck('nama', 'id'))
                            ->searchable()
                            ->required(),
                    ]),
                ]),

            Forms\Components\Section::make('Harga & Stok')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('harga_beli')
                            ->label('Harga Beli')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),

                        /* Forms\Components\TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0), */

                        Forms\Components\TextInput::make('stok_minimal')
                            ->label('Stok Minimal')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit.nama')
                    ->label('Satuan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('IDR')
                    ->sortable(),

                /* Tables\Columns\TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(), */

                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok Akhir')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn($record): string => $record->stok <= $record->stok_minimal ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(Kategori::pluck('nama', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBarangs::route('/'),
            'create' => Pages\CreateBarang::route('/create'),
            'edit'   => Pages\EditBarang::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}

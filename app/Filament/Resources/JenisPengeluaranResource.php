<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisPengeluaranResource\Pages;
use App\Models\JenisPengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JenisPengeluaranResource extends Resource
{
    protected static ?string $model = JenisPengeluaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Jenis Pengeluaran';

    protected static ?string $modelLabel = 'Jenis Pengeluaran';

    protected static ?string $pluralModelLabel = 'Jenis Pengeluaran';

    protected static ?string $slug = 'jenis-pengeluaran';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nama')
                ->label('Nama Jenis')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\Textarea::make('deskripsi')
                ->label('Deskripsi')
                ->nullable()
                ->maxLength(500)
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Jenis')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(60)
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('pengeluarans_count')
                    ->label('Jumlah Transaksi')
                    ->counts('pengeluarans')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListJenisPengeluarans::route('/'),
            'create' => Pages\CreateJenisPengeluaran::route('/create'),
            'edit'   => Pages\EditJenisPengeluaran::route('/{record}/edit'),
        ];
    }
}

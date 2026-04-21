<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StokOpnameResource\Pages;
use App\Models\Barang;
use App\Models\StokOpname;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StokOpnameResource extends Resource
{
    protected static ?string $model = StokOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Inventori';

    protected static ?string $navigationLabel = 'Stok Opname';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        $isValidated = $form->getRecord()?->isValidated() ?? false;

        return $form->schema([
            Forms\Components\Section::make('Header Opname')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal Opname')
                            ->required()
                            ->default(now())
                            ->disabled($isValidated),

                        Forms\Components\Select::make('user_id')
                            ->label('Petugas')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->live()
                            ->preload()
                            ->disabled($isValidated),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                StokOpname::STATUS_DRAFT     => 'Draft',
                                StokOpname::STATUS_VALIDATED => 'Validated',
                            ])
                            ->default(StokOpname::STATUS_DRAFT)
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ]),

                    Forms\Components\Textarea::make('keterangan')
                        ->label('Keterangan')
                        ->rows(2)
                        ->disabled($isValidated)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Detail Stok')
                ->schema([
                    Forms\Components\Repeater::make('details')
                        ->relationship('details')
                        ->label('Item Opname')
                        ->disabled($isValidated)
                        ->schema([
                            Forms\Components\Select::make('barang_id')
                                ->label('Barang')
                                ->options(Barang::query()->pluck('nama_barang', 'id'))
                                ->required()
                                ->searchable()
                                ->distinct()
                                ->live()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $stok = $state ? (\App\Models\Barang::find($state)?->stok ?? 0) : 0;
                                    $set('stok_sistem', $stok);
                                    $set('stok_fisik', 0);
                                    $set('selisih', 0 - $stok);
                                })
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('stok_sistem')
                                ->label('Stok Sistem')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->readOnly()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $set('selisih', (int) $get('stok_fisik') - (int) $get('stok_sistem'));
                                })
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('stok_fisik')
                                ->label('Stok Fisik')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $set('selisih', (int) $get('stok_fisik') - (int) $get('stok_sistem'));
                                })
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('selisih')
                                ->label('Selisih')
                                ->numeric()
                                ->default(0)
                                ->readOnly()
                                ->columnSpan(2),
                        ])
                        ->columns(9)
                        ->addActionLabel('Tambah Barang')
                        ->reorderable(false)
                        ->defaultItems(1),
                ])
                ->hidden($isValidated === false ? false : false), // always show, items disabled
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Petugas')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => StokOpname::STATUS_DRAFT,
                        'success' => StokOpname::STATUS_VALIDATED,
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Jml Item')
                    ->counts('details')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->actions([
                Tables\Actions\Action::make('validasi')
                    ->label('Validasi Stok')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Validasi Stok Opname')
                    ->modalDescription('Stok barang akan diperbarui berdasarkan data opname ini. Proses tidak bisa dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Validasi Sekarang')
                    ->visible(fn(StokOpname $record): bool => $record->isDraft())
                    ->authorize(fn(StokOpname $record): bool => auth()->user()->can('validate', $record))
                    ->action(function (StokOpname $record): void {
                        $record->update(['status' => StokOpname::STATUS_VALIDATED]);

                        Notification::make()
                            ->title('Stok Berhasil Disinkronkan')
                            ->body('Stok barang telah diperbarui berdasarkan hasil opname.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('rollback')
                    ->label('Rollback Validasi')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rollback Validasi Stok Opname')
                    ->modalDescription('Batalkan validasi? Sistem akan melakukan penyesuaian stok ulang dan mencatat riwayat pembatalan ini di log.')
                    ->modalSubmitActionLabel('Ya, Batalkan Validasi')
                    ->visible(fn(StokOpname $record): bool => $record->isValidated())
                    ->authorize(fn(StokOpname $record): bool => auth()->user()->can('rollback', $record))
                    ->action(function (StokOpname $record): void {
                        $record->update(['status' => StokOpname::STATUS_DRAFT]);

                        Notification::make()
                            ->title('Validasi Dibatalkan & Riwayat Dicatat')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(StokOpname $record): bool => $record->isDraft()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(StokOpname $record): bool => $record->isDraft()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete', $record);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStokOpnames::route('/'),
            'create' => Pages\CreateStokOpname::route('/create'),
            'edit'   => Pages\EditStokOpname::route('/{record}/edit'),
            'view'   => Pages\ViewStokOpname::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Pembelian Barang';

    protected static ?string $modelLabel = 'Pembelian';

    protected static ?string $pluralModelLabel = 'Pembelian Barang';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Gudang']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Header Pembelian')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('supplier_id')
                                ->label('Supplier')
                                ->options(Supplier::query()->pluck('nama', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\TextInput::make('no_nota')
                                ->label('Nomor Nota')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(100),

                            Forms\Components\DatePicker::make('tanggal_pembelian')
                                ->label('Tanggal Pembelian')
                                ->default(now())
                                ->native(false)
                                ->required(),

                            Forms\Components\Select::make('status_pembayaran')
                                ->label('Status Pembayaran')
                                ->options([
                                    'tunai' => 'Tunai',
                                    'kredit' => 'Kredit',
                                ])
                                ->default('tunai')
                                ->live()
                                ->required(),

                            Forms\Components\DatePicker::make('tanggal_jatuh_tempo')
                                ->label('Tanggal Jatuh Tempo')
                                ->native(false)
                                ->visible(fn(Forms\Get $get): bool => $get('status_pembayaran') === 'kredit')
                                ->required(fn(Forms\Get $get): bool => $get('status_pembayaran') === 'kredit'),
                        ]),
                ]),

            Forms\Components\Section::make('Item Pembelian')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('Daftar Barang')
                        ->defaultItems(1)
                        ->addActionLabel('Tambah Barang')
                        ->reorderable(false)
                        ->collapsible(false)
                        ->columns(12)
                        ->schema([
                            Forms\Components\Select::make('barang_id')
                                ->label('Barang (SKU/Nama)')
                                ->searchable()
                                ->required()
                                ->preload()
                                ->options(function (): array {
                                    return Barang::query()
                                        ->orderBy('nama_barang')
                                        ->limit(200)
                                        ->get()
                                        ->mapWithKeys(fn(Barang $barang) => [
                                            $barang->id => $barang->sku . ' - ' . $barang->nama_barang,
                                        ])
                                        ->all();
                                })
                                ->getSearchResultsUsing(function (string $search): array {
                                    return Barang::query()
                                        ->where(function ($query) use ($search) {
                                            $query->where('sku', 'like', "%{$search}%")
                                                ->orWhere('nama_barang', 'like', "%{$search}%");
                                        })
                                        ->limit(30)
                                        ->get()
                                        ->mapWithKeys(fn(Barang $barang) => [
                                            $barang->id => $barang->sku . ' - ' . $barang->nama_barang,
                                        ])
                                        ->all();
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    $barang = Barang::find($value);
                                    if (! $barang) {
                                        return null;
                                    }

                                    return $barang->sku . ' - ' . $barang->nama_barang;
                                })
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state): void {
                                    $barang = Barang::find($state);
                                    if (! $barang) {
                                        return;
                                    }

                                    $set('harga_beli', (float) $barang->harga_beli);
                                    $set('harga_jual_baru', (float) $barang->harga_jual);
                                })
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('harga_beli')
                                ->label('Harga Beli')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->live()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('qty')
                                ->label('Qty')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->live()
                                ->columnSpan(1),

                            Forms\Components\Placeholder::make('subtotal')
                                ->label('Subtotal')
                                ->content(function (Forms\Get $get): string {
                                    $harga = (float) ($get('harga_beli') ?? 0);
                                    $qty = (int) ($get('qty') ?? 0);
                                    $subtotal = $harga * $qty;

                                    return 'Rp ' . number_format($subtotal, 0, ',', '.');
                                })
                                ->columnSpan(2),

                            Forms\Components\Checkbox::make('update_harga_jual_master')
                                ->label('Update Harga Jual')
                                ->live()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('harga_jual_baru')
                                ->label('Harga Jual Baru')
                                ->numeric()
                                ->minValue(0)
                                ->visible(fn(Forms\Get $get): bool => (bool) $get('update_harga_jual_master'))
                                ->required(fn(Forms\Get $get): bool => (bool) $get('update_harga_jual_master'))
                                ->columnSpan(2),
                        ])
                        ->columnSpanFull()
                        ->required(),

                    Forms\Components\Placeholder::make('grand_total')
                        ->label('Grand Total Pembelian')
                        ->content(function (Forms\Get $get): string {
                            $items = $get('items') ?? [];
                            $grandTotal = collect($items)->sum(function (array $item): float {
                                $harga = (float) ($item['harga_beli'] ?? 0);
                                $qty = (int) ($item['qty'] ?? 0);

                                return $harga * $qty;
                            });

                            return 'Rp ' . number_format($grandTotal, 0, ',', '.');
                        })
                        ->extraAttributes(['class' => 'text-xl font-bold']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed())
            ->columns([
                Tables\Columns\TextColumn::make('no_nota')
                    ->label('Nomor Nota')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('supplier.nama')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->color(fn(string $state): string => $state === 'kredit' ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Grand Total')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\Filter::make('hanya_batal')
                    ->label('Tampilkan yang dibatalkan saja')
                    ->query(fn(Builder $q) => $q->onlyTrashed()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('void')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Pembelian $record) => ! $record->trashed() && Auth::user()?->hasRole('Admin'))
                    ->form([
                        Forms\Components\Textarea::make('void_reason')
                            ->label('Alasan Pembatalan')
                            ->required()
                            ->minLength(5)
                            ->rows(3),
                    ])
                    ->modalHeading('Batal Pembelian (Void)')
                    ->modalDescription('Hati-hati! Pembatalan ini akan mengurangi stok barang secara otomatis. Pastikan barang memang dikembalikan atau tidak jadi diterima.')
                    ->modalSubmitActionLabel('Ya, Batalkan Pembelian')
                    ->action(function (Pembelian $record, array $data): void {
                        if (! Auth::user()?->hasRole('Admin')) {
                            Notification::make()
                                ->title('Akses Ditolak')
                                ->body('Hanya Admin yang dapat membatalkan pembelian.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if ($record->trashed()) {
                            Notification::make()
                                ->title('Pembelian Sudah Dibatalkan')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            DB::transaction(function () use ($record, $data): void {
                                $record->update([
                                    'void_reason' => $data['void_reason'],
                                    'void_by'     => Auth::id(),
                                ]);

                                $record->delete();
                            });

                            Notification::make()
                                ->title('Pembelian Dibatalkan')
                                ->body('Stok telah dikurangi kembali.')
                                ->danger()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Gagal Membatalkan Pembelian')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pembelian')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('no_nota')
                            ->label('Nomor Nota')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('tanggal')
                            ->label('Tanggal Pembelian')
                            ->date('d M Y'),

                        Infolists\Components\TextEntry::make('supplier.nama')
                            ->label('Supplier'),

                        Infolists\Components\TextEntry::make('status_pembayaran')
                            ->label('Status Pembayaran')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => ucfirst($state))
                            ->color(fn(string $state): string => $state === 'kredit' ? 'warning' : 'success'),

                        Infolists\Components\TextEntry::make('tanggal_jatuh_tempo')
                            ->label('Jatuh Tempo')
                            ->date('d M Y')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('total_harga')
                            ->label('Grand Total')
                            ->money('IDR', locale: 'id'),
                    ]),

                Infolists\Components\Section::make('Informasi Pembatalan')
                    ->columns(2)
                    ->visible(fn(Pembelian $record) => $record->trashed())
                    ->schema([
                        Infolists\Components\TextEntry::make('voidedBy.name')
                            ->label('Dibatalkan Oleh'),

                        Infolists\Components\TextEntry::make('deleted_at')
                            ->label('Waktu Pembatalan')
                            ->dateTime('d M Y, H:i'),

                        Infolists\Components\TextEntry::make('void_reason')
                            ->label('Alasan Pembatalan')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Detail Item')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->columns(6)
                            ->schema([
                                Infolists\Components\TextEntry::make('barang.sku')
                                    ->label('SKU'),

                                Infolists\Components\TextEntry::make('barang.nama_barang')
                                    ->label('Nama Barang'),

                                Infolists\Components\TextEntry::make('qty')
                                    ->label('Qty'),

                                Infolists\Components\TextEntry::make('harga_beli')
                                    ->label('Harga Beli')
                                    ->money('IDR', locale: 'id'),

                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR', locale: 'id'),

                                Infolists\Components\IconEntry::make('update_harga_jual_master')
                                    ->label('Update Harga Jual')
                                    ->boolean(),
                            ]),
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
            'index'  => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'view'   => Pages\ViewPembelian::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}

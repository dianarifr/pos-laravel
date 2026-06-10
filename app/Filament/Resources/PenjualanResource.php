<?php

namespace App\Filament\Resources;

use App\Enums\StatusPenjualan;
use App\Filament\Resources\PenjualanResource\Pages;
use App\Models\Penjualan;
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

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'History Penjualan';

    protected static ?string $modelLabel = 'Penjualan';

    protected static ?string $pluralModelLabel = 'History Penjualan';

    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
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
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed())
            ->columns([
                Tables\Columns\TextColumn::make('no_faktur')
                    ->label('No. Faktur')
                    ->formatStateUsing(function (Penjualan $record): string {
                        $status = $record->trashed() ? 'Dibatalkan' : ($record->status?->label() ?? '-');
                        $statusStyle = match (true) {
                            $record->trashed() => 'color:#b91c1c;font-weight:700;',
                            $record->status === StatusPenjualan::Lunas => 'color:#15803d;font-weight:700;',
                            $record->status === StatusPenjualan::BelumLunas => 'color:#b45309;font-weight:700;',
                            default => 'color:#6b7280;',
                        };

                        return $record->no_faktur
                            . '<div style="font-size:12px;margin-top:4px;' . $statusStyle . '">Status: ' . e($status) . '</div>';
                    })
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.nama')
                    ->label('Customer')
                    ->default('— Umum —')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nominal_bayar')
                    ->label('Bayar')
                    ->money('IDR', locale: 'id'),

                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->label('Sisa Tagihan')
                    ->money('IDR', locale: 'id')
                    ->color(fn(Penjualan $r) => $r->sisa_bayar > 0 ? 'danger' : 'success')
                    ->placeholder('—'),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'lunas'       => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ]),

                Tables\Filters\Filter::make('hanya_batal')
                    ->label('Tampilkan yang dibatalkan saja')
                    ->query(fn(Builder $q) => $q->onlyTrashed()),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari')->label('Dari'),
                        Forms\Components\DatePicker::make('sampai')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['dari'],   fn($q, $v) => $q->whereDate('tanggal', '>=', $v))
                            ->when($data['sampai'], fn($q, $v) => $q->whereDate('tanggal', '<=', $v));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\Action::make('cetak')
                        ->label('Cetak')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn(Penjualan $record) => ! $record->trashed())
                        ->url(fn(Penjualan $record): string => route('penjualan.print', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('void')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(
                            fn(Penjualan $record) =>
                            ! $record->trashed() && Auth::user()?->hasRole('Owner')
                        )
                        ->form([
                            Forms\Components\Textarea::make('void_reason')
                                ->label('Alasan Pembatalan')
                                ->placeholder('Masukkan alasan pembatalan transaksi ini...')
                                ->required()
                                ->minLength(5)
                                ->rows(3),
                        ])
                        ->modalHeading('Batal Transaksi (Void)')
                        ->modalDescription('⚠️ Tindakan ini tidak dapat dibatalkan. Stok barang akan dikembalikan ke gudang secara otomatis. Lanjutkan?')
                        ->modalSubmitActionLabel('Ya, Batalkan Transaksi')
                        ->requiresConfirmation(false)
                        ->action(function (Penjualan $record, array $data): void {
                            // Security check ulang di sisi server
                            if (! Auth::user()?->hasRole('Owner')) {
                                Notification::make()
                                    ->title('Akses Ditolak')
                                    ->body('Hanya Owner yang dapat membatalkan transaksi.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Prevent double-void (race condition guard)
                            if ($record->trashed()) {
                                Notification::make()
                                    ->title('Transaksi Sudah Dibatalkan')
                                    ->body('Transaksi ini sudah dibatalkan sebelumnya.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            try {
                                DB::transaction(function () use ($record, $data): void {
                                    // Simpan alasan dan pelaku void sebelum soft delete
                                    $record->update([
                                        'void_reason' => $data['void_reason'],
                                        'void_by'     => Auth::id(),
                                    ]);

                                    // Trigger PenjualanObserver::deleting() untuk reversal stok
                                    $record->delete();
                                });

                                Notification::make()
                                    ->title('Transaksi Berhasil Dibatalkan')
                                    ->body("Transaksi {$record->no_faktur} telah dibatalkan. Stok barang sudah dikembalikan.")
                                    ->danger()
                                    ->send();
                            } catch (\RuntimeException $e) {
                                Notification::make()
                                    ->title('Gagal Membatalkan Transaksi')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('pelunasan')
                        ->label('Pelunasan')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->visible(
                            fn(Penjualan $record) =>
                            ! $record->trashed()
                                && $record->status === \App\Enums\StatusPenjualan::BelumLunas
                        )
                        ->mountUsing(function (Forms\ComponentContainer $form, Penjualan $record): void {
                            $form->fill([
                                'total_harga'       => $record->total_harga,
                                'sudah_dibayar'     => $record->nominal_bayar,
                                'sisa_tagihan_info' => $record->sisa_bayar,
                                'nominal_pelunasan' => number_format((int) $record->sisa_bayar, 0, ',', '.'),
                            ]);
                        })
                        ->form([
                            Forms\Components\Placeholder::make('total_harga')
                                ->label('Total Transaksi')
                                ->content(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                            Forms\Components\Placeholder::make('sudah_dibayar')
                                ->label('Sudah Dibayar')
                                ->content(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                            Forms\Components\Placeholder::make('sisa_tagihan_info')
                                ->label('Sisa Tagihan')
                                ->content(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),

                            Forms\Components\TextInput::make('nominal_pelunasan')
                                ->label('Nominal Pelunasan (Rp)')
                                ->extraInputAttributes([
                                    'oninput' => 'this.value = (this.value || "").replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".")',
                                ])
                                ->required()
                                ->inputMode('numeric')
                                ->placeholder('Masukkan jumlah pembayaran'),
                        ])
                        ->modalHeading('Pelunasan Piutang')
                        ->modalSubmitActionLabel('Simpan Pelunasan')
                        ->action(function (Penjualan $record, array $data): void {
                            $nominal = (int) preg_replace('/\D/', '', (string) ($data['nominal_pelunasan'] ?? ''));

                            if ($nominal < 1) {
                                Notification::make()
                                    ->title('Nominal Pelunasan Tidak Valid')
                                    ->warning()
                                    ->body('Nominal pelunasan harus lebih dari 0.')
                                    ->send();
                                return;
                            }

                            $sisaBaru = max(0, (float) $record->sisa_bayar - $nominal);
                            $bayarBaru = (float) $record->nominal_bayar + $nominal;
                            $statusBaru = $sisaBaru <= 0
                                ? \App\Enums\StatusPenjualan::Lunas
                                : \App\Enums\StatusPenjualan::BelumLunas;

                            // Update langsung — tidak menyentuh PenjualanDetail → observer stok tidak terpicu
                            $record->updateQuietly([
                                'nominal_bayar' => $bayarBaru,
                                'sisa_bayar'    => $sisaBaru,
                                'status'        => $statusBaru,
                            ]);

                            $pesanBody = $sisaBaru <= 0
                                ? 'Transaksi ' . $record->no_faktur . ' telah lunas.'
                                : 'Sisa tagihan tersisa Rp ' . number_format($sisaBaru, 0, ',', '.') . '.';

                            Notification::make()
                                ->title('Pelunasan Berhasil Disimpan')
                                ->success()
                                ->body($pesanBody)
                                ->send();
                        }),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Transaksi')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('no_faktur')
                            ->label('No. Faktur')
                            ->fontFamily('mono')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('tanggal')
                            ->label('Tanggal & Jam')
                            ->dateTime('d M Y, H:i'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn(StatusPenjualan $state) => $state->label())
                            ->color(fn(StatusPenjualan $state) => $state->color()),

                        Infolists\Components\TextEntry::make('customer.nama')
                            ->label('Customer')
                            ->default('— Umum —'),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Kasir'),

                        Infolists\Components\TextEntry::make('total_harga')
                            ->label('Grand Total')
                            ->money('IDR', locale: 'id'),

                        Infolists\Components\TextEntry::make('nominal_bayar')
                            ->label('Nominal Bayar')
                            ->money('IDR', locale: 'id'),

                        Infolists\Components\TextEntry::make('sisa_bayar')
                            ->label('Sisa Tagihan')
                            ->money('IDR', locale: 'id')
                            ->color(fn(Penjualan $r) => $r->sisa_bayar > 0 ? 'danger' : 'success'),

                        Infolists\Components\TextEntry::make('kembali')
                            ->label('Kembalian')
                            ->state(fn(Penjualan $r) => max(0, $r->nominal_bayar - $r->total_harga))
                            ->money('IDR', locale: 'id')
                            ->visible(fn(Penjualan $r) => $r->sisa_bayar <= 0),
                    ]),

                Infolists\Components\Section::make('Informasi Pembatalan')
                    ->columns(2)
                    ->visible(fn(Penjualan $record) => $record->trashed())
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

                Infolists\Components\Section::make('Detail Barang')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('details')
                            ->label('')
                            ->columns(5)
                            ->schema([
                                Infolists\Components\TextEntry::make('barang.nama_barang')
                                    ->label('Barang'),

                                Infolists\Components\TextEntry::make('barang.sku')
                                    ->label('SKU')
                                    ->fontFamily('mono'),

                                Infolists\Components\TextEntry::make('qty')
                                    ->label('Qty'),

                                Infolists\Components\TextEntry::make('harga_jual')
                                    ->label('Harga')
                                    ->money('IDR', locale: 'id'),

                                Infolists\Components\TextEntry::make('diskon')
                                    ->label('Diskon')
                                    ->money('IDR', locale: 'id'),

                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR', locale: 'id'),
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
            'index' => Pages\ListPenjualans::route('/'),
            'view'  => Pages\ViewPenjualan::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Override agar withTrashed bisa diakses di View page
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}

<?php

namespace App\Filament\Resources\StokOpnameResource\Pages;

use App\Filament\Resources\StokOpnameResource;
use App\Models\StokOpname;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStokOpname extends ViewRecord
{
    protected static string $resource = StokOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('validasi')
                ->label('Validasi Stok')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Validasi Stok Opname')
                ->modalDescription('Stok barang akan diperbarui berdasarkan data opname ini. Proses tidak bisa dibatalkan.')
                ->modalSubmitActionLabel('Ya, Validasi Sekarang')
                ->visible(fn(): bool => $this->getRecord()->isDraft())
                ->authorize(fn(): bool => auth()->user()->can('validate', $this->getRecord()))
                ->action(function (): void {
                    $this->getRecord()->update(['status' => StokOpname::STATUS_VALIDATED]);

                    Notification::make()
                        ->title('Stok Berhasil Disinkronkan')
                        ->body('Stok barang telah diperbarui berdasarkan hasil opname.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Actions\EditAction::make()
                ->visible(fn(): bool => $this->getRecord()->isDraft()),

            Actions\Action::make('rollback')
                ->label('Rollback Validasi')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rollback Validasi Stok Opname')
                ->modalDescription('Batalkan validasi? Sistem akan melakukan penyesuaian stok ulang dan mencatat riwayat pembatalan ini di log.')
                ->modalSubmitActionLabel('Ya, Batalkan Validasi')
                ->visible(fn(): bool => $this->getRecord()->isValidated())
                ->authorize(fn(): bool => auth()->user()->can('rollback', $this->getRecord()))
                ->action(function (): void {
                    $this->getRecord()->update(['status' => StokOpname::STATUS_DRAFT]);

                    Notification::make()
                        ->title('Validasi Dibatalkan & Riwayat Dicatat')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Header Opname')->schema([
                TextEntry::make('tanggal')->label('Tanggal')->date('d M Y'),
                TextEntry::make('user.name')->label('Petugas'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        StokOpname::STATUS_VALIDATED => 'success',
                        default                      => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                TextEntry::make('keterangan')->label('Keterangan')->columnSpanFull(),
            ])->columns(3),

            Section::make('Detail Stok')->schema([
                RepeatableEntry::make('details')->label('')->schema([
                    TextEntry::make('barang.nama_barang')->label('Barang'),
                    TextEntry::make('stok_sistem')->label('Stok Sistem'),
                    TextEntry::make('stok_fisik')->label('Stok Fisik'),
                    TextEntry::make('selisih')
                        ->label('Selisih')
                        ->badge()
                        ->color(fn(int $state): string => match (true) {
                            $state < 0 => 'danger',
                            $state > 0 => 'warning',
                            default    => 'success',
                        }),
                ])->columns(4),
            ]),
        ]);
    }
}

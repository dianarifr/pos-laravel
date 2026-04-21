<?php

namespace App\Filament\Resources\StokOpnameResource\Pages;

use App\Filament\Resources\StokOpnameResource;
use App\Models\StokOpname;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStokOpname extends EditRecord
{
    protected static string $resource = StokOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

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

                    $this->redirect(StokOpnameResource::getUrl('index'));
                }),

            Actions\DeleteAction::make()
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

                    $this->redirect(StokOpnameResource::getUrl('index'));
                }),
        ];
    }
}

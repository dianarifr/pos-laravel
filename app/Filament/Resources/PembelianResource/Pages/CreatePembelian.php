<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function hasStickyFormActions(): bool
    {
        return true;
    }

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Minimal 1 item pembelian wajib diisi.',
            ]);
        }

        $grandTotal = collect($items)->sum(function (array $item): float {
            $harga = (float) ($item['harga_beli'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            return $harga * $qty;
        });

        return DB::transaction(function () use ($data, $items, $grandTotal): Pembelian {
            $pembelian = Pembelian::create([
                'supplier_id'          => $data['supplier_id'],
                'user_id'              => Auth::id(),
                'no_nota'              => $data['no_nota'],
                'total_harga'          => $grandTotal,
                'tanggal'              => $data['tanggal_pembelian'],
                'status_pembayaran'    => $data['status_pembayaran'],
                'tanggal_jatuh_tempo'  => $data['status_pembayaran'] === 'kredit'
                    ? ($data['tanggal_jatuh_tempo'] ?? null)
                    : null,
            ]);

            foreach ($items as $item) {
                $hargaBeli = (float) ($item['harga_beli'] ?? 0);
                $qty = (int) ($item['qty'] ?? 0);

                PembelianDetail::create([
                    'pembelian_id'              => $pembelian->id,
                    'barang_id'                 => $item['barang_id'],
                    'qty'                       => $qty,
                    'harga_beli'                => $hargaBeli,
                    'diskon'                    => 0,
                    'subtotal'                  => $hargaBeli * $qty,
                    'update_harga_jual_master'  => (bool) ($item['update_harga_jual_master'] ?? false),
                    'harga_jual_baru'           => ! empty($item['update_harga_jual_master'])
                        ? (float) ($item['harga_jual_baru'] ?? 0)
                        : null,
                ]);
            }

            return $pembelian;
        });
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Pembelian berhasil disimpan')
            ->body('Stok barang telah diperbarui dan log stok masuk sudah tercatat.')
            ->success()
            ->send();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}

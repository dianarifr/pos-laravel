<?php

namespace App\Observers;

use App\Models\Barang;
use App\Models\Penjualan;
use App\Models\StokLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenjualanObserver
{
    /**
     * Saat Penjualan di-void (soft delete):
     *  1. Kembalikan stok barang per detail.
     *  2. Catat audit trail di stoklogs.
     *  3. Cascade soft delete semua detail (via query builder → tanpa trigger observer detail).
     */
    public function deleting(Penjualan $penjualan): void
    {
        // Race-condition guard: pastikan record belum di-void oleh request lain
        $fresh = Penjualan::withTrashed()->lockForUpdate()->find($penjualan->id);
        if ($fresh === null || $fresh->trashed()) {
            throw new \RuntimeException('Transaksi ini sudah dibatalkan sebelumnya.');
        }

        DB::transaction(function () use ($penjualan) {
            // Load detail sebelum di-soft-delete (withTrashed sudah ada di relasi)
            $details = $penjualan->details()->whereNull('penjualan_details.deleted_at')->get();

            foreach ($details as $detail) {
                // Kembalikan stok dengan pessimistic locking
                Barang::lockForUpdate()->where('id', $detail->barang_id)
                    ->increment('stok', $detail->qty);

                // Audit trail
                StokLog::create([
                    'barang_id'  => $detail->barang_id,
                    'user_id'    => Auth::id(),
                    'tipe'       => 'in',
                    'qty'        => $detail->qty,
                    'ref_id'     => $penjualan->id,
                    'keterangan' => 'Batal Transaksi ' . $penjualan->no_faktur
                        . ' - Alasan: ' . ($penjualan->void_reason ?? '-'),
                    'created_at' => now(),
                ]);
            }

            // Cascade soft delete detail via query builder (tidak memicu PenjualanDetailObserver)
            $penjualan->details()->whereNull('penjualan_details.deleted_at')
                ->update(['deleted_at' => now()]);
        });
    }
}

<?php

namespace App\Observers;

use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\StokLog;
use RuntimeException;
use Illuminate\Support\Facades\DB;

class PembelianObserver
{
    public function deleting(Pembelian $pembelian): void
    {
        if ($pembelian->isForceDeleting()) {
            return;
        }

        DB::transaction(function () use ($pembelian): void {
            $pembelian->loadMissing(['details', 'supplier']);

            // Validasi kecukupan stok sebelum reversal pembelian.
            foreach ($pembelian->details as $detail) {
                $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);

                if ($barang->stok < $detail->qty) {
                    throw new RuntimeException(
                        "Stok {$barang->nama_barang} tidak mencukupi untuk membatalkan pembelian."
                    );
                }
            }

            foreach ($pembelian->details as $detail) {
                $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);
                $barang->decrement('stok', $detail->qty);

                $supplierName = $pembelian->supplier->nama ?? '-';
                $reason = $pembelian->void_reason ?: '-';

                StokLog::create([
                    'barang_id'  => $detail->barang_id,
                    'user_id'    => $pembelian->void_by ?: $pembelian->user_id,
                    'tipe'       => 'out',
                    'qty'        => $detail->qty,
                    'ref_id'     => $pembelian->id,
                    'keterangan' => 'Batal Pembelian dari Supplier ' . $supplierName . ' - Alasan: ' . $reason,
                ]);
            }

            // Cascading soft delete detail tanpa trigger observer detail.
            $pembelian->details()->getQuery()->delete();
        });
    }
}

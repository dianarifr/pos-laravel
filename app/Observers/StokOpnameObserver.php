<?php

namespace App\Observers;

use App\Models\Barang;
use App\Models\StokLog;
use App\Models\StokOpname;
use Illuminate\Support\Facades\DB;

class StokOpnameObserver
{
    public function updated(StokOpname $stokOpname): void
    {
        if (! $stokOpname->wasChanged('status')) {
            return;
        }

        // Forward: draft → validated
        if ($stokOpname->status === StokOpname::STATUS_VALIDATED) {
            DB::transaction(function () use ($stokOpname) {
                foreach ($stokOpname->details()->with('barang')->get() as $detail) {
                    if ($detail->selisih === 0) {
                        continue;
                    }

                    $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);
                    $barang->update(['stok' => $barang->stok + $detail->selisih]);

                    StokLog::create([
                        'barang_id'  => $detail->barang_id,
                        'user_id'    => $stokOpname->user_id,
                        'tipe'       => 'opname',
                        'qty'        => $detail->selisih,
                        'ref_id'     => $stokOpname->id,
                        'keterangan' => 'Validasi stok opname: ' . ($stokOpname->keterangan ?? '-'),
                    ]);
                }
            });

            return;
        }

        // Rollback: validated → draft
        if (
            $stokOpname->status === StokOpname::STATUS_DRAFT
            && $stokOpname->getOriginal('status') === StokOpname::STATUS_VALIDATED
        ) {
            DB::transaction(function () use ($stokOpname) {
                foreach ($stokOpname->details()->with('barang')->get() as $detail) {
                    if ($detail->selisih === 0) {
                        continue;
                    }

                    $reversalQty = -$detail->selisih;

                    $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);
                    $barang->update(['stok' => $barang->stok + $reversalQty]);

                    // Audit trail: buat log baru, TIDAK menghapus log lama
                    StokLog::create([
                        'barang_id'  => $detail->barang_id,
                        'user_id'    => auth()->id() ?? $stokOpname->user_id,
                        'tipe'       => 'opname',
                        'qty'        => $reversalQty,
                        'ref_id'     => $stokOpname->id,
                        'keterangan' => 'Rollback validasi Opname ID: ' . $stokOpname->id,
                    ]);
                }
            });
        }
    }
}

<?php

namespace App\Observers;

use App\Models\PembelianDetail;
use App\Models\Barang;
use App\Models\StokLog;
use Illuminate\Support\Facades\DB;

class PembelianDetailObserver
{
    public function created(PembelianDetail $detail): void
    {
        DB::transaction(function () use ($detail) {
            $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);
            $barang->increment('stok', $detail->qty);

            // Sinkron harga modal terakhir setiap pembelian.
            $barang->harga_beli = $detail->harga_beli;

            if ($detail->update_harga_jual_master && $detail->harga_jual_baru !== null) {
                $barang->harga_jual = $detail->harga_jual_baru;
            }

            $barang->save();

            $supplierName = $detail->pembelian->supplier->nama ?? '-';

            StokLog::create([
                'barang_id'  => $detail->barang_id,
                'user_id'    => $detail->pembelian->user_id,
                'tipe'       => 'in',
                'qty'        => $detail->qty,
                'ref_id'     => $detail->pembelian_id,
                'keterangan' => 'Pembelian dari Supplier ' . $supplierName,
            ]);
        });
    }

    public function deleted(PembelianDetail $detail): void
    {
        DB::transaction(function () use ($detail) {
            $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);
            $barang->decrement('stok', $detail->qty);

            StokLog::create([
                'barang_id'  => $detail->barang_id,
                'user_id'    => $detail->pembelian->user_id,
                'tipe'       => 'out',
                'qty'        => $detail->qty,
                'ref_id'     => $detail->pembelian_id,
                'keterangan' => 'Reversal pembelian ' . $detail->pembelian->no_nota,
            ]);
        });
    }
}

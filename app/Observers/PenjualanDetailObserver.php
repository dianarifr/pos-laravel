<?php

namespace App\Observers;

use App\Models\PenjualanDetail;
use App\Models\Barang;
use App\Models\StokLog;
use Illuminate\Support\Facades\DB;

class PenjualanDetailObserver
{
    public function created(PenjualanDetail $detail): void
    {
        DB::transaction(function () use ($detail) {
            $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);
            $barang->decrement('stok', $detail->qty);

            StokLog::create([
                'barang_id'  => $detail->barang_id,
                'user_id'    => $detail->penjualan->user_id,
                'tipe'       => 'out',
                'qty'        => $detail->qty,
                'ref_id'     => $detail->penjualan_id,
                'keterangan' => 'Penjualan ' . $detail->penjualan->no_faktur,
            ]);
        });
    }

    public function deleted(PenjualanDetail $detail): void
    {
        DB::transaction(function () use ($detail) {
            $barang = Barang::lockForUpdate()->findOrFail($detail->barang_id);
            $barang->increment('stok', $detail->qty);

            StokLog::create([
                'barang_id'  => $detail->barang_id,
                'user_id'    => $detail->penjualan->user_id,
                'tipe'       => 'in',
                'qty'        => $detail->qty,
                'ref_id'     => $detail->penjualan_id,
                'keterangan' => 'Reversal penjualan ' . $detail->penjualan->no_faktur,
            ]);
        });
    }
}

<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\StokLog;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class StokSeeder extends Seeder
{
    public function run(): void
    {
        $user    = User::where('email', 'dian@mail.com')->first();
        $barangs = Barang::all();

        // --- Stok Log dari Pembelian (tipe: in) ---
        foreach (Pembelian::with('details.barang')->get() as $pembelian) {
            foreach ($pembelian->details as $detail) {
                StokLog::create([
                    'barang_id'  => $detail->barang_id,
                    'user_id'    => $pembelian->user_id,
                    'tipe'       => 'in',
                    'qty'        => $detail->qty,
                    'ref_id'     => $pembelian->id,
                    'keterangan' => 'Pembelian ' . $pembelian->no_nota,
                    'created_at' => $pembelian->tanggal,
                ]);
            }
        }

        // --- Stok Log dari Penjualan (tipe: out) ---
        foreach (Penjualan::with('details.barang')->get() as $penjualan) {
            foreach ($penjualan->details as $detail) {
                StokLog::create([
                    'barang_id'  => $detail->barang_id,
                    'user_id'    => $penjualan->user_id,
                    'tipe'       => 'out',
                    'qty'        => $detail->qty,
                    'ref_id'     => $penjualan->id,
                    'keterangan' => 'Penjualan ' . $penjualan->no_faktur,
                    'created_at' => $penjualan->tanggal,
                ]);
            }
        }

        // --- Stok Opname (tipe: opname) ---
        $opname = StokOpname::create([
            'user_id'     => $user->id,
            'tanggal'     => '2026-04-15',
            'keterangan'  => 'Stok opname bulanan April 2026',
        ]);

        // Hitung stok sistem per barang berdasarkan stoklog yang sudah ada
        foreach ($barangs as $barang) {
            $stokIn     = StokLog::where('barang_id', $barang->id)->where('tipe', 'in')->sum('qty');
            $stokOut    = StokLog::where('barang_id', $barang->id)->where('tipe', 'out')->sum('qty');
            $stokSistem = $stokIn - $stokOut;

            // Simulasi stok fisik: sebagian kecil ada selisih
            $stokFisik = match ($barang->sku) {
                'MKN-001' => $stokSistem - 2,   // selisih kurang 2
                'MNM-003' => $stokSistem + 1,   // selisih lebih 1
                'ELK-001' => $stokSistem - 1,   // selisih kurang 1
                default   => $stokSistem,        // sesuai
            };

            $selisih = $stokFisik - $stokSistem;

            StokOpnameDetail::create([
                'stok_opname_id' => $opname->id,
                'barang_id'      => $barang->id,
                'stok_sistem'    => $stokSistem,
                'stok_fisik'     => $stokFisik,
                'selisih'        => $selisih,
            ]);

            // Catat log opname jika ada selisih
            if ($selisih !== 0) {
                StokLog::create([
                    'barang_id'  => $barang->id,
                    'user_id'    => $user->id,
                    'tipe'       => 'opname',
                    'qty'        => $selisih,
                    'ref_id'     => $opname->id,
                    'keterangan' => 'Penyesuaian stok opname April 2026',
                    'created_at' => '2026-04-15 08:00:00',
                ]);
            }
        }
    }
}

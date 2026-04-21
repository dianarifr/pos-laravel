<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PembelianSeeder extends Seeder
{
    public function run(): void
    {
        $user      = User::where('email', 'dian@mail.com')->first();
        $suppliers = Supplier::all()->keyBy('nama');
        $barangs   = Barang::all()->keyBy('sku');

        $transaksi = [
            [
                'supplier'  => 'CV. Maju Bersama',
                'no_nota'   => 'PO-20260325-001',
                'tanggal'   => '2026-03-25 08:00:00',
                'items'     => [
                    ['sku' => 'MKN-001', 'qty' => 100, 'diskon' => 0],
                    ['sku' => 'MKN-002', 'qty' => 200, 'diskon' => 0],
                    ['sku' => 'MKN-003', 'qty' => 50, 'diskon' => 0],
                ],
            ],
            [
                'supplier'  => 'PT. Sumber Makmur',
                'no_nota'   => 'PO-20260326-001',
                'tanggal'   => '2026-03-26 09:30:00',
                'items'     => [
                    ['sku' => 'MNM-001', 'qty' => 30, 'diskon' => 0],
                    ['sku' => 'MNM-002', 'qty' => 20, 'diskon' => 10000],
                    ['sku' => 'MNM-003', 'qty' => 500, 'diskon' => 0],
                ],
            ],
            [
                'supplier'  => 'UD. Berkah Jaya',
                'no_nota'   => 'PO-20260401-001',
                'tanggal'   => '2026-04-01 07:00:00',
                'items'     => [
                    ['sku' => 'ELK-001', 'qty' => 50, 'diskon' => 0],
                    ['sku' => 'ELK-002', 'qty' => 100, 'diskon' => 0],
                ],
            ],
            [
                'supplier'  => 'CV. Agro Sejahtera',
                'no_nota'   => 'PO-20260402-001',
                'tanggal'   => '2026-04-02 08:30:00',
                'items'     => [
                    ['sku' => 'PKN-001', 'qty' => 60, 'diskon' => 0],
                    ['sku' => 'PRL-001', 'qty' => 40, 'diskon' => 0],
                ],
            ],
            [
                'supplier'  => 'PT. Nusantara Distribusi',
                'no_nota'   => 'PO-20260410-001',
                'tanggal'   => '2026-04-10 10:00:00',
                'items'     => [
                    ['sku' => 'MKN-001', 'qty' => 50, 'diskon' => 50000],
                    ['sku' => 'MNM-003', 'qty' => 300, 'diskon' => 0],
                    ['sku' => 'MKN-002', 'qty' => 100, 'diskon' => 0],
                ],
            ],
        ];

        foreach ($transaksi as $data) {
            $supplier   = $suppliers->get($data['supplier']);
            $totalHarga = 0;

            $pembelian = Pembelian::firstOrCreate(
                ['no_nota' => $data['no_nota']],
                [
                    'supplier_id' => $supplier->id,
                    'user_id'     => $user->id,
                    'total_harga' => 0,
                    'tanggal'     => $data['tanggal'],
                    'status_pembayaran' => 'tunai',
                    'tanggal_jatuh_tempo' => null,
                ]
            );

            // Hapus detail lama jika seeder dijalankan ulang
            $pembelian->details()->delete();

            foreach ($data['items'] as $item) {
                $barang = $barangs->get($item['sku']);
                if (! $barang) {
                    continue;
                }

                $subtotal = ($barang->harga_beli * $item['qty']) - $item['diskon'];
                $totalHarga += $subtotal;

                PembelianDetail::create([
                    'pembelian_id' => $pembelian->id,
                    'barang_id'    => $barang->id,
                    'qty'          => $item['qty'],
                    'harga_beli'   => $barang->harga_beli,
                    'diskon'       => $item['diskon'],
                    'subtotal'     => $subtotal,
                ]);
            }

            $pembelian->update(['total_harga' => $totalHarga]);
        }
    }
}

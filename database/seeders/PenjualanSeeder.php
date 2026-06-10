<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class PenjualanSeeder extends Seeder
{
    public function run(): void
    {
        $user      = User::where('email', 'dian@mail.com')->first();
        $customers = Customer::all()->keyBy('nama');
        $barangs   = Barang::all()->keyBy('sku');

        $transaksi = [
            [
                'customer'   => 'Budi Santoso',
                'no_faktur'  => 'INV-20260401-001',
                'tanggal'    => '2026-04-01 09:15:00',
                'items'      => [
                    ['sku' => 'MKN-001', 'qty' => 5, 'diskon' => 0],
                    ['sku' => 'MNM-003', 'qty' => 10, 'diskon' => 0],
                ],
            ],
            [
                'customer'   => 'Siti Rahayu',
                'no_faktur'  => 'INV-STR-000001',
                'tanggal'    => '2026-04-02 10:30:00',
                'items'      => [
                    ['sku' => 'MNM-002', 'qty' => 2, 'diskon' => 2000],
                    ['sku' => 'MKN-003', 'qty' => 1, 'diskon' => 0],
                    ['sku' => 'ELK-002', 'qty' => 3, 'diskon' => 0],
                ],
            ],
            [
                'customer'   => 'Umum',
                'no_faktur'  => 'INV-STR-000002',
                'tanggal'    => '2026-04-03 14:00:00',
                'items'      => [
                    ['sku' => 'MKN-002', 'qty' => 5, 'diskon' => 0],
                    ['sku' => 'MNM-003', 'qty' => 5, 'diskon' => 0],
                ],
            ],
            [
                'customer'   => 'Agus Permadi',
                'no_faktur'  => 'INV-STR-000003',
                'tanggal'    => '2026-04-05 11:20:00',
                'items'      => [
                    ['sku' => 'ELK-001', 'qty' => 3, 'diskon' => 5000],
                    ['sku' => 'PRL-001', 'qty' => 2, 'diskon' => 0],
                ],
            ],
            [
                'customer'   => 'Dewi Lestari',
                'no_faktur'  => 'INV-STR-000004',
                'tanggal'    => '2026-04-07 16:45:00',
                'items'      => [
                    ['sku' => 'PKN-001', 'qty' => 2, 'diskon' => 10000],
                    ['sku' => 'MKN-001', 'qty' => 3, 'diskon' => 0],
                    ['sku' => 'MNM-001', 'qty' => 1, 'diskon' => 0],
                ],
            ],
        ];

        foreach ($transaksi as $data) {
            $customer   = $customers->get($data['customer']);
            $totalHarga = 0;

            $penjualan = Penjualan::firstOrCreate(
                ['no_faktur' => $data['no_faktur']],
                [
                    'customer_id' => $customer?->id,
                    'user_id'     => $user->id,
                    'total_harga' => 0,
                    'tanggal'     => $data['tanggal'],
                ]
            );

            // Hapus detail lama jika seeder dijalankan ulang
            $penjualan->details()->delete();

            foreach ($data['items'] as $item) {
                $barang  = $barangs->get($item['sku']);
                if (! $barang) {
                    continue;
                }

                $subtotal = ($barang->harga_jual * $item['qty']) - $item['diskon'];
                $totalHarga += $subtotal;

                PenjualanDetail::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id'    => $barang->id,
                    'qty'          => $item['qty'],
                    'harga_jual'   => $barang->harga_jual,
                    'diskon'       => $item['diskon'],
                    'subtotal'     => $subtotal,
                ]);
            }

            $penjualan->update(['total_harga' => $totalHarga]);
        }
    }
}

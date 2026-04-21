<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class PenjualanPrintController extends Controller
{
    public function __invoke(Penjualan $penjualan): View
    {
        $penjualan->load(['details.barang.unit', 'customer', 'user']);

        $settings = [
            'logo'         => Setting::get('logo'),
            'nama_toko'    => Setting::get('nama_toko', 'Toko Kami'),
            'alamat_toko'  => Setting::get('alamat_toko', '-'),
            'no_hp_toko'   => Setting::get('no_hp_toko', '-'),
            'pesan_faktur' => Setting::get('pesan_faktur', Setting::get('footer_nota', 'Terima kasih telah berbelanja!')),
        ];

        $logoUrl = $settings['logo'] ? Storage::disk('public')->url($settings['logo']) : null;
        $totalItem = (int) $penjualan->details->sum('qty');
        $totalDiskon = (int) $penjualan->details->sum('diskon');
        $grandTotal = (int) $penjualan->total_harga;
        $nominalBayar = (int) $penjualan->nominal_bayar;
        $selisih = $nominalBayar - $grandTotal;

        return view('penjualan.print', [
            'penjualan'    => $penjualan,
            'settings'     => $settings,
            'logoUrl'      => $logoUrl,
            'totalItem'    => $totalItem,
            'totalDiskon'  => $totalDiskon,
            'grandTotal'   => $grandTotal,
            'nominalBayar' => $nominalBayar,
            'kembalian'    => max(0, $selisih),
            'sisaTagihan'  => max(0, (int) $penjualan->sisa_bayar),
            'terbilang'    => ucfirst(trim($this->terbilang($grandTotal))) . ' Rupiah',
        ]);
    }

    private function terbilang(int $nilai): string
    {
        if ($nilai === 0) {
            return 'nol';
        }

        $angka = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($nilai < 12) {
            return $angka[$nilai];
        }

        if ($nilai < 20) {
            return $this->terbilang($nilai - 10) . ' belas';
        }

        if ($nilai < 100) {
            return $this->terbilang((int) floor($nilai / 10)) . ' puluh ' . $this->terbilang($nilai % 10);
        }

        if ($nilai < 200) {
            return 'seratus ' . $this->terbilang($nilai - 100);
        }

        if ($nilai < 1000) {
            return $this->terbilang((int) floor($nilai / 100)) . ' ratus ' . $this->terbilang($nilai % 100);
        }

        if ($nilai < 2000) {
            return 'seribu ' . $this->terbilang($nilai - 1000);
        }

        if ($nilai < 1000000) {
            return $this->terbilang((int) floor($nilai / 1000)) . ' ribu ' . $this->terbilang($nilai % 1000);
        }

        if ($nilai < 1000000000) {
            return $this->terbilang((int) floor($nilai / 1000000)) . ' juta ' . $this->terbilang($nilai % 1000000);
        }

        if ($nilai < 1000000000000) {
            return $this->terbilang((int) floor($nilai / 1000000000)) . ' miliar ' . $this->terbilang($nilai % 1000000000);
        }

        return $this->terbilang((int) floor($nilai / 1000000000000)) . ' triliun ' . $this->terbilang($nilai % 1000000000000);
    }
}

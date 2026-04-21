<?php

namespace App\Livewire;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class KasirPos extends Component
{
    // Scanner
    public string $sku = '';

    // Keranjang: array of ['barang_id', 'sku', 'nama_barang', 'harga_jual', 'qty', 'diskon', 'subtotal']
    public array $keranjang = [];

    // Pembayaran
    public string $bayar = '';

    // Customer
    public ?int $customerId = null;
    public string $customerSearch = '';

    // Flash message
    public ?string $flashError = null;

    // No faktur
    public string $noFaktur = '';

    public function mount(): void
    {
        $this->noFaktur = $this->generateNoFaktur();
    }

    // -------------------------------------------------------
    // Computed Properties
    // -------------------------------------------------------

    #[Computed]
    public function totalItems(): int
    {
        return collect($this->keranjang)->sum('qty');
    }

    #[Computed]
    public function totalDiskon(): int
    {
        return (int) collect($this->keranjang)->sum('diskon');
    }

    #[Computed]
    public function grandTotal(): int
    {
        return (int) collect($this->keranjang)->sum('subtotal');
    }

    #[Computed]
    public function kembali(): int
    {
        $bayar = (int) preg_replace('/\D/', '', $this->bayar);
        return max(0, $bayar - $this->grandTotal);
    }

    #[Computed]
    public function customers(): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::query()
            ->when($this->customerSearch, fn($q) => $q->where('nama', 'like', '%' . $this->customerSearch . '%'))
            ->orderBy('nama')
            ->limit(20)
            ->get(['id', 'nama', 'no_hp']);
    }

    #[Computed]
    public function selectedCustomer(): ?Customer
    {
        return $this->customerId ? Customer::find($this->customerId) : null;
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $this->customerSearch = '';
    }

    public function clearCustomer(): void
    {
        $this->customerId = null;
        $this->customerSearch = '';
    }

    // -------------------------------------------------------
    // Scanner: scan/ketik SKU
    // -------------------------------------------------------

    public function scanBarcode(): void
    {
        $sku = trim($this->sku);
        $this->sku = '';

        if ($sku === '') {
            return;
        }

        $barang = Barang::where('sku', $sku)->first();

        if (! $barang) {
            $this->flashError = "Barang dengan SKU «{$sku}» tidak ditemukan.";
            $this->dispatch('focus-scanner');
            return;
        }

        if ($barang->stok <= 0) {
            $this->flashError = "Stok barang «{$barang->nama_barang}» habis.";
            $this->dispatch('focus-scanner');
            return;
        }

        $this->flashError = null;
        $this->tambahKeKeranjang($barang);
        $this->dispatch('focus-scanner');
    }

    private function tambahKeKeranjang(Barang $barang): void
    {
        foreach ($this->keranjang as $i => $item) {
            if ($item['barang_id'] === $barang->id) {
                $this->keranjang[$i]['qty']++;
                $this->hitungSubtotal($i);
                return;
            }
        }

        $this->keranjang[] = [
            'barang_id'   => $barang->id,
            'sku'         => $barang->sku,
            'nama_barang' => $barang->nama_barang,
            'harga_jual'  => (int) $barang->harga_jual,
            'qty'         => 1,
            'diskon'      => 0,
            'subtotal'    => (int) $barang->harga_jual,
        ];
    }

    public function updateQty(int $index, int $qty): void
    {
        if ($qty < 1) {
            $this->hapusItem($index);
            return;
        }
        $this->keranjang[$index]['qty'] = $qty;
        $this->hitungSubtotal($index);
    }

    public function updateDiskon(int $index, int $diskon): void
    {
        $this->keranjang[$index]['diskon'] = max(0, $diskon);
        $this->hitungSubtotal($index);
    }

    public function hapusItem(int $index): void
    {
        array_splice($this->keranjang, $index, 1);
    }

    private function hitungSubtotal(int $index): void
    {
        $item = $this->keranjang[$index];
        $this->keranjang[$index]['subtotal'] =
            ($item['harga_jual'] * $item['qty']) - $item['diskon'];
    }

    // -------------------------------------------------------
    // Simpan Transaksi
    // -------------------------------------------------------

    public function simpan(): void
    {
        if (empty($this->keranjang)) {
            $this->flashError = 'Keranjang masih kosong.';
            return;
        }

        $bayarInt   = (int) preg_replace('/\D/', '', $this->bayar);
        $isBelumLunas = $bayarInt < $this->grandTotal;

        // Validasi: transaksi belum lunas wajib memiliki customer
        if ($isBelumLunas && ! $this->customerId) {
            $this->flashError = 'Transaksi piutang (belum lunas) wajib memilih customer terlebih dahulu.';
            return;
        }

        $status    = $isBelumLunas ? 'belum_lunas' : 'lunas';
        $sisaBayar = $isBelumLunas ? ($this->grandTotal - $bayarInt) : 0;

        $penjualanId = null;

        DB::transaction(function () use ($bayarInt, $status, $sisaBayar, &$penjualanId) {
            $penjualan = Penjualan::create([
                'user_id'       => Auth::id(),
                'customer_id'   => $this->customerId,
                'no_faktur'     => $this->noFaktur,
                'total_harga'   => $this->grandTotal,
                'nominal_bayar' => $bayarInt,
                'sisa_bayar'    => $sisaBayar,
                'status'        => $status,
                'tanggal'       => now(),
            ]);

            foreach ($this->keranjang as $item) {
                PenjualanDetail::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id'    => $item['barang_id'],
                    'qty'          => $item['qty'],
                    'harga_jual'   => $item['harga_jual'],
                    'diskon'       => $item['diskon'],
                    'subtotal'     => $item['subtotal'],
                ]);
                // Stok diupdate otomatis via PenjualanDetailObserver
            }

            $penjualanId = $penjualan->id;
        });

        // Kirim notifikasi sesuai status
        if ($isBelumLunas) {
            \Filament\Notifications\Notification::make()
                ->title('Transaksi Disimpan sebagai Piutang')
                ->warning()
                ->body('Sisa tagihan sebesar Rp ' . number_format($sisaBayar, 0, ',', '.') . ' telah dicatat.')
                ->send();
        }

        $this->reset(['keranjang', 'bayar', 'flashError', 'sku', 'customerId', 'customerSearch']);
        $this->noFaktur = $this->generateNoFaktur();
        $this->dispatch('transaksi-sukses');
        $this->dispatch('open-print-layout', id: $penjualanId);
        $this->dispatch('focus-scanner');
    }

    public function batalTransaksi(): void
    {
        $this->reset(['keranjang', 'bayar', 'flashError', 'sku', 'customerId', 'customerSearch']);
        $this->noFaktur = $this->generateNoFaktur();
        $this->dispatch('focus-scanner');
    }

    public function dismissError(): void
    {
        $this->flashError = null;
        $this->dispatch('focus-scanner');
    }

    private function generateNoFaktur(): string
    {
        $prefix = 'INV/' . now()->format('Ymd') . '/';
        $last = Penjualan::where('no_faktur', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('no_faktur');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.kasir-pos')
            ->layout('layouts.kasir');
    }
}

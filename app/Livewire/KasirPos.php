<?php

namespace App\Livewire;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class KasirPos extends Component
{
    // Scanner
    public string $sku = '';

    // Keranjang: array of ['barang_id', 'sku', 'nama_barang', 'harga_jual', 'harga_beli', 'qty', 'diskon', 'subtotal']
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
        return collect($this->keranjang)->sum(fn ($item) => (int) $item['qty']);
    }

    #[Computed]
    public function totalDiskon(): int
    {
        return collect($this->keranjang)->sum(fn ($item) => (int) $item['diskon']);
    }

    #[Computed]
    public function grandTotal(): int
    {
        return (int) collect($this->keranjang)->sum(fn ($item) => (int) $item['subtotal']);
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

    #[Computed]
    public function searchResults()
    {
        if (strlen($this->sku) < 2) {
            return collect();
        }

        return Barang::query()
            ->where(
                fn($q) => $q->where('nama_barang', 'like', "%{$this->sku}%")
                    ->orWhere('sku', 'like', "%{$this->sku}%")
            )
            ->where('stok', '>', 0)
            ->orderBy('nama_barang')
            ->limit(10)
            ->get();
    }

    public function scanBarcode(): void
    {
        $sku = trim($this->sku);

        if ($sku === '') {
            return;
        }

        // Cari berdasarkan SKU atau nama. Jika ada 1 hasil, langsung tambahkan ke keranjang.
        $results = Barang::query()
            ->where(
                fn($q) => $q->where('nama_barang', 'like', "%{$sku}%")
                    ->orWhere('sku', 'like', "%{$sku}%")
            )->get();

        if ($results->count() === 1) {
            $this->pilihBarang($results->first()->id);
        } elseif ($results->isEmpty()) {
            $this->flashError = "Barang dengan kata kunci «{$sku}» tidak ditemukan.";
            $this->dispatch('focus-scanner');
        }
        // Jika lebih dari 1, biarkan pengguna memilih dari daftar autocomplete.
    }

    private function tambahKeKeranjang(Barang $barang): void
    {
        foreach ($this->keranjang as $i => $item) {
            if ($item['barang_id'] === $barang->id) {
                $currentQty = $this->keranjang[$i]['qty'] === '' ? 0 : (int) $this->keranjang[$i]['qty'];
                $qtyDibutuhkan = $currentQty + 1;

                if (!$this->cekStokCukup($barang, $qtyDibutuhkan)) {
                    $this->dispatch('focus-scanner');
                    return;
                }
                $this->keranjang[$i]['qty']++;
                $this->hitungSubtotal($i);
                return;
            }
        }

        $this->keranjang[] = [
            'barang_id'   => $barang->id,
            'sku'         => $barang->sku,
            'nama_barang' => $barang->nama_barang,
            'harga_jual'  => 0,
            'harga_beli'  => (int) $barang->harga_beli,
            'qty'         => 1,
            'diskon'      => 0,
            'subtotal'    => 0,
        ];
    }

    public function pilihBarang(int $barangId): void
    {
        $barang = Barang::find($barangId);

        if (!$barang) {
            $this->flashError = "Barang tidak ditemukan.";
            return;
        }

        if (!$this->cekStokCukup($barang, 1)) {
            return;
        }

        $this->flashError = null;
        $this->tambahKeKeranjang($barang);
        $this->sku = ''; // Kosongkan input setelah memilih
        $this->dispatch('focus-scanner');
    }

    public function updateQty(int $index, $qty): void
    {
        if ($qty === '' || $qty === null) {
            $this->keranjang[$index]['qty'] = '';
            $this->keranjang[$index]['subtotal'] = 0;
            return;
        }

        $qty = (int) $qty;

        if ($qty < 1) {
            $this->hapusItem($index);
            return;
        }

        // Cek stok sebelum menambah qty
        $barang = Barang::find($this->keranjang[$index]['barang_id']);
        if ($barang && !$this->cekStokCukup($barang, $qty)) {
            $this->keranjang[$index]['qty'] = $qty;
            $this->dispatch('focus-scanner');
            return;
        }

        $this->keranjang[$index]['qty'] = $qty;
        $this->hitungSubtotal($index);
        $this->flashError = null;
    }

    public function updateHargaJual(int $index, $harga): void
    {
        $harga = (int) preg_replace('/\D/', '', (string) $harga);
        $this->keranjang[$index]['harga_jual'] = max(0, $harga);
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

    private function cekStokCukup(Barang $barang, int $qtyDibutuhkan): bool
    {
        if ($barang->stok < $qtyDibutuhkan) {
            $this->flashError = "Stok barang «{$barang->nama_barang}» tidak mencukupi. Sisa stok: {$barang->stok}.";
            return false;
        }
        return true;
    }

    private function hitungSubtotal(int $index): void
    {
        $item = $this->keranjang[$index];
        $qty = $item['qty'] === '' ? 0 : (int) $item['qty'];
        $diskon = $item['diskon'] === '' ? 0 : (int) $item['diskon'];
        $this->keranjang[$index]['subtotal'] = ($item['harga_jual'] * $qty) - $diskon;
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


        foreach ($this->keranjang as $item) {
            if ($item['qty'] === '' || $item['qty'] === null || (int) $item['qty'] < 1) {
                $this->flashError = "Jumlah (QTY) untuk barang «{$item['nama_barang']}» tidak boleh kosong.";
                $this->dispatch('focus-scanner');
                return;
            }
            if ($item['harga_jual'] === '' || $item['harga_jual'] === null || (int) $item['harga_jual'] < 1) {
                $this->flashError = "Harga untuk barang «{$item['nama_barang']}» tidak boleh kosong.";
                $this->dispatch('focus-scanner');
                return;
            }
            $barang = Barang::find($item['barang_id']);
            if (!$barang) {
                $this->flashError = "Barang «{$item['nama_barang']}» tidak ditemukan. Transaksi dibatalkan.";
                $this->dispatch('focus-scanner');
                return;
            }
            if (!$this->cekStokCukup($barang, (int) $item['qty'])) {
                $this->flashError .= ' Transaksi dibatalkan.';
                $this->dispatch('focus-scanner');
                return;
            }
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

        try {
             DB::transaction(function () use ($bayarInt, $status, $sisaBayar, &$penjualanId) {
                foreach ($this->keranjang as $item) {
                    $barang = Barang::where('id', $item['barang_id'])->lockForUpdate()->first();

                    if (!$barang || !$this->cekStokCukup($barang, $item['qty'])) {
                        throw new \Exception("Stok barang «{$item['nama_barang']}» tidak mencukupi atau tidak ditemukan. Transaksi dibatalkan.");
                    }
                }

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
                        'harga_beli'   => $item['harga_beli'],
                        'diskon'       => $item['diskon'],
                        'subtotal'     => $item['subtotal'],
                    ]);
                    // Stok diupdate otomatis via PenjualanDetailObserver
                }

                $penjualanId = $penjualan->id;
            });
        } catch (\Exception $e) {
            // Tangkap exception jika stok tidak cukup saat antrean lock berjalan
            $this->flashError = $e->getMessage();
            $this->dispatch('focus-scanner');
            return;
        }

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
        $lastThisMonth = Penjualan::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->orderByDesc('id')
            ->value('no_faktur');

        $seq = $lastThisMonth ? ((int) substr($lastThisMonth, -6)) + 1 : 1;

        return 'INV-STR-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.kasir-pos')
            ->layout('layouts.kasir');
    }
}

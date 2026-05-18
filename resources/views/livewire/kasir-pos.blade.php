<div class="flex flex-col h-screen select-none" x-data="kasirPos()" x-init="init()"
    @keydown.f12.window.prevent="focusBayar()" @keydown.f1.window.prevent="focusScanner()"
    @keydown.f2.window.prevent="focusCustomer()" @keydown.f10.window.prevent="konfirmSimpan()"
    @keydown.escape.window="showKonfirm ? tutupKonfirm() : $wire.batalTransaksi()"
    @transaksi-sukses.window="showSukses()" @focus-scanner.window="focusScanner()">

    {{-- ════════════════════════════════════════════ --}}
    {{-- HEADER HUD --}}
    {{-- ════════════════════════════════════════════ --}}
    <div class="shrink-0 bg-gray-900 border-b border-gray-700 px-6 py-3">
        <div class="flex gap-6">

            {{-- Sisi Kiri: Metadata --}}
            <div class="flex-1 space-y-1 text-sm font-mono">
                <div class="flex gap-2">
                    <span class="text-gray-400 w-28">NO. FAKTUR</span>
                    <span class="text-white font-bold">: {{ $noFaktur }}</span>
                </div>
                <div class="flex gap-2">
                    <span class="text-gray-400 w-28">KASIR</span>
                    <span class="text-white font-bold">: {{ auth()->user()->name }}</span>
                </div>
                <div class="flex gap-2">
                    <span class="text-gray-400 w-28">TGL &amp; JAM</span>
                    <span class="text-white font-bold">:
                        <span x-text="waktu" class="tabular-nums"></span>
                    </span>
                </div>
                {{-- Customer Dropdown --}}
                <div class="flex gap-2 items-start pt-0.5" x-data="{ open: false, highlighted: -1 }"
                    @click.outside="open = false; highlighted = -1"
                    @focus-customer.window="$nextTick(() => { $refs.customerInput?.focus(); open = true; })">
                    <span class="text-gray-400 w-28 shrink-0 mt-1">CUSTOMER</span>
                    <div class="flex-1 relative">
                        @if ($this->selectedCustomer)
                        {{-- Customer terpilih --}}
                        <div class="flex items-center gap-2">
                            <span class="text-emerald-400 font-bold">: {{ $this->selectedCustomer->nama }}</span>
                            <span class="text-gray-500 text-xs">({{ $this->selectedCustomer->no_hp }})</span>
                            <button wire:click="clearCustomer"
                                class="text-red-400 hover:text-red-300 text-xs ml-1">✕</button>
                        </div>
                        @else
                        <span class="text-gray-500 absolute left-0 top-1 pointer-events-none">:</span>
                        <input id="input-customer" x-ref="customerInput" type="text"
                            wire:model.live.debounce.300ms="customerSearch" @focus="open = true"
                            @input="highlighted = -1" @keydown.escape.prevent="open = false; highlighted = -1"
                            @keydown.arrow-down.prevent="
                                    open = true;
                                    highlighted = Math.min(highlighted + 1, $refs.dropdown.children.length - 1);
                                    $refs.dropdown.children[highlighted]?.scrollIntoView({ block: 'nearest' });
                                " @keydown.arrow-up.prevent="
                                    highlighted = Math.max(highlighted - 1, -1);
                                    if (highlighted >= 0) $refs.dropdown.children[highlighted]?.scrollIntoView({ block: 'nearest' });
                                " @keydown.enter.prevent="
                                    if (highlighted >= 0 && $refs.dropdown.children[highlighted]) {
                                        $refs.dropdown.children[highlighted].click();
                                    }
                                " placeholder="Cari nama customer... (F2)"
                            class="ml-3 bg-gray-800 border border-gray-600 text-white text-xs rounded px-2 py-1 w-52 focus:outline-none focus:border-blue-400 placeholder-gray-600"
                            autocomplete="off">
                        {{-- Dropdown hasil pencarian --}}
                        <div x-ref="dropdown" x-show="open && {{ count($this->customers) }} > 0"
                            class="absolute left-3 top-7 z-50 bg-gray-800 border border-gray-600 rounded shadow-xl w-64 max-h-48 overflow-y-auto"
                            style="display:none">
                            @foreach ($this->customers as $index => $c)
                            <div wire:click="selectCustomer({{ $c->id }})" @click="open = false; highlighted = -1"
                                :class="highlighted === {{ $index }} ? 'bg-blue-700' : 'hover:bg-gray-700'"
                                class="px-3 py-2 cursor-pointer border-b border-gray-700 last:border-0">
                                <div class="text-white text-xs font-bold">{{ $c->nama }}</div>
                                <div class="text-gray-400 text-xs">{{ $c->no_hp }}</div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Divider --}}
            <div class="w-px bg-gray-700"></div>

            {{-- Sisi Kanan: Financial HUD --}}
            <div class="flex-1 flex flex-col justify-center items-end gap-0.5">
                {{-- Baris 1: Items & Diskon --}}
                <div class="flex gap-6 text-xs text-gray-400 font-mono">
                    <span>ITEMS: <span class="text-gray-200 font-bold">{{ $this->totalItems }}</span></span>
                    <span>TOTAL DISKON: <span class="text-yellow-300 font-bold">Rp {{ number_format($this->totalDiskon,
                            0, ',', '.') }}</span></span>
                </div>

                {{-- Baris 2: Grand Total --}}
                <div class="text-4xl font-black text-white tabular-nums leading-none">
                    Rp {{ number_format($this->grandTotal, 0, ',', '.') }}
                </div>

                {{-- Baris 3: Bayar --}}
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-400 font-mono">BAYAR</span>
                    <input id="input-bayar" type="text" x-model="bayarDisplay" @input="syncBayar()"
                        @keydown.enter.prevent="" inputmode="numeric"
                        class="bg-gray-800 border border-yellow-500 text-yellow-300 font-bold text-xl tabular-nums text-right rounded px-3 py-1 w-48 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        placeholder="0">
                </div>

                {{-- Baris 4: Kembalian / Kekurangan --}}
                @php
                $bayarInt = (int) preg_replace('/\D/', '', $this->bayar);
                $selisihHutang = max(0, $this->grandTotal - $bayarInt);
                @endphp
                @if ($selisihHutang > 0 && $this->grandTotal > 0)
                <div class="flex items-center gap-2">
                    <span class="text-xs text-red-400 font-mono font-bold">KEKURANGAN</span>
                    <span class="text-2xl font-black text-red-400 tabular-nums">
                        Rp {{ number_format($selisihHutang, 0, ',', '.') }}
                    </span>
                </div>
                @else
                <div class="flex items-center gap-2">
                    <span class="text-xs text-emerald-400 font-mono font-bold">KEMBALIAN</span>
                    <span class="text-2xl font-black text-emerald-400 tabular-nums">
                        Rp {{ number_format($this->kembali, 0, ',', '.') }}
                    </span>
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ════════════════════════════════════════════ --}}
    {{-- FLASH ERROR --}}
    {{-- ════════════════════════════════════════════ --}}
    @if ($flashError)
    <div class="shrink-0 bg-red-600 text-white text-center py-2 px-4 font-bold text-sm flex items-center justify-between cursor-pointer"
        wire:click="dismissError">
        <span>⚠ {{ $flashError }}</span>
        <span class="text-xs text-red-200">[ klik untuk tutup ]</span>
    </div>
    @endif

    {{-- ════════════════════════════════════════════ --}}
    {{-- SUKSES TOAST --}}
    {{-- ════════════════════════════════════════════ --}}
    <div x-show="sukses" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="shrink-0 bg-emerald-600 text-white text-center py-2 px-4 font-bold text-sm" style="display: none;">
        ✔ Transaksi Berhasil Disimpan!
    </div>

    {{-- ════════════════════════════════════════════ --}}
    {{-- SCANNER AREA --}}
    {{-- ════════════════════════════════════════════ --}}
    <div class="shrink-0 bg-gray-900 border-b border-gray-700 px-6 py-3">
        <div class="flex items-center gap-3">
            <span class="text-gray-400 text-xs font-mono shrink-0">SCAN / SKU</span>
            <div class="flex-1 relative" x-data="{
                highlightedIndex: -1,
                get results() { return this.$refs.results ? Array.from(this.$refs.results.children) : [] },
                get open() { return $wire.get('sku').length >= 2 && this.results.length > 0 },
                moveDown() {
                    if (!this.open) return;
                    this.highlightedIndex = (this.highlightedIndex < this.results.length - 1) ? this.highlightedIndex + 1 : 0;
                    this.scrollToHighlighted();
                },
                moveUp() {
                    if (!this.open) return;
                    this.highlightedIndex = (this.highlightedIndex > 0) ? this.highlightedIndex - 1 : this.results.length - 1;
                    this.scrollToHighlighted();
                },
                selectItem() {
                    if (this.highlightedIndex > -1) {
                        this.results[this.highlightedIndex].click();
                    } else {
                        $wire.scanBarcode();
                    }
                    this.highlightedIndex = -1;
                },
                scrollToHighlighted() {
                    this.results[this.highlightedIndex]?.scrollIntoView({ block: 'nearest' });
                },
                resetHighlight() { this.highlightedIndex = -1; }
            }">
                <input id="input-scanner" type="text" wire:model.live.debounce.300ms="sku"
                    @input.debounce.350ms="resetHighlight()" @keydown.arrow-down.prevent="moveDown()"
                    @keydown.arrow-up.prevent="moveUp()" @keydown.enter.prevent="selectItem()"
                    @keydown.escape.prevent="resetHighlight(); $el.blur()"
                    class="w-full bg-gray-800 border-2 border-blue-500 text-white text-xl font-mono rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-400 placeholder-gray-600"
                    placeholder="Scan/Cari SKU atau Nama Barang..." autocomplete="off" autofocus>

                {{-- Dropdown hasil pencarian --}}
                @if (strlen($sku) >= 2 && $this->searchResults->isNotEmpty())
                <ul x-ref="results"
                    class="absolute top-full w-full mt-2 z-50 bg-gray-800 border border-gray-600 rounded-lg shadow-xl max-h-80 overflow-y-auto">
                    @foreach ($this->searchResults as $index => $barang)
                    <li wire:click="pilihBarang({{ $barang->id }})" wire:key="search-{{ $barang->id }}"
                        :class="{ 'bg-blue-700': highlightedIndex === {{ $index }} }"
                        @mouseenter="highlightedIndex = {{ $index }}"
                        class="px-4 py-3 cursor-pointer hover:bg-blue-700 border-b border-gray-700 last:border-0">
                        <p class="font-semibold text-white">{{ $barang->nama_barang }}</p>
                        <p class="text-sm text-gray-400 font-mono">SKU: {{ $barang->sku }} - Stok: {{ $barang->stok }}
                        </p>
                    </li>
                    @endforeach
                </ul>
                @elseif(strlen($sku) >= 2 && $this->searchResults->isEmpty() && !$flashError)
                <div
                    class="absolute top-full w-full mt-2 z-50 bg-gray-800 border border-gray-600 rounded-lg shadow-xl p-4 text-gray-400 text-center">
                    Barang tidak ditemukan.
                </div>
                @endif
            </div>
            <span class="text-gray-600 text-xs font-mono shrink-0">F1</span>
        </div>
    </div>

    {{-- ════════════════════════════════════════════ --}}
    {{-- DAFTAR BELANJA --}}
    {{-- ════════════════════════════════════════════ --}}
    <div class="flex-1 overflow-hidden flex flex-col bg-white">

        {{-- Sticky Header --}}
        <div
            class="shrink-0 bg-gray-800 text-gray-300 text-xs font-bold uppercase font-mono px-4 py-2 grid grid-cols-12 gap-2">
            <div class="col-span-1 text-center">No</div>
            <div class="col-span-4">Nama Barang</div>
            <div class="col-span-1 text-center">Qty</div>
            <div class="col-span-2 text-right">Harga</div>
            <div class="col-span-2 text-right">Diskon</div>
            <div class="col-span-1 text-right">Subtotal</div>
            <div class="col-span-1 text-center">Hapus</div>
        </div>

        {{-- Tabel Keranjang --}}
        <div class="flex-1 overflow-y-auto scroll-thin bg-white">
            @forelse ($keranjang as $i => $item)
            <div wire:key="keranjang-item-{{ $item['barang_id'] }}"
                class="grid grid-cols-12 gap-2 px-4 py-2 border-b border-gray-100 text-gray-800 text-sm items-center {{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                <div class="col-span-1 text-center text-gray-400 font-mono">{{ $i + 1 }}</div>
                <div class="col-span-4 font-medium">
                    <div>{{ $item['nama_barang'] }}</div>
                    <div class="text-xs text-gray-400 font-mono">{{ $item['sku'] }}</div>
                </div>
                <div class="col-span-1 flex items-center justify-center gap-1">
                    <button wire:click="updateQty({{ $i }}, {{ $item['qty'] - 1 }})"
                        class="w-6 h-6 bg-gray-200 hover:bg-gray-300 rounded text-center font-bold leading-none">−</button>
                    <input type="number" min="1" wire:input.debounce.300ms="updateQty({{ $i }}, $event.target.value)"
                        value="{{ $item['qty'] }}"
                        class="w-12 text-center font-bold tabular-nums border border-gray-300 rounded px-1 py-0.5 text-sm focus:outline-none focus:border-blue-400">
                    <button wire:click="updateQty({{ $i }}, {{ $item['qty'] + 1 }})"
                        class="w-6 h-6 bg-gray-200 hover:bg-gray-300 rounded text-center font-bold leading-none">+</button>
                </div>
                <div class="col-span-2 text-right font-mono text-sm">
                    {{ number_format($item['harga_jual'], 0, ',', '.') }}
                </div>
                <div class="col-span-2 text-right">
                    <input type="number" min="0" wire:input.debounce.500ms="updateDiskon({{ $i }}, $event.target.value)"
                        value="{{ $item['diskon'] }}"
                        class="w-full text-right border border-gray-300 rounded px-1 py-0.5 text-sm focus:outline-none focus:border-blue-400">
                </div>
                <div class="col-span-1 text-right font-bold tabular-nums text-sm">
                    {{ number_format($item['subtotal'], 0, ',', '.') }}
                </div>
                <div class="col-span-1 text-center">
                    <button wire:click="hapusItem({{ $i }})"
                        class="text-red-400 hover:text-red-600 text-xs font-bold px-2 py-1 rounded hover:bg-red-50">✕</button>
                </div>
            </div>
            @empty
            <div class="flex items-center justify-center h-full text-gray-300 text-lg font-mono py-16">
                — Keranjang kosong. Scan barang untuk memulai —
            </div>
            @endforelse
        </div>

        {{-- ════════════════════════════════════════════ --}}
        {{-- ACTION BAR (Footer) --}}
        {{-- ════════════════════════════════════════════ --}}
        <div class="shrink-0 bg-gray-900 border-t border-gray-700 px-6 py-3 flex items-center justify-between gap-4">
            <div class="text-xs text-gray-500 font-mono space-x-4">
                <span><kbd class="bg-gray-700 text-gray-300 px-1 rounded">F1</kbd> Scanner</span>
                <span><kbd class="bg-gray-700 text-gray-300 px-1 rounded">F2</kbd> Customer</span>
                <span><kbd class="bg-gray-700 text-gray-300 px-1 rounded">F12</kbd> Bayar</span>
                <span><kbd class="bg-gray-700 text-gray-300 px-1 rounded">F10</kbd> Simpan</span>
                <span><kbd class="bg-gray-700 text-gray-300 px-1 rounded">ESC</kbd> Batal</span>
            </div>
            <div class="flex gap-3">
                <button wire:click="batalTransaksi" wire:loading.attr="disabled"
                    class="px-5 py-2.5 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-lg text-sm disabled:opacity-50">
                    ✕ Batal (ESC)
                </button>
                <button @click="konfirmSimpan()" wire:loading.attr="disabled"
                    class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-lg text-sm tracking-wide disabled:opacity-50 disabled:cursor-not-allowed">
                    ✔ SIMPAN TRANSAKSI <span class="text-emerald-200 font-normal text-xs ml-1">(F10)</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════ --}}
    {{-- MODAL KONFIRMASI SIMPAN --}}
    {{-- ════════════════════════════════════════════ --}}
    <div x-show="showKonfirm" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
        style="display:none" @keydown.enter.window="if(showKonfirm) { tutupKonfirm(); $wire.simpan(); }">
        <div x-show="showKonfirm" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="bg-gray-900 border border-gray-600 rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
            <div class="text-center mb-6">
                <div class="text-5xl mb-3">🧾</div>
                <h2 class="text-xl font-black text-white">Konfirmasi Transaksi</h2>
                <p class="text-gray-400 text-sm mt-1">Pastikan semua data sudah benar sebelum menyimpan.</p>
            </div>

            {{-- Warning Belum Lunas / Piutang --}}
            @php
            $bayarKonfirm = (int) preg_replace('/\D/', '', $this->bayar);
            $isHutangKonfirm = $this->grandTotal > 0 && $bayarKonfirm < $this->grandTotal;
                @endphp
                @if ($isHutangKonfirm)
                <div class="bg-red-900/60 border border-red-500 rounded-xl px-4 py-3 mb-4 flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <p class="text-red-300 font-black text-sm">Transaksi ini akan dicatat sebagai BELUM LUNAS</p>
                        <p class="text-red-400 text-xs mt-0.5">
                            Kekurangan: <span class="font-bold">Rp {{ number_format($this->grandTotal - $bayarKonfirm,
                                0, ',', '.') }}</span>
                        </p>
                    </div>
                </div>
                @endif

                {{-- Ringkasan --}}
                <div class="bg-gray-800 rounded-xl p-4 mb-6 space-y-2 font-mono text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">No. Faktur</span>
                        <span class="text-white font-bold">{{ $noFaktur }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Customer</span>
                        <span class="text-white">{{ $this->selectedCustomer?->nama ?? '— Umum —' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Item</span>
                        <span class="text-white">{{ $this->totalItems }} item</span>
                    </div>
                    <div class="border-t border-gray-700 pt-2 flex justify-between">
                        <span class="text-gray-400">Grand Total</span>
                        <span class="text-yellow-300 font-black text-base">Rp {{ number_format($this->grandTotal, 0,
                            ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Bayar</span>
                        <span class="text-white font-bold">Rp {{ $bayar ? number_format((int) preg_replace('/\D/', '',
                            $bayar), 0, ',', '.') : '0' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Kembalian</span>
                        @if ($isHutangKonfirm)
                        <span class="text-red-400 font-black text-base">— KEKURANGAN —</span>
                        @else
                        <span class="text-emerald-400 font-black text-base">Rp {{ number_format($this->kembali, 0, ',',
                            '.') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Tombol --}}
                <div class="flex gap-3">
                    <button @click="tutupKonfirm()"
                        class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl text-sm">
                        ✕ Batal
                    </button>
                    <button @click="tutupKonfirm(); $wire.simpan()" wire:loading.attr="disabled"
                        class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-xl text-sm tracking-wide disabled:opacity-50">
                        <span wire:loading.remove wire:target="simpan">✔ Ya, Simpan</span>
                        <span wire:loading wire:target="simpan">Menyimpan...</span>
                    </button>
                </div>
                <p class="text-center text-gray-600 text-xs mt-3 font-mono">ENTER untuk konfirmasi · ESC untuk batal</p>
        </div>
    </div>

</div>

<script>
    function kasirPos() {
    return {
        bayarDisplay: '',
        waktu: '',
        sukses: false,
        showKonfirm: false,
        _timer: null,

        init() {
            this.tickWaktu();
            setInterval(() => this.tickWaktu(), 1000);
            this.$nextTick(() => this.focusScanner());
        },

        tickWaktu() {
            const now = new Date();
            this.waktu = now.toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'})
                + '  '
                + now.toLocaleTimeString('id-ID');
        },

        focusScanner() {
            this.$nextTick(() => document.getElementById('input-scanner')?.focus());
        },

        focusCustomer() {
            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('focus-customer'));
            });
        },

        focusBayar() {
            this.$nextTick(() => {
                const el = document.getElementById('input-bayar');
                el?.focus();
                el?.select();
            });
        },

        konfirmSimpan() {
            this.showKonfirm = true;
        },

        tutupKonfirm() {
            this.showKonfirm = false;
        },

        syncBayar() {
            // Format ribuan untuk tampilan, kirim angka murni ke Livewire
            const raw = this.bayarDisplay.replace(/\D/g, '');
            this.bayarDisplay = raw ? parseInt(raw).toLocaleString('id-ID') : '';
            this.$wire.set('bayar', raw);
        },

        showSukses() {
            this.sukses = true;
            this.showKonfirm = false;
            this.bayarDisplay = '';
            this.$wire.set('bayar', '');
            clearTimeout(this._timer);
            this._timer = setTimeout(() => { this.sukses = false; }, 3000);
        },
    }
}
</script>
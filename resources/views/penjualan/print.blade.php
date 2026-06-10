<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur {{ $penjualan->no_faktur }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #111;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 12mm;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            border: 1px solid #111;
            padding: 10px;
        }

        .store-box {
            border-right: 1px solid #111;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .logo {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 2px;
        }

        .store-name {
            font-size: 15pt;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .store-text {
            line-height: 1.4;
        }

        .meta-wrapper {
            display: flex;
            justify-content: flex-end; /* Memaksa semua anak di dalamnya berbaris di kanan */
            width: 100%;
        }

        .invoice-box {
            padding-left: 10px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .invoice-title {
            font-size: 16pt;
            font-weight: 800;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        .meta-row {
            display: grid;
            grid-template-columns: 96px 8px 1fr;
            align-items: baseline;
        }

        .recipient {
            border: 1px solid #111;
            padding: 8px 10px;
            min-height: 54px;
        }

        .recipient-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .recipient-name {
            font-weight: 700;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #111;
            padding: 5px 6px;
            vertical-align: top;
        }

        .items-table th {
            background: #111;
            color: #fff;
            text-align: center;
            font-weight: 700;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .center {
            text-align: center;
        }

        .footer-wrap {
            margin-top: 8px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 10px;
        }

        .terbilang-box {
            border: 1px solid #111;
            padding: 8px 10px;
            min-height: 72px;
        }

        .terbilang-title {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .faktur-box {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .summary-box {
            border: 1px solid #111;
        }

        .summary-row {
            display: grid;
            grid-template-columns: 1fr auto;
            padding: 6px 8px;
            border-bottom: 1px solid #111;
            align-items: center;
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .grand-row {
            font-size: 12pt;
            font-weight: 800;
            background: #f0f0f0;
        }

        .due-row {
            font-weight: 700;
        }

        .due-unpaid {
            background: #fff2c7;
            color: #7a4a00;
        }

        .signature {
            margin-top: auto;
            padding-top: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .sign-box {
            text-align: center;
        }

        .sign-label {
            margin-bottom: 46px;
        }

        .sign-line {
            border-top: 1px solid #111;
            padding-top: 4px;
        }

        .message {
            margin-top: 8px;
            text-align: center;
            font-size: 9pt;
        }

        @page {
            size: A5 portrait;
            margin: 0;
        }

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .page {
                margin: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        @media screen {
            body {
                background: #d8dce3;
                padding: 8px;
            }

            .no-print {
                position: fixed;
                right: 14px;
                bottom: 14px;
                display: flex;
                gap: 8px;
            }

            .no-print button {
                border: 0;
                color: #fff;
                background: #111;
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="header">
            <div class="store-box">
                @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo Toko" class="logo">
                @endif
                <div class="store-name">{{ $settings['nama_toko'] }}</div>
                <div class="store-text">{{ $settings['alamat_toko'] }}</div>
                <div class="store-text">Telp: {{ $settings['no_hp_toko'] }}</div>
            </div>

            <div class="meta-wrapper">
                <div class="invoice-box">
                    <div class="meta-row"><span>No. Faktur</span><span>:</span><span>#{{ $penjualan->no_faktur }}</span></div>
                    <div class="meta-row"><span>Tanggal</span><span>:</span><span>{{ $penjualan->tanggal?->format('d-m-Y H:i') }}</span></div>
                    <div class="meta-row"><span>Kepada Yth</span><span>:</span><span>{{ $penjualan->customer->nama ?? 'Pelanggan Umum' }}</span></div>
                    {{-- <div class="meta-row"><span>Kasir</span><span>:</span><span>{{ $penjualan->user->name ?? '-' }}</span></div>
                    <div class="meta-row"><span>Status</span><span>:</span><span>{{ $penjualan->status->label() }}</span></div> --}}
                    <div class="meta-row"><span>Sales</span><span>:</span><span>.................................</span></div>
                </div>
            </div>
        </div>

        {{-- <div class="recipient">
            <div class="recipient-title">Kepada Yth:</div>
            <div class="recipient-name">{{ $penjualan->customer->nama ?? 'Pelanggan Umum' }}</div>
            <div>{{ $penjualan->customer->alamat ?? '-' }}</div>
        </div> --}}

        <div class="faktur-box">
            <div class="invoice-title">FAKTUR PENJUALAN</div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th>Nama Barang</th>
                    <th style="width: 48px;">Qty</th>
                    <th style="width: 64px;">Satuan</th>
                    <th style="width: 102px;">Harga Satuan</th>
                    <th style="width: 92px;">Diskon Item</th>
                    <th style="width: 110px;">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($penjualan->details as $index => $detail)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $detail->barang->nama_barang ?? '-' }}</td>
                    <td class="center">{{ $detail->qty }}</td>
                    <td class="center">{{ $detail->barang->unit->nama ?? '-' }}</td>
                    <td class="num">{{ number_format((int) $detail->harga_jual, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((int) $detail->diskon, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((int) $detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-wrap">
            <div class="terbilang-box">
                <div class="terbilang-title">Catatan</div>
                <div class="terbilang-title">&nbsp;</div>
                <div class="terbilang-title">&nbsp;</div>
                <div class="terbilang-title">&nbsp;</div>
                {{-- <div>{{ $penjualan->catatan ?? '-' }}</div> --}}
                <div class="terbilang-title">Perhatian!!</div>
                @if (!empty($settings['pesan_faktur']))
                <div class="message">{{ $settings['pesan_faktur'] }}</div>
                @endif
            </div>

            <div class="summary-box">
                <div class="summary-row"><span>Total Item</span><strong>{{ $totalItem }}</strong></div>
                <div class="summary-row"><span>Total Diskon</span><strong>Rp {{ number_format($totalDiskon, 0, ',', '.')
                        }}</strong></div>
                <div class="summary-row grand-row"><span>GRAND TOTAL</span><strong>Rp {{ number_format($grandTotal, 0,
                        ',', '.') }}</strong></div>
                <div class="summary-row"><span>Nominal Bayar</span><strong>Rp {{ number_format($nominalBayar, 0, ',',
                        '.') }}</strong></div>
                @if ($penjualan->status->value === 'belum_lunas')
                <div class="summary-row due-row due-unpaid"><span>Sisa Tagihan</span><strong>Rp {{
                        number_format($sisaTagihan, 0, ',', '.') }}</strong></div>
                @else
                <div class="summary-row due-row"><span>Kembalian</span><strong>Rp {{ number_format($kembalian, 0, ',',
                        '.') }}</strong></div>
                @endif
            </div>
        </div>

        <div class="signature">
            <div class="sign-box">
                <div class="sign-label">Penerima</div>
                <div class="sign-line">(................................)</div>
            </div>
            <div class="sign-box">
                <div class="sign-label">Hormat Kami</div>
                <div class="sign-line">(................................)</div>
            </div>
        </div>
    </div>

    <div class="no-print">
        <button type="button" onclick="window.print()">Cetak</button>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <script>
        window.onload = function () {
            window.print();
            window.onafterprint = function () {
                window.close();
            };
        };
    </script>
</body>

</html>
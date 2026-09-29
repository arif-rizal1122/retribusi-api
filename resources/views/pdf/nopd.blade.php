<!DOCTYPE html>
<html>
<head>
    <title>Kartu NOPD - {{ $taxObject->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 25px; line-height: 1.35; color: #111; }
        .card-container { border: 2px solid #000; padding: 16px; width: 470px; margin: 0 auto; }
        .head-table { width: 100%; border-collapse: collapse; border-bottom: 1px solid #d9a742; padding-bottom: 5px; margin-bottom: 6px; }
        .head-table td { padding: 2px; }
        h1 { font-size: 11px; text-align: center; margin: 4px 0 8px 0; letter-spacing: .3px; }
        .nomor-box {
            text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 1.5px;
            border: 1px dashed #074764; color: #074764; padding: 8px; margin: 6px 0 10px 0;
            font-family: "Courier New", monospace;
        }
        .jenis { text-align: center; font-size: 9px; color: #555; margin-bottom: 10px; text-transform: uppercase; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data td { padding: 3px 2px; vertical-align: top; }
        td.label { font-weight: bold; width: 135px; }
        td.label-wide { font-weight: bold; width: 150px; font-size: 10px; }
        .spesifikasi { margin-top: 8px; border-top: 1px solid #ccc; padding-top: 6px; }
        .spesifikasi-title { font-weight: bold; font-size: 10px; margin-bottom: 4px; color: #074764; }
        .footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 6px; font-size: 9px; color: #555; text-align: justify; }
        .ttd { margin-top: 14px; text-align: right; font-size: 9px; }
    </style>
</head>
<body>
@php
    // Nomor diambil dari object_number_registry (sumber kebenaran), dikirim oleh controller.
    $nomor = $nomor ?? null;
    $tampilan = null;
    $nomorBaku = false;

    if ($nomor) {
        $bersih = preg_replace('/[^0-9]/', '', (string) $nomor);
        // Tampilkan bertitik HANYA bila bentuk baku 14 digit diawali 7472.
        // NOP lama (13/18 digit, bertitik) dipasang APA ADANYA — jangan dipaksa jadi 14 digit.
        if (strlen($bersih) === 14 && str_starts_with($bersih, '7472')) {
            $tampilan = substr($bersih, 0, 4).'.'.substr($bersih, 4, 2).'.'.substr($bersih, 6, 4).'.'.substr($bersih, 10, 4);
            $nomorBaku = true;
        } else {
            $tampilan = (string) $nomor;
        }
    }

    // Catatan: config/kodifikasi.php SUDAH di namespace 'kodifikasi',
    // jadi key-nya 'sektor.XX.nama' — JANGAN diawali 'kodifikasi.' lagi (dobel prefix -> null).
    $sektor = $sektor ?? null;
    $infoSektor = ($sektor && config("sektor.$sektor")) ? config("sektor.$sektor") : null;

    $jenisNomor = $jenis ?? ($infoSektor['jenis'] ?? null);
    $jenisLabel = $jenisNomor ? (config("jenis.$jenisNomor") ?? $jenisNomor) : 'Nomor Objek Pajak/Retribusi Daerah';
    $namaSektor = $infoSektor['nama'] ?? null;
    $namaOpd = $infoSektor['opd'] ?? null;
@endphp
    <div class="card-container">
        <table class="head-table">
            <tr>
                <td style="width: 15%; text-align: left;">
                    <img src="{{ public_path('assets/logos/logo-baubau.png') }}" style="width: 26px;">
                </td>
                <td style="width: 70%; text-align: center;">
                    <div style="font-size: 9px; font-weight: bold; color: #074764;">PEMERINTAH KOTA BAUBAU</div>
                    <div style="font-size: 8px; color: #555;">BADAN PENDAPATAN DAERAH</div>
                </td>
                <td style="width: 15%; text-align: right;">
                    <img src="{{ public_path('assets/logos/mitra-logo.png') }}" style="width: 26px;">
                </td>
            </tr>
        </table>

        <h1>KARTU {{ strtoupper($jenisLabel) }}</h1>

        <div class="nomor-box">{{ $tampilan ?: 'BELUM DITERBITKAN' }}</div>
        <div class="jenis">
            @if ($nomor)
                Format 14 digit &mdash; {{ $nomor }}
            @else
                Nomor belum diterbitkan untuk objek ini
            @endif
        </div>

        <table class="data">
            <tr>
                <td class="label">NAMA WAJIB PAJAK</td>
                <td>: {{ $taxObject->taxpayer->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">NPWPD</td>
                <td>: {{ $taxObject->taxpayer->npwpd ?? '-' }}</td>
            </tr>
            @if (! empty($taxObject->npwpd))
            <tr>
                <td class="label">NPWPD OBJEK</td>
                <td>: {{ $taxObject->npwpd }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">NAMA OBJEK / USAHA</td>
                <td>: {{ $taxObject->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">ALAMAT OBJEK</td>
                <td>: {{ $taxObject->address ?? '-' }}</td>
            </tr>
            @if ($namaSektor)
            <tr>
                <td class="label">SEKTOR</td>
                <td>: {{ $sektor }} &mdash; {{ $namaSektor }}</td>
            </tr>
            @endif
            @if ($namaOpd)
            <tr>
                <td class="label">UNIT PEMUNGUT</td>
                <td>: {{ $namaOpd }}</td>
            </tr>
            @endif
            @if (! empty($taxObject->opd))
            <tr>
                <td class="label-wide">OPD PENANGGUNG JAWAB</td>
                <td>: {{ $taxObject->opd->name ?? '-' }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">TANGGAL TERBIT</td>
                <td>: {{ optional($taxObject->created_at)->format('d M Y') ?? '-' }}</td>
            </tr>
        </table>

        @if (! empty($taxObject->metadata) && is_array($taxObject->metadata))
        <div class="spesifikasi">
            <div class="spesifikasi-title">SPESIFIKASI TEKNIS OBJEK</div>
            <table class="data">
                @foreach ($taxObject->metadata as $k => $v)
                    @if (! is_array($v) && $v !== null && $v !== '')
                    <tr>
                        <td class="label">{{ ucwords(str_replace('_', ' ', $k)) }}</td>
                        <td>: {{ $v }}</td>
                    </tr>
                    @endif
                @endforeach
            </table>
        </div>
        @endif

        <div class="footer">
            Kartu ini adalah bukti terdaftarnya objek {{ strtolower($jenisLabel) }} pada sistem M-PAD
            Pemerintah Kota Baubau. Harap disimpan dengan baik dan dibawa saat melakukan
            pembayaran pajak/retribusi daerah.
        </div>

        <div class="ttd">
            Baubau, {{ now()->format('d M Y') }}<br>
            Badan Pendapatan Daerah Kota Baubau
        </div>
    </div>
</body>
</html>

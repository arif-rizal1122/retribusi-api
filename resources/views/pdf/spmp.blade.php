<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 25px; line-height: 1.3; }
        .title { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; margin-top: 10px; margin-bottom: 2px; }
        .number { text-align: center; margin-bottom: 15px; }
        .content { margin-bottom: 20px; text-align: justify; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        td { padding: 3px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .footer { margin-top: 30px; }
        .signature-table { width: 100%; }
        .signature-box { text-align: center; width: 250px; }
        .danger-alert { background: #fee; border: 1px solid #CC0000; padding: 10px; margin-bottom: 20px; font-weight: bold; color: #CC0000; text-align: center; }
    </style>
<style>
    /* Compact layout — agar dokumen muat 1 halaman A4 */
    html, body { font-size: 9px !important; line-height: 1.28 !important; margin: 0 !important; padding: 0 !important; }
    @page { margin: 22px 26px 22px 26px; }
    table { border-collapse: collapse; }
    td, th { padding: 2px 4px !important; font-size: 9px; }
    h1 { font-size: 13px !important; margin: 3px 0 !important; }
    h2 { font-size: 11px !important; margin: 3px 0 !important; }
    h3 { font-size: 10px !important; margin: 2px 0 !important; }
    p { margin: 2px 0 !important; }
    .footer { font-size: 7px !important; margin-top: 6px !important; }
    .signature-section, .tte-block { page-break-inside: avoid !important; }
    img.qr-code, .qr-code { width: 70px !important; height: 70px !important; }
</style>
</head>
<body>
    @include('pdf.header')

    <div class="title">SURAT PERINTAH MELAKSANAKAN PENYITAAN (SPMP)</div>
    <div class="number">Nomor: {{ $spmp_number }}</div>

    <div class="danger-alert">PERINTAH PENYITAAN ASET</div>

    <div class="content">
        <p>Berdasarkan Peraturan Walikota Baubau Nomor 58 Tahun 2024 dan Surat Paksa Nomor {{ $enforcement_number }}, dengan ini diperintahkan kepada Juru Sita Pajak Daerah untuk melakukan penyitaan terhadap kekayaan milik:</p>
        
        <table class="table-info">
            <tr>
                <td class="label">NAMA WAJIB PAJAK</td>
                <td>: {{ $taxpayer_name }}</td>
            </tr>
            <tr>
                <td class="label">NIK / NPWPD</td>
                <td>: {{ $taxpayer_nik }}</td>
            </tr>
            <tr>
                <td class="label">ALAMAT WP</td>
                <td>: {{ $taxpayer_address }}</td>
            </tr>
            <tr>
                <td class="label">OBJEK PAJAK</td>
                <td>: {{ $tax_object }}</td>
            </tr>
            <tr>
                <td class="label">LOKASI OBJEK</td>
                <td>: {{ $object_address }}</td>
            </tr>
            <tr>
                <td class="label">TOTAL HUTANG PAJAK</td>
                <td>: <strong>Rp {{ number_format($deficit_amount, 0, ',', '.') }}</strong></td>
            </tr>
        </table>

        <p><strong>DENGAN KETENTUAN:</strong></p>
        <ol>
            <li>Penyitaan dilakukan atas barang milik Penanggung Pajak yang nilainya cukup untuk melunasi hutang pajak dan biaya penagihan pajak.</li>
            <li>Barang yang disita berada dalam penguasaan negara sebagai jaminan pelunasan hutang pajak.</li>
            <li>Apabila dalam waktu 14 hari hutang pajak tidak dilunasi, maka akan dilanjutkan dengan pengumuman Lelang.</li>
        </ol>
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div style="text-align: center;">
                    <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url, 100) }}" alt="QR Code">
                    <p style="font-size: 8px; margin: 5px 0 0 0;">VERIFIKASI LEGALITAS PENYITAAN</p>
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div style="display: inline-block; text-align: center;">
                    <p>Baubau, {{ $issued_at }}</p>
                    <p>Kepala Badan Pendapatan Daerah,</p>
                    <br><br><br>
                    <p><strong>( ________________________ )</strong></p>
                    <p>NIP. .............................</p>
                </div>
            </td>
        </tr>
    </table>
    
    <div style="margin-top: 20px; font-size: 10px;">
        <p><strong>Petugas Pelaksana (Juru Sita):</strong> {{ $auditor_name }}</p>
        <p><strong>Catatan:</strong> {{ $notes ?? '-' }}</p>
    </div>

    <div class="tte-block" style="margin-top:12px;">
        <table style="width:100%; border:none; border-collapse:collapse;">
            <tr>
                <td style="width:50%; text-align:center; vertical-align:top; border:none; padding:0;">
                    @if(!empty($signature['tte_qr']))
                    <img src="{{ $signature['tte_qr'] }}" alt="QR TTE" style="width:62px; height:62px;">
                    <p style="font-size:6px; margin:1px 0 0 0; line-height:1.1;">Tanda Tangan Elektronik</p>
                    @endif
                </td>
                <td style="width:50%; text-align:center; vertical-align:top; border:none; padding:0;">
                    <p style="margin:0; font-size:8px; line-height:1.25;">{{ $signature['kota'] ?? 'Baubau' }}, {{ date('d F Y') }}</p>
                    <p style="margin:0; font-size:8px; line-height:1.25;"><strong>{{ $signature['jabatan'] ?? 'Kepala Badan Pendapatan Daerah' }}</strong></p>
                    <div style="height:46px;"></div>
                    <p style="margin:0; font-size:8px; line-height:1.25; text-decoration:underline; font-weight:bold;">{{ $signature['nama'] ?? '[Nama Pejabat]' }}</p>
                    <p style="margin:0; font-size:8px; line-height:1.25;">NIP. {{ $signature['nip'] ?? '-' }}</p>
                </td>
            </tr>
        </table>
        @if(!empty($signature['tte_keterangan']))
        <p style="font-size:6px; font-style:italic; text-align:center; margin:3px 0 0 0; line-height:1.15;">{{ $signature['tte_keterangan'] }}</p>
        @endif
    </div>
</body>
</html>

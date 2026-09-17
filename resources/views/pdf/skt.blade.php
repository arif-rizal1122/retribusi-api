<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.3; color: #333; margin: 25px; border: 1px solid #000; padding: 15px; }
        .title { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; margin-top: 10px; margin-bottom: 2px; }
        .number { text-align: center; margin-bottom: 15px; }
        .content { margin-bottom: 20px; }
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-info td { padding: 3px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .footer { margin-top: 30px; }
        .signature-table { width: 100%; }
        .signature-box { text-align: center; width: 250px; }
        .qr-box { text-align: center; margin-top: 5px; }
    </style>
<style>
    /* Compact layout — margin kertas dipertahankan agar hasil print rapi.
       CATATAN: jangan set html/body margin:0, karena dompdf akan
       mengabaikan @page margin dan konten mentok ke tepi kertas. */
    @page { margin: 34px 40px 34px 40px; }
    html, body { font-size: 9px !important; line-height: 1.28 !important; }
    body { margin: 0 !important; padding: 0 !important; }
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

    <div class="title">SURAT KETERANGAN TERDAFTAR (SKT)</div>
    <div class="number">Nomor: {{ $skt_number }}</div>

    <div class="content">
        <p>Berdasarkan Peraturan Walikota Baubau Nomor 58 Tahun 2024 tentang Tata Cara Pemungutan Pajak Daerah dan Retribusi Daerah, dengan ini menerangkan bahwa:</p>
        
        <table class="table-info">
            <tr>
                <td class="label">NAMA WAJIB PAJAK</td>
                <td>: {{ $taxpayer_name }}</td>
            </tr>
            <tr>
                <td class="label">NPWPD</td>
                <td>: <strong>{{ $npwpd }}</strong></td>
            </tr>
            <tr>
                <td class="label">ALAMAT</td>
                <td>: {{ $taxpayer_address }}</td>
            </tr>
            <tr>
                <td class="label">NO. TELEPON</td>
                <td>: {{ $taxpayer_phone }}</td>
            </tr>
            <tr>
                <td class="label">TANGGAL TERDAFTAR</td>
                <td>: {{ $registered_at }}</td>
            </tr>
            <tr>
                <td class="label">OPD PENGELOLA</td>
                <td>: {{ $opd_name }}</td>
            </tr>
        </table>

        <p>Telah terdaftar dalam sistem database perpajakan daerah Kota Baubau dengan jumlah objek pajak sebanyak {{ $tax_objects_count }} unit.</p>
        <p>Surat keterangan ini berfungsi sebagai bukti pendaftaran resmi dan dapat digunakan sebagaimana mestinya sesuai ketentuan yang berlaku.</p>
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="qr-box">
                    <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url, 100) }}" alt="QR Code">
                    <p style="font-size: 8px; margin: 5px 0 0 0;">VERIFIKASI DIGITAL E-REGISTRY</p>
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div style="display: inline-block; text-align: center;">
                    <p>Baubau, {{ date('d F Y') }}</p>
                    <p>Kepala Badan Pendapatan Daerah,</p>
                    <br><br><br>
                    <p><strong>( ________________________ )</strong></p>
                    <p>NIP. .............................</p>
                </div>
            </td>
        </tr>
    </table>

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

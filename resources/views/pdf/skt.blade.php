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

    <div class="signature-section" style="margin-top: 25px; page-break-inside: avoid;">
        <table style="width:100%; border:none;">
            <tr>
                <td style="width:55%; text-align:center; vertical-align:top; border:none;">
                    @if(!empty($signature['tte_qr']))
                    <img src="{{ $signature['tte_qr'] }}" alt="QR TTE" style="width:80px; height:80px;">
                    <p style="font-size:8px; margin:4px 0 0 0;">Tanda Tangan Elektronik</p>
                    @endif
                </td>
                <td style="width:45%; text-align:center; vertical-align:top; border:none;">
                    <p style="margin:0;">{{ $signature['kota'] ?? 'Baubau' }}, {{ date('d F Y') }}</p>
                    <p style="margin:0;"><strong>{{ $signature['jabatan'] ?? 'Kepala Badan Pendapatan Daerah' }}</strong></p>
                    <div style="height:70px;"></div>
                    <p style="text-decoration: underline; font-weight: bold; margin:0;">{{ $signature['nama'] ?? '[Nama Pejabat]' }}</p>
                    <p style="margin:0;">NIP. {{ $signature['nip'] ?? '-' }}</p>
                </td>
            </tr>
        </table>
        @if(!empty($signature['tte_keterangan']))
        <p style="font-size:8px; font-style:italic; text-align:center; margin-top:6px;">{{ $signature['tte_keterangan'] }}</p>
        @endif
    </div>

</body>
</html>

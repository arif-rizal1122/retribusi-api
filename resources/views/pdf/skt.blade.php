<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 40px; border: 1px solid #000; padding: 20px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat { font-weight: bold; font-size: 18px; margin: 0; }
        .sub-kop { font-size: 12px; margin: 0; }
        .title { text-align: center; font-weight: bold; font-size: 16px; text-decoration: underline; margin-top: 20px; margin-bottom: 5px; }
        .number { text-align: center; margin-bottom: 30px; }
        .content { margin-bottom: 30px; }
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-info td { padding: 5px; vertical-align: top; }
        .label { width: 200px; font-weight: bold; }
        .footer { margin-top: 50px; }
        .signature-table { width: 100%; }
        .signature-box { text-align: center; width: 250px; }
        .qr-box { text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="kop-surat">PEMERINTAH KOTA BAUBAU</p>
        <p class="kop-surat">BADAN PENDAPATAN DAERAH</p>
        <p class="sub-kop">Jl. Raya No. 1, Kota Baubau, Sulawesi Tenggara</p>
    </div>

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
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qr_url) }}" alt="QR Code">
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
</body>
</html>

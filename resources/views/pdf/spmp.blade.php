<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 40px; border: 2px solid #CC0000; padding: 25px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat { font-weight: bold; font-size: 18px; margin: 0; color: #CC0000; }
        .sub-kop { font-size: 12px; margin: 0; }
        .title { text-align: center; font-weight: bold; font-size: 18px; text-decoration: underline; margin-top: 20px; margin-bottom: 5px; color: #CC0000; }
        .number { text-align: center; margin-bottom: 30px; font-weight: bold; }
        .content { margin-bottom: 30px; text-align: justify; }
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-info td { padding: 5px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .footer { margin-top: 50px; }
        .signature-table { width: 100%; }
        .signature-box { text-align: center; width: 250px; }
        .danger-alert { background: #fee; border: 1px solid #CC0000; padding: 10px; margin-bottom: 20px; font-weight: bold; color: #CC0000; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="kop-surat">PEMERINTAH KOTA BAUBAU</p>
        <p class="kop-surat">BADAN PENDAPATAN DAERAH</p>
        <p class="sub-kop">Jl. Raya No. 1, Kota Baubau, Sulawesi Tenggara</p>
    </div>

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
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qr_url) }}" alt="QR Code">
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
</body>
</html>

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
        .footer { margin-top: 30px; display: flex; justify-content: space-between; }
        .signature { text-align: center; width: 250px; }
        .qr-code { margin-top: 20px; text-align: center; border: 1px solid #ccc; padding: 10px; display: inline-block; }
    </style>
</head>
<body>
    @include('pdf.header')

    <div class="title">SURAT PERINTAH PEMERIKSAAN</div>
    <div class="number">Nomor: {{ $number }}</div>

    <div class="content">
        <p>Berdasarkan Peraturan Walikota No. 58 Tahun 2024 tentang Tata Cara Pemungutan PDRD, dengan ini diperintahkan kepada petugas di bawah ini untuk melakukan pemeriksaan lapangan terhadap:</p>
        
        <div class="field"><span class="label">Nama Wajib Pajak:</span> <span>{{ $taxpayer }}</span></div>
        <div class="field"><span class="label">Nama Objek Pajak:</span> <span>{{ $tax_object }}</span></div>
        <div class="field"><span class="label">Alamat:</span> <span>{{ $address }}</span></div>
        
        <p style="margin-top: 20px;"><strong>Catatan/Instruksi:</strong></p>
        <p>{{ $notes ?? 'Lakukan audit kepatuhan dan pencocokan data transaksi harian dengan pelaporan SPTPD.' }}</p>
    </div>

    <p>Demikian Surat Perintah ini dibuat untuk dijalankan dengan penuh tanggung jawab.</p>

    <div class="footer">
        <div class="qr-box">
             <div class="qr-code">
                <p style="font-size: 10px; margin: 0 0 5px 0;">VERIFIKASI DIGITAL</p>
                <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url, 100) }}" alt="QR Code">
             </div>
        </div>
        <div style="float: right; text-align: center; width: 200px;">
            <p>Baubau, {{ $date }}</p>
            <p>Kepala Badan Pendapatan Daerah,</p>
            <br><br><br>
            <p><strong>( ________________________ )</strong></p>
            <p>NIP. .............................</p>
        </div>
    </div>

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

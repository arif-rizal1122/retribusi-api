<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 40px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; text-decoration: underline; margin-bottom: 5px; }
        .number { text-align: center; margin-bottom: 20px; }
        .content { margin-bottom: 30px; }
        .field { display: flex; margin-bottom: 5px; }
        .label { width: 150px; font-weight: bold; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; }
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
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qr_url) }}" alt="QR Code">
             </div>
        </div>
        <div class="signature">
            <p>Kendari, {{ $date }}</p>
            <p>Kepala Bidang Pengawasan,</p>
            <br><br><br>
            <p><strong>( ________________________ )</strong></p>
            <p>NIP. .............................</p>
        </div>
    </div>
</body>
</html>

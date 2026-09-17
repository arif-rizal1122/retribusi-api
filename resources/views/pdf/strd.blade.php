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
    </style>
</head>
<body>
    @include('pdf.header')
    <div class="title">SURAT TAGIHAN RETRIBUSI DAERAH (STRD)</div>
    <div style="text-align: center; margin-bottom: 20px;">Nomor: {{ $strd_number }}</div>

    <div class="section">
        <table style="width: 100%;">
            <tr>
                <td style="width: 150px;">NAMA WAJIB PAJAK</td>
                <td>: {{ $taxpayer }}</td>
            </tr>
            <tr>
                <td style="width: 150px;">ALAMAT</td>
                <td>: {{ $address ?? '-' }}</td>
            </tr>
            <tr>
                <td style="width: 150px;">NOMOR TAGIHAN ASLI</td>
                <td>: <strong>{{ $bill_number }}</strong></td>
            </tr>
        </table>
    </div>

    <p>Berdasarkan catatan kami, Saudara belum melakukan pelunasan atas retribusi daerah hingga tanggal jatuh tempo. Berikut adalah rincian kewajiban yang harus segera diselesaikan:</p>

    <table class="table">
        <thead>
            <tr>
                <th>URAIAN</th>
                <th>JUMLAH (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $title }} - {{ $period }} (Pokok)</td>
                <td style="text-align: right;">{{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Sanksi Bunga Keterlambatan ({{ $months_late }} Bulan)</td>
                <td style="text-align: right;">{{ number_format($penalty_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td style="text-align: right;">TOTAL TUNGGAKAN</td>
                <td style="text-align: right;">{{ number_format($total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section">
        <p><strong>TERBILANG:</strong> <em># {{ strtoupper($terbilang) }} #</em></p>
    </div>

    <div class="section">
        <p><strong>PERINGATAN:</strong></p>
        <p>Harap segera melakukan pembayaran selambat-lambatnya pada tanggal <strong>{{ $payment_deadline }}</strong>. Apabila sampai batas waktu tersebut pembayaran belum dilakukan, maka akan dilanjutkan dengan tindakan penagihan aktif (Surat Paksa/Penyitaan).</p>
    </div>

    <div class="qr-box">
        <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url, 80) }}" alt="QR Code">
        <p style="font-size: 8px;">SCAN UNTUK DETAIL</p>
    </div>

    <div class="footer">
        <p>Baubau, {{ date('d F Y') }}</p>
        <p>KEPALA BIDANG PENAGIHAN,</p>
        <br><br><br>
        <p>( ________________________ )</p>
        <p style="font-size: 10px;">Surat ini sah tanpa tanda tangan basah karena diterbitkan oleh sistem MPAD.</p>
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

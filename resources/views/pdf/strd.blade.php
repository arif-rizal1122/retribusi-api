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

    <div class="footer">
        <p>Baubau, {{ date('d F Y') }}</p>
        <p>KEPALA BIDANG PENAGIHAN,</p>
        <br><br><br>
        <p>( ________________________ )</p>
        <p style="font-size: 10px;">Surat ini sah tanpa tanda tangan basah karena diterbitkan oleh sistem MPAD.</p>
    </div>

</body>
</html>

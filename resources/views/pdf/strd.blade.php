<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Courier', monospace; font-size: 12px; color: #000; margin: 30px; border: 2px solid #000; padding: 20px; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 5px; text-align: center; }
        .section { margin-bottom: 15px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #000; padding: 10px; text-align: left; }
        .total-row { font-weight: bold; background: #eee; }
        .footer { margin-top: 40px; text-align: right; }
        .qr-box { float: left; text-align: center; margin-top: 20px; }
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
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($qr_url) }}" alt="QR Code">
        <p style="font-size: 8px;">SCAN UNTUK DETAIL</p>
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

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #333; margin: 25px; line-height: 1.3; }
        .main-title { font-size: 16px; font-weight: bold; margin: 5px 0; text-align: center; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .info-box { border: 1px solid #ccc; padding: 8px; border-radius: 5px; }
        .info-title { font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 3px; font-size: 9px; color: #666; }
        .payment-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .payment-table th { background: #333; color: #fff; padding: 8px; text-align: left; }
        .payment-table td { border-bottom: 1px solid #eee; padding: 8px; }
        .status-badge { background: #dcfce7; color: #166534; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 12px; display: inline-block; }
        .footer { margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 9px; color: #999; text-align: center; }
    </style>
</head>
<body>
    @include('pdf.header')
    <div class="title">SURAT SETORAN RETRIBUSI DAERAH (SSRD)</div>
    <div style="text-align: center; margin-bottom: 20px;">Bukti Bayar Sah</div>

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
                <td style="width: 150px;">NOMOR TAGIHAN</td>
                <td>: <strong>{{ $number }}</strong></td>
            </tr>
            <tr>
                <td style="width: 150px;">TANGGAL BAYAR</td>
                <td>: {{ date('d F Y', strtotime($paid_at)) }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>KODE REKENING</th>
                <th>URAIAN RETRIBUSI</th>
                <th>JUMLAH (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>4.1.2.01.01</td>
                <td>{{ $title }} - {{ $period }} (Pokok)</td>
                <td style="text-align: right;">{{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            @if(isset($penalty_amount) && $penalty_amount > 0)
            <tr>
                <td>4.1.2.01.01.01</td>
                <td>Sanksi Bunga Keterlambatan</td>
                <td style="text-align: right;">{{ number_format($penalty_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if(isset($fixed_fine_amount) && $fixed_fine_amount > 0)
            <tr>
                <td>4.1.2.01.01.02</td>
                <td>Denda Administratif (Lapor)</td>
                <td style="text-align: right;">{{ number_format($fixed_fine_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if(isset($surcharge_amount) && $surcharge_amount > 0)
            <tr>
                <td>4.1.2.01.01.03</td>
                <td>Kenaikan Sanksi (Surcharge)</td>
                <td style="text-align: right;">{{ number_format($surcharge_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">TOTAL BAYAR</td>
                <td style="text-align: right;">{{ number_format($total_amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section">
        <p><strong>TERBILANG:</strong> <em># {{ strtoupper($terbilang) }} #</em></p>
        <p><strong>METODE PEMBAYARAN:</strong> {{ strtoupper($payment_method) }}</p>
    </div>

    <div class="qr-box">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($qr_url) }}" alt="QR Code">
        <p style="font-size: 8px;">VERIFIKASI PEMBAYARAN</p>
    </div>

    <div class="footer">
        <p>Kendari, {{ date('d F Y') }}</p>
        <p>BENDAHARA PENERIMA,</p>
        <br><br><br>
        <p>( ________________________ )</p>
        <p style="font-size: 10px;">Dokumen ini diterbitkan secara elektronik oleh sistem M-PAD.</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; color: #333; margin: 30px; }
        .main-title { font-size: 18px; font-weight: bold; margin: 10px 0; text-align: center; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-box { border: 1px solid #ccc; padding: 10px; border-radius: 5px; }
        .info-title { font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 5px; font-size: 10px; color: #666; }
        .payment-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .payment-table th { background: #333; color: #fff; padding: 10px; text-align: left; }
        .payment-table td { border-bottom: 1px solid #eee; padding: 10px; }
        .status-badge { background: #dcfce7; color: #166534; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 14px; display: inline-block; }
        .footer { margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 20px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div style="float: right;" class="status-badge">LUNAS / PAID</div>
    
    @include('pdf.header')
    <div class="main-title">SURAT SETORAN PAJAK DAERAH (SSPD)</div>
    <div style="text-align: center; margin-bottom: 20px;">BUKTI PEMBAYARAN ELEKTRONIK</div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-title">DATA WAJIB PAJAK</div>
            <div style="font-weight: bold; font-size: 14px;">{{ $taxpayer }}</div>
            <div>Alamat: {{ $address ?? 'Sesuai Database' }}</div>
        </div>
        <div class="info-box">
            <div class="info-title">DATA TRANSAKSI</div>
            <div>Invoice: <strong>{{ $number }}</strong></div>
            <div>Dibayar Pada: {{ date('d/m/Y H:i', strtotime($paid_at)) }}</div>
            <div>Metode: BANK SULTRA / QRIS / TUNAI</div>
        </div>
    </div>

    <table class="payment-table">
        <thead>
            <tr>
                <th>DESKRIPSI PEMBAYARAN</th>
                <th style="text-align: right;">JUMLAH (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $title }} - {{ $period }} (Pokok)</td>
                <td style="text-align: right;">{{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            @if(isset($penalty_amount) && $penalty_amount > 0)
            <tr>
                <td>Sanksi Bunga Keterlambatan</td>
                <td style="text-align: right;">{{ number_format($penalty_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if(isset($fixed_fine_amount) && $fixed_fine_amount > 0)
            <tr>
                <td>Denda Administratif (Lapor)</td>
                <td style="text-align: right;">{{ number_format($fixed_fine_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if(isset($surcharge_amount) && $surcharge_amount > 0)
            <tr>
                <td>Kenaikan Sanksi (Surcharge)</td>
                <td style="text-align: right;">{{ number_format($surcharge_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td style="font-size: 14px;">TOTAL PEMBAYARAN</td>
                <td style="text-align: right; font-size: 16px;">Rp {{ number_format($total_amount ?? $amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-bottom: 20px;">
        <p style="font-size: 11px;"><strong>TERBILANG:</strong> <em># {{ strtoupper($terbilang ?? App\Services\OfficialDocumentService::terbilang($total_amount ?? $amount)) }} #</em></p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($qr_url) }}" alt="Verification QR">
        <p style="font-size: 10px; margin-top: 5px;">PINDAI UNTUK VALIDASI KEASLIAN DOKUMEN</p>
    </div>

    <div class="footer">
        <p>Dokumen ini diterbitkan secara otomatis oleh MITRA (Mitra PAD - Manajemen Integrasi Tax, Retribusi, dan Aset Daerah) Kota Baubau.</p>
        <p>Bukti ini sah dan memiliki kekuatan hukum yang sama dengan tanda terima manual sesuai regulasi e-Government.</p>
        <p>ID Transaksi: {{ md5($number . $paid_at) }}</p>
    </div>
</body>
</html>

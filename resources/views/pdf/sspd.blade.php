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
    <div style="float: right;" class="status-badge">LUNAS / PAID</div>
    
    @include('pdf.header')
    <div class="main-title">SURAT SETORAN PAJAK DAERAH (SSPD)</div>
    <div style="text-align: center; margin-bottom: 20px;">BUKTI PEMBAYARAN ELEKTRONIK</div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-title">DATA WAJIB PAJAK</div>
            <div style="font-weight: bold; font-size: 14px;">{{ $taxpayer }}</div>
            <div>Alamat: {{ $address ?? 'Sesuai Database' }}</div>
            @if(isset($object_name) && $object_name)
            <div>Objek: {{ $object_name }}</div>
            @endif
        </div>
        <div class="info-box">
            <div class="info-title">DISETOR KEPADA</div>
            <div style="font-weight: bold; font-size: 12px;">{{ $unit_name ?? 'BAPENDA Kota Baubau' }}</div>
            <div>Unit Pemungut: BAPENDA Kota Baubau</div>
        </div>
        <div class="info-box">
            <div class="info-title">DATA TRANSAKSI</div>
            <div>Invoice: <strong>{{ $number }}</strong></div>
            <div>Dibayar Pada: {{ date('d/m/Y H:i', strtotime($paid_at)) }}</div>
            <div>Metode: {{ $payment_method ?? 'Elektronik' }}</div>
            @if(isset($payment_reference) && $payment_reference)
            <div>NTPD: <strong>{{ $payment_reference }}</strong></div>
            @endif
        </div>
        <div class="info-box">
            <div class="info-title">PERIODE &amp; TENGGAT</div>
            @php
                $cycleLabel = 'Pembayaran';
                if (isset($billing_cycle) && $billing_cycle) {
                    $cycleLabel = strtolower((string) $billing_cycle) === 'yearly' || strtolower((string) $billing_cycle) === 'tahunan'
                        ? 'Pembayaran Tahunan'
                        : 'Pembayaran Bulanan';
                } elseif (isset($period_start) && isset($period_end) && $period_start && $period_end) {
                    $startTs = strtotime($period_start);
                    $endTs = strtotime($period_end);
                    if ($startTs && $endTs && ($endTs - $startTs) >= 300 * 86400) {
                        $cycleLabel = 'Pembayaran Tahunan';
                    } else {
                        $cycleLabel = 'Pembayaran Bulanan';
                    }
                }
            @endphp
            <div style="font-weight: bold;">{{ $cycleLabel }}</div>
            @if(isset($period_start) && $period_start)
            <div>Periode: {{ $period_start }} s/d {{ $period_end ?? '...' }}</div>
            @else
            <div>Periode: {{ $period }}</div>
            @endif
            @if(isset($due_date) && $due_date)
            <div>Jatuh Tempo: {{ $due_date }}</div>
            @endif
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
                <td>{{ $title }} - {{ $period }} (Pokok)
                    @if(isset($object_name) && $object_name)<br><span style="font-size: 9px; color: #666;">{{ $object_name }}</span>@endif
                </td>
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
        <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url, 120) }}" alt="Verification QR">
        <p style="font-size: 10px; margin-top: 5px;">PINDAI UNTUK VALIDASI KEASLIAN DOKUMEN</p>
    </div>
</td>
            </tr>
        </table>

        <p style="text-align: right; text-decoration: underline; font-weight: bold;">{{ $signature['nama'] ?? '[Nama Pejabat]' }}</p>
        <p style="text-align: right;">NIP. {{ $signature['nip'] ?? '-' }}</p>
        @if(!empty($signature['tte_keterangan']))
        <p style="text-align: right; font-size:8px; font-style:italic; margin-top:6px;">{{ $signature['tte_keterangan'] }}</p>
        @endif
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
        <p>Dokumen ini diterbitkan secara otomatis oleh MITRA (Mitra PAD - Manajemen Integrasi Tax, Retribusi, dan Aset Daerah) Kota Baubau.</p>
        <p>Bukti ini sah dan memiliki kekuatan hukum yang sama dengan tanda terima manual sesuai regulasi e-Government.</p>
        <p>ID Transaksi: {{ md5($number . $paid_at) }}</p>
    </div>
</body>
</html>

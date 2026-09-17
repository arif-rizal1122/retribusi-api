<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Ketetapan Pajak Daerah (SKPD)</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 25px; line-height: 1.3; }
        .title { text-align: center; font-weight: bold; font-size: 15px; margin-bottom: 5px; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-info td { padding: 3px; vertical-align: top; }
        .table-amount { border: 1px solid #000; }
        .table-amount th, .table-amount td { border: 1px solid #000; padding: 5px; text-align: left; }
        .table-amount th { background-color: #f0f0f0; }
        .total-row { font-weight: bold; }
        .signature-section { float: right; width: 300px; text-align: center; margin-top: 20px; }
        .signature-section p { margin: 2px 0; }
        .signature-space { height: 60px; }
        .footer { clear: both; margin-top: 20px; font-size: 9px; text-align: center; color: #555; border-top: 1px solid #ccc; padding-top: 5px; }
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

    <div class="title">SURAT KETETAPAN PAJAK DAERAH (SKPD)</div>

    <table class="table-info">
        <tr>
            <td width="20%">No. Kohir</td>
            <td width="2%">:</td>
            <td>{{ str_pad($billing->id, 8, '0', STR_PAD_LEFT) }}</td>
            <td width="20%">Masa Pajak</td>
            <td width="2%">:</td>
            <td>{{ \Carbon\Carbon::parse($billing->due_date)->format('M Y') }}</td>
        </tr>
        <tr>
            <td>Nama WP</td>
            <td>:</td>
            <td><strong>{{ $billing->taxObject->taxpayer->name }}</strong></td>
            <td>NPWPD</td>
            <td>:</td>
            <td>{{ $billing->taxObject->taxpayer->npwpd ?? '-' }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td colspan="4">{{ $billing->taxObject->taxpayer->address }}</td>
        </tr>
        <tr>
            <td>Objek Pajak</td>
            <td>:</td>
            <td colspan="4">{{ $billing->taxObject->name }} ({{ $billing->taxObject->address }})</td>
        </tr>
    </table>

    <table class="table-amount">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Uraian Pajak</th>
                <th width="20%">Tarif Pokok</th>
                <th width="30%">Jumlah Ketetapan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>{{ $billing->taxObject->retributionType->name ?? 'Pajak Daerah' }}<br>
                    <small>Klasifikasi: {{ $billing->taxObject->retributionClassification->name ?? '-' }}</small>
                </td>
                <td>Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Total Pajak Terutang :</td>
                <td>Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p style="text-transform: capitalize;"><strong>Perhatian:</strong> Simpan tanda bukti pembayaran ini.</p>

    <div style="margin-top: 20px;">
        <p><strong>Perhatian:</strong></p>
        <ol style="margin-top: 5px; padding-left: 20px;">
            <li>Jatuh tempo pembayaran tanggal: <strong>{{ \Carbon\Carbon::parse($billing->due_date)->format('d F Y') }}</strong></li>
            <li>Keterlambatan pembayaran akan dikenakan sanksi denda administrasi sesuai ketentuan peraturan daerah.</li>
        </ol>
    </div>
<p style="text-decoration: underline; font-weight: bold;">Muh. Nama Pejabat, S.Sos., M.Si</p>
        <p>NIP. 19700101 199503 1 001</p>
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
        Dicetak melalui Sistem Informasi Manajemen PDRD Bapenda Baubau pada {{ date('d-m-Y H:i:s') }}
    </div>

</body>
</html>

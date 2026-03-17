<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Ketetapan Pajak Daerah (SKPD)</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 15px; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-info td { padding: 5px; vertical-align: top; }
        .table-amount { border: 1px solid #000; }
        .table-amount th, .table-amount td { border: 1px solid #000; padding: 8px; text-align: left; }
        .table-amount th { background-color: #f0f0f0; }
        .total-row { font-weight: bold; }
        .signature-section { float: right; width: 300px; text-align: center; margin-top: 30px; }
        .signature-section p { margin: 5px 0; }
        .signature-space { height: 80px; }
        .footer { clear: both; margin-top: 40px; font-size: 10px; text-align: center; color: #555; border-top: 1px solid #ccc; padding-top: 5px; }
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

    <div class="signature-section">
        <p>Baubau, {{ date('d F Y') }}</p>
        <p><strong>Kepala Bapenda Kota Baubau</strong></p>
        <div class="signature-space">
            <img src="{{ public_path('images/ttd_bapenda_dummy.png') }}" style="height: 60px; display: none;" alt="TTD">
            <!-- Signature image would go here -->
        </div>
        <p style="text-decoration: underline; font-weight: bold;">Muh. Nama Pejabat, S.Sos., M.Si</p>
        <p>NIP. 19700101 199503 1 001</p>
    </div>

    <div class="footer">
        Dicetak melalui Sistem Informasi Manajemen PDRD Bapenda Baubau pada {{ date('d-m-Y H:i:s') }}
    </div>

</body>
</html>

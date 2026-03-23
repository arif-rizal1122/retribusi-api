<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Ketetapan Retribusi Daerah (SKRD)</title>
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
</head>
<body>

    @include('pdf.header')

    <div class="title">SURAT KETETAPAN RETRIBUSI DAERAH (SKRD)</div>

    <table class="table-info">
        <tr>
            <td width="20%">No. Urut</td>
            <td width="2%">:</td>
            <td>{{ str_pad($billing->id, 8, '0', STR_PAD_LEFT) }}</td>
            <td width="20%">Masa Retribusi</td>
            <td width="2%">:</td>
            <td>{{ \Carbon\Carbon::parse($billing->due_date)->format('M Y') }}</td>
        </tr>
        <tr>
            <td>Nama WR</td>
            <td>:</td>
            <td><strong>{{ $billing->taxObject->taxpayer->name }}</strong></td>
            <td>NPWRD</td>
            <td>:</td>
            <td>{{ $billing->taxObject->taxpayer->npwpd ?? '-' }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>:</td>
            <td colspan="4">{{ $billing->taxObject->taxpayer->address }}</td>
        </tr>
        <tr>
            <td>Objek Retribusi</td>
            <td>:</td>
            <td colspan="4">{{ $billing->taxObject->name }} ({{ $billing->taxObject->address }})</td>
        </tr>
    </table>

    <table class="table-amount">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Jenis Retribusi</th>
                <th width="20%">Tarif Retribusi</th>
                <th width="30%">Jumlah Ketetapan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;">1</td>
                <td>{{ $billing->taxObject->retributionType->name ?? 'Retribusi Daerah' }}<br>
                    <small>Klasifikasi: {{ $billing->taxObject->retributionClassification->name ?? '-' }}</small>
                </td>
                <td>Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">Total Retribusi :</td>
                <td>Rp {{ number_format($billing->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <p><strong>Perhatian:</strong></p>
        <ol style="margin-top: 5px; padding-left: 20px;">
            <li>Harap melakukan pelunasan sebelum tanggal: <strong>{{ \Carbon\Carbon::parse($billing->due_date)->format('d F Y') }}</strong></li>
            <li>Penyetoran dapat dilakukan melalui Kas Daerah / Teller Bank Sultra / QRIS.</li>
        </ol>
    </div>

    <div class="signature-section">
        <p>Baubau, {{ date('d F Y') }}</p>
        <p><strong>Kepala Bidang Pendapatan Bapenda</strong></p>
        <div class="signature-space">
            <!-- Signature image would go here -->
        </div>
        <p style="text-decoration: underline; font-weight: bold;">Nama Pejabat Retribusi, S.E, M.Si</p>
        <p>NIP. 19800101 200503 1 002</p>
    </div>

    <div class="footer">
        Dokumen INI SAH / VALID dicetak melalui Sistem PDRD Bapenda Baubau pada {{ date('d-m-Y H:i:s') }}
    </div>

</body>
</html>

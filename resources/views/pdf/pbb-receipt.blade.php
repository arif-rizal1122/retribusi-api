<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran PBB</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; font-size: 11pt; }
        .container { padding: 28px; }
        h1 { text-align: center; font-size: 16pt; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .label { width: 38%; font-weight: bold; color: #4b5563; }
        .total { font-size: 14pt; font-weight: bold; }
        .status { margin-top: 24px; padding: 12px; border: 1px solid #d1d5db; }
        .footer { margin-top: 36px; font-size: 9pt; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>BUKTI PEMBAYARAN PBB</h1>
        <table>
            <tr><td class="label">NTPD</td><td>{{ $transaction->ntpd ?: '-' }}</td></tr>
            <tr><td class="label">NOP</td><td>{{ $transaction->nop }}</td></tr>
            <tr><td class="label">Tahun Pajak</td><td>{{ $transaction->tahun }}</td></tr>
            <tr><td class="label">Nama Wajib Pajak</td><td>{{ $transaction->wp_name ?: '-' }}</td></tr>
            <tr><td class="label">Waktu Pembayaran</td><td>{{ optional($transaction->created_at)->format('d-m-Y H:i:s') }}</td></tr>
            <tr><td class="label">Status</td><td>{{ strtoupper($transaction->payment_status) }}</td></tr>
            <tr><td class="label total">Total Pembayaran</td><td class="total">Rp {{ number_format((float) $transaction->total_bayar, 0, ',', '.') }}</td></tr>
        </table>
        <div class="status">Simpan dokumen ini sebagai bukti pembayaran resmi yang diterbitkan oleh sistem.</div>
        <div class="footer">Dokumen dibuat oleh layanan Retribusi/Bapenda.</div>
    </div>
</body>
</html>

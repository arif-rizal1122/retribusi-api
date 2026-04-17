<!DOCTYPE html>
<html>
<head>
    <title>Kartu NPWPD - {{ $taxpayer->npwpd }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 25px; line-height: 1.3; }
        .title { text-align: center; font-weight: bold; font-size: 15px; margin-bottom: 10px; }
        .card-container { border: 2px solid #000; padding: 15px; width: 450px; margin: 0 auto; }
        .label { font-weight: bold; width: 120px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px; vertical-align: top; }
    </style>
</head>
<body>
    <div class="card">
        <div class="bg-pattern"></div>
        <table style="width: 100%; border-bottom: 1px solid #d9a742; padding-bottom: 5px; margin-bottom: 5px;">
            <tr>
                <td style="width: 15%; text-align: left;"><img src="{{ public_path('assets/logos/logo-baubau.png') }}" style="width: 25px;"></td>
                <td style="width: 70%; text-align: center;">
                    <div style="font-size: 8px; font-weight: bold; color: #074764;">PEMERINTAH KOTA BAUBAU</div>
                    <div style="font-size: 7px; color: #555;">BADAN PENDAPATAN DAERAH</div>
                </td>
                <td style="width: 15%; text-align: right;"><img src="{{ public_path('assets/logos/mitra-logo.png') }}" style="width: 25px;"></td>
            </tr>
        </table>
        <h1 style="font-size:10px; margin-top:2px;">KARTU NOMOR POKOK WAJIB PAJAK DAERAH</h1>
        
        <div class="npwpd-box">
            {{ $taxpayer->npwpd ?: 'DALAM PROSES' }}
        </div>

        <div class="content">
            <div class="row">
                <div class="label">NAMA</div>
                <div class="val">: {{ $taxpayer->name }}</div>
            </div>
            <div class="row">
                <div class="label">ALAMAT</div>
                <div class="val">: {{ $taxpayer->address }}</div>
            </div>
            <div class="row">
                <div class="label">TERDAFTAR SEJAK</div>
                <div class="val">: {{ $taxpayer->created_at->format('d M Y') }}</div>
            </div>
        </div>

        <div class="footer">
            Kartu ini harap disimpan dengan baik dan dibawa saat melakukan pembayaran pajak/retribusi daerah.
        </div>
    </div>
</body>
</html>

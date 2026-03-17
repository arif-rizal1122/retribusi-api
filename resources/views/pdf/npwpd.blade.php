<!DOCTYPE html>
<html>
<head>
    <title>Kartu NPWPD - {{ $taxpayer->npwpd }}</title>
    <style>
        body { font-family: sans-serif; margin: 0; padding: 0; background: #ffffff; }
        .card { width: 100%; height: 100%; box-sizing: border-box; padding: 10px; border: 2px solid #074764; border-radius: 8px; position: relative; overflow: hidden; }
        .bg-pattern { position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.1; background-image: repeating-linear-gradient(45deg, #074764 0, #074764 1px, transparent 0, transparent 50%); background-size: 10px 10px; z-index: -1; }
        h1 { margin: 0; font-size: 11px; color: #074764; text-transform: uppercase; text-align: center; }
        .content { font-size: 8px; font-weight: bold; line-height: 1.4; margin-top: 5px; }
        .row { display: table; width: 100%; margin-bottom: 2px; }
        .label { display: table-cell; width: 30%; color: #666; }
        .val { display: table-cell; width: 70%; color: #000; text-transform: uppercase; }
        .footer { position: absolute; bottom: 5px; left: 10px; right: 10px; text-align: center; font-size: 6px; color: #888; border-top: 1px dashed #ccc; padding-top: 3px; }
        .npwpd-box { background: #074764; color: #fff; text-align: center; padding: 4px; border-radius: 4px; font-size: 12px; font-weight: bold; letter-spacing: 1px; margin-top: 5px; }
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

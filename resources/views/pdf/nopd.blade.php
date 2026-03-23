<!DOCTYPE html>
<html>
<head>
    <title>Sertifikat NOPD - {{ $taxObject->nop }}</title>
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
    @include('pdf.header')

    <div class="title-doc">SURAT KETERANGAN TERDAFTAR OBJEK DAERAH</div>
    <div class="subtitle-doc">Nomor: BAPENDA/{{ date('Y') }}/NOPD/{{ $taxObject->id }}</div>

    <p style="text-align: justify; line-height: 1.6; font-size:14px; margin-bottom:20px;">
        Kepala Badan Pendapatan Daerah Kota Baubau menerangkan bahwa objek pajak/retribusi di bawah ini telah terdaftar dalam sistem administrasi Pendapatan Asli Daerah (PAD):
    </p>

    <table>
        <tr>
            <th>NOMOR OBJEK PAJAK (NOPD)</th>
            <td style="color: #074764; font-size: 16px;">{{ $taxObject->nop }}</td>
        </tr>
        <tr>
            <th>NAMA OBJEK</th>
            <td>{{ strtoupper($taxObject->name) }}</td>
        </tr>
        <tr>
            <th>ALAMAT OBJEK LOKASI</th>
            <td>{{ strtoupper($taxObject->address) }}, KEL. {{ strtoupper($taxObject->sub_district) }}, KEC. {{ strtoupper($taxObject->district) }}</td>
        </tr>
        <tr>
            <th>JENIS PUNGUTAN</th>
            <td>
                @foreach($taxObject->retributionTypes as $rt)
                    {{ $rt->name }}<br>
                @endforeach
            </td>
        </tr>
        <tr>
            <th>NAMA WAJIB PAJAK</th>
            <td>{{ strtoupper($taxObject->taxpayer->name) }}</td>
        </tr>
        <tr>
            <th>NPWPD WAJIB PAJAK</th>
            <td>{{ $taxObject->taxpayer->npwpd ?: 'DALAM PROSES' }}</td>
        </tr>
    </table>

    <p style="text-align: justify; line-height: 1.6; font-size:14px;">
        Surat Keterangan ini merupakan bukti sah pendaftaran objek pada sistem <strong>M-PAD (Mitra PAD) Kota Baubau</strong>. Segala bentuk kewajiban penyetoran akan ditagihkan pada objek ini sesuai dengan Peraturan Daerah yang berlaku.
    </p>

    <div class="footer-sig">
        <div class="sig-col">
            <!-- QR Placeholder -->
            <div style="border:1px solid #ccc; width:80px; height:80px; margin: 0 auto; line-height:80px; color:#999; font-size:10px;">QR CODE</div>
        </div>
        <div class="sig-col right">
            Baubau, {{ date('d F Y') }}<br>
            <strong>Kepala Badan Pendapatan Daerah</strong>
            <div class="sig-name">______________________________</div>
            <div>NIP. ............................</div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Penegakan - {{ $enforcement->number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 0; padding: 30px 40px; line-height: 1.6; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header img { width: 80px; float: left; }
        .header .titles { margin-left: 90px; }
        .header h2, .header h3, .header h4 { margin: 2px 0; }
        
        .letter-info { width: 100%; margin-bottom: 30px; }
        .letter-info td { vertical-align: top; }
        
        .content { text-align: justify; margin-bottom: 40px; }
        .recipient { margin-top: 10px; font-weight: bold; }
        
        .signature-section { float: right; width: 300px; text-align: center; margin-top: 30px; }
        .signature-section p { margin: 5px 0; }
        .signature-space { height: 100px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="titles">
            <h2>PEMERINTAH KOTA BAUBAU</h2>
            <h3>BADAN PENDAPATAN DAERAH (BAPENDA)</h3>
            <p style="font-size: 12px; margin:0;">Jalan Dayanu Ikhsanuddin No. 12, Baubau, Sulawesi Tenggara</p>
        </div>
    </div>

    <table class="letter-info">
        <tr>
            <td width="15%">Nomor</td>
            <td width="2%">:</td>
            <td width="48%">{{ $enforcement->number }}</td>
            <td width="35%" style="text-align:right;">Baubau, {{ date('d F Y') }}</td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td style="font-weight:bold; text-transform:uppercase;">{{ str_replace('_', ' ', $enforcement->type) }}</td>
            <td></td>
        </tr>
    </table>

    <div class="recipient">
        Kepada Yth,<br>
        Sdr/i. {{ $enforcement->taxObject->taxpayer->name }}<br>
        di - <br>
        &nbsp;&nbsp;&nbsp;&nbsp;Tempat
    </div>

    <div class="content">
        <br>
        <p>Dengan hormat,</p>
        <p>Berdasarkan catatan pada sistem administrasi kami, diketahui bahwa Bapak/Ibu/Saudara selaku Wajib Pajak / Wajib Retribusi untuk objek pajak/retribusi:</p>
        
        <table style="margin-left: 20px; margin-bottom: 10px;">
            <tr><td width="150">Nama Objek</td><td>: <strong>{{ $enforcement->taxObject->name }}</strong></td></tr>
            <tr><td>Alamat Objek</td><td>: {{ $enforcement->taxObject->address }}</td></tr>
            <tr><td>NPWPD/NPWRD</td><td>: {{ $enforcement->taxObject->taxpayer->npwpd ?? '-' }}</td></tr>
        </table>

        @if($enforcement->type === 'teguran_1' || $enforcement->type === 'teguran_2')
            <p>Hingga saat surat ini diterbitkan, Saudara tercatat menunggak kewajiban pembayaran pajak daerah/retribusi. 
               Sehubungan dengan hal tersebut, kami menerbitkan <strong>{{ strtoupper(str_replace('_', ' ', $enforcement->type)) }}</strong> agar Saudara 
               segera melakukan pelunasan sebelum tanggal <strong>{{ \Carbon\Carbon::parse($enforcement->due_date)->format('d F Y') }}</strong>.</p>
        @elseif($enforcement->type === 'paksa')
            <p>Sehubungan Surat Teguran I dan II yang telah dilayangkan sebelumnya tidak ditindaklanjuti dengan pelunasan pokok beserta denda tunggakan, 
               dengan ini kami menerbitkan <strong>SURAT PAKSA</strong>. Saudara diwajibkan segera melunasi tunggakan dalam batas waktu selambat-lambatnya 2x24 jam sejak surat ini diterima.</p>
        @else
            <p>Berdasarkan aturan Perundang-undangan Perpajakan Daerah, objek pajak milik Saudara dengan ini kami nyatakan <strong>DALAM SITA / PENYITAAN</strong>.</p>
        @endif

        @if($enforcement->notes)
            <p><strong>Catatan Tambahan:</strong> {{ $enforcement->notes }}</p>
        @endif

        <p>Demikian surat penegakan ini disampaikan untuk menjadi perhatian dan segera ditindaklanjuti. Atas kerja samanya kami ucapkan terima kasih.</p>
    </div>

    <div class="signature-section">
        <p><strong>Kepala Bidang Penegakan</strong></p>
        <p><strong>Bapenda Kota Baubau</strong></p>
        <div class="signature-space"></div>
        <p style="text-decoration: underline; font-weight: bold;">[Nama Kabid Penegakan]</p>
        <p>NIP. [NIP Pegawai]</p>
    </div>

</body>
</html>

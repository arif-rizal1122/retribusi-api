<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 40px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; text-decoration: underline; margin-top: 20px; margin-bottom: 5px; }
        .number { text-align: center; margin-bottom: 25px; }
        .content { margin-bottom: 30px; text-align: justify; }
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-info td { padding: 5px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .footer { margin-top: 50px; }
        .signature-table { width: 100%; }
        .signature-box { text-align: center; width: 250px; }
        .warning-box { border: 2px solid #000; padding: 15px; margin: 20px 0; background: #f9f9f9; }
        .urgent { color: #CC0000; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf.header')

    <div class="title">
        @if($type == 'teguran_1') SURAT TEGURAN I @endif
        @if($type == 'teguran_2') SURAT TEGURAN II @endif
        @if($type == 'jatuh_tempo') SURAT TEGURAN JATUH TEMPO @endif
    </div>
    <div class="number">Nomor: {{ $number }}</div>

    <div class="content">
        <p>Berdasarkan catatan pada Badan Pendapatan Daerah Kota Baubau, sampai dengan saat ini Saudara/i belum melakukan pelunasan Pajak/Retribusi Daerah untuk objek berikut:</p>
        
        <table class="table-info">
            <tr>
                <td class="label">NAMA WAJIB PAJAK</td>
                <td>: {{ $taxpayer_name }}</td>
            </tr>
            <tr>
                <td class="label">ALAMAT</td>
                <td>: {{ $taxpayer_address }}</td>
            </tr>
            <tr>
                <td class="label">OBJEK PAJAK</td>
                <td>: {{ $tax_object_name }}</td>
            </tr>
            <tr>
                <td class="label">MASA PAJAK</td>
                <td>: {{ $period }}</td>
            </tr>
            <tr>
                <td class="label">NOMOR TAGIHAN</td>
                <td>: {{ $bill_number }}</td>
            </tr>
        </table>

        <div class="warning-box">
            <p style="margin: 0; font-weight: bold; text-decoration: underline;">RINCIAN TUNGGAKAN:</p>
            <table style="width: 100%; margin-top: 10px;">
                <tr>
                    <td>Pokok Pajak/Retribusi</td>
                    <td style="text-align: right;">Rp {{ number_format($amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Sanksi Administrasi (Bunga/Denda)</td>
                    <td style="text-align: right;">Rp {{ number_format($penalty, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 1px solid #000; font-weight: bold;">
                    <td>TOTAL YANG HARUS DIBAYAR</td>
                    <td style="text-align: right;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </table>
            <p style="margin-top: 10px; font-style: italic;">Terbilang: {{ $terbilang }}</p>
        </div>

        <p>
            @if($type == 'teguran_1')
                Sehubungan dengan hal tersebut di atas, Saudara/i diminta untuk segera melakukan pembayaran dalam waktu 7 (tujuh) hari sejak diterimanya surat ini.
            @elseif($type == 'teguran_2')
                <span class="urgent">Ini adalah Teguran Terakhir.</span> Apabila dalam waktu 3 (tiga) hari Saudara/i tidak melunasi tunggakan tersebut, maka akan diterbitkan <strong>Surat Paksa</strong> sesuai ketentuan peraturan perundang-undangan.
            @else
                Mengingat masa pajak telah jatuh tempo, Saudara/i diminta untuk segera melakukan penyelesaian administrasi perpajakan guna menghindari sanksi yang lebih berat.
            @endif
        </p>

        <p>Pembayaran dapat dilakukan melalui teller bank persepsi atau kanal pembayaran digital resmi (QRIS/Mobile Banking).</p>
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div style="text-align: center;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qr_url) }}" alt="QR Code">
                    <p style="font-size: 8px; margin: 5px 0 0 0;">VERIFIKASI LEGALITAS DOKUMEN</p>
                </div>
            </td>
            <td style="width: 50%; text-align: right;">
                <div style="display: inline-block; text-align: center;">
                    <p>Baubau, {{ $date }}</p>
                    <p>Kepala Badan Pendapatan Daerah,</p>
                    <br><br><br>
                    <p><strong>( ________________________ )</strong></p>
                    <p>NIP. .............................</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

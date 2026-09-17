<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.3; color: #333; margin: 25px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; text-decoration: underline; margin-top: 10px; margin-bottom: 2px; }
        .number { text-align: center; margin-bottom: 15px; }
        .content { margin-bottom: 15px; text-align: justify; }
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-info td { padding: 3px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .footer { margin-top: 30px; }
        .signature-table { width: 100%; }
        .signature-box { text-align: center; width: 250px; }
        .warning-box { border: 2px solid #000; padding: 10px; margin: 10px 0; background: #f9f9f9; }
        .urgent { color: #CC0000; font-weight: bold; }
    </style>
<style>
    /* Compact layout — agar dokumen muat 1 halaman A4 */
    html, body { font-size: 9px !important; line-height: 1.28 !important; margin: 0 !important; padding: 0 !important; }
    @page { margin: 22px 26px 22px 26px; }
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
                    <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url, 100) }}" alt="QR Code">
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
</body>
</html>

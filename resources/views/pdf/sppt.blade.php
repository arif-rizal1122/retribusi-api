<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPPT PBB-P2</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 10pt; line-height: 1.3; color: #333; margin: 0; padding: 0; }
        .container { padding: 25px; }
        
        .sppt-label { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 10px; text-decoration: underline; }
        
        .section { margin-bottom: 10px; }
        .section-title { font-weight: bold; background: #f0f0f0; padding: 3px; margin-bottom: 5px; border: 1px solid #ccc; font-size: 9pt; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table th, table td { padding: 4px 8px; vertical-align: top; }
        
        .label { width: 30%; font-weight: bold; }
        .value { width: 70%; }
        
        .grid-table { border: 1px solid #000; }
        .grid-table th, .grid-table td { border: 1px solid #000; text-align: left; }
        
        .total-box { border: 2px solid #000; padding: 10px; margin-top: 20px; }
        .total-amount { font-size: 14pt; font-weight: bold; color: #000; }
        
        .footer { margin-top: 50px; }
        .signature-table { width: 100%; }
        .signature-cell { width: 50%; text-align: center; }
        .qr-code { width: 100px; height: 100px; }
        
        .watermark { position: fixed; top: 40%; left: 15%; font-size: 80pt; color: rgba(0,0,0,0.05); transform: rotate(-45deg); z-index: -1; }
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
    <div class="watermark">V-TAX VERIFIED</div>
    
    <div class="container">
        @include('pdf.header')

        <div class="sppt-label">SURAT PEMBERITAHUAN PAJAK TERUTANG (SPPT)</div>
        
        <div class="section">
            <table>
                <tr>
                    <td class="label">NOMOR OBJEK PAJAK (NOP)</td>
                    <td class="value">: <strong>{{ $nop }}</strong></td>
                </tr>
                <tr>
                    <td class="label">TAHUN PAJAK</td>
                    <td class="value">: {{ $year }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">A. PENETAPAN OBJEK PAJAK</div>
            <table class="grid-table">
                <thead>
                    <tr>
                        <th>OBJEK PAJAK</th>
                        <th>LUAS (m2)</th>
                        <th>KELAS</th>
                        <th>NJOP PER m2 (Rp)</th>
                        <th>TOTAL NJOP (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>BUMI (TANAH)</td>
                        <td>{{ number_format($luas_tanah, 0, ',', '.') }}</td>
                        <td>{{ $kelas_bumi }}</td>
                        <td>{{ number_format($njop_bumi_m2, 0, ',', '.') }}</td>
                        <td>{{ number_format($total_njop_bumi, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BANGUNAN</td>
                        <td>{{ number_format($luas_bangunan, 0, ',', '.') }}</td>
                        <td>{{ $kelas_bangunan }}</td>
                        <td>{{ number_format($njop_bangunan_m2, 0, ',', '.') }}</td>
                        <td>{{ number_format($total_njop_bangunan, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td colspan="4" style="text-align: right;">TOTAL NJOP</td>
                        <td>{{ number_format($total_njop, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="section">
            <div class="section-title">B. PERHITUNGAN PBB-P2</div>
            <table>
                <tr>
                    <td class="label">NJOP sebagai dasar pengenaan PBB</td>
                    <td class="value">: Rp {{ number_format($total_njop, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">NJOPTKP (Niat Jual Objek Pajak Tidak Kena Pajak)</td>
                    <td class="value">: Rp {{ number_format($njoptkp, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">NJOP untuk perhitungan PBB</td>
                    <td class="value">: Rp {{ number_format($njop_kp, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Tarif Pajak (%)</td>
                    <td class="value">: {{ $tariff_percent }}%</td>
                </tr>
                <tr>
                    <td class="label"><strong>PBB YANG TERUTANG</strong></td>
                    <td class="value">: <span class="total-amount">Rp {{ number_format($pbb_terhutang, 0, ',', '.') }}</span></td>
                </tr>
                <tr>
                    <td class="label">TERBILANG</td>
                    <td class="value">: <em>{{ $terbilang }} Rupiah</em></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="total-box">
                <strong>JATUH TEMPO PEMBAYARAN: {{ $due_date }}</strong><br>
                <small>Pembayaran dapat dilakukan melalui Bank Sultra, Kantor Pos, atau Payment Point yang ditunjuk.</small>
            </div>
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
            <table class="signature-table">
                <tr>
                    <td class="signature-cell">
                        <p>Scan untuk Verifikasi:</p>
                        <img src="{{ $qr_data_uri ?? ('data:image/svg+xml;base64,' . ($qr_base64 ?? '')) }}" class="qr-code" alt="QR Code">
                    </td>
                    <td class="signature-cell">
                        <p>Baubau, {{ date('d F Y') }}</p>
                        <p>KEPALA BADAN PENDAPATAN DAERAH</p>
                        <br><br><br>
                        <p><strong>( __________________________ )</strong></p>
                        <p>NIP. .............................</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>

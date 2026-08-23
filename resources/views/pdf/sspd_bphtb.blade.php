<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SSPD-BPHTB {{ $sspd_number }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #000; margin: 20px 30px; line-height: 1.3; }
        .center { text-align: center; }
        .gov-title { font-size: 15px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .doc-title { font-size: 14px; font-weight: bold; margin: 2px 0; }
        .sub-title { font-size: 10px; margin: 1px 0; font-weight: bold; }
        .normal-note { font-size: 9px; margin: 2px 0; }
        .section { margin: 10px 0; }
        .sec-title { background: #000; color: #fff; font-weight: bold; padding: 3px 8px; font-size: 11px; }
        table.fields { width: 100%; border-collapse: collapse; }
        table.fields td { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
        table.fields td.label { width: 45%; font-weight: normal; }
        table.fields td.value { font-weight: bold; }
        table.njop { width: 100%; border-collapse: collapse; }
        table.njop th, table.njop td { border: 1px solid #000; padding: 4px 6px; text-align: center; }
        table.njop td.num { text-align: right; }
        table.njop th { background: #e0e0e0; }
        .total-row { font-weight: bold; background: #f0f0f0; }
        .amount { font-weight: bold; }
        .two-col { width: 100%; }
        .two-col td { width: 50%; vertical-align: top; }
        .ttd { text-align: center; margin-top: 8px; }
        .ttd-name { font-weight: bold; text-decoration: underline; margin-top: 70px; }
        .check { font-size: 16px; font-weight: bold; }
        .qr { text-align: center; }
    </style>
</head>
<body>
    <div class="center">
        <div class="gov-title">Pemerintah Kota Baubau</div>
        <div class="doc-title">SURAT SETORAN PAJAK DAERAH (SSPD-BPHTB)</div>
        <div class="sub-title">BEA PEROLEHAN HAK ATAS TANAH DAN BANGUNAN BERFUNGSI SEBAGAI SURAT PEMBERITAHUAN OBJEK PAJAK PAJAK BUMI DAN BANGUNAN (SPOP PBB)</div>
        <div class="normal-note">LEMBAR 1 - Untuk Wajib Pajak</div>
    </div>

    <div class="section">
        <div class="sec-title">A. IDENTITAS WAJIB PAJAK</div>
        <table class="fields">
            <tr><td class="label">1. Nama Wajib Pajak</td><td class="value">{{ $nama_wp }}</td></tr>
            <tr><td class="label">2. NPWP</td><td class="value">{{ $nik_wp ?? '-' }}</td></tr>
            <tr><td class="label">3. Alamat Wajib Pajak</td><td class="value">{{ $alamat_wp ?? '-' }}</td></tr>
            <tr><td class="label">4. Kelurahan/Desa</td><td class="value"></td></tr>
            <tr><td class="label">5. RT/RW</td><td class="value"></td></tr>
            <tr><td class="label">6. Kecamatan</td><td class="value"></td></tr>
            <tr><td class="label">7. Kabupaten/Kota</td><td class="value">Baubau</td></tr>
            <tr><td class="label">8. Kode Pos</td><td class="value"></td></tr>
        </table>
    </div>

    <div class="section">
        <div class="sec-title">B. DATA OBJEK PAJAK</div>
        <table class="fields">
            <tr><td class="label">1. No. Objek Pajak (NOP)</td><td class="value">{{ $nop ?? '-' }}</td></tr>
            <tr><td class="label">2. Letak Tanah dan/atau Bangunan</td><td class="value">{{ $alamat_objek ?? '-' }}</td></tr>
            <tr><td class="label">3. Kelurahan/Desa</td><td class="value"></td></tr>
            <tr><td class="label">4. RT/RW</td><td class="value"></td></tr>
            <tr><td class="label">5. Kecamatan</td><td class="value"></td></tr>
            <tr><td class="label">6. Kabupaten/Kota</td><td class="value">Baubau</td></tr>
        </table>
        <br>
        <table class="njop">
            <thead>
                <tr>
                    <th style="width:40%;">Uraian</th>
                    <th>Luas (m²)</th>
                    <th>NJOP PBB/M²</th>
                    <th>Luas x NJOP PBB/M²</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tanah (Bumi)</td>
                    <td class="num">{{ number_format($luas_tanah, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($njop_per_m2, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($nilai_tanah, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Bangunan</td>
                    <td class="num">{{ number_format($luas_bangunan, 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($njop_per_m2, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($nilai_bangunan, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total NJOP PBB</td>
                    <td colspan="2"></td>
                    <td class="num">{{ number_format($total_njop_pbb, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        <br>
        <table class="fields">
            <tr><td class="label">14. Harga Transaksi/Nilai Pasar</td><td class="value">Rp {{ number_format($harga_transaksi, 0, ',', '.') }}</td></tr>
            <tr><td class="label">15. Jenis perolehan hak atas tanah dan bangunan</td><td class="value">{{ $jenis_perolehan ?? '-' }}</td></tr>
            <tr><td class="label">16. No. Sertifikat</td><td class="value">{{ $no_sertifikat ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="sec-title">C. PENGHITUNGAN BPHTB</div>
        <table class="fields">
            <tr><td class="label">1. Nilai Perolehan Objek Pajak (NPOP)</td><td class="value">Rp {{ number_format($npop, 0, ',', '.') }}</td></tr>
            <tr><td class="label">2. NPOPTKP</td><td class="value">Rp {{ number_format($npoptkp, 0, ',', '.') }}</td></tr>
            <tr><td class="label">3. NPOPKP</td><td class="value">Rp {{ number_format($npopkp, 0, ',', '.') }}</td></tr>
            <tr><td class="label">4. BPHTB terhutang</td><td class="value">Rp {{ number_format($bphtb, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="sec-title">D. JUMLAH SETORAN</div>
        <table class="fields">
            <tr>
                <td class="label">Berdasarkan</td>
                <td class="value">
                    <span class="check">[&nbsp;&nbsp;]</span>&nbsp;a&nbsp;penghitungan WP&nbsp;&nbsp;
                    <span class="check">[&nbsp;&nbsp;]</span>&nbsp;b&nbsp;STPD/SKPDB&nbsp;&nbsp;
                    <span class="check">[&nbsp;&nbsp;]</span>&nbsp;c&nbsp;pengurangan&nbsp;&nbsp;
                    <span class="check">[&nbsp;&nbsp;]</span>&nbsp;d&nbsp;lainnya
                </td>
            </tr>
        </table>
        <table class="fields" style="margin-top:6px;">
            <tr><td class="label">JUMLAH YANG DISETOR (angka)</td><td class="value amount">Rp {{ number_format($jumlah_disetor, 0, ',', '.') }}</td></tr>
            <tr><td class="label">JUMLAH YANG DISETOR (huruf)</td><td class="value">{{ $terbilang }} Rupiah</td></tr>
        </table>
    </div>

    <table class="two-col">
        <tr>
            <td>
                <div class="ttd">WAJIB PAJAK / PENYETOR</div>
                <div class="ttd-name">{{ $nama_wp }}</div>
                <div style="text-align:center;">(nama dan stempel)</div>
            </td>
            <td>
                <div class="ttd">DITERIMA OLEH,<br>TEMPAT PEMBAYARAN BPHTB</div>
                <div class="ttd-name"></div>
                <div style="text-align:center;">
                    Baubau, {{ $issued_at ? $issued_at->format('d-m-Y') : \Carbon\Carbon::parse($paid_at ?? now())->format('d-m-Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table style="width:100%; margin-top:14px;">
        <tr>
            <td style="width:40%; font-size:9px;">
                <div>Nomor SSPD: <strong>{{ $sspd_number }}</strong></div>
                <div>No. Hak Akses (QR): <strong>{{ $sspd_number }}</strong></div>
            </td>
            <td class="qr">
                @if($qr_image)
                <img src="{{ $qr_image }}" style="width:80px; height:80px;">
                @else
                <div style="width:80px; height:80px; border:1px solid #000; display:inline-block;"></div>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #333; margin: 25px; line-height: 1.3; }
        .main-title { font-size: 15px; font-weight: bold; margin: 5px 0; text-align: center; text-transform: uppercase; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
        .info-box { border: 1px solid #ccc; padding: 8px; border-radius: 5px; }
        .info-title { font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 3px; font-size: 9px; color: #666; }
        .payment-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .payment-table th { background: #1e3a8a; color: #fff; padding: 8px; text-align: left; font-size: 10px; }
        .payment-table td { border-bottom: 1px solid #eee; padding: 7px 8px; }
        .status-badge { background: #dcfce7; color: #166534; padding: 5px 10px; border-radius: 20px; font-weight: bold; font-size: 11px; display: inline-block; }
        .token-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 8px; margin: 15px 0; }
        .footer { margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div style="float: right;" class="status-badge">LUNAS / IZIN RESMI AKTIF</div>
    
    @include('pdf.header')
    <div class="main-title">{{ $document_title ?? 'SURAT IZIN PEMAKAIAN ALAT BERAT & UTILITAS (SIPAB)' }}</div>
    <div style="text-align: center; margin-bottom: 15px; font-size: 10px; color: #555;">NOMOR: <strong>{{ $document_number ?? $number }}</strong></div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-title">IDENTITAS PENYEWA / PEMOHON</div>
            <div style="font-weight: bold; font-size: 13px;">{{ $tenant_name ?? $taxpayer }}</div>
            <div>NIK/NIB: {{ $tenant_nik ?? $taxpayer_nik ?? '-' }}</div>
            <div>Alamat: {{ $address ?? 'Kota Baubau' }}</div>
            <div>Penanggung Jawab: {{ $penanggung_jawab ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-title">OPD PENGELOLA BARANG MILIK DAERAH (BMD)</div>
            <div style="font-weight: bold; font-size: 12px;">{{ $opd_name ?? 'DINAS PEKERJAAN UMUM DAN PENATAAN RUANG' }}</div>
            <div>Unit Kerja: {{ $unit_name ?? 'UPTD Workshop & Pengelolaan Alat' }}</div>
            <div>Dasar Regulasi: Perda Kota Baubau No. 1 Tahun 2024</div>
        </div>
        <div class="info-box">
            <div class="info-title">RINCIAN UNIT ASET DAERAH</div>
            <div>Nama Unit / Objek: <strong>{{ $asset_name ?? $object_name ?? 'Unit Alat Berat' }}</strong></div>
            <div>Kode Barang Milik Daerah: {{ $bmd_code ?? '-' }}</div>
            <div>Lokasi Penggunaan: {{ $lokasi_penggunaan ?? 'Wilayah Kota Baubau' }}</div>
        </div>
        <div class="info-box">
            <div class="info-title">JANGKA WAKTU &amp; OPERASIONAL</div>
            <div>Masa Berlaku: <strong>{{ $valid_from ?? '-' }} s.d. {{ $valid_until ?? '-' }}</strong></div>
            <div>Durasi Sewa: {{ $duration ?? '1' }} Hari / Satuan</div>
            <div>Operator Resmi: {{ $operator_name ?? 'Staf UPTD Ditugaskan' }}</div>
        </div>
    </div>

    <table class="payment-table">
        <thead>
            <tr>
                <th>DESKRIPSI RETRIBUSI PEMANFAATAN ASET</th>
                <th style="text-align: right;">NILAI SETORAN (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Retribusi Sewa BMD - {{ $asset_name ?? $title }} (Lunas ke RKUD)</td>
                <td style="text-align: right;">{{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            @if(isset($biaya_tronton) && $biaya_tronton > 0)
            <tr>
                <td>Biaya Mobilisasi / Tronton Unit</td>
                <td style="text-align: right;">{{ number_format($biaya_tronton, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td style="font-size: 12px;">TOTAL SETORAN LUNAS</td>
                <td style="text-align: right; font-size: 14px; color: #1e3a8a;">Rp {{ number_format($total_amount ?? $amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-bottom: 12px;">
        <p style="font-size: 10px;"><strong>TERBILANG:</strong> <em># {{ strtoupper($terbilang ?? App\Services\OfficialDocumentService::terbilang($total_amount ?? $amount)) }} RUPIAH #</em></p>
        <p style="font-size: 10px;"><strong>NOMOR TRANSAKSI (NTPD):</strong> <span style="font-family: monospace; font-weight: bold;">{{ $ntpd ?? $payment_reference ?? 'NTPD-7471-TERVALIDASI' }}</span></p>
    </div>

    @if(isset($token_code) && $token_code)
    <div class="token-box">
        <div style="font-size: 9px; font-weight: bold; color: #166534;">KODE REGISTRASI VERIFIKASI RESMI OPD (TRANSISI TTE TERDAFTAR):</div>
        <div style="font-family: monospace; font-size: 14px; font-weight: bold; color: #14532d; letter-spacing: 1px; margin-top: 2px;">{{ $token_code }}</div>
        <div style="font-size: 8px; color: #15803d; margin-top: 3px;">
            Dokumen ini disahkan oleh Kepala OPD melalui Sistem M-PAD Kota Baubau dan dilindungi secara kriptografis oleh Segel Elektronik Pemerintah Kota Baubau.
        </div>
    </div>
    @endif

    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url ?? url('/api/tte/verify/' . ($document_number ?? $number)), 95) }}" alt="Verification QR">
                <p style="font-size: 8px; margin-top: 4px; color: #666;">PINDAI UNTUK VALIDASI BSrE</p>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <p style="font-size: 10px; margin-bottom: 3px;">Baubau, {{ date('d F Y') }}</p>
                <p style="font-size: 10px; font-weight: bold; margin-top: 0;">{{ $signer_title ?? 'KEPALA DINAS PENGELOLA ASET' }}</p>
                <div style="margin: 10px 0; color: #047857; font-size: 9px; font-weight: bold; border: 1px dashed #059669; padding: 4px; border-radius: 4px; display: inline-block;">
                    [ TANDATANGAN ELEKTRONIK TERVERIFIKASI ]<br>
                    SEGEL ELEKTRONIK RESMI BSrE - BSSN
                </div>
                <p style="font-size: 10px; font-weight: bold; margin-bottom: 0;">{{ $signer_name ?? 'PEMERINTAH KOTA BAUBAU' }}</p>
                <p style="font-size: 9px; color: #666; margin-top: 1px;">NIP. {{ $signer_nip ?? '-' }}</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Surat Izin Pemakaian Aset ini merupakan dokumen resmi yang diterbitkan secara elektronik melalui Super App M-PAD Kota Baubau.</p>
        <p>Penyewa wajib mematuhi batas jam operasional dan ketentuan keselamatan kerja yang berlaku.</p>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #333; margin: 25px; line-height: 1.3; }
        .title { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; margin-top: 5px; margin-bottom: 2px; }
        .subtitle { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-info td { padding: 3px; vertical-align: top; }
        .section-header { background: #f0f0f0; font-weight: bold; padding: 5px; border: 1px solid #ccc; margin-top: 5px; margin-bottom: 5px; }
        .grid-table { border: 1px solid #000; }
        .grid-table td, .grid-table th { border: 1px solid #000; padding: 5px; }
        .footer { margin-top: 15px; font-size: 9px; text-align: center; color: #666; }
    </style>
</head>
<body>
    @include('pdf.header')

    <div class="section-title">I. IDENTITAS SUBJEK DAN OBJEK PAJAK</div>
    <table class="table-data">
        <tr>
            <td class="label">Nama Wajib Pajak</td>
            <td>{{ $taxpayer_name }}</td>
        </tr>
        <tr>
            <td class="label">Nama Objek Pajak</td>
            <td>{{ $object_name }}</td>
        </tr>
        <tr>
            <td class="label">Alamat Objek</td>
            <td>{{ $object_address }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Retribusi/Pajak</td>
            <td>{{ $retribution_type }}</td>
        </tr>
        <tr>
            <td class="label">Klasifikasi / Zona</td>
            <td>{{ $classification }} / {{ $zone }}</td>
        </tr>
        <tr>
            <td class="label">Koordinat GPS</td>
            <td>Lat: {{ $coordinates['latitude'] ?? '-' }}, Lng: {{ $coordinates['longitude'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">II. METADATA DAN PARAMETER PERHITUNGAN</div>
    <table class="table-data">
        @forelse ($metadata as $key => $value)
        <tr>
            <td class="label">{{ strtoupper(str_replace('_', ' ', $key)) }}</td>
            <td>{{ is_scalar($value) ? $value : json_encode($value) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" style="text-align: center; font-style: italic;">Tidak ada metadata tambahan</td>
        </tr>
        @endforelse
    </table>

    <div class="section-title">III. STATUS DAN VERIFIKASI</div>
    <table class="table-data">
        <tr>
            <td class="label">Status Aktif</td>
            <td>{{ strtoupper($status) }}</td>
        </tr>
        <tr>
            <td class="label">Status Audit</td>
            <td>{{ strtoupper($audit_status) }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Registrasi</td>
            <td>{{ $registered_at }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Penilaian</td>
            <td>{{ $assessed_at }}</td>
        </tr>
    </table>

    <div class="footer">
        <div class="qr-box">
            <img src="{{ $qr_data_uri ?? app(\App\Services\OfficialDocumentService::class)->qrImageDataUri($qr_url, 80) }}" alt="QR Code">
            <p style="font-size: 7px;">VERIFIKASI DATA TEKNIS</p>
        </div>
        
        <div class="signature-box">
            <p>Baubau, {{ date('d F Y') }}</p>
            <p>Petugas Penilai / Pemeriksa,</p>
            <br><br><br>
            <p><strong>( ________________________ )</strong></p>
            <p>NIP. .............................</p>
        </div>
    </div>

    <div class="signature-section" style="margin-top: 25px; page-break-inside: avoid;">
        <table style="width:100%; border:none;">
            <tr>
                <td style="width:55%; text-align:center; vertical-align:top; border:none;">
                    @if(!empty($signature['tte_qr']))
                    <img src="{{ $signature['tte_qr'] }}" alt="QR TTE" style="width:80px; height:80px;">
                    <p style="font-size:8px; margin:4px 0 0 0;">Tanda Tangan Elektronik</p>
                    @endif
                </td>
                <td style="width:45%; text-align:center; vertical-align:top; border:none;">
                    <p style="margin:0;">{{ $signature['kota'] ?? 'Baubau' }}, {{ date('d F Y') }}</p>
                    <p style="margin:0;"><strong>{{ $signature['jabatan'] ?? 'Kepala Badan Pendapatan Daerah' }}</strong></p>
                    <div style="height:70px;"></div>
                    <p style="text-decoration: underline; font-weight: bold; margin:0;">{{ $signature['nama'] ?? '[Nama Pejabat]' }}</p>
                    <p style="margin:0;">NIP. {{ $signature['nip'] ?? '-' }}</p>
                </td>
            </tr>
        </table>
        @if(!empty($signature['tte_keterangan']))
        <p style="font-size:8px; font-style:italic; text-align:center; margin-top:6px;">{{ $signature['tte_keterangan'] }}</p>
        @endif
    </div>

</body>
</html>

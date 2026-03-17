<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; margin: 30px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; }
        .section-title { font-weight: bold; background: #eee; padding: 5px; margin-top: 15px; border: 1px solid #000; }
        .table-data { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .table-data td { padding: 4px; border: 1px solid #000; vertical-align: top; }
        .label { width: 150px; font-weight: bold; }
        .footer { margin-top: 30px; }
        .qr-box { float: left; text-align: center; }
        .signature-box { float: right; text-align: center; width: 200px; }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-weight: bold; font-size: 12px;">PEMERINTAH KOTA BAUBAU</div>
        <div class="title">LEMBAR KERJA OBJEK KHUSUS (LKOK)</div>
        <div>Nomor: {{ $lkok_number }}</div>
    </div>

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
        @forelse($metadata as $key => $value)
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
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($qr_url) }}" alt="QR Code">
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
</body>
</html>

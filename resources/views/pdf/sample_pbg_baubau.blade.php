<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Persetujuan Bangunan Gedung (PBG) - Kota Baubau</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 20mm 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #111;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header h3 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 2px 0;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 9pt;
            color: #333;
        }
        .title {
            text-align: center;
            margin: 15px 0 10px 0;
        }
        .title h4 {
            margin: 0;
            font-size: 12pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .title p {
            margin: 3px 0 0 0;
            font-size: 10pt;
            font-weight: bold;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table.data-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 10.5pt;
        }
        table.data-table td.label {
            width: 32%;
            font-weight: 600;
        }
        table.data-table td.sep {
            width: 3%;
            text-align: center;
        }
        table.data-table td.val {
            width: 65%;
        }
        .box-info {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin: 12px 0;
        }
        .box-title {
            font-weight: bold;
            font-size: 10.5pt;
            margin-bottom: 6px;
            color: #0f2547;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .footer-sign {
            margin-top: 25px;
            width: 100%;
        }
        .footer-sign table {
            width: 100%;
        }
        .qr-placeholder {
            border: 1px dashed #64748b;
            width: 80px;
            height: 80px;
            text-align: center;
            line-height: 80px;
            font-size: 8pt;
            color: #64748b;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>PEMERINTAH KOTA BAUBAU</h3>
        <h2>DINAS PEKERJAAN UMUM DAN PENATAAN RUANG</h2>
        <p>Jl. Raya Palagimata No. 1, Kota Baubau, Sulawesi Tenggara 93724</p>
        <p>Laman: simbg.pu.go.id | Pos-el: pupr@baubaukota.go.id</p>
    </div>

    <div class="title">
        <h4>SURAT KEPUTUSAN KEPALA DINAS PUPR KOTA BAUBAU</h4>
        <p>Nomor: PBG-747201-20092026-001</p>
        <p style="font-size: 9pt; font-weight: normal; margin-top: 2px;">Nomor Registrasi SIMBG: SIMBG-747201-20092026-001</p>
    </div>

    <p style="font-size: 10pt; text-align: justify; margin: 8px 0;">
        Berdasarkan Undang-Undang Nomor 28 Tahun 2002 tentang Bangunan Gedung dan Peraturan Pemerintah Nomor 16 Tahun 2021 tentang Peraturan Pelaksanaan Undang-Undang Nomor 28 Tahun 2002 tentang Bangunan Gedung, bersama ini Kepala Dinas Pekerjaan Umum dan Penataan Ruang Kota Baubau memberikan:
    </p>

    <div style="text-align: center; margin: 6px 0;">
        <strong style="font-size: 11pt; letter-spacing: 1px;">PERSETUJUAN BANGUNAN GEDUNG (PBG)</strong>
    </div>

    <div class="box-info">
        <div class="box-title">I. IDENTITAS PEMILIK / PEMOHON</div>
        <table class="data-table">
            <tr>
                <td class="label">Nama Pemilik</td>
                <td class="sep">:</td>
                <td class="val"><strong>{{ $data['owner_name'] ?? 'Wajib Pajak Test' }}</strong></td>
            </tr>
            <tr>
                <td class="label">NIK</td>
                <td class="sep">:</td>
                <td class="val">{{ $data['owner_nik'] ?? '7472060412038801' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat Pemilik</td>
                <td class="sep">:</td>
                <td class="val">{{ $data['owner_address'] ?? 'Kanakea Dalam, Kel. Nganganaumala, Kec. Batupoaro, Kota Baubau' }}</td>
            </tr>
        </table>
    </div>

    <div class="box-info">
        <div class="box-title">II. SPESIFIKASI TEKNIS BANGUNAN GEDUNG</div>
        <table class="data-table">
            <tr>
                <td class="label">Lokasi Bangunan</td>
                <td class="sep">:</td>
                <td class="val">{{ $data['building_address'] ?? 'Kanakea Dalam, Baubau, Sulawesi Tenggara, 93721' }}</td>
            </tr>
            <tr>
                <td class="label">Fungsi Bangunan</td>
                <td class="sep">:</td>
                <td class="val"><strong>{{ $data['building_usage'] ?? 'Fungsi Hunian (Rumah Tinggal)' }}</strong></td>
            </tr>
            <tr>
                <td class="label">Jumlah Lantai</td>
                <td class="sep">:</td>
                <td class="val"><strong>{{ $data['building_floors'] ?? '2' }} Lantai</strong></td>
            </tr>
            <tr>
                <td class="label">Luas Bangunan</td>
                <td class="sep">:</td>
                <td class="val"><strong>{{ $data['building_area'] ?? '140' }} m²</strong></td>
            </tr>
            <tr>
                <td class="label">Luas Lantai Dasar</td>
                <td class="sep">:</td>
                <td class="val">{{ $data['ground_floor_area'] ?? '85' }} m²</td>
            </tr>
            <tr>
                <td class="label">Luas Tanah</td>
                <td class="sep">:</td>
                <td class="val">{{ $data['land_area'] ?? '200' }} m²</td>
            </tr>
        </table>
    </div>

    <div class="footer-sign">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: bottom; font-size: 8.5pt; color: #475569;">
                    <p style="margin: 0;">Dokumen ini diterbitkan secara elektronik melalui SIMBG.</p>
                    <p style="margin: 2px 0;">Keabsahan dapat diverifikasi melalui pemindaian QR Code resmi.</p>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: top;">
                    <p style="margin: 0 0 4px 0;">Ditetapkan di: Baubau</p>
                    <p style="margin: 0 0 8px 0;">Pada tanggal: <strong>{{ $data['pbg_date'] ?? '15 September 2026' }}</strong></p>
                    <p style="margin: 0; font-size: 9pt;">Kepala Dinas PUPR Kota Baubau</p>
                    <br><br><br>
                    <p style="margin: 0; font-weight: bold; text-decoration: underline;">Ir. H. SYAMSUL BAHRI, M.Si</p>
                    <p style="margin: 0; font-size: 8.5pt;">NIP. 19740510 200212 1 004</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 40px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .kop-surat { font-weight: bold; font-size: 18px; margin: 0; }
        .sub-kop { font-size: 14px; margin: 0; }
        .title { text-align: center; font-weight: bold; font-size: 16px; text-decoration: underline; margin-top: 20px; margin-bottom: 5px; }
        .number { text-align: center; margin-bottom: 25px; }
        .content { margin-bottom: 30px; text-align: justify; }
        .table-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-info td { padding: 5px; vertical-align: top; }
        .label { width: 180px; font-weight: bold; }
        .signature-table { width: 100%; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="kop-surat">PEMERINTAH KOTA BAUBAU</p>
        <p class="kop-surat">BADAN PENDAPATAN DAERAH</p>
        <p class="sub-kop">Jl. Raya No. 1, Kota Baubau, Sulawesi Tenggara</p>
    </div>

    <div class="title">SURAT TEGURAN PELAPORAN SPTPD</div>
    <div class="number">Nomor: {{ $number }}</div>

    <div class="content">
        <p>Berdasarkan sistem monitoring pelaporan pajak daerah (E-SPTPD), Saudara/i sebagai Wajib Pajak yang terdaftar belum melakukan pelaporan data omzet/transaksi untuk masa pajak berikut:</p>
        
        <table class="table-info">
            <tr>
                <td class="label">NAMA WAJIB PAJAK</td>
                <td>: {{ $taxpayer_name }}</td>
            </tr>
            <tr>
                <td class="label">NPWPD</td>
                <td>: {{ $npwpd }}</td>
            </tr>
            <tr>
                <td class="label">OBJEK PAJAK</td>
                <td>: {{ $tax_object_name }}</td>
            </tr>
            <tr>
                <td class="label">MASA PAJAK</td>
                <td>: <strong>{{ $period }}</strong></td>
            </tr>
        </table>

        <p>Mengingat batas waktu pelaporan SPTPD paling lambat adalah setiap tanggal 15 bulan berikutnya, maka Saudara/i telah melewati batas waktu yang ditentukan. Pelambatan pelaporan ini dikenakan sanksi administrasi berupa denda sesuai dengan Peraturan Walikota Baubau Nomor 58 Tahun 2024.</p>

        <p>Sehubungan dengan hal tersebut, Saudara/i diminta untuk segera melakukan pelaporan melalui aplikasi <strong>M-PAD</strong> atau portal <strong>https://mpad.online</strong> dalam waktu 3 (tiga) hari kerja sejak surat ini diterima.</p>

        <p>Apabila Saudara/i mengabaikan teguran ini, maka akan dilakukan **Penetapan Pajak secara Jabatan (SKPDKB)** oleh Tim Pemeriksa Pajak Daerah Badan Pendapatan Daerah Kota Baubau.</p>
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
                    <p>Kepala Bidang Pengawasan,</p>
                    <br><br><br>
                    <p><strong>( ________________________ )</strong></p>
                    <p>NIP. .............................</p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

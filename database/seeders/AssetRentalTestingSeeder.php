<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Taxpayer;
use App\Models\User;
use App\Models\Opd;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssetRentalTestingSeeder extends Seeder
{
    public function run(): void
    {
        $dishub = Opd::where('code', 'DISHUB')->first() ?? Opd::create([
            'code' => 'DISHUB',
            'name' => 'Dinas Perhubungan',
            'status' => 'approved',
            'is_active' => true,
        ]);

        $dishubOpdId = $dishub->id;

        // 1. Ambil/Buat AssetItem milik Dishub
        $catBangunan = DB::table('asset_categories')->where('slug', 'bangunan')->value('id') ?? 2;
        $catTanah = DB::table('asset_categories')->where('slug', 'tanah')->value('id') ?? 1;

        $kiosItem = AssetItem::updateOrCreate(
            ['code' => 'AST-JB-KIOS-15'],
            [
                'category_id' => $catBangunan,
                'opd_id' => $dishubOpdId,
                'name' => 'Kompleks 15 Kios Jembatan Batu',
                'lokasi' => 'Kawasan Terpadu Pelabuhan Jembatan Batu, Kel. Wale',
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'tarif_default' => 120000.00,
                'satuan_tarif' => 'per bulan',
                'is_active' => true,
                'metadata' => ['total_units' => 15, 'legal_basis' => 'Perda 1/2024 Pasal 84 ayat (1) huruf i']
            ]
        );

        $penitipanItem = AssetItem::updateOrCreate(
            ['code' => 'AST-JB-TRUK-01'],
            [
                'category_id' => $catTanah,
                'opd_id' => $dishubOpdId,
                'name' => 'Pelataran Penitipan Inap Truk & Bus Jembatan Batu',
                'lokasi' => 'Kawasan Pelabuhan Jembatan Batu',
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'tarif_default' => 25000.00,
                'satuan_tarif' => 'per hari',
                'is_active' => true,
                'metadata' => ['legal_basis' => 'Perda 1/2024 Pasal 88']
            ]
        );

        $lakologouItem = AssetItem::updateOrCreate(
            ['code' => 'AST-LKG-DERMAGA'],
            [
                'category_id' => $catTanah,
                'opd_id' => $dishubOpdId,
                'name' => 'Dermaga Bongkar Muat Curah & Tambang Lakologou',
                'lokasi' => 'Kawasan Pelabuhan Lakologou, Kota Baubau',
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'tarif_default' => 20000.00,
                'satuan_tarif' => 'per kegiatan',
                'is_active' => true,
                'metadata' => ['legal_basis' => 'Perda 1/2024 Pasal 91']
            ]
        );

        // 2. Ambil petugas pengelola aset Dishub untuk approver / verifikator
        $officer = User::withoutGlobalScopes()->where('email', 'aset.dishub@baubaukota.go.id')->first()
            ?? User::withoutGlobalScopes()->first();

        // 3. Buat beberapa Wajib Retribusi / Penyewa (Taxpayer)
        $tp1 = Taxpayer::withoutGlobalScopes()->updateOrCreate(
            ['nik' => '7472011001850001'],
            [
                'opd_id' => $dishubOpdId,
                'name' => 'La Ode Hamdan (Kios Berkah Laut)',
                'phone' => '081234567801',
                'address' => 'Kios No. 01 Jembatan Batu, Kota Baubau',
                'is_active' => true,
            ]
        );

        $tp2 = Taxpayer::withoutGlobalScopes()->updateOrCreate(
            ['nik' => '7472012002880002'],
            [
                'opd_id' => $dishubOpdId,
                'name' => 'Wa Ode Farida (Kios Bahari Jaya)',
                'phone' => '081234567802',
                'address' => 'Kios No. 02 Jembatan Batu, Kota Baubau',
                'is_active' => true,
            ]
        );

        $tp3 = Taxpayer::withoutGlobalScopes()->updateOrCreate(
            ['nik' => '7472013003750003'],
            [
                'opd_id' => $dishubOpdId,
                'name' => 'H. Ruslan (Warung Kopi Dermaga)',
                'phone' => '081234567803',
                'address' => 'Kios No. 03 Jembatan Batu, Kota Baubau',
                'is_active' => true,
            ]
        );

        $tp4 = Taxpayer::withoutGlobalScopes()->updateOrCreate(
            ['nik' => '7472014004820004'],
            [
                'opd_id' => $dishubOpdId,
                'name' => 'Amirudin (Kios Nelayan Mandiri)',
                'phone' => '081234567804',
                'address' => 'Kios No. 04 Jembatan Batu, Kota Baubau',
                'is_active' => true,
            ]
        );

        $tpLogistik = Taxpayer::withoutGlobalScopes()->updateOrCreate(
            ['nik' => '7472019909990001'],
            [
                'opd_id' => $dishubOpdId,
                'name' => 'CV Ekspedisi Buton Raya Logistik',
                'phone' => '081198765432',
                'address' => 'Jl. Yos Sudarso No. 18, Baubau',
                'is_active' => true,
            ]
        );

        $tpTambang = Taxpayer::withoutGlobalScopes()->updateOrCreate(
            ['nik' => '7472018808880002'],
            [
                'opd_id' => $dishubOpdId,
                'name' => 'PT Aspal Buton Mandiri (Dermaga Lakologou)',
                'phone' => '081122334455',
                'address' => 'Kawasan Industri Lakologou, Baubau',
                'is_active' => true,
            ]
        );

        // 4. Seed Data Asset Rentals Dishub
        // Rental 1: Kios 01 - Status Disetujui (Kontrak Aktif)
        AssetRental::updateOrCreate(
            ['rental_code' => 'RNT-DISHUB-2026-001'],
            [
                'taxpayer_id' => $tp1->id,
                'asset_item_id' => $kiosItem->id,
                'nomor_kontrak' => '045.2/KTR-AST/DISHUB/IX/2026-001',
                'tanggal_mulai' => Carbon::now()->startOfMonth()->toDateString(),
                'tanggal_selesai' => Carbon::now()->addMonths(12)->endOfMonth()->toDateString(),
                'lama_sewa' => 12,
                'satuan_sewa' => 'per bulan',
                'tarif_per_satuan' => 120000.00,
                'total_biaya' => 1440000.00,
                'dp' => 144000.00,
                'sisa_pembayaran' => 1296000.00,
                'status' => 'Disetujui',
                'lokasi_penggunaan' => 'Kios No. 01 Kompleks Jembatan Batu',
                'nama_proyek' => 'Pemanfaatan Kios Dagang Sembako & Perikanan',
                'survey_akses_jalan' => true,
                'survey_dekat_jalan_raya' => true,
                'survey_keamanan' => true,
                'survey_lahan_luas' => true,
                'survey_kesimpulan' => 'Lokasi kios sangat strategis dan siap dioperasikan.',
                'verified_by_user_id' => $officer?->id,
                'approved_by_user_id' => $officer?->id,
                'catatan_verifikator' => 'KTP dan data penyewa valid.',
                'catatan_approval' => 'SKRD recurring bulanan diterbitkan otomatis Rp 120.000.',
                'created_at' => Carbon::now()->subDays(5),
            ]
        );

        // Rental 2: Kios 02 - Status Diverifikasi (Menunggu Approval)
        AssetRental::updateOrCreate(
            ['rental_code' => 'RNT-DISHUB-2026-002'],
            [
                'taxpayer_id' => $tp2->id,
                'asset_item_id' => $kiosItem->id,
                'nomor_kontrak' => null,
                'tanggal_mulai' => Carbon::now()->toDateString(),
                'tanggal_selesai' => Carbon::now()->addMonths(6)->toDateString(),
                'lama_sewa' => 6,
                'satuan_sewa' => 'per bulan',
                'tarif_per_satuan' => 120000.00,
                'total_biaya' => 720000.00,
                'dp' => 120000.00,
                'sisa_pembayaran' => 600000.00,
                'status' => 'Diverifikasi',
                'lokasi_penggunaan' => 'Kios No. 02 Kompleks Jembatan Batu',
                'nama_proyek' => 'Usaha Warung Makan Bahari Jaya',
                'survey_akses_jalan' => true,
                'survey_dekat_jalan_raya' => true,
                'survey_keamanan' => true,
                'survey_lahan_luas' => true,
                'survey_kesimpulan' => 'Bangunan petak layak dan memenuhi standar sanitasi.',
                'verified_by_user_id' => $officer?->id,
                'catatan_verifikator' => 'Dokumen permohonan lengkap, siap disetujui Kepala UPTD.',
                'created_at' => Carbon::now()->subDays(3),
            ]
        );

        // Rental 3: Kios 03 - Status Diajukan (Permohonan Baru Masuk)
        AssetRental::updateOrCreate(
            ['rental_code' => 'RNT-DISHUB-2026-003'],
            [
                'taxpayer_id' => $tp3->id,
                'asset_item_id' => $kiosItem->id,
                'nomor_kontrak' => null,
                'tanggal_mulai' => Carbon::now()->addDays(2)->toDateString(),
                'tanggal_selesai' => Carbon::now()->addMonths(3)->toDateString(),
                'lama_sewa' => 3,
                'satuan_sewa' => 'per bulan',
                'tarif_per_satuan' => 120000.00,
                'total_biaya' => 360000.00,
                'dp' => 0.00,
                'sisa_pembayaran' => 360000.00,
                'status' => 'Diajukan',
                'lokasi_penggunaan' => 'Kios No. 03 Kompleks Jembatan Batu',
                'nama_proyek' => 'Kedai Kopi Tradisional Pelabuhan',
                'created_at' => Carbon::now()->subHours(4),
            ]
        );

        // Rental 4: Penitipan Inap Truk - Status Disetujui
        AssetRental::updateOrCreate(
            ['rental_code' => 'RNT-DISHUB-2026-004'],
            [
                'taxpayer_id' => $tpLogistik->id,
                'asset_item_id' => $penitipanItem->id,
                'nomor_kontrak' => '045.2/TKP-INAP/DISHUB/2026-004',
                'tanggal_mulai' => Carbon::now()->subDays(2)->toDateString(),
                'tanggal_selesai' => Carbon::now()->addDays(5)->toDateString(),
                'lama_sewa' => 7,
                'satuan_sewa' => 'per hari',
                'tarif_per_satuan' => 25000.00,
                'total_biaya' => 175000.00,
                'dp' => 50000.00,
                'sisa_pembayaran' => 125000.00,
                'status' => 'Disetujui',
                'lokasi_penggunaan' => 'Pelataran TKP Jembatan Batu Sisi Timur',
                'nama_proyek' => 'Penitipan 2 Unit Truk Ekspedisi Antarpulau',
                'verified_by_user_id' => $officer?->id,
                'approved_by_user_id' => $officer?->id,
                'catatan_approval' => 'Parkir inap resmi dengan pengamanan pos jaga Dishub.',
                'created_at' => Carbon::now()->subDays(2),
            ]
        );

        // Rental 5: Dermaga Lakologou - Status Diverifikasi
        AssetRental::updateOrCreate(
            ['rental_code' => 'RNT-DISHUB-2026-005'],
            [
                'taxpayer_id' => $tpTambang->id,
                'asset_item_id' => $lakologouItem->id,
                'nomor_kontrak' => null,
                'tanggal_mulai' => Carbon::now()->toDateString(),
                'tanggal_selesai' => Carbon::now()->addDays(3)->toDateString(),
                'lama_sewa' => 1,
                'satuan_sewa' => 'per kegiatan',
                'tarif_per_satuan' => 20000.00,
                'total_biaya' => 20000000.00,
                'dp' => 5000000.00,
                'sisa_pembayaran' => 15000000.00,
                'status' => 'Diverifikasi',
                'lokasi_penggunaan' => 'Dermaga Curah Pelabuhan Lakologou',
                'nama_proyek' => 'Bongkar Muat 1.000 Ton Komoditas Aspal Buton',
                'survey_akses_jalan' => true,
                'survey_dekat_jalan_raya' => true,
                'survey_keamanan' => true,
                'survey_lahan_luas' => true,
                'survey_kesimpulan' => 'Dermaga dalam kondisi kokoh untuk sandar tongkang.',
                'verified_by_user_id' => $officer?->id,
                'catatan_verifikator' => 'Manifes muat tonase telah diverifikasi petugas dermaga.',
                'created_at' => Carbon::now()->subDay(),
            ]
        );

        // Rental 6: Kios 04 - Status Selesai
        AssetRental::updateOrCreate(
            ['rental_code' => 'RNT-DISHUB-2026-006'],
            [
                'taxpayer_id' => $tp4->id,
                'asset_item_id' => $kiosItem->id,
                'nomor_kontrak' => '045.2/KTR-AST/DISHUB/VIII/2026-006',
                'tanggal_mulai' => Carbon::now()->subMonths(3)->toDateString(),
                'tanggal_selesai' => Carbon::now()->subDays(1)->toDateString(),
                'lama_sewa' => 3,
                'satuan_sewa' => 'per bulan',
                'tarif_per_satuan' => 120000.00,
                'total_biaya' => 360000.00,
                'dp' => 360000.00,
                'sisa_pembayaran' => 0.00,
                'status' => 'Selesai',
                'lokasi_penggunaan' => 'Kios No. 04 Jembatan Batu',
                'nama_proyek' => 'Sewa Kios Periode Sebelumnya (Lunas)',
                'verified_by_user_id' => $officer?->id,
                'approved_by_user_id' => $officer?->id,
                'catatan_approval' => 'Masa kontrak berakhir tanpa tunggakan.',
                'created_at' => Carbon::now()->subMonths(3),
            ]
        );
    }
}

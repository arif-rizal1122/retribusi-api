-- ============================================================
-- M-PAD: Implementasi Struktur Retribusi UU 1/2022
-- Hanya INSERT + UPDATE relasi. TIDAK ada DELETE/DROP.
-- Data wajib pajak (taxpayers, tax_objects) TIDAK disentuh.
-- ============================================================

-- ---------- 1. UPDATE nama & category type yang ada ----------
-- type 16: dari "Pendapatan Wilayah I" -> "Pajak"
UPDATE retribution_types
   SET name = 'Pajak',
       category = 'Pajak'
 WHERE id = 16 AND is_active = 1;

-- type 17: dari "Pendapatan Wilayah II" -> "Retribusi Jasa Umum"
UPDATE retribution_types
   SET name = 'Retribusi Jasa Umum',
       category = 'Retribusi'
 WHERE id = 17 AND is_active = 1;

-- ---------- 2. Perbaiki relasi classifications lama ----------
-- PBJT & pajak lain yang salah nempel di type 17 -> pindah ke type 16 (Pajak)
UPDATE retribution_classifications
   SET retribution_type_id = 16
 WHERE retribution_type_id = 17
   AND code IN ('PBJT-PRK','PBJT-FOOD','PBJT-HTL','PBJT-HBR','PBJT-HBR-SP','PBJT-PLN','AIR-TANAH');

-- "Retribusi Jasa Umum Lainnya" (W1-OTH) yang salah di type 16 -> pindah ke type 17 (Jasa Umum)
UPDATE retribution_classifications
   SET retribution_type_id = 17
 WHERE id = 156;

-- ---------- 3. INSERT type baru (Jasa Usaha, Perizinan, Aset) ----------
INSERT INTO retribution_types (opd_id, name, category, tariff_percent, icon, base_amount, unit, billing_cycle, is_active, created_at, updated_at)
SELECT 5, 'Retribusi Jasa Usaha', 'Retribusi', 0, NULL, NULL, NULL, 'monthly', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM retribution_types WHERE name = 'Retribusi Jasa Usaha');

INSERT INTO retribution_types (opd_id, name, category, tariff_percent, icon, base_amount, unit, billing_cycle, is_active, created_at, updated_at)
SELECT 5, 'Retribusi Perizinan Tertentu', 'Retribusi', 0, NULL, NULL, NULL, 'monthly', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM retribution_types WHERE name = 'Retribusi Perizinan Tertentu');

INSERT INTO retribution_types (opd_id, name, category, tariff_percent, icon, base_amount, unit, billing_cycle, is_active, created_at, updated_at)
SELECT 5, 'Aset lainnya', 'Aset', 0, NULL, NULL, NULL, 'monthly', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM retribution_types WHERE name = 'Aset lainnya');

-- ---------- 4. INSERT classifications baru ----------
-- Jasa Umum (type 17): Kesehatan & Pasar
INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, 17, 'Retribusi Pelayanan Kesehatan', 0, 'RET-KES', NULL, NULL, 'tarif_flat', NULL, 'Pelayanan kesehatan (Perda 16/2012)', NULL, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'RET-KES');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, 17, 'Retribusi Pelayanan Pasar', 0, 'RET-PSR', NULL, NULL, 'tarif_flat', NULL, 'Pelayanan pasar (Perda 26/2012 jo. 8/2016)', NULL, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'RET-PSR');

-- Jasa Usaha (type id dinamis): 9 objek Pasal 88(3) (a-g, i-j; h ditiadakan)
INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Tempat Kegiatan Usaha (Pasar Grosir, Pertokoan)', 0, 'JU-TKU', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf a', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-TKU');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Tempat Pelelangan (Ikan, Ternak, Hasil Bumi)', 0, 'JU-PEL', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf b', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-PEL');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Tempat Khusus Parkir (Luar Badan Jalan)', 0, 'JU-PRK', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf c (Perda 21/2012 jo. 3/2018)', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-PRK');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Penginapan / Pesanggrahan / Vila', 0, 'JU-VILA', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf d', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-VILA');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Rumah Pemotongan Hewan', 0, 'JU-RPH', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf e (Perda 31/2012)', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-RPH');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Jasa Kepelabuhanan', 0, 'JU-PELAB', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf f (Perda 25/2012 jo. 6/2018)', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-PELAB');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Rekreasi, Pariwisata & Olahraga', 0, 'JU-REK', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf g (Perda 32/2012 jo. 5/2020)', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-REK');

-- huruf h (penyeberangan air) DITIADAKAN - tidak di-insert

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Penjualan Hasil Produksi Pemda', 0, 'JU-PROD', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf i', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-PROD');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Pemanfaatan Aset Daerah', 0, 'JU-ASET', NULL, NULL, 'tarif_flat', NULL, 'Pasal 88(3) huruf j (Perda 30/2012 jo. Perwal 19/2016)', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Jasa Usaha'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'JU-ASET');

-- Perizinan Tertentu (type dinamis): 2 objek Pasal 87(1)c
INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Persetujuan Bangunan Gedung (PBG)', 0, 'PZ-PBG', NULL, NULL, 'tarif_flat', NULL, 'Pasal 87(1)c huruf a', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Perizinan Tertentu'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'PZ-PBG');

INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Penggunaan Tenaga Kerja Asing (TKA)', 0, 'PZ-TKA', NULL, NULL, 'tarif_flat', NULL, 'Pasal 87(1)c huruf b', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Retribusi Perizinan Tertentu'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'PZ-TKA');

-- Aset lainnya (type dinamis)
INSERT INTO retribution_classifications (opd_id, retribution_type_id, name, is_self_assessment, code, form_schema, requirements, calculation_formula, bank_accounts, description, icon, created_at, updated_at)
SELECT 5, t.id, 'Pemanfaatan Aset Daerah Lainnya', 0, 'AS-OTH', NULL, NULL, 'tarif_flat', NULL, 'Optimalisasi aset daerah', NULL, NOW(), NOW()
FROM retribution_types t WHERE t.name = 'Aset lainnya'
AND NOT EXISTS (SELECT 1 FROM retribution_classifications WHERE code = 'AS-OTH');

-- ============================================================
-- SELESAI - verifikasi di bawah
-- ============================================================
SELECT id, name, category, is_active FROM retribution_types ORDER BY id;

-- Database: retribusi
-- Generated at: 2026-03-02 20:41:26

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_logs` VALUES
('1',NULL,'create','App\\Models\\User','1',NULL,'{\"id\": 1, \"name\": \"Super Admin\", \"role\": \"super_admin\", \"email\": \"admin@retribusi.id\", \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:24\", \"updated_at\": \"2026-03-02 05:13:24\"}','127.0.0.1','Symfony','2026-03-02 05:13:24','2026-03-02 05:13:24'),
('2',NULL,'create','App\\Models\\User','2',NULL,'{\"id\": 2, \"name\": \"Dev Super Admin\", \"role\": \"super_admin\", \"email\": \"superadmin@sipanda.online\", \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:25\", \"updated_at\": \"2026-03-02 05:13:25\"}','127.0.0.1','Symfony','2026-03-02 05:13:25','2026-03-02 05:13:25'),
('3',NULL,'create','App\\Models\\User','3',NULL,'{\"id\": 3, \"name\": \"Admin Dishub\", \"role\": \"opd\", \"email\": \"dishub@retribusi.id\", \"opd_id\": 1, \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:26\", \"updated_at\": \"2026-03-02 05:13:26\"}','127.0.0.1','Symfony','2026-03-02 05:13:26','2026-03-02 05:13:26'),
('4',NULL,'create','App\\Models\\User','4',NULL,'{\"id\": 4, \"name\": \"Admin Disperindag\", \"role\": \"opd\", \"email\": \"disperindag@retribusi.id\", \"opd_id\": 2, \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:26\", \"updated_at\": \"2026-03-02 05:13:26\"}','127.0.0.1','Symfony','2026-03-02 05:13:26','2026-03-02 05:13:26'),
('5',NULL,'create','App\\Models\\User','5',NULL,'{\"id\": 5, \"name\": \"Admin DLH\", \"role\": \"opd\", \"email\": \"dlh@retribusi.id\", \"opd_id\": 3, \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:27\", \"updated_at\": \"2026-03-02 05:13:27\"}','127.0.0.1','Symfony','2026-03-02 05:13:27','2026-03-02 05:13:27'),
('6',NULL,'create','App\\Models\\User','6',NULL,'{\"id\": 6, \"name\": \"Admin BAPENDA\", \"role\": \"opd\", \"email\": \"bapenda@baubaukota.go.id\", \"opd_id\": 4, \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\"}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('7',NULL,'create','App\\Models\\User','7',NULL,'{\"id\": 7, \"name\": \"Petugas BAPENDA\", \"role\": \"petugas\", \"email\": \"petugas@bapenda.go.id\", \"opd_id\": 4, \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\"}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('8',NULL,'create','App\\Models\\RetributionType','1',NULL,'{\"id\": 1, \"icon\": \"car\", \"name\": \"Retribusi Parkir Mobil\", \"unit\": \"per jam\", \"opd_id\": 1, \"category\": \"Parkir\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"base_amount\": 5000}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('9',NULL,'create','App\\Models\\RetributionType','2',NULL,'{\"id\": 2, \"icon\": \"bike\", \"name\": \"Retribusi Parkir Motor\", \"unit\": \"per jam\", \"opd_id\": 1, \"category\": \"Parkir\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"base_amount\": 2000}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('10',NULL,'create','App\\Models\\RetributionType','3',NULL,'{\"id\": 3, \"icon\": \"bus\", \"name\": \"Retribusi Terminal\", \"unit\": \"per bus\", \"opd_id\": 1, \"category\": \"Terminal\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"base_amount\": 10000}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('11',NULL,'create','App\\Models\\RetributionType','4',NULL,'{\"id\": 4, \"icon\": \"store\", \"name\": \"Retribusi Kios Pasar\", \"unit\": \"per bulan\", \"opd_id\": 2, \"category\": \"Pasar\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"base_amount\": 150000}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('12',NULL,'create','App\\Models\\RetributionType','5',NULL,'{\"id\": 5, \"icon\": \"market\", \"name\": \"Retribusi Los Pasar\", \"unit\": \"per bulan\", \"opd_id\": 2, \"category\": \"Pasar\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"base_amount\": 50000}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('13',NULL,'create','App\\Models\\RetributionType','6',NULL,'{\"id\": 6, \"icon\": \"trash\", \"name\": \"Retribusi Persampahan\", \"unit\": \"per bulan\", \"opd_id\": 3, \"category\": \"Kebersihan\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"base_amount\": 30000}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('14',NULL,'create','App\\Models\\RetributionType','7',NULL,'{\"id\": 7, \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"Wilayah I\", \"opd_id\": 4, \"category\": \"Pajak\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\"}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('15',NULL,'create','App\\Models\\RetributionType','8',NULL,'{\"id\": 8, \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"Wilayah II\", \"opd_id\": 4, \"category\": \"Pajak\", \"is_active\": true, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\"}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('16',NULL,'create','App\\Models\\RetributionClassification','1',NULL,'{\"id\": 1, \"code\": \"PBJT-MNM\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBJT - Makan dan Minum\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('17',NULL,'create','App\\Models\\RetributionClassification','2',NULL,'{\"id\": 2, \"code\": \"PBJT-LIS\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBJT - Tenaga Listrik\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('18',NULL,'create','App\\Models\\RetributionClassification','3',NULL,'{\"id\": 3, \"code\": \"PBJT-HTL\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBJT - Jasa Perhotelan\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:28\", \"updated_at\": \"2026-03-02 05:13:28\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('19',NULL,'create','App\\Models\\RetributionClassification','4',NULL,'{\"id\": 4, \"code\": \"PBJT-PRK\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBJT - Jasa Parkir\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('20',NULL,'create','App\\Models\\RetributionClassification','5',NULL,'{\"id\": 5, \"code\": \"PBJT-HBR\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBJT - Jasa Kesenian dan Hiburan\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('21',NULL,'create','App\\Models\\RetributionClassification','6',NULL,'{\"id\": 6, \"code\": \"PBJT-CAT\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBJT - Jasa Catering\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('22',NULL,'create','App\\Models\\RetributionClassification','7',NULL,'{\"id\": 7, \"code\": \"PBJT-EVT\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBJT - Jasa Event/Lainnya\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('23',NULL,'create','App\\Models\\RetributionClassification','8',NULL,'{\"id\": 8, \"code\": \"PTKU\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg\", \"name\": \"Penyediaan Tempat Kegiatan Usaha\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('24',NULL,'create','App\\Models\\RetributionClassification','9',NULL,'{\"id\": 9, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg\", \"name\": \"Pajak Reklame\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('25',NULL,'create','App\\Models\\RetributionClassification','10',NULL,'{\"id\": 10, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg\", \"name\": \"Pajak MBLB\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('26',NULL,'create','App\\Models\\RetributionClassification','11',NULL,'{\"id\": 11, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855486/retribusi/icons/agqc0orhv7i9wg4a7x1t.jpg\", \"name\": \"Pajak Sarang Burung Walet\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('27',NULL,'create','App\\Models\\RetributionClassification','12',NULL,'{\"id\": 12, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"Air Tanah\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('28',NULL,'create','App\\Models\\RetributionClassification','13',NULL,'{\"id\": 13, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg\", \"name\": \"BPHTB\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('29',NULL,'create','App\\Models\\RetributionClassification','14',NULL,'{\"id\": 14, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"Opsen PKB\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('30',NULL,'create','App\\Models\\RetributionClassification','15',NULL,'{\"id\": 15, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"Opsen BBNKB\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('31',NULL,'create','App\\Models\\RetributionClassification','16',NULL,'{\"id\": 16, \"code\": \"RET\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg\", \"name\": \"Retribusi Jasa Umum\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('32',NULL,'create','App\\Models\\RetributionClassification','17',NULL,'{\"id\": 17, \"code\": \"RET\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg\", \"name\": \"Retribusi Perizinan Tertentu\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('33',NULL,'create','App\\Models\\RetributionClassification','18',NULL,'{\"id\": 18, \"code\": \"TAX\", \"icon\": \"https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg\", \"name\": \"PBB\", \"opd_id\": 4, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"form_schema\": \"[{\\\"key\\\":\\\"tanggal_pendataan\\\",\\\"label\\\":\\\"Tanggal Pendataan\\\",\\\"type\\\":\\\"date\\\",\\\"required\\\":true},{\\\"key\\\":\\\"nama_jenis_usaha\\\",\\\"label\\\":\\\"Nama Jenis Usaha\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"omset_penjualan\\\",\\\"label\\\":\\\"Omset Penjualan (Rata-rata\\\\/Bulan)\\\",\\\"type\\\":\\\"number\\\",\\\"required\\\":true},{\\\"key\\\":\\\"tarif_pajak\\\",\\\"label\\\":\\\"Tarif Pajak (%)\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true},{\\\"key\\\":\\\"keterangan_usaha\\\",\\\"label\\\":\\\"Keterangan Usaha\\\",\\\"type\\\":\\\"select\\\",\\\"options\\\":[\\\"Aktif\\\",\\\"Tidak Aktif\\\"],\\\"required\\\":true},{\\\"key\\\":\\\"lokasi_google_maps\\\",\\\"label\\\":\\\"Link Lokasi Google Maps\\\",\\\"type\\\":\\\"text\\\",\\\"required\\\":true}]\", \"requirements\": \"[{\\\"key\\\":\\\"foto_lokasi_open_kamera\\\",\\\"label\\\":\\\"Dokumentasi Open Kamera\\\",\\\"required\\\":true},{\\\"key\\\":\\\"formulir_data_dukung\\\",\\\"label\\\":\\\"Upload Formulir Data Dukung\\\",\\\"required\\\":true}]\", \"retribution_type_id\": 7}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('34',NULL,'create','App\\Models\\RetributionRate','1',NULL,'{\"id\": 1, \"name\": \"Kios Sentra Kuliner\", \"unit\": \"Tahun/Kios\", \"amount\": 6000000, \"opd_id\": 4, \"zone_id\": 1, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 7, \"retribution_classification_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('35',NULL,'create','App\\Models\\RetributionRate','2',NULL,'{\"id\": 2, \"name\": \"Lapak Pujasera\", \"unit\": \"Bulan/Lapak\", \"amount\": 60000, \"opd_id\": 4, \"zone_id\": 2, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 7, \"retribution_classification_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('36',NULL,'create','App\\Models\\RetributionRate','3',NULL,'{\"id\": 3, \"name\": \"Lapak Pujasera\", \"unit\": \"Bulan/Lapak\", \"amount\": 60000, \"opd_id\": 4, \"zone_id\": 3, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 7, \"retribution_classification_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('37',NULL,'create','App\\Models\\RetributionRate','4',NULL,'{\"id\": 4, \"name\": \"Sewa Kantor\", \"unit\": \"Bulan\", \"amount\": 200000, \"opd_id\": 4, \"zone_id\": 4, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 7, \"retribution_classification_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('38',NULL,'create','App\\Models\\RetributionRate','5',NULL,'{\"id\": 5, \"name\": \"Sewa Toko/Kios\", \"unit\": \"Tahun\", \"amount\": 15000000, \"opd_id\": 4, \"zone_id\": 5, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 7, \"retribution_classification_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('39',NULL,'create','App\\Models\\RetributionRate','6',NULL,'{\"id\": 6, \"name\": \"Kios Kuliner\", \"unit\": \"Bulan\", \"amount\": 500000, \"opd_id\": 4, \"zone_id\": 6, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 7, \"retribution_classification_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('40',NULL,'create','App\\Models\\RetributionRate','7',NULL,'{\"id\": 7, \"name\": \"Kotamara\", \"unit\": \"Lapak/Bulan\", \"amount\": 120000, \"opd_id\": 4, \"zone_id\": 7, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 7, \"retribution_classification_id\": 8}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('41',NULL,'create','App\\Models\\RetributionRate','8',NULL,'{\"id\": 8, \"name\": \"Tarif Hiburan Umum\", \"unit\": \"%\", \"amount\": 10, \"opd_id\": 4, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 8, \"retribution_classification_id\": 5}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('42',NULL,'create','App\\Models\\RetributionRate','9',NULL,'{\"id\": 9, \"name\": \"Tarif Khusus\", \"unit\": \"%\", \"amount\": 40, \"opd_id\": 4, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 8, \"retribution_classification_id\": 5}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('43',NULL,'create','App\\Models\\RetributionRate','10',NULL,'{\"id\": 10, \"name\": \"Tarif Parkir\", \"unit\": \"%\", \"amount\": 30, \"opd_id\": 4, \"is_active\": true, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"retribution_type_id\": 8, \"retribution_classification_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('44',NULL,'create','App\\Models\\TaxObject','1',NULL,'{\"id\": 1, \"nop\": \"NPWPD-D3BCCF19-8-1\", \"name\": \"Rumah Makan Padang - Ahmad Subarjo\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. La Ode Hadi, Wameo\", \"latitude\": -5.4573, \"metadata\": \"{\\\"omzet\\\":15000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.6035, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 1, \"retribution_type_id\": 8, \"retribution_classification_id\": 1}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('45',NULL,'create','App\\Models\\TaxObject','2',NULL,'{\"id\": 2, \"nop\": \"NPWPD-4A05B28E-8-1\", \"name\": \"Warung Nasi Kuning - Siti Nurhaliza\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Dayanu Ikhsanuddin, Baadia\", \"latitude\": -5.451, \"metadata\": \"{\\\"omzet\\\":5000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5975, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 2, \"retribution_type_id\": 8, \"retribution_classification_id\": 1}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('46',NULL,'create','App\\Models\\TaxObject','3',NULL,'{\"id\": 3, \"nop\": \"NPWPD-A66FEDFE-8-1\", \"name\": \"Coffee Shop - Budi Santoso\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Wangkanapi, Wangkanapi\", \"latitude\": -5.4695, \"metadata\": \"{\\\"omzet\\\":20000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.606, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 3, \"retribution_type_id\": 8, \"retribution_classification_id\": 1}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('47',NULL,'create','App\\Models\\TaxObject','4',NULL,'{\"id\": 4, \"nop\": \"NPWPD-905982ED-8-1\", \"name\": \"Warung Bakso - Dewi Lestari\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Lipu Permai, Lipu\", \"latitude\": -5.4445, \"metadata\": \"{\\\"omzet\\\":8000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5885, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 4, \"retribution_type_id\": 8, \"retribution_classification_id\": 1}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('48',NULL,'create','App\\Models\\TaxObject','5',NULL,'{\"id\": 5, \"nop\": \"NPWPD-2769EF0A-8-1\", \"name\": \"Rumah Makan Seafood - Muhammad Rizky\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Sukanaeyo, Sukanaeyo\", \"latitude\": -5.479, \"metadata\": \"{\\\"omzet\\\":25000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.617, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 5, \"retribution_type_id\": 8, \"retribution_classification_id\": 1}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('49',NULL,'create','App\\Models\\TaxObject','6',NULL,'{\"id\": 6, \"nop\": \"NPWPD-89F811E1-8-3\", \"name\": \"Hotel Bintang 3 - Ani Rahayu\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. La Ode Hadi, Wameo\", \"latitude\": -5.4573, \"metadata\": \"{\\\"omzet\\\":80000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.6035, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 6, \"retribution_type_id\": 8, \"retribution_classification_id\": 3}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('50',NULL,'create','App\\Models\\TaxObject','7',NULL,'{\"id\": 7, \"nop\": \"NPWPD-6F5E791F-8-3\", \"name\": \"Penginapan - Hasan Al-Bashri\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Dayanu Ikhsanuddin, Baadia\", \"latitude\": -5.451, \"metadata\": \"{\\\"omzet\\\":15000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5975, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 7, \"retribution_type_id\": 8, \"retribution_classification_id\": 3}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('51',NULL,'create','App\\Models\\TaxObject','8',NULL,'{\"id\": 8, \"nop\": \"NPWPD-1E2DEC5C-8-3\", \"name\": \"Villa - Fatimah Zahra\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Wangkanapi, Wangkanapi\", \"latitude\": -5.4695, \"metadata\": \"{\\\"omzet\\\":30000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.606, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 8, \"retribution_type_id\": 8, \"retribution_classification_id\": 3}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('52',NULL,'create','App\\Models\\TaxObject','9',NULL,'{\"id\": 9, \"nop\": \"NPWPD-4DAFFEBA-8-3\", \"name\": \"Homestay - La Ode Rahman\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Lipu Permai, Lipu\", \"latitude\": -5.4445, \"metadata\": \"{\\\"omzet\\\":10000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5885, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 9, \"retribution_type_id\": 8, \"retribution_classification_id\": 3}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('53',NULL,'create','App\\Models\\TaxObject','10',NULL,'{\"id\": 10, \"nop\": \"NPWPD-6041D3A5-8-3\", \"name\": \"Hotel Bintang 2 - Wa Ode Sarina\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Sukanaeyo, Sukanaeyo\", \"latitude\": -5.479, \"metadata\": \"{\\\"omzet\\\":45000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.617, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 10, \"retribution_type_id\": 8, \"retribution_classification_id\": 3}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('54',NULL,'create','App\\Models\\TaxObject','11',NULL,'{\"id\": 11, \"nop\": \"NPWPD-8864931B-8-4\", \"name\": \"Lahan Parkir Pasar - Agus Prasetyo\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. La Ode Hadi, Wameo\", \"latitude\": -5.4573, \"metadata\": \"{\\\"omzet\\\":12000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.6035, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 11, \"retribution_type_id\": 8, \"retribution_classification_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('55',NULL,'create','App\\Models\\TaxObject','12',NULL,'{\"id\": 12, \"nop\": \"NPWPD-BE4B1A57-8-4\", \"name\": \"Gedung Parkir - Rina Marlina\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Dayanu Ikhsanuddin, Baadia\", \"latitude\": -5.451, \"metadata\": \"{\\\"omzet\\\":25000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5975, \"created_at\": \"2026-03-02 05:13:29\", \"updated_at\": \"2026-03-02 05:13:29\", \"taxpayer_id\": 12, \"retribution_type_id\": 8, \"retribution_classification_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('56',NULL,'create','App\\Models\\TaxObject','13',NULL,'{\"id\": 13, \"nop\": \"NPWPD-41CF9E80-8-4\", \"name\": \"Area Parkir Pelabuhan - Sulaiman Darwis\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Wangkanapi, Wangkanapi\", \"latitude\": -5.4695, \"metadata\": \"{\\\"omzet\\\":18000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.606, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 13, \"retribution_type_id\": 8, \"retribution_classification_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('57',NULL,'create','App\\Models\\TaxObject','14',NULL,'{\"id\": 14, \"nop\": \"NPWPD-0984D77F-8-4\", \"name\": \"Parkir Pusat Kota - Nurhayati Amin\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Lipu Permai, Lipu\", \"latitude\": -5.4445, \"metadata\": \"{\\\"omzet\\\":35000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5885, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 14, \"retribution_type_id\": 8, \"retribution_classification_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('58',NULL,'create','App\\Models\\TaxObject','15',NULL,'{\"id\": 15, \"nop\": \"NPWPD-0663C821-8-4\", \"name\": \"Parkir Objek Wisata - La Ode Muh Akbar\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Sukanaeyo, Sukanaeyo\", \"latitude\": -5.479, \"metadata\": \"{\\\"omzet\\\":8000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.617, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 15, \"retribution_type_id\": 8, \"retribution_classification_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('59',NULL,'create','App\\Models\\TaxObject','16',NULL,'{\"id\": 16, \"nop\": \"NPWPD-7A07C453-8-5\", \"name\": \"Studio Karaoke - Wa Ode Fitri\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. La Ode Hadi, Wameo\", \"latitude\": -5.4573, \"metadata\": \"{\\\"omzet\\\":20000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.6035, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 16, \"retribution_type_id\": 8, \"retribution_classification_id\": 5}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('60',NULL,'create','App\\Models\\TaxObject','17',NULL,'{\"id\": 17, \"nop\": \"NPWPD-4D3655B1-8-5\", \"name\": \"Pusat Billiard - Abdul Karim\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Dayanu Ikhsanuddin, Baadia\", \"latitude\": -5.451, \"metadata\": \"{\\\"omzet\\\":10000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5975, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 17, \"retribution_type_id\": 8, \"retribution_classification_id\": 5}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('61',NULL,'create','App\\Models\\TaxObject','18',NULL,'{\"id\": 18, \"nop\": \"NPWPD-C3536C27-8-5\", \"name\": \"Arena Bermain - Salmah Basri\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Wangkanapi, Wangkanapi\", \"latitude\": -5.4695, \"metadata\": \"{\\\"omzet\\\":15000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.606, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 18, \"retribution_type_id\": 8, \"retribution_classification_id\": 5}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('62',NULL,'create','App\\Models\\TaxObject','19',NULL,'{\"id\": 19, \"nop\": \"NPWPD-1FE43C43-8-5\", \"name\": \"Gedung Serbaguna - La Ode Syahrul\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Lipu Permai, Lipu\", \"latitude\": -5.4445, \"metadata\": \"{\\\"omzet\\\":30000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5885, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 19, \"retribution_type_id\": 8, \"retribution_classification_id\": 5}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('63',NULL,'create','App\\Models\\TaxObject','20',NULL,'{\"id\": 20, \"nop\": \"NPWPD-05734CFE-8-5\", \"name\": \"Bioskop - Wa Ode Hasna\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Sukanaeyo, Sukanaeyo\", \"latitude\": -5.479, \"metadata\": \"{\\\"omzet\\\":40000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.617, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 20, \"retribution_type_id\": 8, \"retribution_classification_id\": 5}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('64',NULL,'create','App\\Models\\TaxObject','21',NULL,'{\"id\": 21, \"nop\": \"NPWPD-4BBDC128-8-2\", \"name\": \"Gardu Listrik - Irfan Hakim\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. La Ode Hadi, Wameo\", \"latitude\": -5.4573, \"metadata\": \"{\\\"omzet\\\":500000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.6035, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 21, \"retribution_type_id\": 8, \"retribution_classification_id\": 2}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('65',NULL,'create','App\\Models\\TaxObject','22',NULL,'{\"id\": 22, \"nop\": \"NPWPD-E6291036-8-2\", \"name\": \"Pembangkit Listrik Swasta - Marwah Said\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Dayanu Ikhsanuddin, Baadia\", \"latitude\": -5.451, \"metadata\": \"{\\\"omzet\\\":80000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5975, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 22, \"retribution_type_id\": 8, \"retribution_classification_id\": 2}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('66',NULL,'create','App\\Models\\TaxObject','23',NULL,'{\"id\": 23, \"nop\": \"NPWPD-F3A18B93-8-2\", \"name\": \"Instalasi Solar - La Ode Safri\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Wangkanapi, Wangkanapi\", \"latitude\": -5.4695, \"metadata\": \"{\\\"omzet\\\":20000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.606, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 23, \"retribution_type_id\": 8, \"retribution_classification_id\": 2}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('67',NULL,'create','App\\Models\\TaxObject','24',NULL,'{\"id\": 24, \"nop\": \"NPWPD-D3978351-8-2\", \"name\": \"Pembangkit Diesel - Wa Ode Nursia\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Lipu Permai, Lipu\", \"latitude\": -5.4445, \"metadata\": \"{\\\"omzet\\\":45000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.5885, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 24, \"retribution_type_id\": 8, \"retribution_classification_id\": 2}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('68',NULL,'create','App\\Models\\TaxObject','25',NULL,'{\"id\": 25, \"nop\": \"NPWPD-FF89B759-8-2\", \"name\": \"Pembangkit Mikro - Ruslan Abadi\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Sukanaeyo, Sukanaeyo\", \"latitude\": -5.479, \"metadata\": \"{\\\"omzet\\\":6000000,\\\"keterangan_usaha\\\":\\\"Aktif\\\"}\", \"longitude\": 122.617, \"created_at\": \"2026-03-02 05:13:30\", \"updated_at\": \"2026-03-02 05:13:30\", \"taxpayer_id\": 25, \"retribution_type_id\": 8, \"retribution_classification_id\": 2}','127.0.0.1','Symfony','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('69',NULL,'create','App\\Models\\User','8',NULL,'{\"id\": 8, \"nik\": \"9999999999999901\", \"name\": \"Kabid Pengawas\", \"role\": \"kabid_pengawas\", \"email\": \"kabid@retribusi.id\", \"opd_id\": 4, \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:31\", \"updated_at\": \"2026-03-02 05:13:31\"}','127.0.0.1','Symfony','2026-03-02 05:13:31','2026-03-02 05:13:31'),
('70',NULL,'create','App\\Models\\User','9',NULL,'{\"id\": 9, \"nik\": \"9999999999999902\", \"name\": \"Kasubid Pengawas\", \"role\": \"kasubid_pengawas\", \"email\": \"kasubid@retribusi.id\", \"opd_id\": 4, \"status\": \"active\", \"created_at\": \"2026-03-02 05:13:31\", \"updated_at\": \"2026-03-02 05:13:31\"}','127.0.0.1','Symfony','2026-03-02 05:13:31','2026-03-02 05:13:31'),
('71',NULL,'create','App\\Models\\TaxObject','26',NULL,'{\"id\": 26, \"nop\": \"PRK-74-001\", \"name\": \"Lahan Parkir Toko Budi\", \"opd_id\": 1, \"status\": \"active\", \"address\": \"Jl. Merdeka No. 5\", \"latitude\": -5.4633, \"longitude\": 122.6012, \"created_at\": \"2026-03-02 05:13:33\", \"updated_at\": \"2026-03-02 05:13:33\", \"approved_at\": \"2026-01-02 05:13:33\", \"taxpayer_id\": 26, \"retribution_type_id\": 1}','127.0.0.1','Symfony','2026-03-02 05:13:33','2026-03-02 05:13:33'),
('72',NULL,'create','App\\Models\\TaxObject','27',NULL,'{\"id\": 27, \"nop\": \"PBB-74-001\", \"name\": \"Rumah Tinggal Budi\", \"opd_id\": 4, \"status\": \"active\", \"address\": \"Jl. Wolter Monginsidi No. 12\", \"latitude\": -5.4645, \"metadata\": \"{\\\"luas_bumi\\\":200,\\\"luas_bangunan\\\":100}\", \"longitude\": 122.6025, \"created_at\": \"2026-03-02 05:13:33\", \"updated_at\": \"2026-03-02 05:13:33\", \"approved_at\": \"2025-09-02 05:13:33\", \"taxpayer_id\": 26, \"retribution_type_id\": 7, \"retribution_classification_id\": 18}','127.0.0.1','Symfony','2026-03-02 05:13:33','2026-03-02 05:13:33'),
('73',NULL,'create','App\\Models\\TaxObject','28',NULL,'{\"id\": 28, \"nop\": \"KIO-74-001\", \"name\": \"Kios Sembako Ani\", \"opd_id\": 2, \"status\": \"active\", \"address\": \"Pasar Karya No. 10\", \"created_at\": \"2026-03-02 05:13:33\", \"updated_at\": \"2026-03-02 05:13:33\", \"approved_at\": \"2026-02-02 05:13:33\", \"taxpayer_id\": 27, \"retribution_type_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:33','2026-03-02 05:13:33'),
('74',NULL,'create','App\\Models\\TaxObject','29',NULL,'{\"id\": 29, \"nop\": \"SMP-74-001\", \"name\": \"Rumah Ani (Retribusi Sampah)\", \"opd_id\": 3, \"status\": \"active\", \"address\": \"Jl. Pahlawan No. 45\", \"created_at\": \"2026-03-02 05:13:33\", \"updated_at\": \"2026-03-02 05:13:33\", \"approved_at\": \"2025-12-02 05:13:33\", \"taxpayer_id\": 27, \"retribution_type_id\": 6}','127.0.0.1','Symfony','2026-03-02 05:13:33','2026-03-02 05:13:33'),
('75',NULL,'create','App\\Models\\TaxObject','30',NULL,'{\"id\": 30, \"name\": \"Kios Pakaian Baru\", \"opd_id\": 2, \"status\": \"pending\", \"address\": \"Pasar Karya Blok B\", \"created_at\": \"2026-03-02 05:13:33\", \"updated_at\": \"2026-03-02 05:13:33\", \"taxpayer_id\": 27, \"retribution_type_id\": 4}','127.0.0.1','Symfony','2026-03-02 05:13:33','2026-03-02 05:13:33');

DROP TABLE IF EXISTS `bills`;
CREATE TABLE `bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `taxpayer_id` bigint unsigned NOT NULL,
  `tax_object_id` bigint unsigned DEFAULT NULL,
  `opd_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `retribution_type_id` bigint unsigned NOT NULL,
  `retribution_classification_id` bigint unsigned DEFAULT NULL,
  `bill_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `penalty_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `waived_penalty_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `penalty_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stpd',
  `fixed_fine_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `surcharge_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `due_date` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bills_bill_number_unique` (`bill_number`),
  KEY `bills_taxpayer_id_foreign` (`taxpayer_id`),
  KEY `bills_tax_object_id_foreign` (`tax_object_id`),
  KEY `bills_retribution_type_id_foreign` (`retribution_type_id`),
  KEY `bills_opd_id_foreign` (`opd_id`),
  KEY `bills_user_id_foreign` (`user_id`),
  KEY `bills_class_id_foreign` (`retribution_classification_id`),
  CONSTRAINT `bills_class_id_foreign` FOREIGN KEY (`retribution_classification_id`) REFERENCES `retribution_classifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_retribution_type_id_foreign` FOREIGN KEY (`retribution_type_id`) REFERENCES `retribution_types` (`id`),
  CONSTRAINT `bills_tax_object_id_foreign` FOREIGN KEY (`tax_object_id`) REFERENCES `tax_objects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bills_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bills_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bills` VALUES
('1','1','1','4','6','8','1','BIL-2026-00001','1500000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','1','1','4','6','8','1','BIL-2026-00002','1500000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','1','1','4','6','8','1','BIL-2026-00003','1500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','2','2','4','6','8','1','BIL-2026-00004','500000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','2','2','4','6','8','1','BIL-2026-00005','500000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','2','2','4','6','8','1','BIL-2026-00006','500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','3','3','4','6','8','1','BIL-2026-00007','2000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','3','3','4','6','8','1','BIL-2026-00008','2000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','3','3','4','6','8','1','BIL-2026-00009','2000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','4','4','4','6','8','1','BIL-2026-00010','800000.00','16000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('11','4','4','4','6','8','1','BIL-2026-00011','800000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('12','4','4','4','6','8','1','BIL-2026-00012','800000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('13','5','5','4','6','8','1','BIL-2026-00013','2500000.00','50000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('14','5','5','4','6','8','1','BIL-2026-00014','2500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('15','5','5','4','6','8','1','BIL-2026-00015','2500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('16','6','6','4','6','8','3','BIL-2026-00016','8000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('17','6','6','4','6','8','3','BIL-2026-00017','8000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('18','6','6','4','6','8','3','BIL-2026-00018','8000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('19','7','7','4','6','8','3','BIL-2026-00019','1500000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('20','7','7','4','6','8','3','BIL-2026-00020','1500000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('21','7','7','4','6','8','3','BIL-2026-00021','1500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('22','8','8','4','6','8','3','BIL-2026-00022','3000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('23','8','8','4','6','8','3','BIL-2026-00023','3000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('24','8','8','4','6','8','3','BIL-2026-00024','3000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('25','9','9','4','6','8','3','BIL-2026-00025','1000000.00','20000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('26','9','9','4','6','8','3','BIL-2026-00026','1000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('27','9','9','4','6','8','3','BIL-2026-00027','1000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('28','10','10','4','6','8','3','BIL-2026-00028','4500000.00','90000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('29','10','10','4','6','8','3','BIL-2026-00029','4500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('30','10','10','4','6','8','3','BIL-2026-00030','4500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('31','11','11','4','6','8','4','BIL-2026-00031','1200000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('32','11','11','4','6','8','4','BIL-2026-00032','1200000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('33','11','11','4','6','8','4','BIL-2026-00033','1200000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('34','12','12','4','6','8','4','BIL-2026-00034','2500000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('35','12','12','4','6','8','4','BIL-2026-00035','2500000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('36','12','12','4','6','8','4','BIL-2026-00036','2500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('37','13','13','4','6','8','4','BIL-2026-00037','1800000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('38','13','13','4','6','8','4','BIL-2026-00038','1800000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('39','13','13','4','6','8','4','BIL-2026-00039','1800000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('40','14','14','4','6','8','4','BIL-2026-00040','3500000.00','70000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('41','14','14','4','6','8','4','BIL-2026-00041','3500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('42','14','14','4','6','8','4','BIL-2026-00042','3500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('43','15','15','4','6','8','4','BIL-2026-00043','800000.00','16000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('44','15','15','4','6','8','4','BIL-2026-00044','800000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('45','15','15','4','6','8','4','BIL-2026-00045','800000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('46','16','16','4','6','8','5','BIL-2026-00046','2000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('47','16','16','4','6','8','5','BIL-2026-00047','2000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('48','16','16','4','6','8','5','BIL-2026-00048','2000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('49','17','17','4','6','8','5','BIL-2026-00049','1000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('50','17','17','4','6','8','5','BIL-2026-00050','1000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('51','17','17','4','6','8','5','BIL-2026-00051','1000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('52','18','18','4','6','8','5','BIL-2026-00052','1500000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('53','18','18','4','6','8','5','BIL-2026-00053','1500000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('54','18','18','4','6','8','5','BIL-2026-00054','1500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('55','19','19','4','6','8','5','BIL-2026-00055','3000000.00','60000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('56','19','19','4','6','8','5','BIL-2026-00056','3000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('57','19','19','4','6','8','5','BIL-2026-00057','3000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('58','20','20','4','6','8','5','BIL-2026-00058','4000000.00','80000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('59','20','20','4','6','8','5','BIL-2026-00059','4000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('60','20','20','4','6','8','5','BIL-2026-00060','4000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('61','21','21','4','6','8','2','BIL-2026-00061','50000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('62','21','21','4','6','8','2','BIL-2026-00062','50000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('63','21','21','4','6','8','2','BIL-2026-00063','50000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('64','22','22','4','6','8','2','BIL-2026-00064','8000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('65','22','22','4','6','8','2','BIL-2026-00065','8000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('66','22','22','4','6','8','2','BIL-2026-00066','8000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('67','23','23','4','6','8','2','BIL-2026-00067','2000000.00','0.00','0.00','stpd','0.00','0.00','paid','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('68','23','23','4','6','8','2','BIL-2026-00068','2000000.00','0.00','0.00','stpd','0.00','0.00','paid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('69','23','23','4','6','8','2','BIL-2026-00069','2000000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('70','24','24','4','6','8','2','BIL-2026-00070','4500000.00','90000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('71','24','24','4','6','8','2','BIL-2026-00071','4500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('72','24','24','4','6','8','2','BIL-2026-00072','4500000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('73','25','25','4','6','8','2','BIL-2026-00073','600000.00','12000.00','0.00','stpd','0.00','0.00','overdue','Januari 2026','2026-01-01','2026-01-31',NULL,'2026-01-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('74','25','25','4','6','8','2','BIL-2026-00074','600000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Februari 2026','2026-02-01','2026-02-28',NULL,'2026-02-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('75','25','25','4','6','8','2','BIL-2026-00075','600000.00','0.00','0.00','stpd','0.00','0.00','unpaid','Maret 2026','2026-03-01','2026-03-31',NULL,'2026-03-15 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('76','26','26','1',NULL,'1',NULL,'INV-202501-001','50000.00','0.00','0.00','stpd','0.00','0.00','paid','2025-01',NULL,NULL,NULL,'2026-02-02 05:13:33','2026-03-02 05:13:33','2026-03-02 05:13:33'),
('77','26','27','4',NULL,'7',NULL,'INV-202502-002','750000.00','0.00','0.00','stpd','0.00','0.00','pending','2025-02',NULL,NULL,NULL,'2026-03-17 05:13:33','2026-03-02 05:13:33','2026-03-02 05:13:33');

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` VALUES
('5c785c036466adea360111aa28563bfd556b5fba','i:1;','1772471780'),
('5c785c036466adea360111aa28563bfd556b5fba:timer','i:1772471780;','1772471780');

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `enforcement_notices`;
CREATE TABLE `enforcement_notices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tax_object_id` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enforcement_notices_number_unique` (`number`),
  KEY `enforcement_notices_tax_object_id_foreign` (`tax_object_id`),
  KEY `enforcement_notices_created_by_foreign` (`created_by`),
  KEY `enforcement_notices_approved_by_foreign` (`approved_by`),
  KEY `enforcement_notices_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `enforcement_notices_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enforcement_notices_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `enforcement_notices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enforcement_notices_tax_object_id_foreign` FOREIGN KEY (`tax_object_id`) REFERENCES `tax_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `incentive_tables`;
CREATE TABLE `incentive_tables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `monthly_reports`;
CREATE TABLE `monthly_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `taxpayer_id` bigint unsigned NOT NULL,
  `tax_object_id` bigint unsigned NOT NULL,
  `period` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `turnover_amount` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL,
  `attachments` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `validated_at` timestamp NULL DEFAULT NULL,
  `validated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `monthly_reports_tax_object_id_period_unique` (`tax_object_id`,`period`),
  KEY `monthly_reports_taxpayer_id_foreign` (`taxpayer_id`),
  KEY `monthly_reports_validated_by_foreign` (`validated_by`),
  CONSTRAINT `monthly_reports_tax_object_id_foreign` FOREIGN KEY (`tax_object_id`) REFERENCES `tax_objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_reports_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `monthly_reports_validated_by_foreign` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `object_verifications`;
CREATE TABLE `object_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tax_object_id` bigint unsigned NOT NULL,
  `pendata_tanggal` date DEFAULT NULL,
  `pendata_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pendata_nip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pendata_tanda_tangan_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pejabat_tanggal` date DEFAULT NULL,
  `pejabat_nama` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pejabat_nip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pejabat_tanda_tangan_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `object_verifications_tax_object_id_foreign` (`tax_object_id`),
  KEY `object_verifications_verified_by_foreign` (`verified_by`),
  CONSTRAINT `object_verifications_tax_object_id_foreign` FOREIGN KEY (`tax_object_id`) REFERENCES `tax_objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `object_verifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `opds`;
CREATE TABLE `opds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `opds_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `opds` VALUES
('1','Dinas Perhubungan','DISHUB','Jl. Protokol No. 1','0401-123456','dishub@baubau.go.id',NULL,'approved','1','2026-03-02 05:13:25','2026-03-02 05:13:25'),
('2','Dinas Perindustrian dan Perdagangan','DISPERINDAG','Jl. Pasar No. 2','0401-654321','disperindag@baubau.go.id',NULL,'approved','1','2026-03-02 05:13:25','2026-03-02 05:13:25'),
('3','Dinas Lingkungan Hidup','DLH','Jl. Hijau No. 3','0401-111222','dlh@baubau.go.id',NULL,'approved','1','2026-03-02 05:13:25','2026-03-02 05:13:25'),
('4','Badan Pendapatan Daerah','BAPENDA','Jl. Bapenda No. 1','0401-999888','bapenda@baubau.go.id',NULL,'approved','1','2026-03-02 05:13:25','2026-03-02 05:13:25');

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint unsigned DEFAULT NULL,
  `billing_period` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taxpayer_id` bigint unsigned DEFAULT NULL,
  `tax_object_id` bigint unsigned DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `proof_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
  KEY `payments_bill_id_foreign` (`bill_id`),
  KEY `payments_approved_by_foreign` (`approved_by`),
  KEY `payments_taxpayer_id_foreign` (`taxpayer_id`),
  KEY `payments_tax_object_id_foreign` (`tax_object_id`),
  CONSTRAINT `payments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_tax_object_id_foreign` FOREIGN KEY (`tax_object_id`) REFERENCES `tax_objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` VALUES
('1','1','Januari 2026','1','1',NULL,'cash','1500000.00','approved','6',NULL,'2026-01-12 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','2','Februari 2026','1','1',NULL,'transfer','1500000.00','approved','6',NULL,'2026-02-14 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','4','Januari 2026','2','2',NULL,'transfer','500000.00','approved','6',NULL,'2026-01-13 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','5','Februari 2026','2','2',NULL,'qris','500000.00','approved','6',NULL,'2026-02-13 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','7','Januari 2026','3','3',NULL,'transfer','2000000.00','approved','6',NULL,'2026-01-14 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','8','Februari 2026','3','3',NULL,'cash','2000000.00','approved','6',NULL,'2026-02-10 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','16','Januari 2026','6','6',NULL,'transfer','8000000.00','approved','6',NULL,'2026-01-11 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','17','Februari 2026','6','6',NULL,'qris','8000000.00','approved','6',NULL,'2026-02-11 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','19','Januari 2026','7','7',NULL,'cash','1500000.00','approved','6',NULL,'2026-01-14 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','20','Februari 2026','7','7',NULL,'transfer','1500000.00','approved','6',NULL,'2026-02-13 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('11','22','Januari 2026','8','8',NULL,'qris','3000000.00','approved','6',NULL,'2026-01-11 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('12','23','Februari 2026','8','8',NULL,'cash','3000000.00','approved','6',NULL,'2026-02-11 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('13','31','Januari 2026','11','11',NULL,'cash','1200000.00','approved','6',NULL,'2026-01-10 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('14','32','Februari 2026','11','11',NULL,'cash','1200000.00','approved','6',NULL,'2026-02-12 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('15','34','Januari 2026','12','12',NULL,'cash','2500000.00','approved','6',NULL,'2026-01-12 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('16','35','Februari 2026','12','12',NULL,'cash','2500000.00','approved','6',NULL,'2026-02-11 00:00:00','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('17','37','Januari 2026','13','13',NULL,'transfer','1800000.00','approved','6',NULL,'2026-01-10 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('18','38','Februari 2026','13','13',NULL,'transfer','1800000.00','approved','6',NULL,'2026-02-14 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('19','46','Januari 2026','16','16',NULL,'transfer','2000000.00','approved','6',NULL,'2026-01-11 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('20','47','Februari 2026','16','16',NULL,'cash','2000000.00','approved','6',NULL,'2026-02-12 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('21','49','Januari 2026','17','17',NULL,'transfer','1000000.00','approved','6',NULL,'2026-01-13 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('22','50','Februari 2026','17','17',NULL,'transfer','1000000.00','approved','6',NULL,'2026-02-10 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('23','52','Januari 2026','18','18',NULL,'transfer','1500000.00','approved','6',NULL,'2026-01-13 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('24','53','Februari 2026','18','18',NULL,'cash','1500000.00','approved','6',NULL,'2026-02-13 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('25','61','Januari 2026','21','21',NULL,'transfer','50000000.00','approved','6',NULL,'2026-01-14 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('26','62','Februari 2026','21','21',NULL,'cash','50000000.00','approved','6',NULL,'2026-02-11 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('27','64','Januari 2026','22','22',NULL,'transfer','8000000.00','approved','6',NULL,'2026-01-10 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('28','65','Februari 2026','22','22',NULL,'transfer','8000000.00','approved','6',NULL,'2026-02-11 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('29','67','Januari 2026','23','23',NULL,'qris','2000000.00','approved','6',NULL,'2026-01-12 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('30','68','Februari 2026','23','23',NULL,'transfer','2000000.00','approved','6',NULL,'2026-02-14 00:00:00','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('31','76','2025-01','26','26','TRANS-BR8TG3QVU8','VA_BCA','50000.00','success',NULL,NULL,'2026-02-16 05:13:33','2026-03-02 05:13:33','2026-03-02 05:13:33');

DROP TABLE IF EXISTS `pbb_njop_classifications`;
CREATE TABLE `pbb_njop_classifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('bumi','bangunan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_value` decimal(15,2) NOT NULL,
  `max_value` decimal(15,2) DEFAULT NULL,
  `njop_value` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pbb_njop_classifications_type_class_code_unique` (`type`,`class_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `penalty_waivers`;
CREATE TABLE `penalty_waivers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reduction_type` enum('percentage','fixed_amount') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `reduction_value` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approval_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `penalty_waivers_bill_id_foreign` (`bill_id`),
  KEY `penalty_waivers_requested_by_foreign` (`requested_by`),
  KEY `penalty_waivers_approved_by_foreign` (`approved_by`),
  CONSTRAINT `penalty_waivers_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `penalty_waivers_bill_id_foreign` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penalty_waivers_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `personal_access_tokens` VALUES
('1','App\\Models\\User','6','auth_token','5cd5eee6917d196f5b35bc028929716a939d0b8923bda2ba14910e0cd49a00d3','[\"*\"]','2026-03-02 17:31:23',NULL,'2026-03-02 16:47:40','2026-03-02 17:31:23');

DROP TABLE IF EXISTS `petugas_tasks`;
CREATE TABLE `petugas_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `taxpayer_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `petugas_tasks_user_id_foreign` (`user_id`),
  KEY `petugas_tasks_zone_id_foreign` (`zone_id`),
  KEY `petugas_tasks_taxpayer_id_foreign` (`taxpayer_id`),
  CONSTRAINT `petugas_tasks_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `petugas_tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `petugas_tasks_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `retribution_classifications`;
CREATE TABLE `retribution_classifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opd_id` bigint unsigned NOT NULL,
  `retribution_type_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_self_assessment` tinyint(1) NOT NULL DEFAULT '0',
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `form_schema` json DEFAULT NULL,
  `requirements` json DEFAULT NULL,
  `calculation_formula` text COLLATE utf8mb4_unicode_ci,
  `bank_accounts` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retribution_classifications_opd_id_foreign` (`opd_id`),
  KEY `retribution_classifications_retribution_type_id_foreign` (`retribution_type_id`),
  CONSTRAINT `retribution_classifications_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `retribution_classifications_retribution_type_id_foreign` FOREIGN KEY (`retribution_type_id`) REFERENCES `retribution_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `retribution_classifications` VALUES
('1','4','8','PBJT - Makan dan Minum','0','PBJT-MNM','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('2','4','8','PBJT - Tenaga Listrik','0','PBJT-LIS','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('3','4','8','PBJT - Jasa Perhotelan','0','PBJT-HTL','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('4','4','8','PBJT - Jasa Parkir','0','PBJT-PRK','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','4','8','PBJT - Jasa Kesenian dan Hiburan','0','PBJT-HBR','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','4','8','PBJT - Jasa Catering','0','PBJT-CAT','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','4','8','PBJT - Jasa Event/Lainnya','0','PBJT-EVT','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','4','7','Penyediaan Tempat Kegiatan Usaha','0','PTKU','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','4','7','Pajak Reklame','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','4','7','Pajak MBLB','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('11','4','7','Pajak Sarang Burung Walet','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855486/retribusi/icons/agqc0orhv7i9wg4a7x1t.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('12','4','7','Air Tanah','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('13','4','7','BPHTB','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('14','4','7','Opsen PKB','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('15','4','7','Opsen BBNKB','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('16','4','7','Retribusi Jasa Umum','0','RET','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('17','4','7','Retribusi Perizinan Tertentu','0','RET','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('18','4','7','PBB','0','TAX','[{\"key\": \"tanggal_pendataan\", \"type\": \"date\", \"label\": \"Tanggal Pendataan\", \"required\": true}, {\"key\": \"nama_jenis_usaha\", \"type\": \"text\", \"label\": \"Nama Jenis Usaha\", \"required\": true}, {\"key\": \"omset_penjualan\", \"type\": \"number\", \"label\": \"Omset Penjualan (Rata-rata/Bulan)\", \"required\": true}, {\"key\": \"tarif_pajak\", \"type\": \"text\", \"label\": \"Tarif Pajak (%)\", \"required\": true}, {\"key\": \"keterangan_usaha\", \"type\": \"select\", \"label\": \"Keterangan Usaha\", \"options\": [\"Aktif\", \"Tidak Aktif\"], \"required\": true}, {\"key\": \"lokasi_google_maps\", \"type\": \"text\", \"label\": \"Link Lokasi Google Maps\", \"required\": true}]','[{\"key\": \"foto_lokasi_open_kamera\", \"label\": \"Dokumentasi Open Kamera\", \"required\": true}, {\"key\": \"formulir_data_dukung\", \"label\": \"Upload Formulir Data Dukung\", \"required\": true}]',NULL,NULL,NULL,'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg','2026-03-02 05:13:29','2026-03-02 05:13:29');

DROP TABLE IF EXISTS `retribution_rates`;
CREATE TABLE `retribution_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opd_id` bigint unsigned NOT NULL,
  `retribution_type_id` bigint unsigned NOT NULL,
  `retribution_classification_id` bigint unsigned DEFAULT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `calculation_formula` text COLLATE utf8mb4_unicode_ci,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retribution_rates_opd_id_foreign` (`opd_id`),
  KEY `retribution_rates_retribution_type_id_foreign` (`retribution_type_id`),
  KEY `retribution_rates_retribution_classification_id_foreign` (`retribution_classification_id`),
  KEY `retribution_rates_zone_id_foreign` (`zone_id`),
  CONSTRAINT `retribution_rates_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `retribution_rates_retribution_classification_id_foreign` FOREIGN KEY (`retribution_classification_id`) REFERENCES `retribution_classifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `retribution_rates_retribution_type_id_foreign` FOREIGN KEY (`retribution_type_id`) REFERENCES `retribution_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `retribution_rates_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `retribution_rates` VALUES
('1','4','7','8','1','Kios Sentra Kuliner','6000000.00',NULL,'Tahun/Kios','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','4','7','8','2','Lapak Pujasera','60000.00',NULL,'Bulan/Lapak','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','4','7','8','3','Lapak Pujasera','60000.00',NULL,'Bulan/Lapak','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','4','7','8','4','Sewa Kantor','200000.00',NULL,'Bulan','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','4','7','8','5','Sewa Toko/Kios','15000000.00',NULL,'Tahun','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','4','7','8','6','Kios Kuliner','500000.00',NULL,'Bulan','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','4','7','8','7','Kotamara','120000.00',NULL,'Lapak/Bulan','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','4','8','5',NULL,'Tarif Hiburan Umum','10.00',NULL,'%','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','4','8','5',NULL,'Tarif Khusus','40.00',NULL,'%','1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','4','8','4',NULL,'Tarif Parkir','30.00',NULL,'%','1','2026-03-02 05:13:29','2026-03-02 05:13:29');

DROP TABLE IF EXISTS `retribution_types`;
CREATE TABLE `retribution_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opd_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tariff_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_amount` decimal(15,2) DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_cycle` enum('daily','weekly','monthly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retribution_types_opd_id_foreign` (`opd_id`),
  CONSTRAINT `retribution_types_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `retribution_types` VALUES
('1','1','Retribusi Parkir Mobil','Parkir','0.00','car','5000.00','per jam','monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('2','1','Retribusi Parkir Motor','Parkir','0.00','bike','2000.00','per jam','monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('3','1','Retribusi Terminal','Terminal','0.00','bus','10000.00','per bus','monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('4','2','Retribusi Kios Pasar','Pasar','0.00','store','150000.00','per bulan','monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('5','2','Retribusi Los Pasar','Pasar','0.00','market','50000.00','per bulan','monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('6','3','Retribusi Persampahan','Kebersihan','0.00','trash','30000.00','per bulan','monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('7','4','Wilayah I','Pajak','0.00','https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg',NULL,NULL,'monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28'),
('8','4','Wilayah II','Pajak','0.00','https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg',NULL,NULL,'monthly','1','2026-03-02 05:13:28','2026-03-02 05:13:28');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` VALUES
('g64mwdUzjSHG2VHrKNHrHr0zowxSYnp2RhG25yRz',NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoia1pKOHFIdldyV2FPaTJ1Tk56TE1lMFZDVEVYSFdZZ2lON2xHVm5SSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=','1772471720');

DROP TABLE IF EXISTS `signed_documents`;
CREATE TABLE `signed_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_id` bigint unsigned NOT NULL,
  `document_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_hash` text COLLATE utf8mb4_unicode_ci,
  `signed_by` bigint unsigned DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `verification_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `signed_documents_document_number_unique` (`document_number`),
  KEY `signed_documents_signed_by_foreign` (`signed_by`),
  KEY `signed_documents_document_type_document_id_index` (`document_type`,`document_id`),
  CONSTRAINT `signed_documents_signed_by_foreign` FOREIGN KEY (`signed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tax_objects`;
CREATE TABLE `tax_objects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nop` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taxpayer_id` bigint unsigned NOT NULL,
  `retribution_type_id` bigint unsigned NOT NULL,
  `retribution_classification_id` bigint unsigned DEFAULT NULL,
  `opd_id` bigint unsigned NOT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `nomor_formulir` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'perekaman_data',
  `metadata` json DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `audit_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `nama_penandatangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_pernyataan` date DEFAULT NULL,
  `tanda_tangan_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_objects_nop_unique` (`nop`),
  KEY `tax_objects_taxpayer_id_foreign` (`taxpayer_id`),
  KEY `tax_objects_retribution_type_id_foreign` (`retribution_type_id`),
  KEY `tax_objects_opd_id_foreign` (`opd_id`),
  KEY `tax_objects_zone_id_foreign` (`zone_id`),
  KEY `tax_objects_approved_by_foreign` (`approved_by`),
  KEY `tax_objects_retribution_classification_id_foreign` (`retribution_classification_id`),
  CONSTRAINT `tax_objects_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_objects_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_objects_retribution_classification_id_foreign` FOREIGN KEY (`retribution_classification_id`) REFERENCES `retribution_classifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_objects_retribution_type_id_foreign` FOREIGN KEY (`retribution_type_id`) REFERENCES `retribution_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_objects_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_objects_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tax_objects` VALUES
('1','NPWPD-D3BCCF19-8-1','1','8','1','4',NULL,'Rumah Makan Padang - Ahmad Subarjo','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000',NULL,'perekaman_data','{\"omzet\": 15000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','NPWPD-4A05B28E-8-1','2','8','1','4',NULL,'Warung Nasi Kuning - Siti Nurhaliza','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000',NULL,'perekaman_data','{\"omzet\": 5000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','NPWPD-A66FEDFE-8-1','3','8','1','4',NULL,'Coffee Shop - Budi Santoso','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000',NULL,'perekaman_data','{\"omzet\": 20000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','NPWPD-905982ED-8-1','4','8','1','4',NULL,'Warung Bakso - Dewi Lestari','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000',NULL,'perekaman_data','{\"omzet\": 8000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','NPWPD-2769EF0A-8-1','5','8','1','4',NULL,'Rumah Makan Seafood - Muhammad Rizky','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000',NULL,'perekaman_data','{\"omzet\": 25000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','NPWPD-89F811E1-8-3','6','8','3','4',NULL,'Hotel Bintang 3 - Ani Rahayu','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000',NULL,'perekaman_data','{\"omzet\": 80000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','NPWPD-6F5E791F-8-3','7','8','3','4',NULL,'Penginapan - Hasan Al-Bashri','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000',NULL,'perekaman_data','{\"omzet\": 15000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','NPWPD-1E2DEC5C-8-3','8','8','3','4',NULL,'Villa - Fatimah Zahra','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000',NULL,'perekaman_data','{\"omzet\": 30000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','NPWPD-4DAFFEBA-8-3','9','8','3','4',NULL,'Homestay - La Ode Rahman','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000',NULL,'perekaman_data','{\"omzet\": 10000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','NPWPD-6041D3A5-8-3','10','8','3','4',NULL,'Hotel Bintang 2 - Wa Ode Sarina','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000',NULL,'perekaman_data','{\"omzet\": 45000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('11','NPWPD-8864931B-8-4','11','8','4','4',NULL,'Lahan Parkir Pasar - Agus Prasetyo','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000',NULL,'perekaman_data','{\"omzet\": 12000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('12','NPWPD-BE4B1A57-8-4','12','8','4','4',NULL,'Gedung Parkir - Rina Marlina','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000',NULL,'perekaman_data','{\"omzet\": 25000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('13','NPWPD-41CF9E80-8-4','13','8','4','4',NULL,'Area Parkir Pelabuhan - Sulaiman Darwis','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000',NULL,'perekaman_data','{\"omzet\": 18000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('14','NPWPD-0984D77F-8-4','14','8','4','4',NULL,'Parkir Pusat Kota - Nurhayati Amin','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000',NULL,'perekaman_data','{\"omzet\": 35000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('15','NPWPD-0663C821-8-4','15','8','4','4',NULL,'Parkir Objek Wisata - La Ode Muh Akbar','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000',NULL,'perekaman_data','{\"omzet\": 8000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('16','NPWPD-7A07C453-8-5','16','8','5','4',NULL,'Studio Karaoke - Wa Ode Fitri','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000',NULL,'perekaman_data','{\"omzet\": 20000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('17','NPWPD-4D3655B1-8-5','17','8','5','4',NULL,'Pusat Billiard - Abdul Karim','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000',NULL,'perekaman_data','{\"omzet\": 10000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('18','NPWPD-C3536C27-8-5','18','8','5','4',NULL,'Arena Bermain - Salmah Basri','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000',NULL,'perekaman_data','{\"omzet\": 15000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('19','NPWPD-1FE43C43-8-5','19','8','5','4',NULL,'Gedung Serbaguna - La Ode Syahrul','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000',NULL,'perekaman_data','{\"omzet\": 30000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('20','NPWPD-05734CFE-8-5','20','8','5','4',NULL,'Bioskop - Wa Ode Hasna','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000',NULL,'perekaman_data','{\"omzet\": 40000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('21','NPWPD-4BBDC128-8-2','21','8','2','4',NULL,'Gardu Listrik - Irfan Hakim','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000',NULL,'perekaman_data','{\"omzet\": 500000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('22','NPWPD-E6291036-8-2','22','8','2','4',NULL,'Pembangkit Listrik Swasta - Marwah Said','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000',NULL,'perekaman_data','{\"omzet\": 80000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('23','NPWPD-F3A18B93-8-2','23','8','2','4',NULL,'Instalasi Solar - La Ode Safri','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000',NULL,'perekaman_data','{\"omzet\": 20000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('24','NPWPD-D3978351-8-2','24','8','2','4',NULL,'Pembangkit Diesel - Wa Ode Nursia','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000',NULL,'perekaman_data','{\"omzet\": 45000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('25','NPWPD-FF89B759-8-2','25','8','2','4',NULL,'Pembangkit Mikro - Ruslan Abadi','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000',NULL,'perekaman_data','{\"omzet\": 6000000, \"keterangan_usaha\": \"Aktif\"}','active','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('26','PRK-74-001','26','1',NULL,'1',NULL,'Lahan Parkir Toko Budi','Jl. Merdeka No. 5','-5.46330000','122.60120000',NULL,'perekaman_data',NULL,'active','normal',NULL,NULL,NULL,'2026-01-02 05:13:33',NULL,'2026-03-02 05:13:33','2026-03-02 05:13:33'),
('27','PBB-74-001','26','7','18','4',NULL,'Rumah Tinggal Budi','Jl. Wolter Monginsidi No. 12','-5.46450000','122.60250000',NULL,'perekaman_data','{\"luas_bumi\": 200, \"luas_bangunan\": 100}','active','normal',NULL,NULL,NULL,'2025-09-02 05:13:33',NULL,'2026-03-02 05:13:33','2026-03-02 05:13:33'),
('28','KIO-74-001','27','4',NULL,'2',NULL,'Kios Sembako Ani','Pasar Karya No. 10',NULL,NULL,NULL,'perekaman_data',NULL,'active','normal',NULL,NULL,NULL,'2026-02-02 05:13:33',NULL,'2026-03-02 05:13:33','2026-03-02 05:13:33'),
('29','SMP-74-001','27','6',NULL,'3',NULL,'Rumah Ani (Retribusi Sampah)','Jl. Pahlawan No. 45',NULL,NULL,NULL,'perekaman_data',NULL,'active','normal',NULL,NULL,NULL,'2025-12-02 05:13:33',NULL,'2026-03-02 05:13:33','2026-03-02 05:13:33'),
('30',NULL,'27','4',NULL,'2',NULL,'Kios Pakaian Baru','Pasar Karya Blok B',NULL,NULL,NULL,'perekaman_data',NULL,'pending','normal',NULL,NULL,NULL,NULL,NULL,'2026-03-02 05:13:33','2026-03-02 05:13:33');

DROP TABLE IF EXISTS `taxpayer_pbb_objects`;
CREATE TABLE `taxpayer_pbb_objects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `taxpayer_id` bigint unsigned NOT NULL,
  `nop` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_on_sppt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_on_sppt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelurahan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kota` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taxpayer_pbb_objects_taxpayer_id_nop_unique` (`taxpayer_id`,`nop`),
  KEY `taxpayer_pbb_objects_nop_index` (`nop`),
  CONSTRAINT `taxpayer_pbb_objects_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `taxpayer_retribution_type`;
CREATE TABLE `taxpayer_retribution_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `taxpayer_id` bigint unsigned NOT NULL,
  `retribution_type_id` bigint unsigned NOT NULL,
  `retribution_classification_id` bigint unsigned DEFAULT NULL,
  `custom_amount` decimal(15,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taxp_retri_type_class_unique` (`taxpayer_id`,`retribution_type_id`,`retribution_classification_id`),
  KEY `taxpayer_retribution_type_retribution_classification_id_foreign` (`retribution_classification_id`),
  KEY `taxpayer_retribution_type_retribution_type_id_foreign` (`retribution_type_id`),
  CONSTRAINT `taxpayer_retribution_type_retribution_classification_id_foreign` FOREIGN KEY (`retribution_classification_id`) REFERENCES `retribution_classifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `taxpayer_retribution_type_retribution_type_id_foreign` FOREIGN KEY (`retribution_type_id`) REFERENCES `retribution_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `taxpayer_retribution_type_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `taxpayer_retribution_type` VALUES
('1','1','8','1',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','2','8','1',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','3','8','1',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','4','8','1',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','5','8','1',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','6','8','3',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','7','8','3',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','8','8','3',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','9','8','3',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','10','8','3',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('11','11','8','4',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('12','12','8','4',NULL,NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('13','13','8','4',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('14','14','8','4',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('15','15','8','4',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('16','16','8','5',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('17','17','8','5',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('18','18','8','5',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('19','19','8','5',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('20','20','8','5',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('21','21','8','2',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('22','22','8','2',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('23','23','8','2',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('24','24','8','2',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('25','25','8','2',NULL,NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30');

DROP TABLE IF EXISTS `taxpayers`;
CREATE TABLE `taxpayers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opd_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `nik` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `npwpd` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `object_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taxpayers_nik_index` (`nik`),
  KEY `taxpayers_opd_id_is_active_index` (`opd_id`,`is_active`),
  KEY `taxpayers_created_by_foreign` (`created_by`),
  CONSTRAINT `taxpayers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `taxpayers_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `taxpayers` VALUES
('1','4','7','7404635339771441','Ahmad Subarjo','Jl. La Ode Hadi, Wameo','Batupoaro','Wameo','082113657247','NPWPD-D3BCCF19','Rumah Makan Padang','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 15000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Rumah Makan Padang\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','4','7','7404802186767073','Siti Nurhaliza','Jl. Dayanu Ikhsanuddin, Baadia','Murhum','Baadia','082181146010','NPWPD-4A05B28E','Warung Nasi Kuning','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 5000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Warung Nasi Kuning\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','4','7','7404514344848309','Budi Santoso','Jl. Wangkanapi, Wangkanapi','Wolio','Wangkanapi','082129132386','NPWPD-A66FEDFE','Coffee Shop','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 20000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Coffee Shop\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','4','7','7404436557176802','Dewi Lestari','Jl. Lipu Permai, Lipu','Betoambari','Lipu','082171119086','NPWPD-905982ED','Warung Bakso','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 8000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Warung Bakso\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','4','7','7404136263092557','Muhammad Rizky','Jl. Sukanaeyo, Sukanaeyo','Kokalukuna','Sukanaeyo','082145042629','NPWPD-2769EF0A','Rumah Makan Seafood','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 25000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Rumah Makan Seafood\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','4','7','7404397333375351','Ani Rahayu','Jl. La Ode Hadi, Wameo','Batupoaro','Wameo','082163607953','NPWPD-89F811E1','Hotel Bintang 3','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 80000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Hotel Bintang 3\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','4','7','7404891192947333','Hasan Al-Bashri','Jl. Dayanu Ikhsanuddin, Baadia','Murhum','Baadia','082115708868','NPWPD-6F5E791F','Penginapan','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 15000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Penginapan\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','4','7','7404417480368120','Fatimah Zahra','Jl. Wangkanapi, Wangkanapi','Wolio','Wangkanapi','082154813819','NPWPD-1E2DEC5C','Villa','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 30000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Villa\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','4','7','7404352123856390','La Ode Rahman','Jl. Lipu Permai, Lipu','Betoambari','Lipu','082187256950','NPWPD-4DAFFEBA','Homestay','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 10000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Homestay\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','4','7','7404973250383496','Wa Ode Sarina','Jl. Sukanaeyo, Sukanaeyo','Kokalukuna','Sukanaeyo','082196992982','NPWPD-6041D3A5','Hotel Bintang 2','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 45000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Hotel Bintang 2\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('11','4','7','7404460348311376','Agus Prasetyo','Jl. La Ode Hadi, Wameo','Batupoaro','Wameo','082168512928','NPWPD-8864931B','Lahan Parkir Pasar','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 12000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Lahan Parkir Pasar\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('12','4','7','7404641105002345','Rina Marlina','Jl. Dayanu Ikhsanuddin, Baadia','Murhum','Baadia','082175979253','NPWPD-BE4B1A57','Gedung Parkir','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 25000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Gedung Parkir\"}',NULL,'1','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('13','4','7','7404506572988027','Sulaiman Darwis','Jl. Wangkanapi, Wangkanapi','Wolio','Wangkanapi','082162762477','NPWPD-41CF9E80','Area Parkir Pelabuhan','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 18000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Area Parkir Pelabuhan\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('14','4','7','7404307746953882','Nurhayati Amin','Jl. Lipu Permai, Lipu','Betoambari','Lipu','082152335766','NPWPD-0984D77F','Parkir Pusat Kota','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 35000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Parkir Pusat Kota\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('15','4','7','7404771068196049','La Ode Muh Akbar','Jl. Sukanaeyo, Sukanaeyo','Kokalukuna','Sukanaeyo','082186683436','NPWPD-0663C821','Parkir Objek Wisata','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 8000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Parkir Objek Wisata\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('16','4','7','7404953405683825','Wa Ode Fitri','Jl. La Ode Hadi, Wameo','Batupoaro','Wameo','082134840840','NPWPD-7A07C453','Studio Karaoke','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 20000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Studio Karaoke\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('17','4','7','7404357274063520','Abdul Karim','Jl. Dayanu Ikhsanuddin, Baadia','Murhum','Baadia','082149922553','NPWPD-4D3655B1','Pusat Billiard','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 10000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Pusat Billiard\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('18','4','7','7404672150542920','Salmah Basri','Jl. Wangkanapi, Wangkanapi','Wolio','Wangkanapi','082181322102','NPWPD-C3536C27','Arena Bermain','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 15000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Arena Bermain\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('19','4','7','7404789103082560','La Ode Syahrul','Jl. Lipu Permai, Lipu','Betoambari','Lipu','082152682281','NPWPD-1FE43C43','Gedung Serbaguna','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 30000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Gedung Serbaguna\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('20','4','7','7404525401305624','Wa Ode Hasna','Jl. Sukanaeyo, Sukanaeyo','Kokalukuna','Sukanaeyo','082152477239','NPWPD-05734CFE','Bioskop','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 40000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Bioskop\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('21','4','7','7404487411321384','Irfan Hakim','Jl. La Ode Hadi, Wameo','Batupoaro','Wameo','082141300577','NPWPD-4BBDC128','Gardu Listrik','Jl. La Ode Hadi, Wameo','-5.45730000','122.60350000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 500000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Gardu Listrik\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('22','4','7','7404449032553863','Marwah Said','Jl. Dayanu Ikhsanuddin, Baadia','Murhum','Baadia','082142570443','NPWPD-E6291036','Pembangkit Listrik Swasta','Jl. Dayanu Ikhsanuddin, Baadia','-5.45100000','122.59750000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 80000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Pembangkit Listrik Swasta\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('23','4','7','7404869748194195','La Ode Safri','Jl. Wangkanapi, Wangkanapi','Wolio','Wangkanapi','082122030990','NPWPD-F3A18B93','Instalasi Solar','Jl. Wangkanapi, Wangkanapi','-5.46950000','122.60600000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 20000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Instalasi Solar\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('24','4','7','7404495691285117','Wa Ode Nursia','Jl. Lipu Permai, Lipu','Betoambari','Lipu','082119306160','NPWPD-D3978351','Pembangkit Diesel','Jl. Lipu Permai, Lipu','-5.44450000','122.58850000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 45000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Pembangkit Diesel\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('25','4','7','7404205883182298','Ruslan Abadi','Jl. Sukanaeyo, Sukanaeyo','Kokalukuna','Sukanaeyo','082172616074','NPWPD-FF89B759','Pembangkit Mikro','Jl. Sukanaeyo, Sukanaeyo','-5.47900000','122.61700000','{\"tarif_pajak\": \"10\", \"omset_penjualan\": 6000000, \"keterangan_usaha\": \"Aktif\", \"nama_jenis_usaha\": \"Pembangkit Mikro\"}',NULL,'1','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('26','4',NULL,'1234567890123456','Budi Santoso','Jl. Wolter Monginsidi No. 12, Baubau',NULL,NULL,'081234567890','P-2-0000001',NULL,NULL,NULL,NULL,NULL,'$2y$12$0nK/5eUUV0jq78/Hp3YameGQlpNG7juFEnuQnYnDd1lFqNzy9jwyG','1','2026-03-02 05:13:32','2026-03-02 05:13:32'),
('27','4',NULL,'1234567890123457','Ani Lestari','Jl. Pahlawan No. 45, Baubau',NULL,NULL,'081234567899','P-2-0000002',NULL,NULL,NULL,NULL,NULL,'$2y$12$m9Vr.FQLncum59SH/IS3weuR5sQxFJyL7PFBhXP.OwqRUvqqvaCEy','1','2026-03-02 05:13:33','2026-03-02 05:13:33');

DROP TABLE IF EXISTS `transaction_pbb`;
CREATE TABLE `transaction_pbb` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `taxpayer_id` bigint unsigned DEFAULT NULL,
  `nop` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `denda` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_bayar` decimal(15,2) NOT NULL DEFAULT '0.00',
  `ntpd` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` enum('pending','success','failed','reversed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `wp_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wp_address` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelurahan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kota` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reversal_reason` text COLLATE utf8mb4_unicode_ci,
  `api_response` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_pbb_user_id_foreign` (`user_id`),
  KEY `transaction_pbb_taxpayer_id_foreign` (`taxpayer_id`),
  KEY `transaction_pbb_nop_tahun_index` (`nop`,`tahun`),
  KEY `transaction_pbb_payment_status_index` (`payment_status`),
  KEY `transaction_pbb_ntpd_index` (`ntpd`),
  CONSTRAINT `transaction_pbb_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaction_pbb_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `user_retribution_assignments`;
CREATE TABLE `user_retribution_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `retribution_type_id` bigint unsigned NOT NULL,
  `retribution_classification_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_retribution_assignments_user_id_foreign` (`user_id`),
  KEY `ura_type_id_foreign` (`retribution_type_id`),
  KEY `ura_class_id_foreign` (`retribution_classification_id`),
  CONSTRAINT `ura_class_id_foreign` FOREIGN KEY (`retribution_classification_id`) REFERENCES `retribution_classifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ura_type_id_foreign` FOREIGN KEY (`retribution_type_id`) REFERENCES `retribution_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_retribution_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nik` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'citizen',
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `opd_id` bigint unsigned DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_nik_unique` (`nik`),
  KEY `users_opd_id_foreign` (`opd_id`),
  CONSTRAINT `users_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES
('1','Super Admin','admin@retribusi.id',NULL,'super_admin',NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'$2y$12$YXjE6jpFB053OsFxLypuMuWvc1vMu4mfxVcyefjerHpnswBNbLJ.u',NULL,'2026-03-02 05:13:24','2026-03-02 05:13:24'),
('2','Dev Super Admin','superadmin@sipanda.online',NULL,'super_admin',NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'$2y$12$k5IPOQq1pUv9En0VempJAOzNKekttAtEh/S3N1JaV8Vh4AHbB1EUm',NULL,'2026-03-02 05:13:25','2026-03-02 05:13:25'),
('3','Admin Dishub','dishub@retribusi.id',NULL,'opd',NULL,NULL,'1',NULL,NULL,'active',NULL,NULL,'$2y$12$Pt3eoasdjheLOPaQJhF7IOSqMoubiv5cdpZKyFtGBxnA687N3GFM6',NULL,'2026-03-02 05:13:26','2026-03-02 05:13:26'),
('4','Admin Disperindag','disperindag@retribusi.id',NULL,'opd',NULL,NULL,'2',NULL,NULL,'active',NULL,NULL,'$2y$12$5a5XUvpZbyaIdKB8/FqN1.XpXmwA4ZFA2Etc5PZCoCNWERw916JQm',NULL,'2026-03-02 05:13:26','2026-03-02 05:13:26'),
('5','Admin DLH','dlh@retribusi.id',NULL,'opd',NULL,NULL,'3',NULL,NULL,'active',NULL,NULL,'$2y$12$fIjBCsLy.8iPt8BdYbrkBeKb6ncWsnWd3aAMW9BzGEzTKi6SHKyzW',NULL,'2026-03-02 05:13:27','2026-03-02 05:13:27'),
('6','Admin BAPENDA','bapenda@baubaukota.go.id',NULL,'opd',NULL,NULL,'4',NULL,NULL,'active',NULL,NULL,'$2y$12$y4TbDHzNzGx2UbjbjsBGyeR.IbRDD57NgWR8TKR347pb4m3O8zaDS',NULL,'2026-03-02 05:13:28','2026-03-02 05:13:28'),
('7','Petugas BAPENDA','petugas@bapenda.go.id',NULL,'petugas',NULL,NULL,'4',NULL,NULL,'active',NULL,NULL,'$2y$12$Ob6P.qi.R8BLD9ImREUake5NCKku9kuCnBg99TjxBu8pD.DjMYILy',NULL,'2026-03-02 05:13:28','2026-03-02 05:13:28'),
('8','Kabid Pengawas','kabid@retribusi.id','9999999999999901','kabid_pengawas',NULL,NULL,'4',NULL,NULL,'active',NULL,NULL,'$2y$12$aFkRY9UxfSM7ZWB3u3kNqe8Weo1dXkcU0JVOPi5gsTP6aMY9WSNUy',NULL,'2026-03-02 05:13:31','2026-03-02 05:13:31'),
('9','Kasubid Pengawas','kasubid@retribusi.id','9999999999999902','kasubid_pengawas',NULL,NULL,'4',NULL,NULL,'active',NULL,NULL,'$2y$12$sLLYFFCNGkT.LueA9Xcnw.1egAcSTtRS1EBueR99YVUSA2cz/k7P6',NULL,'2026-03-02 05:13:31','2026-03-02 05:13:31');

DROP TABLE IF EXISTS `verifications`;
CREATE TABLE `verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opd_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `taxpayer_id` bigint unsigned DEFAULT NULL,
  `tax_object_id` bigint unsigned DEFAULT NULL,
  `document_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proof_file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `taxpayer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `verifier_id` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `verifications_document_number_unique` (`document_number`),
  KEY `verifications_opd_id_foreign` (`opd_id`),
  KEY `verifications_user_id_foreign` (`user_id`),
  KEY `verifications_verifier_id_foreign` (`verifier_id`),
  KEY `verifications_taxpayer_id_foreign` (`taxpayer_id`),
  KEY `verifications_tax_object_id_foreign` (`tax_object_id`),
  CONSTRAINT `verifications_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verifications_tax_object_id_foreign` FOREIGN KEY (`tax_object_id`) REFERENCES `tax_objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verifications_taxpayer_id_foreign` FOREIGN KEY (`taxpayer_id`) REFERENCES `taxpayers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `verifications_verifier_id_foreign` FOREIGN KEY (`verifier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `verifications` VALUES
('1','4','7','1','1','VRF-202603-0001',NULL,'Ahmad Subarjo','field_survey','1500000.00','approved','Survei lapangan Rumah Makan Padang di Batupoaro','6','2026-02-11 05:13:29','2026-02-26 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','4','7','2','2','VRF-202603-0004',NULL,'Siti Nurhaliza','field_survey','500000.00','approved','Survei lapangan Warung Nasi Kuning di Murhum','6','2026-02-25 05:13:29','2026-03-01 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','4','7','3','3','VRF-202603-0007',NULL,'Budi Santoso','field_survey','2000000.00','approved','Survei lapangan Coffee Shop di Wolio','6','2026-02-14 05:13:29','2026-03-01 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','4','7','4','4','VRF-202603-0010',NULL,'Dewi Lestari','field_survey','800000.00','pending','Survei lapangan Warung Bakso di Betoambari','6','2026-02-25 05:13:29',NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','4','7','5','5','VRF-202603-0013',NULL,'Muhammad Rizky','field_survey','2500000.00','submitted','Survei lapangan Rumah Makan Seafood di Kokalukuna','6','2026-02-19 05:13:29',NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','4','7','6','6','VRF-202603-0016',NULL,'Ani Rahayu','field_survey','8000000.00','approved','Survei lapangan Hotel Bintang 3 di Batupoaro','6','2026-02-18 05:13:29','2026-02-26 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','4','7','7','7','VRF-202603-0019',NULL,'Hasan Al-Bashri','field_survey','1500000.00','approved','Survei lapangan Penginapan di Murhum','6','2026-02-23 05:13:29','2026-02-25 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('8','4','7','8','8','VRF-202603-0022',NULL,'Fatimah Zahra','field_survey','3000000.00','approved','Survei lapangan Villa di Wolio','6','2026-02-25 05:13:29','2026-02-27 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('9','4','7','9','9','VRF-202603-0025',NULL,'La Ode Rahman','field_survey','1000000.00','pending','Survei lapangan Homestay di Betoambari','6','2026-02-16 05:13:29',NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('10','4','7','10','10','VRF-202603-0028',NULL,'Wa Ode Sarina','field_survey','4500000.00','submitted','Survei lapangan Hotel Bintang 2 di Kokalukuna','6','2026-02-09 05:13:29',NULL,'2026-03-02 05:13:29','2026-03-02 05:13:29'),
('11','4','7','11','11','VRF-202603-0031',NULL,'Agus Prasetyo','field_survey','1200000.00','approved','Survei lapangan Lahan Parkir Pasar di Batupoaro','6','2026-02-21 05:13:29','2026-02-27 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('12','4','7','12','12','VRF-202603-0034',NULL,'Rina Marlina','field_survey','2500000.00','approved','Survei lapangan Gedung Parkir di Murhum','6','2026-02-24 05:13:29','2026-02-27 05:13:29','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('13','4','7','13','13','VRF-202603-0037',NULL,'Sulaiman Darwis','field_survey','1800000.00','approved','Survei lapangan Area Parkir Pelabuhan di Wolio','6','2026-02-24 05:13:30','2026-02-28 05:13:30','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('14','4','7','14','14','VRF-202603-0040',NULL,'Nurhayati Amin','field_survey','3500000.00','pending','Survei lapangan Parkir Pusat Kota di Betoambari','6','2026-02-02 05:13:30',NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('15','4','7','15','15','VRF-202603-0043',NULL,'La Ode Muh Akbar','field_survey','800000.00','submitted','Survei lapangan Parkir Objek Wisata di Kokalukuna','6','2026-02-20 05:13:30',NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('16','4','7','16','16','VRF-202603-0046',NULL,'Wa Ode Fitri','field_survey','2000000.00','approved','Survei lapangan Studio Karaoke di Batupoaro','6','2026-02-10 05:13:30','2026-03-01 05:13:30','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('17','4','7','17','17','VRF-202603-0049',NULL,'Abdul Karim','field_survey','1000000.00','approved','Survei lapangan Pusat Billiard di Murhum','6','2026-02-02 05:13:30','2026-02-28 05:13:30','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('18','4','7','18','18','VRF-202603-0052',NULL,'Salmah Basri','field_survey','1500000.00','approved','Survei lapangan Arena Bermain di Wolio','6','2026-02-20 05:13:30','2026-02-26 05:13:30','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('19','4','7','19','19','VRF-202603-0055',NULL,'La Ode Syahrul','field_survey','3000000.00','pending','Survei lapangan Gedung Serbaguna di Betoambari','6','2026-02-23 05:13:30',NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('20','4','7','20','20','VRF-202603-0058',NULL,'Wa Ode Hasna','field_survey','4000000.00','submitted','Survei lapangan Bioskop di Kokalukuna','6','2026-02-16 05:13:30',NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('21','4','7','21','21','VRF-202603-0061',NULL,'Irfan Hakim','field_survey','50000000.00','approved','Survei lapangan Gardu Listrik di Batupoaro','6','2026-02-08 05:13:30','2026-02-26 05:13:30','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('22','4','7','22','22','VRF-202603-0064',NULL,'Marwah Said','field_survey','8000000.00','approved','Survei lapangan Pembangkit Listrik Swasta di Murhum','6','2026-02-22 05:13:30','2026-02-28 05:13:30','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('23','4','7','23','23','VRF-202603-0067',NULL,'La Ode Safri','field_survey','2000000.00','approved','Survei lapangan Instalasi Solar di Wolio','6','2026-02-05 05:13:30','2026-02-28 05:13:30','2026-03-02 05:13:30','2026-03-02 05:13:30'),
('24','4','7','24','24','VRF-202603-0070',NULL,'Wa Ode Nursia','field_survey','4500000.00','pending','Survei lapangan Pembangkit Diesel di Betoambari','6','2026-02-20 05:13:30',NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('25','4','7','25','25','VRF-202603-0073',NULL,'Ruslan Abadi','field_survey','600000.00','submitted','Survei lapangan Pembangkit Mikro di Kokalukuna','6','2026-02-19 05:13:30',NULL,'2026-03-02 05:13:30','2026-03-02 05:13:30'),
('26','2',NULL,'27','30','REG-20260302-TJ9ITS',NULL,'Ani Lestari','object_registration','0.00','pending',NULL,NULL,'2026-03-01 05:13:33',NULL,'2026-03-02 05:13:33','2026-03-02 05:13:33');

DROP TABLE IF EXISTS `zones`;
CREATE TABLE `zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opd_id` bigint unsigned DEFAULT NULL,
  `retribution_type_id` bigint unsigned DEFAULT NULL,
  `retribution_classification_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `geometry_type` enum('point','polygon') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'point',
  `coordinates` json DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zones_code_unique` (`code`),
  KEY `zones_opd_id_foreign` (`opd_id`),
  KEY `zones_retribution_type_id_foreign` (`retribution_type_id`),
  KEY `zones_retribution_classification_id_foreign` (`retribution_classification_id`),
  CONSTRAINT `zones_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zones_retribution_classification_id_foreign` FOREIGN KEY (`retribution_classification_id`) REFERENCES `retribution_classifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zones_retribution_type_id_foreign` FOREIGN KEY (`retribution_type_id`) REFERENCES `retribution_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `zones` VALUES
('1','4','7','8','Islamic Center','Z-IC',NULL,'point',NULL,'-5.47895000','122.59750000','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('2','4','7','8','Pasar Buah Wale','Z-PBW',NULL,'point',NULL,'-5.46450000','122.59900000','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('3','4','7','8','Murhum','Z-MRH',NULL,'point',NULL,'-5.45780000','122.59540000','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('4','4','7','8','Pelabuhan','Z-PLB',NULL,'point',NULL,'-5.45770000','122.59540000','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('5','4','7','8','Wisata Bahari','Z-WB',NULL,'point',NULL,'-5.49500000','122.58500000','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('6','4','7','8','Batu Sori','Z-BS',NULL,'point',NULL,'-5.47000000','122.61860000','2026-03-02 05:13:29','2026-03-02 05:13:29'),
('7','4','7','8','Pujasera Pelataran','Z-PLT',NULL,'point',NULL,'-5.47000000','122.60480000','2026-03-02 05:13:29','2026-03-02 05:13:29');

SET FOREIGN_KEY_CHECKS=1;

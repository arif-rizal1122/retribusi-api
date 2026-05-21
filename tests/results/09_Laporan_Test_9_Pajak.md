# Laporan Hasil Pengujian E2E 9 Pajak (MPAD)

Pengujian ini mengeksekusi lifecycle lengkap: Pendaftaran Objek -> Perhitungan Tagihan -> Pembayaran.

| Jenis Pajak | Waktu Pendaftaran (ms) | Waktu Tagihan (ms) | Waktu Pembayaran (ms) | Total Tagihan (Rp) | Status Akhir |
| :--- | :--- | :--- | :--- | :--- | :--- |
| PBJT - Makan dan Minum | 46 | 22 | 14 | 5.000.000 | SUCCESS |
| PBJT - Jasa Perhotelan | 6 | 10 | 12 | 10.000.000 | SUCCESS |
| Pajak Reklame | 6 | 11 | 8 | 2.500.000 | SUCCESS |
| Pajak MBLB | 8 | 15 | 7 | 750.000 | SUCCESS |
| PBJT - Jasa Kesenian dan Hiburan | 7 | 10 | 7 | 7.500.000 | SUCCESS |
| PBJT - Tenaga Listrik | 6 | 13 | 17 | 2.000.000 | SUCCESS |
| PBJT - Jasa Parkir | 6 | 18 | 9 | 1.500.000 | SUCCESS |
| Air Tanah | 8 | 16 | 7 | 200.000 | SUCCESS |
| Pajak Sarang Burung Walet | 6 | 12 | 9 | 4.000.000 | SUCCESS |

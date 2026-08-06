# Laporan Hasil Pengujian E2E 9 Pajak (MPAD)

Pengujian ini mengeksekusi lifecycle lengkap: Pendaftaran Objek -> Perhitungan Tagihan -> Pembayaran.

| Jenis Pajak | Waktu Pendaftaran (ms) | Waktu Tagihan (ms) | Waktu Pembayaran (ms) | Total Tagihan (Rp) | Status Akhir |
| :--- | :--- | :--- | :--- | :--- | :--- |
| PBJT - Makan dan Minum | 7 | 7 | 6 | 5.000.000 | SUCCESS |
| PBJT - Jasa Perhotelan | 4 | 7 | 6 | 10.000.000 | SUCCESS |
| Pajak Reklame | 4 | 8 | 7 | 2.500.000 | SUCCESS |
| Pajak MBLB | 6 | 9 | 7 | 750.000 | SUCCESS |
| PBJT - Jasa Kesenian dan Hiburan | 6 | 10 | 7 | 7.500.000 | SUCCESS |
| PBJT - Tenaga Listrik | 6 | 10 | 7 | 2.000.000 | SUCCESS |
| PBJT - Jasa Parkir | 6 | 20 | 9 | 1.500.000 | SUCCESS |
| Air Tanah | 9 | 13 | 8 | 200.000 | SUCCESS |
| Pajak Sarang Burung Walet | 4 | 7 | 7 | 4.000.000 | SUCCESS |

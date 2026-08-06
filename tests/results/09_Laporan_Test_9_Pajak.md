# Laporan Hasil Pengujian E2E 9 Pajak (MPAD)

Pengujian ini mengeksekusi lifecycle lengkap: Pendaftaran Objek -> Perhitungan Tagihan -> Pembayaran.

| Jenis Pajak | Waktu Pendaftaran (ms) | Waktu Tagihan (ms) | Waktu Pembayaran (ms) | Total Tagihan (Rp) | Status Akhir |
| :--- | :--- | :--- | :--- | :--- | :--- |
| PBJT - Makan dan Minum | 6 | 9 | 5 | 5.000.000 | SUCCESS |
| PBJT - Jasa Perhotelan | 4 | 8 | 6 | 10.000.000 | SUCCESS |
| Pajak Reklame | 6 | 9 | 8 | 2.500.000 | SUCCESS |
| Pajak MBLB | 6 | 10 | 6 | 750.000 | SUCCESS |
| PBJT - Jasa Kesenian dan Hiburan | 6 | 15 | 8 | 7.500.000 | SUCCESS |
| PBJT - Tenaga Listrik | 8 | 10 | 7 | 2.000.000 | SUCCESS |
| PBJT - Jasa Parkir | 4 | 9 | 9 | 1.500.000 | SUCCESS |
| Air Tanah | 5 | 8 | 7 | 200.000 | SUCCESS |
| Pajak Sarang Burung Walet | 6 | 9 | 8 | 4.000.000 | SUCCESS |

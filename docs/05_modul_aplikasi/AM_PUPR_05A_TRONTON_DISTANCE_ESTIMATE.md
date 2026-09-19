# AM-PUPR-05A — Kontrak Estimasi Jarak Tronton

`POST /api/pupr/tronton/calculate` menghitung biaya awal mobilisasi memakai jarak garis lurus Haversine dari workshop ke titik proyek. Response kini mencantumkan:

- `distance_basis: straight_line_estimate`
- `route_confirmation_required: true`

Nilai `distance_km` dan `total_mobilization_fee` adalah estimasi untuk membantu warga memilih bundling. Nilai tersebut tidak mengklaim jarak jalan aktual dan tidak menerbitkan tagihan final. Konfirmasi rute, kondisi akses, kelayakan tronton, serta tarif final tetap menjadi keputusan pemeriksaan lapangan/dashboard PUPR.

Tidak ada perubahan migration, tarif, atau consumer frontend pada checkpoint ini.

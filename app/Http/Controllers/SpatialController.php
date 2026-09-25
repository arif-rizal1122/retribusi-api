<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SpatialController — Kanal API layer spasial SmartGIS Bapenda.
 *
 * Sumber data: tabel smartgis_* (hasil impor dump PostGIS SmartGIS Bapenda
 * Kota Baubau, 25 Sep 2026). Geometri WGS84 (EPSG:4326), tipe MULTIPOLYGON.
 *
 * Tabel:
 *   smartgis_bidang     — 52.136 poligon bidang tanah ber-NOP (37.481 NOP unik)
 *   smartgis_kecamatan  — 8 kecamatan
 *   smartgis_kelurahan  — 43 kelurahan
 *   smartgis_blok       — 213 blok PBB-P2
 *   smartgis_dati2      — 1 batas kota
 *
 * Catatan: MariaDB 10.6 — fungsi ST_IsValid TIDAK tersedia (MySQL 8+ only).
 */
class SpatialController extends Controller
{
    /**
     * Cari bidang tanah berdasarkan titik koordinat (lat, lng).
     * GET /api/spatial/bidang-by-point?lat=-5.47&lng=122.60
     */
    public function bidangByPoint(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $lat = $data['lat'];
        $lng = $data['lng'];

        // POINT(lng lat) — perhatikan urutan: X=lng, Y=lat
        $rows = DB::connection($this->conn())->select("
            SELECT gid, nop, luas, d_luas,
                   ST_AsGeoJSON(geometry) AS geojson,
                   ST_Area(geometry) AS luas_derajat
            FROM smartgis_bidang
            WHERE ST_Contains(geometry, ST_GeomFromText(?, 4326))
            LIMIT 10
        ", ["POINT($lng $lat)"]);

        return response()->json([
            'sukses'  => true,
            'titik'   => ['lat' => $lat, 'lng' => $lng],
            'jumlah'  => count($rows),
            'bidang'  => $rows,
        ]);
    }

    /**
     * Ambil polygon bidang berdasarkan NOP.
     * GET /api/spatial/bidang/{nop}
     */
    public function bidangByNop(string $nop)
    {
        $rows = DB::connection($this->conn())->select("
            SELECT gid, nop, luas, d_luas,
                   ST_AsGeoJSON(geometry) AS geojson
            FROM smartgis_bidang
            WHERE nop = ?
            LIMIT 50
        ", [$nop]);

        if (empty($rows)) {
            return response()->json([
                'sukses' => false,
                'pesan'  => "NOP $nop tidak ditemukan di layer SmartGIS",
            ], 404);
        }

        // Ambil geometri bidang pertama sebagai GeoJSON siap-pakai Leaflet.
        // Frontend (modal "Peta Bidang") membaca res.geometry langsung.
        $geojson = $rows[0]->geojson ?? null;
        $geometry = null;
        if ($geojson) {
            $decoded = json_decode($geojson, true);
            if (is_array($decoded)) {
                $geometry = ['type' => 'Feature', 'properties' => [], 'geometry' => $decoded];
            }
        }

        return response()->json([
            'sukses'      => true,
            'nop'         => $nop,
            'jumlah'      => count($rows),
            'luas'        => $rows[0]->luas ?? null,
            'd_luas'      => $rows[0]->d_luas ?? null,
            'geometry'    => $geometry,
            'geometry_raw' => $geojson,
            'bidang'      => $rows,
        ]);
    }

    /**
     * Semua bidang dalam bounding box (untuk render peta).
     * GET /api/spatial/bidang-bbox?min_lat=&min_lng=&max_lat=&max_lng=&limit=500
     */
    public function bidangBbox(Request $request)
    {
        $data = $request->validate([
            'min_lat' => 'required|numeric',
            'min_lng' => 'required|numeric',
            'max_lat' => 'required|numeric',
            'max_lng' => 'required|numeric',
            'limit'   => 'nullable|integer|min:1|max:2000',
        ]);

        $limit = $data['limit'] ?? 500;

        // Buat polygon bbox: POLYGON((minlng minlat, maxlng minlat, maxlng maxlat, minlng maxlat, minlng minlat))
        $bbox = sprintf(
            'POLYGON((%1$s %2$s,%3$s %2$s,%3$s %4$s,%1$s %4$s,%1$s %2$s))',
            $data['min_lng'], $data['min_lat'], $data['max_lng'], $data['max_lat']
        );

        $rows = DB::connection($this->conn())->select("
            SELECT gid, nop, luas,
                   ST_AsGeoJSON(geometry) AS geojson
            FROM smartgis_bidang
            WHERE ST_Intersects(geometry, ST_GeomFromText(?, 4326))
            LIMIT ?
        ", [$bbox, $limit]);

        return response()->json([
            'sukses' => true,
            'bbox'   => $data,
            'jumlah' => count($rows),
            'bidang' => $rows,
        ]);
    }

    /**
     * Layer batas wilayah (GeoJSON FeatureCollection).
     * GET /api/spatial/wilayah?tingkat=kecamatan|kelurahan|blok|kota
     */
    public function wilayah(Request $request)
    {
        $tingkat = $request->query('tingkat', 'kecamatan');

        [$tabel, $kolomKode, $kolomNama] = match ($tingkat) {
            'kecamatan' => ['smartgis_kecamatan', 'kd_kec', 'nm_kec'],
            'kelurahan' => ['smartgis_kelurahan', 'kd_kel', 'nm_kel'],
            'blok'      => ['smartgis_blok', 'kd_blok', 'nm_blok'],
            'kota'      => ['smartgis_dati2', 'kd_dati2', 'nm_dati2'],
            default     => null,
        };

        if (!$tabel) {
            return response()->json([
                'sukses' => false,
                'pesan'  => "tingkat '$tingkat' tidak dikenal. Pilih: kecamatan, kelurahan, blok, kota",
            ], 422);
        }

        $rows = DB::connection($this->conn())->select("
            SELECT $kolomKode AS kode, $kolomNama AS nama, ST_AsGeoJSON(geometry) AS geojson
            FROM $tabel
        ");

        $features = [];
        foreach ($rows as $r) {
            $features[] = [
                'type'       => 'Feature',
                'properties' => ['kode' => $r->kode, 'nama' => $r->nama],
                'geometry'   => json_decode($r->geojson),
            ];
        }

        return response()->json([
            'type'     => 'FeatureCollection',
            'tingkat'  => $tingkat,
            'jumlah'   => count($features),
            'features' => $features,
        ]);
    }

    /**
     * Statistik layer spasial (angka NYATA dari DB, tanpa hardcode).
     * GET /api/spatial/stats
     */
    public function stats()
    {
        $conn = DB::connection($this->conn());

        $bidang = $conn->selectOne("
            SELECT COUNT(*) AS total_bidang,
                   COUNT(DISTINCT nop) AS nop_unik,
                   SUM(CASE WHEN luas > 0 THEN 1 ELSE 0 END) AS luas_terisi,
                   SUM(CASE WHEN luas = 0 THEN 1 ELSE 0 END) AS luas_nol,
                   ROUND(SUM(luas), 2) AS total_luas_m2,
                   ROUND(AVG(CASE WHEN luas > 0 THEN luas END), 2) AS rata_luas_m2
            FROM smartgis_bidang
        ");

        $perKec = $conn->select("
            SELECT LEFT(nop, 7) AS kode_kec,
                   COUNT(*) AS jumlah_bidang,
                   COUNT(DISTINCT nop) AS nop_unik
            FROM smartgis_bidang
            GROUP BY LEFT(nop, 7)
            ORDER BY jumlah_bidang DESC
        ");

        return response()->json([
            'sukses'    => true,
            'sumber'    => 'SmartGIS Bapenda Kota Baubau (impor 25 Sep 2026)',
            'ringkasan' => $bidang,
            'per_kecamatan' => $perKec,
            'layer'     => [
                'bidang'    => (int) $bidang->total_bidang,
                'kecamatan' => (int) $conn->selectOne("SELECT COUNT(*) AS n FROM smartgis_kecamatan")->n,
                'kelurahan' => (int) $conn->selectOne("SELECT COUNT(*) AS n FROM smartgis_kelurahan")->n,
                'blok'      => (int) $conn->selectOne("SELECT COUNT(*) AS n FROM smartgis_blok")->n,
            ],
        ]);
    }

    /**
     * Nama koneksi DB: pakai smartgis_test dulu (fase uji), bisa diubah via env.
     */
    /**
     * Daftar bidang tanah SmartGIS dengan pencarian & paginasi.
     * GET /api/smartgis/bidang?search=&kelurahan=&kecamatan=&page=1&per_page=25
     *
     * Dipakai panel "Data Bidang Tanah (SmartGIS)" di halaman Katalog Aset.
     * Sumber: 52.136 poligon bidang (37.481 NOP unik) hasil impor SmartGIS Bapenda.
     */
    public function daftarBidang(Request $request)
    {
        $search    = trim((string) $request->query('search', ''));
        $kelurahan = trim((string) $request->query('kelurahan', ''));
        $kecamatan = trim((string) $request->query('kecamatan', ''));
        $page      = max(1, (int) $request->query('page', 1));
        $perPage   = max(1, min(200, (int) $request->query('per_page', 25)));
        $offset    = ($page - 1) * $perPage;

        $where = [];
        $bind  = [];

        if ($search !== '') {
            $where[] = '(b.nop LIKE ? OR b.nm_kel LIKE ? OR b.nm_kec LIKE ?)';
            $like = '%' . $search . '%';
            $bind[] = $like; $bind[] = $like; $bind[] = $like;
        }
        if ($kelurahan !== '') { $where[] = 'b.nm_kel = ?'; $bind[] = $kelurahan; }
        if ($kecamatan !== '') { $where[] = 'b.nm_kec = ?'; $bind[] = $kecamatan; }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $conn     = DB::connection($this->conn());

        $total = (int) ($conn->selectOne(
            "SELECT COUNT(*) AS n FROM smartgis_bidang b $whereSql", $bind
        )->n ?? 0);

        $rows = $conn->select(
            "SELECT b.gid, b.nop, b.kd_kec, b.nm_kec, b.kd_kel, b.nm_kel,
                    b.luas, b.d_luas,
                    ST_AsGeoJSON(b.geometry) AS geojson
             FROM smartgis_bidang b
             $whereSql
             ORDER BY b.gid ASC
             LIMIT ? OFFSET ?",
            array_merge($bind, [$perPage, $offset])
        );

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'gid'      => (int) $r->gid,
                'nop'      => $r->nop,
                'kd_kec'   => $r->kd_kec,
                'nm_kec'   => $r->nm_kec,
                'kd_kel'   => $r->kd_kel,
                'nm_kel'   => $r->nm_kel,
                'luas'     => $r->luas !== null ? (float) $r->luas : null,
                'd_luas'   => $r->d_luas !== null ? (float) $r->d_luas : null,
                'geojson'  => $r->geojson,
                'punya_geometri' => !empty($r->geojson),
            ];
        }

        return response()->json([
            'sukses'    => true,
            'sumber'    => 'SmartGIS Bapenda Kota Baubau',
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
            'jumlah'    => count($data),
            'data'      => $data,
        ]);
    }

    private function conn(): string
    {
        return env('SMARTGIS_CONNECTION', 'smartgis');
    }
}

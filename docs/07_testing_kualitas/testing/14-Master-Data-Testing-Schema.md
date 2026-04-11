# Skema Pengujian & Mitigasi Error Komprehensif

Skema ini dirancang berdasarkan **kasus nyata** Error 500 pada Zone Creation di produksi, di mana akar masalahnya adalah **kode lokal belum ter-deploy ke VPS** sehingga validasi lama (`opd_id: required`) masih aktif.

---

## 1. Pelajaran dari Kasus Zone 500

| Aspek | Detail |
|:---|:---|
| **Gejala** | POST `/api/zones` → 500 Internal Server Error di produksi |
| **Akar Masalah** | Server produksi masih menjalankan validasi `'opd_id' => 'required'`, sementara frontend Super Admin tidak mengirim `opd_id` |
| **Kenapa Lokal OK** | Kode lokal sudah diubah ke `'nullable'` + auto-inference, tapi belum di-push/deploy |
| **Solusi** | `git push` → `git pull` di VPS → `php artisan migrate --force` → cache clear |

> [!CAUTION]
> **Golden Rule**: Setiap perubahan kode di lokal **WAJIB** diikuti dengan deployment ke produksi. Jangan hanya tes lokal!

---

## 2. Protokol Pengujian Produksi (Curl Test)

Setelah setiap deployment, jalankan test `curl` langsung ke API produksi untuk memastikan endpoint berfungsi:

```bash
# Test Zone Creation (Super Admin, tanpa opd_id)
curl -s -X POST https://api.sipanda.online/api/zones \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"retribution_type_id": 11, "name": "Test Zona", "geometry_type": "point"}' | python3 -m json.tool

# Test Klasifikasi Creation
curl -s -X POST https://api.sipanda.online/api/retribution-classifications \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"retribution_type_id": 11, "name": "Test Klas", "code": "TK-01"}' | python3 -m json.tool

# Test Rate Creation
curl -s -X POST https://api.sipanda.online/api/retribution-rates \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"retribution_type_id": 11, "retribution_classification_id": 1, "name": "Test Tarif", "amount": 5000}' | python3 -m json.tool
```

**Expected**: Status 201 Created. Jika 500 → kode belum ter-deploy.

---

## 3. Audit Kerentanan Per Controller

### ✅ Sudah Di-Harden (Try-Catch + Throwable)
| Controller | Cakupan Perbaikan | Status |
|:---|:---|:---|
| `ZoneController` | `opd_id` inference & validation | ✅ |
| `RetributionRateController` | Missing `$request` param fix | ✅ |
| `VerificationController` | Hardened `store` & `updateStatus` | ✅ |
| `PaymentController` | Hardened `store` | ✅ |
| `MonthlyReportController` | Hardened `store` & `validateReport` | ✅ |
| `PenaltyWaiverController` | Hardened `store` & `approve` | ✅ |

> [!TIP]
> Audit lengkap histori kesalahan sistem dapat dilihat di:
> 👉 **[error_history_and_mitigation_registry.md](file:///Users/pondokit/.gemini/antigravity/brain/0d142205-22d1-41cf-8ae4-e0cbb2929e29d/error_history_and_mitigation_registry.md)**

---

## 4. Pola Hardening Universal

Terapkan pola ini pada **setiap** controller `store()` dan `update()`:

```php
public function store(Request $request) {
    // 1. SANITASI: Ubah string kosong → null
    $input = $request->all();
    foreach (['opd_id', 'description', ...nullable_fields] as $f) {
        if (isset($input[$f]) && $input[$f] === '') $input[$f] = null;
    }
    $request->merge($input);

    // 2. VALIDASI: Gunakan nullable untuk field yang bisa di-infer
    $request->validate([
        'opd_id' => 'nullable|exists:opds,id',  // BUKAN required!
        // ...
    ]);

    // 3. TRY-CATCH: Tangkap SEMUA error (Throwable, bukan Exception)
    try {
        $data = $request->only([...]);

        // 4. INFERENCE: Auto-fill opd_id dari relasi
        if (empty($data['opd_id'])) {
            $type = RetributionType::find($data['retribution_type_id']);
            if ($type) $data['opd_id'] = $type->opd_id;
        }

        // 5. AUTO-GENERATE: Isi field wajib DB yang tidak perlu diisi user
        if (empty($data['code'])) {
            $data['code'] = 'AUTO-' . Str::random(8);
        }

        $model = Model::create($data);
        return response()->json($model, 201);

    } catch (\Throwable $e) {
        \Log::error('Creation Failed: ' . $e->getMessage(), [
            'request' => $request->all(),
            'trace'   => $e->getTraceAsString()
        ]);
        return response()->json([
            'message'      => 'Gagal: ' . $e->getMessage(),
            'error_detail' => $e->getMessage(),
        ], 500);
    }
}
```

---

## 5. Checklist Deployment Produksi

Setiap kali deploy ke VPS, jalankan checklist ini:

- [ ] `git push origin main` dari lokal
- [ ] SSH ke VPS: `cd /home/mpad/retribusi-api`
- [ ] `git pull origin main`
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan migrate --force`
- [ ] `php artisan config:cache && php artisan route:cache`
- [ ] **Curl Test**: Jalankan semua curl test dari Bagian 2
- [ ] **Build Frontend**: `npm run build` → SCP `dist/` ke VPS (jika ada perubahan frontend)

---

## 6. Skenario Test Otomatis (Feature Test)

File: `tests/Feature/MasterDataHierarchyTest.php`

| Test Case | Apa yang Diuji | Expected |
|:---|:---|:---|
| `test_zone_creation_infers_opd_id` | Super Admin buat zona tanpa isi `opd_id` | 201, `opd_id` diisi otomatis |
| `test_zone_empty_strings_to_null` | Input `description: ""`, `latitude: ""` | 201, field jadi `null` di DB |
| `test_zone_validates_required` | Tanpa `name` dan `retribution_type_id` | 422, bukan 500 |
| `test_zone_allows_long_codes` | Update zona dengan kode > 10 char | 200, bukan 422 |
| `test_classification_infers_opd_id` | Super Admin buat klasifikasi tanpa `opd_id` | 201, `opd_id` diisi otomatis |

```bash
# Jalankan test otomatis
php artisan test tests/Feature/MasterDataHierarchyTest.php
```

---

## 7. Pola Kesalahan Umum & Mitigasi

| Pola Kesalahan | Contoh Kasus | Mitigasi |
|:---|:---|:---|
| **Kode belum deploy** | Lokal OK, prod 500 | Selalu `curl` test ke prod setelah deploy |
| **Validasi `required` untuk Super Admin** | `opd_id: required` padahal SA tidak punya OPD | Ubah ke `nullable` + inference dari relasi |
| **Kolom NOT NULL tanpa default** | `code varchar(10) NOT NULL` di DB | Auto-generate di backend + relaxkan max length |
| **`catch(\Exception)` tidak cukup** | PHP TypeError lolos | Gunakan `catch(\Throwable)` |
| **String kosong → SQL error** | `latitude: ""` gagal masuk kolom `decimal` | Sanitasi `""` → `null` sebelum validasi |
| **`$request` tidak ada** | `destroy()` pakai user() tapi tanpa param | Selalu tambahkan `Request $request` di signature |
| **Migration belum jalan** | Kolom baru ada di lokal, tidak di prod | Selalu `php artisan migrate --force` setelah pull |

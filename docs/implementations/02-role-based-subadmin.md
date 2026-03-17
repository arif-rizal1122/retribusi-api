# Panduan Implementasi: Hak Akses Role-Based Sub-Admin

**Konsep Dasar:** Sistem isolasi data (*multi-tenant architecture*) berbasi **Tipe Retribusi** untuk level Kepala Sub Bidang / Admin Sektoral. Tujuannya adalah memastikan admin wilayah 1 hanya memproses data Wilayah 1, dan tidak membahayakan kerahasiaan data pembayaran tipe pajak zona lain.

---

## 🏗️ 1. Pembaruan Skema Database (Backend)
1. Buat **Migration** baru: `php artisan make:migration add_retribution_type_id_to_users_table`.
2. Modifikasi struktur:
   ```php
   Schema::table('users', function (Blueprint $table) {
       $table->foreignId('retribution_type_id')->nullable()->constrained()->onDelete('set null')->after('role');
   });
   ```
3. Update Model `app/Models/User.php`:
   ```php
   public function retributionType() {
       return $this->belongsTo(RetributionType::class);
   }
   ```

---

## 🛡️ 2. Implementasi Global Scope (Isolasi Horizontal)
Kita tidak boleh mengandalkan pengecekan `if(admin_wilayah_1)` satu per satu di setiap Controller karena sangat rentan jebol. Kita akan menggunakan fitur **Eloquent Global Scope** Laravel.

Buat sebuah direktori/kelas `app/Models/Scopes/RetributionTypeScope.php`:
```php
class RetributionTypeScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Hanya memproses request jika ada user yang terautentikasi dan memiliki role admin terbatas (punya retribution_type_id)
        if (auth()->check() && auth()->user()->role === 'admin' && auth()->user()->retribution_type_id) {
            
            // Logika spesifik berdasarkan Model yang dicari
            $typeId = auth()->user()->retribution_type_id;

            if ($model instanceof Taxpayer) {
                // Skema 1: Wajib pajak difilter jika mereka punya relasi "objects" di tipe pajak tersebut
                $builder->whereHas('taxObjects', function($q) use ($typeId) {
                    $q->where('retribution_type_id', $typeId);
                });
            } 
            elseif ($model instanceof Bill || $model instanceof Payment || $model instanceof TaxObject) {
                // Skema 2: Tagihan dan Objek secara langsung difilter berdasarkan type_id
                $builder->where('retribution_type_id', $typeId);
            }
            elseif ($model instanceof User) {
                // Skema 3: Petugas difilter berdasarkan area kerjanya
                $builder->where('role', 'petugas')->where('retribution_type_id', $typeId);
            }
        }
    }
}
```

### Penerapan Scope ke Model Utama
Buka file `Taxpayer.php`, `Bill.php`, `TaxObject.php`, `Payment.php`, dan `User.php`. Di masing-masing file, daftarkan scope tersebut pada metode `booted`:
```php
protected static function booted()
{
    static::addGlobalScope(new \App\Models\Scopes\RetributionTypeScope);
}
```

---

## 🖥️ 3. Perubahan UI (Frontend `retribusi-admin`)
1. **Dropdown Petugas/Objek/Taxpayer:** Karena Backend sudah menerapkan *Global Scope*, panggilan `GET /api/tax-objects` atau `GET /api/users?role=petugas` oleh Admin berbatas akan *secara otomatis* hanya mengembalikan list orang/objek di divisinya saja. Tidak perlu banyak manipulasi kode di Frontend React-nya.
2. **Dashboard Analytics (`DashboardController.php`):**
   Metriks seperti *Total Pendapatan (Revenue)*, *Total Tunggakan*, harus dibersihkan khusus query `retribution_type_id` admin yang bersangkutan (jika dia admin berbatas).
   *(Contoh: Chart Bar PBB vs Retribusi Parkir tidak perlu dimunculkan karena ia hanya memegang salah satunya).*

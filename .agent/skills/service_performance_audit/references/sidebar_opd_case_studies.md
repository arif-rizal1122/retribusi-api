# Panduan Teknis & Best Practice Penyusunan Sidebar Multi-OPD M-PAD Kota Baubau
**Segregation of Duties (SoD) & Role-Tailored Interface Architecture**

---

## 1. Latar Belakang & Filosofi
Pemerintah Kota Baubau mengelola 19 Organisasi Perangkat Daerah (OPD) dengan fungsi yang berbeda-beda:
1. **Bapenda**: Pemegang wewenang fiskal makro, 9 jenis pajak PBJT, PBB-P2, BPHTB, Reklame, dan rekonsiliasi kasda.
2. **OPD Penghasil Retribusi Teknis (Dishub, Diskan, Disperindag, DLH, PUPR, Perkim, dll.)**: Mengelola perizinan tertentu, karcis/jasa umum, pemanfaatan aset/kios, serta sarana pelayanan dinas.
3. **OPD Penegak & Pendukung (Satpol PP, Kominfo, Dukcapil, dll.)**: Mengawal penertiban, validasi kependudukan, dan infrastruktur komunikasi.

Prinsip utama penyusunan sidebar admin:
> **"Setiap OPD teknis HANYA memiliki menu yang relevan dengan tugas dan layanannya (Perda 1/2024). Dilarang keras menampilkan menu perpajakan daerah Bapenda pada akun dinas teknis."**

---

## 2. Struktur 7 Rumpun Baku Sidebar OPD
Setiap sidebar admin dinas teknis diorganisasikan ke dalam maksimal 7 rumpun logis:

```
┌──────────────────────────────────────────────────────────────┐
│ [1] UTAMA                                                    │
│     └─ Dashboard (Command Center Dinas)                      │
│ [2] LAYANAN INTI 1 (Retribusi Jasa Umum Utama)               │
│ [3] LAYANAN INTI 2 / ASET (Jasa Usaha / Sewa Kios / Sarana) │
│ [4] LAYANAN PENDUKUNG / KHUSUS (Dermaga, BBI, Tera, dll.)    │
│ [5] PENGAWASAN (Uji Petik, Patroli Lapangan, EWS)            │
│ [6] DATA & LAPORAN (Wajib Retribusi, SKRD, Realisasi PAD)    │
│ [7] BANTUAN (Panduan SOP, Unduh Aplikasi Mobile, Tentang)    │
└──────────────────────────────────────────────────────────────┘
```

---

## 3. Matriks Komparasi Studi Kasus: Dishub vs Diskan

```
┌─────────────────────────┬───────────────────────────────┬───────────────────────────────┐
│ RUMPUN                  │ DISHUB (OPD ID 2)             │ DINAS PERIKANAN (OPD ID 10/12)│
├─────────────────────────┼───────────────────────────────┼───────────────────────────────┤
│ 1. Utama                │ • Dashboard                   │ • Dashboard                   │
│ 2. Layanan Inti 1       │ Layanan Parkir:               │ Layanan Pelelangan TPI:       │
│                         │ • Titik Parkir                │ • Lelang Harian Ikan          │
│                         │ • Juru Parkir                 │ • Kasir & Setoran Lelang      │
│                         │ • Peta Parkir                 │ • Armada & Objek Nelayan      │
│                         │ • Bagi Hasil 70:30            │                               │
│                         │ • Deteksi Kebocoran           │                               │
│ 3. Layanan Inti 2 / Aset│ Jembatan Batu:                │ Sarana & Aset TPI:            │
│                         │ • Kios Pelabuhan (15 Kios)    │ • Cold Storage & Kios Ikan    │
│                         │ • Penitipan Inap (TKP)        │                               │
│ 4. Layanan Khusus       │ Kepelabuhanan:                │ Balai Benih (BBI):            │
│                         │ • Dermaga Sandar              │ • Balai Benih Ikan (BBI)      │
│                         │ • Tarif Sandar Kapal(Proxy GT)│                               │
│ 5. Pengawasan           │ Pengawasan:                   │ Pengawasan Perikanan:         │
│                         │ • Uji Petik                   │ • Sinergi Restoran            │
│                         │ • Patroli Lapangan            │ • Uji Petik TPI               │
│                         │                               │ • Petugas Lapangan            │
│ 6. Data & Laporan       │ Data & Laporan:               │ Data & Laporan:               │
│                         │ • Wajib Retribusi             │ • Wajib Retribusi Nelayan     │
│                         │ • Ketetapan e-SKRD            │ • Ketetapan e-SKRD            │
│                         │ • Verifikasi Data             │ • Verifikasi Kapal & Dokumen  │
│                         │ • Laporan PAD                 │ • Laporan PAD TPI             │
│                         │ • Master Tarif                │ • Master Tarif                │
│ 7. Bantuan              │ Bantuan:                      │ Bantuan:                      │
│                         │ • Panduan SOP                 │ • Panduan SOP                 │
│                         │ • Unduh Aplikasi              │ • Unduh Aplikasi              │
│                         │ • Tentang                     │ • Tentang                     │
└─────────────────────────┴───────────────────────────────┴───────────────────────────────┘
```

---

## 4. Kaidah Ergonomi Redaksi (Copywriting Rules)
1. **Ringkas & Padat**: Gunakan 1 hingga 3 kata per label menu.
2. **Hindari Akronim & Tanda Kurung Berlebih**:
   - ❌ `Peta Sebaran Parkir (GIS)` $\rightarrow$ ✅ `Peta Parkir`
   - ❌ `Deteksi Kebocoran (EWS)` $\rightarrow$ ✅ `Deteksi Kebocoran`
   - ❌ `Tarif Sandar (Proxy GT)` $\rightarrow$ ✅ `Tarif Sandar Kapal`
   - ❌ `Verifikasi & Validasi Data` $\rightarrow$ ✅ `Verifikasi Data`
   - ❌ `Laporan Realisasi PAD` $\rightarrow$ ✅ `Laporan PAD`
   - ❌ `Master Tarif Perda Dishub` $\rightarrow$ ✅ `Master Tarif`
3. **Pemberian Identitas Khusus (Subject Scoping)**:
   - Jika label perlu mempertegas subjek dinas, gunakan kata benda yang akrab bagi pelaku usaha terkait:
     - Di Dishub: `Wajib Retribusi` (Penyewa kios, juru parkir, pemilik kapal)
     - Di Perikanan: `Wajib Retribusi Nelayan` atau `Kapal & Nelayan`
     - Di PTSP: `Calon Wajib Retribusi` atau `Warga & Status NIB`

---

## 5. Cuplikan Implementasi Kode

### A. Role Utils (`src/lib/roleUtils.ts`)
```typescript
export const isDishubUser = (user: User | null | undefined): boolean => {
  if (!user) return false;
  return (
    user.role === 'admin_dishub' ||
    user.role === 'dishub' ||
    user.role_code === 'perhubungan' ||
    (user as any).metadata?.role_code === 'perhubungan' ||
    user.opd?.code === 'DISHUB' ||
    user.opd?.code === 'DINAS_PERHUBUNGAN' ||
    (user.opd?.name || '').toLowerCase().includes('perhubungan') ||
    user.opd_id === 2 ||
    (user.email || '').toLowerCase().includes('dishub') ||
    (user.email || '').toLowerCase().includes('perhubungan')
  );
};
```

### B. Menu Guarding & Preservation (`src/components/Layout.tsx`)
```typescript
const filteredMenu: MenuItem[] = useMemo(() => {
  return rawMenu.map((item) => {
    // KUNCI: Pertahankan label kustom khusus Dishub dan Perikanan
    if (isDishub || isPerikanan) {
      return item;
    }
    if (item.path === '/taxpayers') {
      return { ...item, label: domainLabels.subjectPluralLabel };
    }
    return item;
  });
}, [rawMenu, domainLabels, isDishub, isPerikanan]);
```

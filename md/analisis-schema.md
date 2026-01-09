# Analisis Schema PMO vs Data_Part

## Ringkasan
Dokumen ini memisahkan komponen yang menggunakan schema `pmo` dan schema `data_part` dari sistem PMO lama.

---

## 1. SCHEMA PMO (Akan Dihilangkan)

### Tabel di Schema PMO:
1. **tb_temp_cart** - Cart header sementara
2. **tb_temp_cart_detail** - Detail item cart sementara
3. **SuggetedOrder** - Saran order berdasarkan history penjualan
4. **tb_part_flag** - Flag part (hotline, promo, dll)
5. **tbl_serial** - Counter untuk generate nomor SO
6. **tb_part_favorite** - Part favorit user
7. **kontak_sales** - Kontak sales
8. **tb_competitor** - Data kompetitor
9. **tb_role_sales** - Role sales
10. **tb_target_sales_pmo** - Target sales
11. **tbl_users** - User PMO
12. **tbl_user_api_tokens** - Token API user
13. **tbl_user_fcm_tokens** - Token FCM untuk notifikasi

### Fungsi Schema PMO:
- ✅ Menyimpan cart sementara sebelum submit
- ✅ Generate nomor SO
- ✅ Menyimpan preferensi user (favorite, suggested order)
- ✅ Master data tambahan (sales, target, competitor)
- ✅ Autentikasi & token management

---

## 2. SCHEMA DATA_PART (Tetap Digunakan)

### Tabel di Schema Data_Part:
1. **tblso / tblso_id** - Header Sales Order (WRITE)
2. **tblso_detail / tblso_detail_id** - Detail Sales Order (WRITE)
3. **tblstock_part_id** - Stock part per bulan/tahun (READ)
4. **tblpart_id** - Master part (READ)
5. **tbldo_id** - Delivery Order (READ)
6. **tbldo_detail_id** - Detail DO (READ)

### Fungsi Schema Data_Part:
- ✅ Menyimpan transaksi SO (Sales Order)
- ✅ Menyimpan detail item SO
- ✅ Sumber data stock part
- ✅ Master data part
- ✅ Data DO (hasil dari SO yang diapprove)

---

## 3. FLOW INTERAKSI SCHEMA

### Flow Lama (Dengan PMO):
```
User → PMO.tb_temp_cart → PMO.tbl_serial → DATA_PART.tblso
                                          → DATA_PART.tblso_detail
```

### Detail Interaksi:

**Step 1: Add to Cart**
- INPUT: User pilih part
- PROSES: 
  - Cek stock dari `data_part.tblstock_part_id` ✅
  - Simpan ke `pmo.tb_temp_cart` ❌
  - Simpan detail ke `pmo.tb_temp_cart_detail` ❌

**Step 2: View Cart**
- INPUT: User buka cart
- PROSES:
  - Ambil dari `pmo.tb_temp_cart_detail` ❌
  - Join dengan `data_part.tblpart_id` untuk info part ✅

**Step 3: Submit Order**
- INPUT: User submit cart
- PROSES:
  - Generate nomor SO dari `pmo.tbl_serial` ❌
  - Hitung jenis SO (Oli Regular/Other)
  - Insert ke `data_part.tblso` ✅
  - Insert detail ke `data_part.tblso_detail` ✅
  - Clear `pmo.tb_temp_cart` ❌

---

## 4. KOMPONEN YANG PERLU DIGANTI

### A. Cart Management (❌ PMO → ✅ Solusi Baru)
**Lama:**
- `pmo.tb_temp_cart`
- `pmo.tb_temp_cart_detail`

**Opsi Pengganti:**
1. Tabel baru di `pmov2.carts` dan `pmov2.cart_items`
2. Laravel session/cache
3. Direct insert ke `data_part.tblso` dengan status 'Draft'

**Rekomendasi:** Opsi 1 (tabel di pmov2)

---

### B. Serial Number Generator (❌ PMO → ✅ Solusi Baru)
**Lama:**
- `pmo.tbl_serial`
- Format: `TAHUN/NOMOR/POD-PD`
- Counter per tahun

**Opsi Pengganti:**
1. Tabel `pmov2.serials` dengan struktur serupa
2. PostgreSQL Sequence di `data_part`
3. Generate dari `MAX(no_so) + 1`

**Rekomendasi:** Opsi 1 (tabel di pmov2)

---

### C. Master Data Tambahan (❌ PMO → ⚠️ Optional)
**Lama:**
- `pmo.SuggetedOrder` - Saran order
- `pmo.tb_part_flag` - Flag part
- `pmo.tb_part_favorite` - Favorit user
- `pmo.tb_competitor` - Data kompetitor
- `pmo.tb_target_sales_pmo` - Target sales

**Keputusan:**
- Apakah fitur ini masih dibutuhkan di V2?
- Jika ya, pindah ke `pmov2` atau `public`
- Jika tidak, skip saja

---

## 5. YANG TETAP DARI DATA_PART

### Read Operations (Tidak Berubah):
```sql
-- Cek stock part
SELECT * FROM data_part.tblstock_part_id 
WHERE fk_part = ? AND bulan = ? AND tahun = ?

-- Get info part
SELECT * FROM data_part.tblpart_id 
WHERE id = ?

-- Get DO history
SELECT * FROM data_part.tbldo_id 
WHERE fk_toko = ?
```

### Write Operations (Tidak Berubah):
```sql
-- Insert SO Header
INSERT INTO data_part.tblso (
    no_so, jenis_so, tgl_so, fk_salesman, 
    fk_toko, grand_total, status_approve_reject
) VALUES (?, ?, ?, ?, ?, ?, 'Waiting For Approval')

-- Insert SO Detail
INSERT INTO data_part.tblso_detail (
    fk_so, fk_part, harga, qty_so, 
    total_harga, qty_sisa
) VALUES (?, ?, ?, ?, ?, ?)
```

---

## 6. KESIMPULAN

### Yang Hilang (Schema PMO):
- ❌ Cart temporary
- ❌ Serial number generator
- ❌ User preferences (favorite, suggested)
- ❌ Master data tambahan (competitor, target)

### Yang Tetap (Schema DATA_PART):
- ✅ Insert SO Header (`tblso`)
- ✅ Insert SO Detail (`tblso_detail`)
- ✅ Read stock (`tblstock_part_id`)
- ✅ Read part info (`tblpart_id`)
- ✅ Read DO (`tbldo_id`)

### Yang Perlu Dibuat Baru:
1. **Tabel Cart** di schema `pmov2`
2. **Tabel Serial** di schema `pmov2`
3. **Logic Submit Order** yang langsung ke `data_part`

---

## 7. REKOMENDASI ARSITEKTUR BARU

### Schema Strategy:
```
pmov2 (Primary)
├── carts                    → Pengganti pmo.tb_temp_cart
├── cart_items               → Pengganti pmo.tb_temp_cart_detail
├── serials                  → Pengganti pmo.tbl_serial
└── [optional] favorites     → Pengganti pmo.tb_part_favorite

data_part (Target - Existing)
├── tblso / tblso_id         → Target insert SO
├── tblso_detail             → Target insert SO detail
├── tblstock_part_id         → Source stock
└── tblpart_id               → Source part info

dms_clone (Config)
└── config_wa                → Config WhatsApp
```

### Flow Baru (Tanpa PMO):
```
User → PMOV2.carts → PMOV2.serials → DATA_PART.tblso
                                   → DATA_PART.tblso_detail
```

---

## 8. NEXT ACTION

### Prioritas 1 (Core):
1. Buat tabel `pmov2.carts` dan `pmov2.cart_items`
2. Buat tabel `pmov2.serials`
3. Implement Cart API (add, view, update, delete)
4. Implement Submit Order ke `data_part.tblso`

### Prioritas 2 (Enhancement):
1. WhatsApp notification
2. Order history
3. Stock validation real-time

### Prioritas 3 (Optional):
1. Suggested order
2. Part favorites
3. Sales target tracking

---

## Catatan Penting
- Semua operasi ke `data_part` harus explicit connection
- Cart di `pmov2` bersifat temporary, bisa auto-cleanup
- Serial number harus thread-safe (pakai database lock)
- Jangan ada dependency ke schema `pmo` lagi

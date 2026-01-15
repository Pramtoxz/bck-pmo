# Fitur Inti untuk Project Baru (Selain Schema PMO)

Berdasarkan analisis project lama, berikut adalah fitur-fitur inti yang perlu dipindahkan ke project baru:

---

## 1. CEK STOCK PART

### Lokasi File Lama:
- **Controller**: `app/Http/Controllers/Stock/StockController.php`
- **Model**: 
  - `app/Model/PMOStock/Mtblstock_part.php`
  - `app/Model/Part/StockPartMD.php`

### Fitur Utama:
- Cek ketersediaan stock part berdasarkan bulan dan tahun
- Filter berdasarkan:
  - Similarity (nama part)
  - Item group
  - Motor type
  - Available/Not Available
  - Promo (yes/no)
- Sorting berdasarkan part_number, description, available_part, promo
- Menampilkan:
  - Part number
  - Part description
  - HET (Harga Eceran Tertinggi)
  - Item group
  - Type motor
  - Hotline flag & max qty
  - Flag numbering
  - Kit flag
  - HGP acc TL
  - Dus
  - Is promo
  - Is love (favorite)
  - Available part (qty tersedia)

### Logika Perhitungan Stock:
```php
$available = ($qty_on_hand - $qty_booking) - $min_stok;
if($available >= 1){
    $ket = 'Available ' . $available . ' pcs';
}else{
    $ket = 'Not Available';
}
```

### Database Tables:
- `tblstock_part_id` (data_part schema)
- `tblpart_id` (public schema)
- `tblpart_detail_tipe_kendaraan_id` (public schema)
- `tb_part_flag` (pmo schema)
- `tbldetail_sub_kelompok_part_id` (public schema)

---

## 2. GENERATE NOMOR SO (Sales Order)

### Lokasi File Lama:
- **Controller**: `app/Http/Controllers/part_sarah/SubmitOrderController.php`
- **Model**: 
  - `app/Model/part_sarah/M_tbl_serial.php`
  - `app/Model/part_sarah/M_so_part.php`

### Fitur Utama:
Generate nomor SO dengan format: `TAHUN/NOMOR_URUT/TEKS`
Contoh: `2026/000001/POD-PD`

### Logika Generate Nomor SO:
```php
// 1. Ambil tahun sekarang
$tahun = date('Y');
$teks = 'POD-PD';

// 2. Ambil counter dari tabel serial dengan LOCK
$serial = M_tbl_serial::where('name', $teks)
                    ->lockForUpdate() 
                    ->first();

// 3. Increment counter
$newCounter = $serial->counter + 1;
$nomax = sprintf('%06d', $newCounter); // Format 6 digit dengan leading zero

// 4. Generate nomor SO
$kd_order = "$tahun/$nomax/$teks";
// Hasil: 2026/000001/POD-PD

// 5. Update counter di database
M_tbl_serial::where('name', $teks)
    ->update([
        'counter' => $newCounter, 
        'last_date' => date('Y-m-d H:i:s')
    ]);
```

### Database Tables:
- `tblserial` (public schema)
  - Kolom: `name`, `counter`, `last_date`
  - Data: name = 'POD-PD'

### PENTING:
- Gunakan `lockForUpdate()` untuk menghindari race condition
- Gunakan transaction (DB::beginTransaction() dan DB::commit())
- Counter harus di-increment setelah SO berhasil dibuat

---

## 3. JENIS ORDER (Penentuan Otomatis)

### Lokasi File Lama:
- **Controller**: `app/Http/Controllers/part_sarah/SubmitOrderController.php`

### Fitur Utama:
Menentukan jenis order secara otomatis berdasarkan komposisi part dalam cart:
- **"Oli Regular"**: Jika mayoritas part adalah OIL
- **"Other"**: Jika mayoritas part bukan OIL

### Logika Penentuan Jenis Order:
```php
// 1. Ambil data part dari cart
$data_part = M_temp_cart_detail::where('users_id', $user_id)
    ->join('public.tblpart', 'kd_part', 'part_number')
    ->get();

// 2. Hitung jumlah part OIL dan non-OIL
$count_part = $data_part->where('fk_detail_sub_kelompok_part', '!=', 'OIL')->count();
$count_oil = $data_part->where('fk_detail_sub_kelompok_part', 'OIL')->count();

// 3. Tentukan jenis order
if ($count_part < $count_oil) {
    // Lebih banyak OIL
    $jenis_order = 'Oli Regular';
    
} elseif ($count_part > $count_oil) {
    // Lebih banyak non-OIL
    $jenis_order = 'Other';
    
} elseif ($count_oil == $count_part) {
    // Jumlah sama, cek part pertama yang dimasukkan
    $data_part_awal = M_temp_cart_detail::where('users_id', $user_id)
        ->join('public.tblpart', 'kd_part', 'part_number')
        ->orderby('created_at', 'asc')
        ->first();
    
    $jenis_awal = $data_part_awal->fk_detail_sub_kelompok_part;
    if ($jenis_awal == 'OIL') {
        $jenis_order = 'Oli Regular';
    } else {
        $jenis_order = 'Other';
    }
}
```

### Jenis Order yang Tersedia:
1. **"Oli Regular"** - Order yang mayoritas berisi oli
2. **"Other"** - Order yang mayoritas berisi part selain oli

### Database Tables:
- `tb_temp_cart_detail` (pmo schema)
- `tblpart` (public schema)
  - Kolom penting: `fk_detail_sub_kelompok_part`

---

## 4. SUBMIT ORDER (Proses Lengkap)

### Lokasi File Lama:
- **Controller**: `app/Http/Controllers/part_sarah/SubmitOrderController.php`

### Flow Proses Submit Order:

```
1. Validasi cart user
   ↓
2. Tentukan jenis order (Oli Regular / Other)
   ↓
3. Generate nomor SO
   ↓
4. Insert data SO ke tblso
   ↓
5. Insert detail SO ke tblso_detail
   ↓
6. Clear cart user
   ↓
7. Update counter serial
   ↓
8. Insert log transaksi
   ↓
9. Kirim notifikasi WhatsApp (optional)
```

### Data yang Disimpan di SO:
```php
$insert = [
    'no_so' => $kd_order,              // Nomor SO yang di-generate
    'jenis_so' => $jenis_order,        // Oli Regular / Other
    'tgl_so' => date('Y-m-d H:m:i'),
    'jenis_pembayaran' => 'Cash',
    'fk_salesman' => $user_id,
    'tipe_source' => 'OTHER',
    'fk_toko' => $fk_toko,
    'tipe_penjualan' => 'Reguler',
    'tgl_jatuh_tempo' => $month_delivery,
    'grand_total' => $data_cart->total_price,
    'status_outstanding' => 't',
    'status_approve_reject' => 'Waiting For Approval',
];
```

### Database Tables:
- `tblso` (data_part schema) - Header SO
- `tblso_detail` (data_part schema) - Detail SO
- `tb_temp_cart` (pmo schema) - Cart header
- `tb_temp_cart_detail` (pmo schema) - Cart detail
- `tblserial` (public schema) - Counter nomor SO
- `tb_log_pmo` (pmo schema) - Log transaksi

---

## 5. FITUR PENDUKUNG LAINNYA

### A. Master Data Part
- **Model**: `app/Model/Part/MasterPart.php`
- Menyimpan data master part (kode, nama, HET, dll)

### B. Part Flag
- **Table**: `tb_part_flag` (pmo schema)
- Menyimpan flag khusus untuk part:
  - `hotline_flag` - Part hotline
  - `hotline_max_qty` - Max qty untuk hotline
  - `flag_numbering` - Part dengan numbering
  - `kit_flag` - Part kit
  - `hgp_acc_tl` - HGP accessories
  - `dus` - Jumlah dus
  - `is_promo` - Status promo

### C. Favorite Part
- User bisa menandai part sebagai favorite
- Flag `is_love` untuk menampilkan status favorite

---

## REKOMENDASI IMPLEMENTASI

### 1. Prioritas Tinggi (Harus Ada):
- ✅ Cek Stock Part
- ✅ Generate Nomor SO
- ✅ Penentuan Jenis Order
- ✅ Submit Order (full flow)

### 2. Prioritas Sedang:
- Master Data Part
- Part Flag
- Cart Management

### 3. Prioritas Rendah:
- Favorite Part
- Notifikasi WhatsApp
- Log transaksi

---

## CATATAN PENTING

### Database Connection:
Project lama menggunakan multiple database connections:
- `pgsql_meta_clone` - Database utama
- `public` schema - Master data
- `data_part` schema - Data transaksi part
- `pmo` schema - Data PMO

### Transaction Management:
Selalu gunakan transaction untuk proses submit order:
```php
DB::beginTransaction();
try {
    // Proses submit order
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Handle error
}
```

### Race Condition Prevention:
Gunakan `lockForUpdate()` saat generate nomor SO:
```php
$serial = M_tbl_serial::where('name', $teks)
                    ->lockForUpdate() 
                    ->first();
```

---

## STRUKTUR FILE YANG DISARANKAN UNTUK PROJECT BARU

```
app/
├── Http/
│   └── Controllers/Api
│       ├────────── Stock/
│       │           └── StockController.php
│       └────────── Order/
│                       └── OrderController.php
├── Models/
│   ├── Stock/
│   │   └── StockPart.php
│   ├── Order/
│   │   ├── SalesOrder.php
│   │   ├── SalesOrderDetail.php
│   │   └── Serial.php
│   └── Part/
│       └── Part.php
└── Services/
    ├── StockService.php
    └── OrderService.php
```

---

## ENDPOINT API YANG PERLU DIBUAT

### 1. Cek Stock
```
GET /api/stock/check
Parameters:
- similarity (optional)
- item_group (optional)
- motor_type (optional)
- shorting (optional)
```

### 2. Submit Order
```
POST /api/order/submit
Parameters:
- month_delivery (required)
Body: Data dari cart
```

### 3. Generate Nomor SO
```
POST /api/order/generate-number
Response: { "no_so": "2026/000001/POD-PD" }
```

---

Semoga dokumentasi ini membantu! 🚀

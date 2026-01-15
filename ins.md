# Logika Bisnis Inti (Konsep)

## 1. CEK STOCK PART

### Konsep:
Cek ketersediaan stock part dengan menghitung: **Stock Tersedia = (Stock di Gudang - Stock Booking) - Minimum Stock**

### Logika Available:
```php
$available = ($qty_on_hand - $qty_booking) - $min_stok;

if($available >= 1){
    return 'Available ' . $available . ' pcs';
} else {
    return 'Not Available';
}
```

### Data yang Dibutuhkan:
- **Stock Part**: qty_on_hand, qty_booking, bulan, tahun
- **Master Part**: kode_part, nama_part, min_stok, het (harga)
- **Part Flag**: hotline_flag, hotline_max_qty, flag_numbering, kit_flag, is_promo
- **Kategori Part**: item_group, detail_sub_kelompok
- **Tipe Motor**: relasi part dengan tipe kendaraan

### Filter:
- Pencarian nama part (LIKE)
- Filter by item_group
- Filter by motor_type
- Filter available/not available
- Filter promo yes/no
- Sorting (part_number, description, available)

---

## 2. GENERATE NOMOR SO

### Konsep:
Generate nomor urut unik dengan format: **TAHUN/NOMOR_URUT_6_DIGIT/KODE**

Contoh: `2026/000001/POD-PD`

### Logika:
```php
DB::beginTransaction();

// 1. Lock table serial untuk ambil counter (hindari duplicate)
$serial = [ambil dari table serial dengan LOCK]
    ->where('name', 'POD-PD')
    ->lockForUpdate()
    ->first();

// 2. Generate nomor
$tahun = date('Y');
$newCounter = $serial->counter + 1;
$nomax = sprintf('%06d', $newCounter);  // 6 digit: 000001
$no_so = "$tahun/$nomax/POD-PD";

// 3. Update counter
[update table serial]
    ->set('counter', $newCounter)
    ->set('last_date', now());

DB::commit();
```

### Data yang Dibutuhkan:
**Table Serial** dengan kolom:
- `name` = 'POD-PD' (identifier)
- `counter` = nomor urut terakhir (integer)
- `last_date` = tanggal terakhir generate

### PENTING:
- **Wajib pakai `lockForUpdate()`** untuk hindari race condition
- **Wajib pakai transaction**
- Counter di-update **setelah** SO berhasil dibuat

---

## 3. JENIS ORDER (Auto Detect)

### Konsep:
Tentukan jenis order otomatis berdasarkan komposisi part dalam cart:
- **"Oli Regular"** = mayoritas part adalah OIL
- **"Other"** = mayoritas part bukan OIL

### Logika:
```php
// 1. Ambil semua part di cart dengan join ke master part
$data_part = [ambil dari cart_detail]
    ->join([master_part], 'kode_part')
    ->get();

// 2. Hitung jumlah
$count_oil = $data_part->where('kategori_part', 'OIL')->count();
$count_non_oil = $data_part->where('kategori_part', '!=', 'OIL')->count();

// 3. Tentukan jenis
if ($count_non_oil < $count_oil) {
    $jenis_order = 'Oli Regular';
    
} elseif ($count_non_oil > $count_oil) {
    $jenis_order = 'Other';
    
} else {
    // Jumlah sama, cek part pertama yang masuk cart
    $first = $data_part->sortBy('created_at')->first();
    $jenis_order = ($first->kategori_part == 'OIL') 
        ? 'Oli Regular' 
        : 'Other';
}
```

### Data yang Dibutuhkan:
- **Cart Detail**: list part di cart user
- **Master Part**: kategori_part (untuk cek apakah OIL atau bukan)

### Hasil:
- `"Oli Regular"` 
- `"Other"`

---

## 4. SUBMIT ORDER - FLOW LENGKAP

### Konsep:
Proses lengkap dari cart user menjadi Sales Order (SO)

### Flow:
```
1. Ambil data cart user
   ↓
2. Tentukan jenis order (logika #3)
   ↓
3. Generate nomor SO (logika #2)
   ↓
4. Insert SO header
   ↓
5. Insert SO detail (loop semua item cart)
   ↓
6. Clear cart user
   ↓
7. Log transaksi (optional)
```

### Logika:
```php
DB::beginTransaction();
try {
    // 1. Ambil cart
    $cart = [ambil cart header user];
    $cart_detail = [ambil cart detail user];
    
    // 2. Tentukan jenis order
    $jenis_order = [logika #3];
    
    // 3. Generate nomor SO
    $no_so = [logika #2];
    
    // 4. Insert SO header
    [insert ke table SO header] = [
        'no_so' => $no_so,
        'jenis_so' => $jenis_order,
        'tgl_so' => now(),
        'jenis_pembayaran' => 'Cash',
        'fk_salesman' => $user_id,
        'tipe_source' => 'OTHER',
        'fk_toko' => $cart->id_toko,
        'tipe_penjualan' => 'Reguler',
        'tgl_jatuh_tempo' => $month_delivery,
        'grand_total' => $cart->total_price,
        'status_outstanding' => true,
        'status_approve_reject' => 'Waiting For Approval',
    ];
    
    // 5. Insert SO detail
    foreach($cart_detail as $item) {
        [insert ke table SO detail] = [
            'fk_so' => $no_so,
            'fk_part' => $item->part_number,
            'harga' => $item->het,
            'qty_so' => $item->qty,
            'total_harga' => $item->amount_total,
            'qty_sisa' => $item->qty,
        ];
    }
    
    // 6. Clear cart
    [update cart header] = [
        'total_price' => null,
        'sub_price' => null,
        'discount' => null
    ];
    [delete cart detail];
    
    // 7. Log (optional)
    [insert log] = [
        'no_pod' => $no_so,
        'kd_sales' => $user_id,
        'kd_toko' => $cart->id_toko,
        'tgl_transaksi' => now()
    ];
    
    DB::commit();
    return ['no_so' => $no_so];
    
} catch(\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

### Data yang Dibutuhkan:

**Table SO Header** dengan kolom:
- no_so (PK)
- jenis_so
- tgl_so
- jenis_pembayaran
- fk_salesman
- tipe_source
- fk_toko
- tipe_penjualan
- tgl_jatuh_tempo
- grand_total
- status_outstanding
- status_approve_reject

**Table SO Detail** dengan kolom:
- fk_so (FK ke SO header)
- fk_part
- harga
- qty_so
- total_harga
- qty_sisa

**Table Cart Header** dengan kolom:
- users_id
- id_toko
- total_price
- sub_price
- discount
- month_delivery

**Table Cart Detail** dengan kolom:
- users_id
- part_number
- het
- qty
- amount_total
- created_at

---

## RINGKASAN KONSEP INTI

### 1. Cek Stock
**Formula:** `(qty_on_hand - qty_booking) - min_stok >= 1` = Available

### 2. Generate Nomor SO
**Format:** `TAHUN/COUNTER_6_DIGIT/KODE`
**Penting:** Lock + Transaction

### 3. Jenis Order
**Logika:** Hitung part OIL vs non-OIL → mayoritas menentukan

### 4. Submit Order
**Flow:** Cart → Jenis Order → Generate SO → Insert SO → Clear Cart

---

## YANG HARUS DIINGAT

1. **Generate SO:** Wajib `lockForUpdate()` + transaction
2. **Jenis Order:** Cek kategori part apakah "OIL" atau bukan
3. **Available Stock:** `(qty_on_hand - qty_booking) - min_stok`
4. **Format Nomor SO:** `TAHUN/NOMOR_6_DIGIT/POD-PD`
5. **Status SO Default:** `'Waiting For Approval'`
6. **Transaction:** Semua proses submit order dalam 1 transaction



# Table dari Schema `data_part` yang Dibutuhkan

## FITUR INTI

### 1. CEK STOCK PART
**Table:** `data_part.tblstock_part_id`

**Kolom:**
- `fk_part` - Kode part (FK ke tblpart)
- `qty_on_hand` - Qty di gudang
- `qty_booking` - Qty yang sudah dibooking
- `bulan` - Bulan stock
- `tahun` - Tahun stock

**Relasi:**
- Join ke `public.tblpart_id` untuk data master part

---

### 2. GENERATE NOMOR SO
**Table:** `public.tblserial` (bukan data_part, tapi dipakai untuk SO)

**Kolom:**
- `name` = 'POD-PD'
- `counter` - Nomor urut terakhir
- `last_date` - Tanggal terakhir generate

---

### 3. SUBMIT ORDER - SO HEADER
**Table:** `data_part.tblso`

**Kolom:**
- `no_so` (PK) - Nomor SO
- `jenis_so` - Jenis order (Oli Regular / Other)
- `tgl_so` - Tanggal SO
- `jenis_pembayaran` - Jenis pembayaran (Cash)
- `fk_salesman` - Kode salesman
- `tipe_source` - Tipe source (OTHER)
- `fk_dealer` - FK dealer (nullable)
- `fk_no_wo` - FK work order (nullable)
- `fk_no_claim_c1_c2` - FK claim (nullable)
- `fk_toko` - FK toko
- `tipe_penjualan` - Tipe penjualan (Reguler)
- `tgl_jatuh_tempo` - Tanggal jatuh tempo
- `grand_total` - Total harga
- `status_outstanding` - Status outstanding (boolean)
- `status_approve_reject` - Status approval (Waiting For Approval)
- `alasan_approve_reject` - Alasan approve/reject (nullable)
- `approve_by` - Approved by (nullable)
- `tgl_approve` - Tanggal approve (nullable)
- `alasan_batal` - Alasan batal (nullable)
- `tgl_batal` - Tanggal batal (nullable)
- `periode_awal` - Periode awal (nullable)
- `periode_akhir` - Periode akhir (nullable)
- `fk_memo` - FK memo (nullable)

---

### 4. SUBMIT ORDER - SO DETAIL
**Table:** `data_part.tblso_detail` atau `data_part.tblso_detail_id`

**Kolom:**
- `fk_so` (FK) - Nomor SO
- `fk_part` - Kode part
- `harga` - Harga satuan
- `qty_so` - Quantity SO
- `total_harga` - Total harga (harga x qty)
- `qty_sisa` - Qty sisa (awalnya sama dengan qty_so)
- `fk_tipe` - FK tipe (nullable)

---

## TABLE TAMBAHAN (UNTUK FITUR LENGKAP)

### 5. DELIVERY ORDER (DO) - HEADER
**Table:** `data_part.tbldo_id` atau `data_part.tbldo`

**Dipakai untuk:** Tracking pengiriman setelah SO approved

**Kolom:**
- `no_do` (PK)
- `fk_so` (FK ke tblso)
- `tgl_do`
- `jenis_do`
- `grand_total`

---

### 6. DELIVERY ORDER (DO) - DETAIL
**Table:** `data_part.tbldo_detail_id` atau `data_part.tbldo_detail`

**Dipakai untuk:** Detail item yang dikirim

**Kolom:**
- `fk_do` (FK ke tbldo)
- `fk_part`
- `qty`
- `harga`

---

### 7. PICKING LIST - HEADER
**Table:** `data_part.tblpicking_list_part`

**Dipakai untuk:** Proses picking barang di gudang

**Kolom:**
- `no_picking_list_part` (PK)
- `fk_do` (FK ke tbldo)
- `tgl_picking`

---

### 8. PICKING LIST - DETAIL
**Table:** `data_part.tblpicking_list_part_detail`

**Dipakai untuk:** Detail item yang dipick

**Kolom:**
- `fk_picking_list_part` (FK)
- `fk_part`
- `qty`

---

### 9. RETUR PENJUALAN
**Table:** `data_part.tblretur_penjualan`

**Dipakai untuk:** Proses retur barang

---

## RINGKASAN TABLE INTI

Untuk fitur **CEK STOCK, GENERATE SO, JENIS ORDER, SUBMIT ORDER**, Anda hanya butuh:

1. ✅ `data_part.tblstock_part_id` - Stock part
2. ✅ `data_part.tblso` - SO header
3. ✅ `data_part.tblso_detail` atau `tblso_detail_id` - SO detail
4. ✅ `public.tblserial` - Generate nomor SO
5. ✅ `public.tblpart_id` - Master part (untuk join)

---

## CATATAN

- Table `tblso_detail` dan `tblso_detail_id` sepertinya sama, cuma beda nama
- Table `tbldo_detail` dan `tbldo_detail_id` juga sama
- Beberapa model pakai connection `pgsql_meta_clone`, beberapa pakai `pgsql_dms`
- Table DO, Picking List, Retur adalah **optional** (untuk fitur lanjutan setelah SO)


Schema public
Table yang dipakai:

public.tblpart_id atau public.tblpart

Master data part
Kolom: kd_part, nm_part, het, min_stok, fk_detail_sub_kelompok_part
Dipakai untuk: Join dengan stock, cek kategori OIL/non-OIL

public.tblserial
Generate nomor SO
Kolom: name, counter, last_date
Data: name = 'POD-PD'
public.tblpart_detail_tipe_kendaraan_id

Relasi part dengan tipe motor
Kolom: fk_part, fk_tipe_kendaraan, id_api
public.tbldetail_sub_kelompok_part_id

Kategori/item group part
Kolom: kd_detail_sub_kelompok_part, detail_sub_kelompok_part, id_api
public.tblkaryawan_id atau public.tblkaryawan

Master karyawan/salesman
Kolom: npk, nm_depan, karyawan_active
public.tbltipe_kendaraan_id

Master tipe kendaraan/motor
Kolom: kd_ptm, desc_tipe_cust
Jadi untuk fitur inti, Anda butuh 2 schema:

data_part → untuk transaksi (stock, SO, DO)
public → untuk master data (part, serial, karyawan, tipe motor)
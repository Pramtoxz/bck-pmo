# Fitur Inti untuk Project Baru (Selain Schema PMO)

## 1. CEK STOCK PART

### Query Utama:
```sql
SELECT 
    tblstock_part_id.fk_part,
    tblstock_part_id.qty_on_hand,
    tblstock_part_id.qty_booking,
    tblpart_id.kd_part,
    tblpart_id.nm_part,
    tblpart_id.min_stok,
    tblpart_id.het,
    tb_part_flag.hotline_flag,
    tb_part_flag.hotline_max_qty,
    tb_part_flag.flag_numbering,
    tb_part_flag.kit_flag,
    tb_part_flag.is_promo,
    tbldetail_sub_kelompok_part_id.detail_sub_kelompok_part
FROM tblstock_part_id
LEFT JOIN tblpart_id ON tblstock_part_id.fk_part = tblpart_id.kd_part
LEFT JOIN tblpart_detail_tipe_kendaraan_id ON tblpart_id.kd_part = tblpart_detail_tipe_kendaraan_id.fk_part
LEFT JOIN pmo.tb_part_flag ON tblstock_part_id.fk_part = tb_part_flag.fk_no_part
JOIN tbldetail_sub_kelompok_part_id ON tblpart_id.fk_detail_sub_kelompok_part = tbldetail_sub_kelompok_part_id.kd_detail_sub_kelompok_part
WHERE bulan = [bulan_sekarang]
  AND tahun = [tahun_sekarang]
```

### Logika Available:
```php
$available = ($qty_on_hand - $qty_booking) - $min_stok;
if($available >= 1){
    return 'Available ' . $available . ' pcs';
} else {
    return 'Not Available';
}
```

### Filter yang Perlu Diimplementasi:
- `similarity` → WHERE nm_part LIKE '%keyword%'
- `item_group` → WHERE id_api = item_group
- `motor_type` → WHERE fk_tipe_kendaraan = motor_type
- `shorting` → ORDER BY (part_number/description/available_part)

---

## 2. GENERATE NOMOR SO

### Logika:
```php
DB::beginTransaction();

// 1. Lock dan ambil counter
$serial = DB::table('tblserial')
    ->where('name', 'POD-PD')
    ->lockForUpdate()
    ->first();

// 2. Generate nomor
$tahun = date('Y');
$newCounter = $serial->counter + 1;
$nomax = sprintf('%06d', $newCounter);
$no_so = "$tahun/$nomax/POD-PD";  // Contoh: 2026/000001/POD-PD

// 3. Update counter
DB::table('tblserial')
    ->where('name', 'POD-PD')
    ->update([
        'counter' => $newCounter,
        'last_date' => now()
    ]);

DB::commit();
```

### Table: `public.tblserial`
- `name` = 'POD-PD'
- `counter` = nomor urut terakhir
- `last_date` = tanggal terakhir generate

**PENTING:** Harus pakai `lockForUpdate()` dan transaction!

---

## 3. JENIS ORDER

### Logika:
```php
// Ambil data part dari cart
$data_part = DB::table('pmo.tb_temp_cart_detail')
    ->where('users_id', $user_id)
    ->join('public.tblpart', 'kd_part', 'part_number')
    ->get();

// Hitung
$count_oil = $data_part->where('fk_detail_sub_kelompok_part', 'OIL')->count();
$count_part = $data_part->where('fk_detail_sub_kelompok_part', '!=', 'OIL')->count();

// Tentukan jenis
if ($count_part < $count_oil) {
    $jenis_order = 'Oli Regular';
} elseif ($count_part > $count_oil) {
    $jenis_order = 'Other';
} else {
    // Sama, cek part pertama
    $first = $data_part->sortBy('created_at')->first();
    $jenis_order = ($first->fk_detail_sub_kelompok_part == 'OIL') 
        ? 'Oli Regular' 
        : 'Other';
}
```

**Hasil:** `"Oli Regular"` atau `"Other"`

---

## 4. SUBMIT ORDER - FLOW LENGKAP

```php
DB::beginTransaction();
try {
    // 1. Ambil data cart
    $cart = DB::table('pmo.tb_temp_cart')
        ->where('users_id', $user_id)
        ->first();
    
    $cart_detail = DB::table('pmo.tb_temp_cart_detail')
        ->where('users_id', $user_id)
        ->get();
    
    // 2. Tentukan jenis order (lihat poin 3)
    $jenis_order = ... // logika di atas
    
    // 3. Generate nomor SO (lihat poin 2)
    $no_so = ... // logika di atas
    
    // 4. Insert SO header
    DB::table('data_part.tblso')->insert([
        'no_so' => $no_so,
        'jenis_so' => $jenis_order,
        'tgl_so' => now(),
        'jenis_pembayaran' => 'Cash',
        'fk_salesman' => $user_id,
        'tipe_source' => 'OTHER',
        'fk_toko' => $cart->id_dealer_ahass,
        'tipe_penjualan' => 'Reguler',
        'tgl_jatuh_tempo' => $month_delivery,
        'grand_total' => $cart->total_price,
        'status_outstanding' => 't',
        'status_approve_reject' => 'Waiting For Approval',
    ]);
    
    // 5. Insert SO detail
    foreach($cart_detail as $item) {
        DB::table('data_part.tblso_detail')->insert([
            'fk_so' => $no_so,
            'fk_part' => $item->part_number,
            'harga' => $item->het,
            'qty_so' => $item->qty,
            'total_harga' => $item->amount_total,
            'qty_sisa' => $item->qty,
        ]);
    }
    
    // 6. Clear cart
    DB::table('pmo.tb_temp_cart')
        ->where('users_id', $user_id)
        ->update([
            'total_price' => null,
            'sub_price' => null,
            'discount' => null
        ]);
    
    DB::table('pmo.tb_temp_cart_detail')
        ->where('users_id', $user_id)
        ->delete();
    
    // 7. Log (optional)
    DB::table('pmo.tb_log_pmo')->insert([
        'no_pod' => $no_so,
        'kd_sales' => $user_id,
        'kd_toko' => $cart->id_dealer_ahass,
        'tgl_transaksi' => now()
    ]);
    
    DB::commit();
    return ['no_so' => $no_so];
    
} catch(\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

---

## DATABASE TABLES YANG DIPAKAI

### Cek Stock:
- `tblstock_part_id` (data_part)
- `tblpart_id` (public)
- `tblpart_detail_tipe_kendaraan_id` (public)
- `pmo.tb_part_flag`
- `tbldetail_sub_kelompok_part_id` (public)

### Generate SO:
- `public.tblserial`

### Jenis Order:
- `pmo.tb_temp_cart_detail`
- `public.tblpart`

### Submit Order:
- `data_part.tblso` (header)
- `data_part.tblso_detail` (detail)
- `pmo.tb_temp_cart`
- `pmo.tb_temp_cart_detail`
- `pmo.tb_log_pmo` (optional)

---

## YANG HARUS DIINGAT

1. **Generate SO:** Wajib pakai `lockForUpdate()` + transaction
2. **Jenis Order:** Cek kolom `fk_detail_sub_kelompok_part` di tblpart
3. **Available Stock:** `(qty_on_hand - qty_booking) - min_stok`
4. **Format Nomor SO:** `TAHUN/NOMOR_6_DIGIT/POD-PD`
5. **Status SO:** Default = `'Waiting For Approval'`

# PMO V2 API Documentation - FINAL

## Base URL
```
http://192.168.40.42:8000/api
```

## Authentication
```
Email: jaya@motor.com
Password: password123
```

---

## API Endpoints

### 1. Authentication

#### Login
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "jaya@motor.com",
  "password": "password123"
}

Response:
{
  "success": true,
  "data": {
    "token": "1|xxxxx",
    "user": {
      "id": "1",
      "name": "Toko Jaya Motor",
      "email": "jaya@motor.com",
      "role": "dealer",
      "dealerCode": "jaya@motor.com",
      "dealerName": "Toko Jaya Motor"
    }
  }
}
```

#### Get Profile
```
GET /api/auth/profile
Authorization: Bearer {token}
```

#### Logout
```
POST /api/auth/logout
Authorization: Bearer {token}
```

---

### 2. Parts

#### Get Parts List
```
GET /api/parts?search=oli&category=OIL&limit=20&page=1
Authorization: Bearer {token}

Query Parameters:
- search: string (optional) - Search by part number or name
- category: string (optional) - Filter by fk_detail_sub_kelompok_part
- limit: integer (optional, default: 20) - Items per page
- page: integer (optional, default: 1) - Page number
- sortBy: string (optional, default: nm_part) - Sort field
- order: string (optional, default: asc) - Sort order (asc/desc)

Response:
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "11200K0JN00",
        "image": "https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png",
        "partNumber": "11200K0JN00",
        "name": "Honda Genuine Oil 10W-30",
        "description": "Oli mesin original Honda untuk motor Honda",
        "price": 45000,
        "isReady": true
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 42974,
      "totalPages": 2149
    }
  }
}

Notes:
- Hanya menampilkan part dengan part_active = true dan het > 0
- Total parts aktif: 42,974 dari 56,476 total parts
- Image default jika tidak ada foto: https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png
- Name prioritas: part_images.name > public.tblpart_id.nm_part > "-"
- Description prioritas: part_images.description > name > "-"
```

#### Get Part Detail
```
GET /api/parts/{partNumber}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "id": "11200K0JN00",
    "image": "https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png",
    "partNumber": "11200K0JN00",
    "name": "Honda Genuine Oil 10W-30",
    "description": "Oli mesin original Honda untuk motor Honda",
    "price": 45000,
    "isReady": true,
    "stock": 70,
    "category": "OIL"
  }
}
```

#### Check Stock (Real-time dari AHM)
```
GET /api/parts/{partNumber}/stock?bulan=1&tahun=2026
Authorization: Bearer {token}

Query Parameters:
- bulan: integer (optional, default: current month) - Bulan (1-12)
- tahun: integer (optional, default: current year) - Tahun

Response:
{
  "success": true,
  "data": {
    "available": true,
    "message": "Available 70 pcs",
    "qty": 70,
    "qty_on_hand": 100,
    "qty_booking": 20,
    "min_stock": 10
  }
}

Formula Stock Available:
available = (qty_on_hand - qty_booking) - min_stock
isReady = available >= 1

Contoh:
- qty_on_hand: 100 (stock fisik di gudang)
- qty_booking: 20 (sudah di-booking order lain)
- min_stock: 10 (safety stock, tidak boleh dijual)
- available: (100 - 20) - 10 = 70 pcs (yang bisa dijual)
```

---

### 3. Cart

#### Get Cart
```
GET /api/cart
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "1",
        "partId": "11200K0JN00",
        "partNumber": "11200K0JN00",
        "name": "Honda Genuine Oil 10W-30",
        "image": "https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png",
        "price": 45000,
        "quantity": 2,
        "subtotal": 90000,
        "isReady": true
      }
    ],
    "summary": {
      "totalItems": 2,
      "totalPrice": 90000
    }
  }
}
```

#### Add to Cart
```
POST /api/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "partNumber": "11200K0JN00",
  "quantity": 2
}

Response:
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "cartItemId": "1",
    "totalItems": 2
  }
}
```

#### Update Cart Item
```
PUT /api/cart/{itemId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 5
}
```

#### Remove from Cart
```
DELETE /api/cart/{itemId}
Authorization: Bearer {token}
```

#### Clear Cart
```
DELETE /api/cart/clear
Authorization: Bearer {token}
```

#### Checkout (Submit Order)
```
POST /api/cart/checkout
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Order submitted successfully",
  "data": {
    "no_so": "2026/000001/POD-PD",
    "jenis_so": "Oli Regular",
    "grand_total": 90000,
    "status": "Waiting For Approval"
  }
}
```

**Logic Jenis Order:**
1. Loop semua items di cart
2. Ambil `fk_detail_sub_kelompok_part` dari `public.tblpart_id`
3. Hitung: countOil (jika = 'OIL') vs countPart (jika != 'OIL')
4. Tentukan jenis:
   - countOil > countPart → "Oli Regular"
   - countPart > countOil → "Other"
   - countOil = countPart → cek item pertama di cart

**Data disimpan ke:**
- `data_part.tblso` (SO header)
- `data_part.tblso_detail` (SO detail per item)
- Nomor SO generate dari `public.tblserial` dengan `lockForUpdate()`

---

### 4. Orders

#### Get Order History
```
GET /api/orders
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "2026/000001/POD-PD",
        "orderNumber": "2026/000001/POD-PD",
        "orderType": "Oli Regular",
        "orderDate": "2026-01-15 10:30:00",
        "grandTotal": 90000,
        "status": "Waiting For Approval"
      }
    ]
  }
}
```

#### Get Order Detail
```
GET /api/orders/{noSo}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "orderNumber": "2026/000001/POD-PD",
    "orderType": "Oli Regular",
    "orderDate": "2026-01-15 10:30:00",
    "grandTotal": 90000,
    "status": "Waiting For Approval",
    "items": [
      {
        "partNumber": "11200K0JN00",
        "partName": "Honda Genuine Oil 10W-30",
        "qty": 2,
        "price": 45000,
        "subtotal": 90000
      }
    ]
  }
}
```

---

### 5. Dashboard

#### Get Dashboard Stats
```
GET /api/dashboard/stats
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "deliveryProgress": "0%",
    "monthlyBuyIn": "Rp 0",
    "cartCount": 3
  }
}
```

---

### 6. Campaigns

#### Get Campaigns
```
GET /api/campaigns
Authorization: Bearer {token}
```

#### Get Campaign Detail
```
GET /api/campaigns/{id}
Authorization: Bearer {token}
```

#### Get My Achievement
```
GET /api/campaigns/my-achievement
Authorization: Bearer {token}
```

---

### 7. Notifications

#### Get Notifications
```
GET /api/notifications?page=1&limit=20
Authorization: Bearer {token}
```

#### Mark as Read
```
PUT /api/notifications/{id}/read
Authorization: Bearer {token}
```

---

## Database Schema

### PMO V2 (Internal - Schema: pmov2)
- `part_images` - Foto dan metadata part (part_number PK, name, description, image)
- `carts` - Cart user (user_id FK)
- `cart_items` - Detail cart (part_number FK)
- `campaigns` - Campaign data
- `notifications` - Notifikasi
- `shops` - Data toko (relasi ke users)

### AHM Data (Read/Write)

**Schema: public**
- `tblpart_id` - Master part AHM
  - kd_part (PK)
  - nm_part (nama part)
  - het (harga)
  - min_stok (minimum stock)
  - fk_detail_sub_kelompok_part (kategori: OIL, BRAKE, dll)
  - part_active (boolean)
  
- `tblserial` - Generate nomor SO
  - name: 'POD-PD'
  - counter: auto increment
  - Format: TAHUN/NOMOR/POD-PD

- `tbldetail_sub_kelompok_part_id` - Kategori part

**Schema: data_part**
- `tblstock_part_id` - Stock part real-time per bulan
  - fk_part (FK to tblpart_id.kd_part)
  - qty_on_hand (stock fisik)
  - qty_booking (stock di-booking)
  - bulan, tahun
  
- `tblso` - Sales Order header
  - no_so (PK)
  - jenis_so ('Oli Regular' / 'Other')
  - tgl_so
  - fk_salesman (user_id)
  - fk_toko (shop_id)
  - grand_total
  - status_approve_reject
  
- `tblso_detail` - Sales Order detail
  - fk_so (FK to tblso.no_so)
  - fk_part (FK to tblpart_id.kd_part)
  - qty_so
  - harga
  - total_harga

---

## Flow Lengkap

### Shopping Flow
```
1. Login → Get token
2. Browse parts → GET /api/parts (filter: part_active=true, het>0)
3. Check stock → GET /api/parts/{partNumber}/stock
4. Add to cart → POST /api/cart/add (pakai partNumber)
5. View cart → GET /api/cart
6. Checkout → POST /api/cart/checkout
7. View orders → GET /api/orders
```

### Submit Order Flow (Backend)
```
1. Ambil cart user dari pmov2.carts (with items.part)
2. Loop items, ambil fk_detail_sub_kelompok_part dari public.tblpart_id
3. Hitung mayoritas OIL vs non-OIL
4. Tentukan jenis order
5. Generate nomor SO dari public.tblserial (dengan lockForUpdate)
6. Insert ke data_part.tblso (header)
7. Insert ke data_part.tblso_detail (items dengan part_number)
8. Clear cart user
9. Return nomor SO
```

---

## Important Notes

1. **Stock Check**: Real-time dari `data_part.tblstock_part_id` per bulan/tahun
2. **Stock Formula**: `(qty_on_hand - qty_booking) - min_stok`
3. **isReady**: `true` jika available >= 1
4. **Jenis Order**: Auto detect dari `fk_detail_sub_kelompok_part == 'OIL'`
5. **Nomor SO**: Format `TAHUN/NOMOR/POD-PD` dengan lock
6. **Status Default**: `Waiting For Approval`
7. **Connection**: Semua pakai `pgsql` ke `menara_agung_live`
8. **Part Filter**: Hanya tampilkan `part_active = true` dan `het > 0`
9. **Image Default**: https://ik.imagekit.io/zlt25mb52fx/ahmcdn/uploads/hgp/thumbnail/fungsi-cairan-pendingin-coolant-image.png
10. **Cart**: Pakai `part_number` bukan `product_id`

---

## Error Handling

```json
{
  "success": false,
  "error": {
    "code": 400,
    "message": "Error message here"
  }
}
```

---

## Test Accounts

| Email | Password | Name |
|-------|----------|------|
| jaya@motor.com | password123 | Toko Jaya Motor |
| maju@motor.com | password123 | Toko Maju Motor |
| sejahtera@motor.com | password123 | Toko Sejahtera Motor |

---

## All Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/login | Login |
| GET | /api/auth/profile | Get profile |
| POST | /api/auth/logout | Logout |
| GET | /api/parts | List parts (active & het>0) |
| GET | /api/parts/{partNumber} | Part detail |
| GET | /api/parts/{partNumber}/stock | Check stock real-time |
| GET | /api/cart | Get cart |
| POST | /api/cart/add | Add to cart (partNumber) |
| PUT | /api/cart/{id} | Update cart |
| DELETE | /api/cart/{id} | Remove from cart |
| DELETE | /api/cart/clear | Clear cart |
| POST | /api/cart/checkout | Submit order to AHM |
| GET | /api/orders | Order history |
| GET | /api/orders/{noSo} | Order detail |
| GET | /api/dashboard/stats | Dashboard |
| GET | /api/campaigns | Campaigns |
| GET | /api/campaigns/{id} | Campaign detail |
| GET | /api/campaigns/my-achievement | My achievement |
| GET | /api/notifications | Notifications |
| PUT | /api/notifications/{id}/read | Mark as read |

**Total: 20 endpoints**

---

## Data Source Summary

| Data | Source | Notes |
|------|--------|-------|
| Part List | public.tblpart_id | Filter: part_active=true, het>0 |
| Part Name | public.tblpart_id.nm_part | Override: pmov2.part_images.name |
| Part Price | public.tblpart_id.het | Harga dari AHM |
| Part Image | pmov2.part_images.image | Default jika null |
| Part Stock | data_part.tblstock_part_id | Real-time per bulan |
| Order Type | public.tblpart_id.fk_detail_sub_kelompok_part | OIL vs non-OIL |
| SO Number | public.tblserial | Generate dengan lock |
| SO Data | data_part.tblso + tblso_detail | Write ke AHM |


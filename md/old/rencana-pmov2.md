# Rencana PMO V2 - E-Commerce B2B Part

## Konsep Utama
PMO V2 adalah platform e-commerce B2B untuk pemesanan part, mirip Tokopedia tapi khusus untuk toko-toko dealer. Data transaksi disimpan di `pmov2`, lalu submit ke `data_part` hanya untuk integrasi dengan sistem lama.

---

## 1. ARSITEKTUR SISTEM

### Konsep E-Commerce
```
┌─────────────────────────────────────────────────────┐
│                    PMO V2                           │
│              (E-Commerce Platform)                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Admin Panel (Web)          Toko Panel (Web/Mobile)│
│  - Email/Password Auth      - WhatsApp OTP Auth    │
│  - Kelola Produk            - Browse & Order       │
│  - Kelola Toko              - Cart Management      │
│  - Approval Order           - Order History        │
│  - Dashboard                - Session 1 Minggu     │
│                                                     │
└─────────────────────────────────────────────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │      Schema: pmov2            │
        │   (Database Utama)            │
        │                               │
        │  - users (admin)              │
        │  - shops (toko)               │
        │  - products (part)            │
        │  - carts                      │
        │  - orders (SO internal)       │
        │  - order_items                │
        │  - otps                       │
        │  - sessions                   │
        └───────────────────────────────┘
                        │
                        ▼ (Submit Order)
        ┌───────────────────────────────┐
        │    Schema: data_part          │
        │  (Integrasi Sistem Lama)      │
        │                               │
        │  - tblso (write)              │
        │  - tblso_detail (write)       │
        │  - tblstock_part_id (read)    │
        │  - tblpart_id (read)          │
        └───────────────────────────────┘
```

---

## 2. AUTENTIKASI & OTORISASI

### A. Admin (Web Panel)
**Metode:** Email & Password (Laravel Fortify)

**Flow Login:**
```
1. Admin buka /admin/login
2. Input email & password
3. Validasi credentials
4. Generate session (standard Laravel)
5. Redirect ke dashboard
```

**Fitur:**
- Login/Logout
- Remember Me (optional)
- Password Reset via Email
- Session timeout: 2 jam (default)

**Role:**
- Super Admin (full access)
- Admin (limited access)

---

### B. Toko (Web/Mobile)
**Metode:** WhatsApp OTP (Passwordless)

**Flow Login:**
```
1. Toko buka /login
2. Input nomor WhatsApp (atau kode toko)
3. System generate OTP 6 digit
4. Kirim OTP via WhatsApp
5. Toko input OTP
6. Validasi OTP
7. Generate session token
8. Session berlaku 1 minggu
```

**Detail Implementasi:**

**Step 1: Request OTP**
```
POST /api/auth/request-otp
Body: {
  "phone": "628123456789"  // atau
  "shop_code": "TK001"
}

Response: {
  "success": true,
  "message": "OTP telah dikirim ke WhatsApp",
  "expires_in": 300  // 5 menit
}
```

**Step 2: Verify OTP**
```
POST /api/auth/verify-otp
Body: {
  "phone": "628123456789",
  "otp": "123456"
}

Response: {
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "shop": {
    "id": 1,
    "code": "TK001",
    "name": "Toko Jaya Motor",
    "phone": "628123456789"
  },
  "expires_at": "2025-01-16 10:00:00"  // 1 minggu
}
```

**Session Management:**
- Token disimpan di `pmov2.sessions`
- Berlaku 7 hari (1 minggu)
- Auto-refresh jika masih aktif
- Bisa logout manual (revoke token)

**Anti-Spam OTP:**
- Rate limit: Max 3 request per 10 menit per nomor
- Cooldown: 1 menit antar request
- OTP expired: 5 menit
- Block sementara jika 5x salah OTP

---

## 3. DATABASE SCHEMA (pmov2)

### Tabel: users (Admin)
```sql
CREATE TABLE pmov2.users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin',  -- 'super_admin', 'admin'
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabel: shops (Toko)
```sql
CREATE TABLE pmov2.shops (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,  -- TK001, TK002
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,  -- 628123456789
    address TEXT,
    city VARCHAR(50),
    province VARCHAR(50),
    
    -- Referensi ke data_part (optional)
    ref_toko_id INTEGER,  -- FK ke data_part.tbltoko
    
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabel: otps
```sql
CREATE TABLE pmov2.otps (
    id SERIAL PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT false,
    attempts INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_otps_phone ON pmov2.otps(phone);
CREATE INDEX idx_otps_expires ON pmov2.otps(expires_at);
```

### Tabel: sessions
```sql
CREATE TABLE pmov2.sessions (
    id SERIAL PRIMARY KEY,
    shop_id INTEGER REFERENCES pmov2.shops(id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sessions_token ON pmov2.sessions(token);
CREATE INDEX idx_sessions_shop ON pmov2.sessions(shop_id);
```

### Tabel: products (Part)
```sql
CREATE TABLE pmov2.products (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,  -- Part number
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50),  -- 'OIL', 'SPARE_PART', etc
    price DECIMAL(15,2) NOT NULL,
    
    -- Referensi ke data_part
    ref_part_id INTEGER,  -- FK ke data_part.tblpart_id
    
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_products_code ON pmov2.products(code);
CREATE INDEX idx_products_category ON pmov2.products(category);
```

### Tabel: carts
```sql
CREATE TABLE pmov2.carts (
    id SERIAL PRIMARY KEY,
    shop_id INTEGER REFERENCES pmov2.shops(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'active',  -- 'active', 'checked_out'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(shop_id, status)  -- 1 toko hanya 1 active cart
);
```

### Tabel: cart_items
```sql
CREATE TABLE pmov2.cart_items (
    id SERIAL PRIMARY KEY,
    cart_id INTEGER REFERENCES pmov2.carts(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES pmov2.products(id),
    qty INTEGER NOT NULL CHECK (qty > 0),
    price DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabel: orders (SO Internal)
```sql
CREATE TABLE pmov2.orders (
    id SERIAL PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,  -- ORD-2025-000001
    shop_id INTEGER REFERENCES pmov2.shops(id),
    
    total_amount DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    grand_total DECIMAL(15,2) NOT NULL,
    
    status VARCHAR(30) DEFAULT 'pending',  
    -- 'pending', 'submitted_to_datapart', 'approved', 'rejected', 'cancelled'
    
    notes TEXT,
    
    -- Referensi ke data_part (setelah submit)
    ref_so_id INTEGER,  -- FK ke data_part.tblso
    ref_so_number VARCHAR(50),  -- 2025/000123/POD-PD
    
    submitted_at TIMESTAMP,
    approved_at TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_orders_shop ON pmov2.orders(shop_id);
CREATE INDEX idx_orders_status ON pmov2.orders(status);
```

### Tabel: order_items
```sql
CREATE TABLE pmov2.order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES pmov2.orders(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES pmov2.products(id),
    
    qty INTEGER NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    discount DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tabel: serials
```sql
CREATE TABLE pmov2.serials (
    id SERIAL PRIMARY KEY,
    type VARCHAR(20) NOT NULL,  -- 'order', 'so_datapart'
    year INTEGER NOT NULL,
    last_number INTEGER NOT NULL DEFAULT 0,
    prefix VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(type, year)
);
```

---

## 4. FLOW LENGKAP

### A. Flow Admin

**1. Login Admin**
```
Admin → /admin/login → Email/Password → Dashboard
```

**2. Kelola Toko**
```
Dashboard → Toko → List/Create/Edit/Delete Toko
- Tambah toko baru (code, name, phone)
- Edit info toko
- Aktif/Non-aktifkan toko
- 1 Toko 1 Salesman
```

**3. Kelola Produk**
```
Dashboard → Produk → List/Create/Edit/Delete Produk
- Tambah produk baru (code, name, price)
- Edit harga & info
- Aktif/Non-aktifkan produk
- Sync dari data_part.tblpart_id (optional)
```

**4. Crud Sales dan SPV**
```
- Tambah Sales
- Edit Sales & Hapus Sales
- field jabatan (sales atau spv)
```


**. Approval Order**
```
Dashboard → Orders → List Pending Orders
- Lihat detail order
- Approve → Submit ke data_part.tblso
- Reject → Update status, kirim notif
```

---

### B. Flow Toko (E-Commerce)

**1. Login via OTP**
```
Toko → /login → Input Phone/Code → Request OTP
     → WhatsApp OTP → Input OTP → Verify → Home
```

**2. Browse Produk**
```
Home → Browse Products
     → Search by name/code
     → Filter by category
     → View detail produk
     → Cek stock (dari data_part.tblstock_part_id)
```

**3. Add to Cart**
```
Product Detail → Pilih Qty → Add to Cart
               → Cart Badge Update
               → Continue Shopping / View Cart
```

**4. Manage Cart**
```
Cart → View Items
     → Update Qty
     → Remove Item
     → See Total
     → Checkout
```

**5. Checkout & Submit Order**
```
Cart → Checkout → Review Order
               → Confirm
               → Create Order di pmov2.orders
               → Status: 'pending'
               → Notif ke Admin (WhatsApp)
               → Redirect ke Order Detail
```

**6. Order History**
```
Profile → My Orders → List Orders
                   → View Detail
                   → Track Status
```

---

## 5. INTEGRASI KE DATA_PART

### Kapan Submit ke data_part?
**Trigger:** Admin approve order di admin panel

### Proses Submit:
```
1. Admin klik "Approve" di order detail
2. System generate SO number dari data_part format
   - Format: YYYY/NNNNNN/POD-PD
   - Ambil dari pmov2.serials
3. Hitung jenis_so (Oli Regular / Other)
4. Insert ke data_part.tblso:
   - no_so: "2025/000123/POD-PD"
   - jenis_so: "Oli Regular"
   - tgl_so: current_date
   - fk_salesman: (dari shop mapping)
   - fk_toko: shop.ref_toko_id
   - grand_total: order.grand_total
   - status_approve_reject: "Waiting For Approval"
5. Insert ke data_part.tblso_detail (loop items):
   - fk_so: so_id
   - fk_part: product.ref_part_id
   - harga: item.price
   - qty_so: item.qty
   - total_harga: item.subtotal
   - qty_sisa: item.qty
6. Update pmov2.orders:
   - status: "submitted_to_datapart"
   - ref_so_id: so_id
   - ref_so_number: "2025/000123/POD-PD"
   - submitted_at: now()
7. Kirim notif WhatsApp ke toko
```

### Sync Stock (Optional)
```
Cron Job / Scheduler:
- Setiap 1 jam sync stock dari data_part.tblstock_part_id
- Update cache stock di Redis/Database
- Untuk performa saat browse produk
```

---

## 6. API ENDPOINTS

### Auth API
```
POST   /api/auth/request-otp       - Request OTP ke WhatsApp
POST   /api/auth/verify-otp        - Verify OTP & login
POST   /api/auth/logout            - Logout (revoke token)
GET    /api/auth/me                - Get current shop info
```

### Product API (Toko)
```
GET    /api/products               - List produk (pagination, search, filter)
GET    /api/products/{id}          - Detail produk
GET    /api/products/{id}/stock    - Cek stock real-time
```

### Cart API (Toko)
```
GET    /api/cart                   - Get cart items
POST   /api/cart/add               - Add item to cart
PUT    /api/cart/item/{id}         - Update qty
DELETE /api/cart/item/{id}         - Remove item
DELETE /api/cart/clear              - Clear cart
POST   /api/cart/checkout          - Checkout (create order)
```

### Order API (Toko)
```
GET    /api/orders                 - Order history
GET    /api/orders/{id}            - Order detail
POST   /api/orders/{id}/cancel     - Cancel order (jika masih pending)
```

### Admin API
```
POST   /api/admin/login            - Admin login
GET    /api/admin/dashboard        - Dashboard stats
GET    /api/admin/orders           - List all orders
PUT    /api/admin/orders/{id}/approve   - Approve & submit ke data_part
PUT    /api/admin/orders/{id}/reject    - Reject order
```

---

## 7. KEAMANAN & PERFORMA

### Session Management
- **Admin:** Laravel session (2 jam)
- **Toko:** Custom token (7 hari)
- Token disimpan di database
- Auto-cleanup expired sessions (cron job)

### Rate Limiting
- OTP Request: 3x per 10 menit per nomor
- API Calls: 60 requests per menit per user
- Login Attempts: 5x per 15 menit

### Caching
- Product list: Cache 1 jam
- Stock info: Cache 5 menit
- Cart: No cache (real-time)

### Security
- HTTPS only
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS protection
- Input validation & sanitization

---

## 8. NOTIFIKASI WHATSAPP

### Trigger Notifikasi:

**1. OTP Login**
```
To: Toko
Message: "Kode OTP Anda: 123456. Berlaku 5 menit."
```

**2. Order Created**
```
To: Admin
Message: "Order baru dari Toko Jaya Motor. 
         Order: ORD-2025-000001
         Total: Rp 5.000.000
         Silakan cek admin panel."
```

**3. Order Approved**
```
To: Toko
Message: "Order ORD-2025-000001 telah disetujui.
         No SO: 2025/000123/POD-PD
         Terima kasih!"
```

**4. Order Rejected**
```
To: Toko
Message: "Order ORD-2025-000001 ditolak.
         Alasan: Stock tidak tersedia.
         Silakan hubungi admin."
```

---

## 9. ROADMAP DEVELOPMENT

### Phase 1: Foundation (Week 1-2)
- ✅ Setup database connections
- ✅ Create migrations (pmov2 tables)
- ✅ Setup authentication (Admin & Toko)
- ✅ OTP service integration
- ✅ WhatsApp gateway integration

### Phase 2: Core Features (Week 3-4)
- Product management (Admin)
- Shop management (Admin)
- Product browsing (Toko)
- Cart management (Toko)
- Stock checking

### Phase 3: Order Flow (Week 5-6)
- Checkout process
- Order creation
- Order approval (Admin)
- Submit to data_part
- Order history

### Phase 4: Enhancement (Week 7-8)
- Dashboard & analytics
- Notifications
- Search & filters
- Performance optimization
- Testing

---

## 10. PERBEDAAN DENGAN SISTEM LAMA

| Aspek | Sistem Lama | PMO V2 |
|-------|-------------|--------|
| Konsep | Mobile ordering tool | E-commerce platform |
| Auth Toko | Username/Password | WhatsApp OTP |
| Session | Per login | 1 minggu |
| Database | Langsung ke data_part | pmov2 → data_part |
| Cart | pmo.tb_temp_cart | pmov2.carts |
| Order | Langsung SO | Order internal → SO |
| Approval | Auto | Manual by admin |
| UI/UX | Basic | Modern e-commerce |
| Admin Panel | Limited | Full featured |

---

## KESIMPULAN

PMO V2 adalah platform e-commerce B2B yang:
- ✅ Independen dari schema `pmo`
- ✅ Punya database sendiri di `pmov2`
- ✅ Auth modern (Email/Password untuk admin, OTP untuk toko)
- ✅ Session toko 1 minggu (anti-spam OTP)
- ✅ Submit ke `data_part` hanya untuk integrasi
- ✅ Full-featured seperti Tokopedia (tapi B2B)

**Next Step:** Mulai development dari Phase 1!

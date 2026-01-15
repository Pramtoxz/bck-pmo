# API PMO V2 - Ready to Use

## Base URL
```
http://192.168.40.42:8000/api
```

## Test Account
```
Email: jaya@motor.com
Password: password123
```

---

## 1. Login
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "jaya@motor.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "1|xxxxx",
    "user": {
      "id": "1",
      "name": "Toko Jaya",
      "email": "jaya@motor.com",
      "role": "dealer",
      "dealerCode": "jaya@motor.com",
      "dealerName": "Toko Jaya"
    }
  }
}
```

---

## 2. Get Profile
```bash
GET /api/auth/profile
Authorization: Bearer {token}
```

---

## 3. Get Parts
```bash
GET /api/parts?search=oli&limit=10
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "1",
        "image": "http://192.168.40.42:8000/storage/...",
        "partNumber": "OIL-001",
        "name": "Honda Genuine Oil 10W-30",
        "description": "Oli mesin original Honda",
        "price": 45000,
        "isReady": true
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 10,
      "totalPages": 1
    }
  }
}
```

---

## 4. Get Part Detail
```bash
GET /api/parts/1
Authorization: Bearer {token}
```

---

## 5. Add to Cart
```bash
POST /api/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "partId": 1,
  "quantity": 2
}
```

---

## 6. Get Cart
```bash
GET /api/cart
Authorization: Bearer {token}
```

---

## 7. Update Cart Item
```bash
PUT /api/cart/{itemId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 5
}
```

---

## 8. Remove from Cart
```bash
DELETE /api/cart/{itemId}
Authorization: Bearer {token}
```

---

## 9. Clear Cart
```bash
DELETE /api/cart/clear
Authorization: Bearer {token}
```

---

## 10. Get Dashboard Stats
```bash
GET /api/dashboard/stats
Authorization: Bearer {token}
```

---

## 11. Get Campaigns
```bash
GET /api/campaigns
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "1",
      "title": "Gear Up & Get Rewarded",
      "badge": "NEW CONTRACT",
      "description": "Ends Dec 31, 2025 • Target: 85% Reach",
      "image": "http://192.168.40.42:8000/storage/...",
      "startDate": "2025-07-01 00:00:00",
      "endDate": "2025-12-31 23:59:59",
      "status": "active"
    }
  ]
}
```

---

## 12. Get Campaign Detail
```bash
GET /api/campaigns/1
Authorization: Bearer {token}
```

---

## 13. Get My Achievement
```bash
GET /api/campaigns/my-achievement
Authorization: Bearer {token}
```

---

## 14. Get Notifications
```bash
GET /api/notifications
Authorization: Bearer {token}
```

---

## 15. Mark Notification as Read
```bash
PUT /api/notifications/1/read
Authorization: Bearer {token}
```

---

## 16. Logout
```bash
POST /api/auth/logout
Authorization: Bearer {token}
```

---

## All Endpoints (16 total)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/auth/login | Login |
| GET | /api/auth/profile | Get profile |
| POST | /api/auth/logout | Logout |
| GET | /api/parts | List parts |
| GET | /api/parts/{id} | Part detail |
| GET | /api/cart | Get cart |
| POST | /api/cart/add | Add to cart |
| PUT | /api/cart/{id} | Update cart item |
| DELETE | /api/cart/{id} | Remove from cart |
| DELETE | /api/cart/clear | Clear cart |
| GET | /api/dashboard/stats | Dashboard stats |
| GET | /api/campaigns | List campaigns |
| GET | /api/campaigns/{id} | Campaign detail |
| GET | /api/campaigns/my-achievement | My achievement |
| GET | /api/notifications | List notifications |
| PUT | /api/notifications/{id}/read | Mark as read |

---

## Notes

- Semua endpoint kecuali login butuh Bearer token
- Response format: `{"success": true/false, "data": {...}}`
- Error format: `{"success": false, "error": {...}}`
- ID semua return sebagai string
- Image return full URL
- Parts pakai field `partNumber` bukan `code`

---

## Sample Products

| ID | Part Number | Name | Category | Price |
|----|-------------|------|----------|-------|
| 1 | OIL-001 | Honda Genuine Oil 10W-30 | OIL | 45,000 |
| 2 | OIL-002 | Honda Genuine Oil 20W-50 | OIL | 52,000 |
| 3 | PART-001 | Kampas Rem Depan | BRAKE | 85,000 |
| 4 | PART-002 | Kampas Rem Belakang | BRAKE | 75,000 |
| 5 | PART-003 | Filter Udara | FILTER | 35,000 |
| 6 | PART-004 | Busi NGK | SPARK_PLUG | 25,000 |
| 7 | PART-005 | Rantai Drive Chain | CHAIN | 125,000 |
| 8 | PART-006 | Ban Depan IRC 70/90-17 | TIRE | 185,000 |
| 9 | PART-007 | Ban Belakang IRC 80/90-17 | TIRE | 215,000 |
| 10 | PART-008 | Aki GS Astra 12V 5Ah | BATTERY | 165,000 |

# API Specification - Mobile Parts Ordering App
**PT. Menara Agung - Main Dealer Honda**

**Backend Framework:** Laravel 11.x  
**Database:** MySQL 8.0+  
**Authentication:** Laravel Sanctum (Token-based)

---

## Base URL
```
https://api.menara-agung.com/v1
```

---

## Authentication

### 1. Login
**POST** `/auth/login`

**Request:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "string",
    "user": {
      "id": "string",
      "name": "string",
      "email": "string",
      "role": "dealer|channel",
      "dealerCode": "string",
      "dealerName": "string"
    }
  }
}
```

### 2. Get User Profile
**GET** `/auth/profile`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "string",
    "name": "string",
    "email": "string",
    "role": "string",
    "dealerCode": "string",
    "dealerName": "string"
  }
}
```

### 3. Logout
**POST** `/auth/logout`

**Headers:** `Authorization: Bearer {token}`

---

## Campaign

### 1. Get Campaign List
**GET** `/campaigns`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `type` (optional): `contract` | `others`
- `status` (optional): `active` | `completed` | `upcoming`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "string",
      "title": "string",
      "badge": "string",
      "description": "string",
      "image": "string (URL)",
      "startDate": "string (YYYY-MM-DD HH:mm:ss, Asia/Jakarta)",
      "endDate": "string (YYYY-MM-DD HH:mm:ss, Asia/Jakarta)",
      "status": "active|completed|upcoming"
    }
  ]
}
```

### 2. Get Campaign Detail
**GET** `/campaigns/{id}`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "string",
    "title": "string",
    "badge": "string",
    "description": "string",
    "image": "string (URL)",
    "startDate": "string (YYYY-MM-DD HH:mm:ss, Asia/Jakarta)",
    "endDate": "string (YYYY-MM-DD HH:mm:ss, Asia/Jakarta)",
    "status": "active|completed|upcoming",
    "fullDescription": "string",
    "partsIncluded": ["string"],
    "termsAndConditions": "string",
    "rewards": ["string"]
  }
}
```

### 3. Get User Campaign Achievement
**GET** `/campaigns/my-achievement`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "currentCampaign": {
      "id": "string",
      "title": "string",
      "endDate": "string (YYYY-MM-DD HH:mm:ss, Asia/Jakarta)",
      "achievementPercentage": "number (0-100)",
      "achievementLabel": "string (e.g., '50%')"
    }
  }
}
```

---

## Parts (Suku Cadang)

### 1. Get Parts List
**GET** `/parts`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `search` (optional): Search by part number or name
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 20)
- `sortBy` (optional): `name` | `price` | `partNumber`
- `order` (optional): `asc` | `desc`

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "string",
        "image": "string (URL)",
        "partNumber": "string",
        "name": "string",
        "description": "string",
        "price": "number",
        "isReady": "boolean"
      }
    ],
    "pagination": {
      "page": "number",
      "limit": "number",
      "total": "number",
      "totalPages": "number"
    }
  }
}
```

### 2. Get Part Detail
**GET** `/parts/{id}`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "string",
    "image": "string (URL)",
    "partNumber": "string",
    "name": "string",
    "description": "string",
    "price": "number",
    "isReady": "boolean",
    "stock": "number",
    "category": "string"
  }
}
```

---

## Cart

### 1. Get Cart
**GET** `/cart`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "string",
        "partId": "string",
        "partNumber": "string",
        "name": "string",
        "image": "string (URL)",
        "price": "number",
        "quantity": "number",
        "subtotal": "number",
        "isReady": "boolean"
      }
    ],
    "summary": {
      "totalItems": "number",
      "totalPrice": "number"
    }
  }
}
```

### 2. Add to Cart
**POST** `/cart/add`

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "partId": "string",
  "quantity": "number"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "cartItemId": "string",
    "totalItems": "number"
  }
}
```

### 3. Update Cart Item
**PUT** `/cart/{itemId}`

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "quantity": "number"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cart updated"
}
```

### 4. Remove from Cart
**DELETE** `/cart/{itemId}`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Item removed from cart"
}
```

### 5. Clear Cart
**DELETE** `/cart/clear`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "message": "Cart cleared"
}
```

---

## Dashboard Statistics

### 1. Get Dashboard Stats
**GET** `/dashboard/stats`

**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
  "success": true,
  "data": {
    "deliveryProgress": "string (e.g., '50%')",
    "monthlyBuyIn": "string (e.g., 'Rp 1.500.000')",
    "cartCount": "number"
  }
}
```

---

## Notifications

### 1. Get Notifications
**GET** `/notifications`

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `page` (optional): Page number
- `limit` (optional): Items per page

**Response:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "string",
        "title": "string",
        "message": "string",
        "type": "info|success|warning|error",
        "isRead": "boolean",
        "createdAt": "string (YYYY-MM-DD HH:mm:ss, Asia/Jakarta)"
      }
    ],
    "unreadCount": "number"
  }
}
```

### 2. Mark as Read
**PUT** `/notifications/{id}/read`

**Headers:** `Authorization: Bearer {token}`

---

## Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "string",
    "message": "string"
  }
}
```

### Common Error Codes:
- `AUTH_INVALID_CREDENTIALS` - Invalid username or password
- `AUTH_TOKEN_EXPIRED` - Token has expired
- `AUTH_UNAUTHORIZED` - Unauthorized access
- `VALIDATION_ERROR` - Request validation failed
- `NOT_FOUND` - Resource not found
- `SERVER_ERROR` - Internal server error

---

## Notes untuk Backend Team (Laravel):

1. **Authentication:** 
   - Gunakan Laravel Sanctum untuk token-based authentication
   - Token expiry: 24 jam (config di `sanctum.php`)
   - Middleware: `auth:sanctum` untuk protected routes

2. **Pagination:** 
   - Gunakan Laravel pagination: `Model::paginate(20)`
   - Default limit 20 items per page
   - Return meta pagination info

3. **Image URLs:** 
   - Store images di `storage/app/public/`
   - Return full URL menggunakan `Storage::url()`
   - Atau gunakan CDN untuk production

4. **Date Format:** 
   - Database: Store sebagai `datetime` atau `timestamp`
   - Response: Format menggunakan Carbon: `->format('Y-m-d H:i:s')`
   - Timezone: Set di `config/app.php` → `'timezone' => 'Asia/Jakarta'`

5. **Price Format:** 
   - Database: Store sebagai `decimal(15,2)` atau `bigint` (dalam satuan terkecil)
   - Response: Return sebagai number (integer), frontend akan format ke Rupiah

6. **Validation:** 
   - Gunakan Form Request Validation
   - Return validation errors dengan format Laravel default

7. **API Resources:** 
   - Gunakan Laravel API Resources untuk transform response
   - Consistent response structure

8. **Error Handling:**
   - Gunakan Laravel Exception Handler
   - Custom error response di `app/Exceptions/Handler.php`

9. **Database Migrations:**
   - Buat migrations untuk semua tables
   - Gunakan seeders untuk dummy data

10. **Testing:**
    - Gunakan Laravel Feature Tests untuk API endpoints
    - PHPUnit untuk unit tests

---

## Laravel Specific Implementation:

### Routes Structure (`routes/api.php`):
```php
// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    Route::apiResource('campaigns', CampaignController::class);
    Route::get('/campaigns/my-achievement', [CampaignController::class, 'myAchievement']);
    
    Route::apiResource('parts', PartController::class);
    
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/clear', [CartController::class, 'clear']);
    });
    
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::apiResource('notifications', NotificationController::class);
});
```

### Response Format Helper:
```php
// app/Helpers/ApiResponse.php
class ApiResponse
{
    public static function success($data, $message = null, $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
    
    public static function error($message, $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'errors' => $errors
            ]
        ], $code);
    }
}
```

### Database Tables (Migrations):
```
- users (id, name, email, password, role, dealer_code, dealer_name, timestamps)
- campaigns (id, title, badge, description, image, start_date, end_date, status, full_description, terms_and_conditions, timestamps)
- campaign_parts (id, campaign_id, part_number)
- campaign_rewards (id, campaign_id, reward_name)
- parts (id, part_number, name, description, price, image, is_ready, stock, category, timestamps)
- carts (id, user_id, part_id, quantity, timestamps)
- notifications (id, user_id, title, message, type, is_read, timestamps)
```

---

## Priority Implementation:

### Phase 1 (High Priority):
- Authentication (Login, Profile, Logout)
- Parts List & Detail
- Cart Management
- Dashboard Stats

### Phase 2 (Medium Priority):
- Campaign List & Detail
- Campaign Achievement
- Notifications

### Phase 3 (Low Priority):
- Advanced filtering & sorting
- Search optimization
- Analytics tracking

---

## Laravel Packages Recommended:

- `laravel/sanctum` - API authentication
- `spatie/laravel-query-builder` - Advanced filtering & sorting
- `intervention/image` - Image processing
- `barryvdh/laravel-cors` - CORS handling
- `spatie/laravel-permission` - Role & permission management (optional)

---

## Environment Variables (.env):

```env
APP_TIMEZONE=Asia/Jakarta
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SANCTUM_EXPIRATION=1440

FILESYSTEM_DISK=public
```

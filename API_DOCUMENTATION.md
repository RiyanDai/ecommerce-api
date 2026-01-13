# 📱 API Documentation untuk Flutter

## Base URL
```
http://127.0.0.1:8000/api
```
atau untuk production:
```
https://yourdomain.com/api
```

## Authentication
Semua endpoint payment memerlukan **Bearer Token** dari Laravel Sanctum.

### Headers yang diperlukan:
```
Authorization: Bearer {your_token}
Accept: application/json
Content-Type: application/json
```

---

## 🔐 Authentication Endpoints

### 1. Register
```http
POST /api/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "081234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  },
  "token": "1|xxxxxxxxxxxxx"
}
```

### 2. Login
```http
POST /api/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  },
  "token": "1|xxxxxxxxxxxxx"
}
```

---

## 💳 Payment Endpoints

### 1. Generate Snap Token
Generate token untuk Midtrans Snap payment.

```http
POST /api/payment/snap-token
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "order_number": "ORD-20260113-0014"
}
```

**Response Success:**
```json
{
  "success": true,
  "snap_token": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
}
```

**Response Error:**
```json
{
  "success": false,
  "message": "Order payment is not pending. Current payment status: paid"
}
```

**Status Codes:**
- `200` - Success
- `400` - Order payment is not pending
- `403` - Unauthorized (order doesn't belong to user)
- `404` - Order not found
- `422` - Validation error
- `500` - Server error

---

### 2. Check Payment Status
Check status pembayaran dari Midtrans dan update jika ada perubahan.

```http
POST /api/payment/check-status
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "order_number": "ORD-20260113-0014"
}
```

**Response Success:**
```json
{
  "success": true,
  "payment_status": "paid",
  "order_status": "new",
  "transaction_status": "settlement",
  "status_updated": true,
  "message": "Payment status updated successfully"
}
```

**Response (Status Up to Date):**
```json
{
  "success": true,
  "payment_status": "paid",
  "order_status": "new",
  "transaction_status": "settlement",
  "status_updated": false,
  "message": "Payment status is up to date"
}
```

**Response (Transaction Not Found):**
```json
{
  "success": true,
  "payment_status": "pending",
  "order_status": "new",
  "message": "Status retrieved from database. Transaction not found in Midtrans.",
  "note": "If payment was completed via Midtrans simulator, webhook should update automatically.",
  "webhook_url": "http://127.0.0.1:8000/payment/midtrans-webhook"
}
```

**Payment Status Values:**
- `pending` - Payment is pending
- `paid` - Payment is confirmed
- `failed` - Payment failed/cancelled
- `expired` - Payment expired

**Order Status Values:**
- `new` - New order (waiting for admin processing)
- `processing` - Order is being processed
- `shipped` - Order has been shipped
- `completed` - Order completed
- `refunded` - Order refunded

**Status Codes:**
- `200` - Success
- `404` - Order not found
- `500` - Server error

---

## 📦 Order Endpoints

### Get Orders
```http
GET /api/orders
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `payment_status` - Filter by payment status (pending, paid, failed, expired)
- `order_status` - Filter by order status (new, processing, shipped, completed, refunded)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20260113-0014",
      "payment_status": "paid",
      "order_status": "new",
      "total_amount": "1200000.00",
      "created_at": "2026-01-13T16:05:42.000000Z",
      "order_items": [...]
    }
  ]
}
```

### Get Order Detail
```http
GET /api/orders/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "order_number": "ORD-20260113-0014",
  "payment_status": "paid",
  "order_status": "new",
  "total_amount": "1200000.00",
  "created_at": "2026-01-13T16:05:42.000000Z",
  "order_items": [
    {
      "id": 1,
      "product_id": 1,
      "quantity": 1,
      "price": "1200000.00",
      "subtotal": "1200000.00",
      "product": {
        "id": 1,
        "name": "Product Name",
        "image": "products/image.jpg"
      }
    }
  ]
}
```

---

## 🔄 Payment Flow untuk Flutter

### Step 1: Create Order
Gunakan endpoint checkout yang sudah ada:
```http
POST /api/checkout
Authorization: Bearer {token}
```

### Step 2: Generate Snap Token
```dart
final response = await http.post(
  Uri.parse('http://127.0.0.1:8000/api/payment/snap-token'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: jsonEncode({
    'order_number': orderNumber,
  }),
);

final data = jsonDecode(response.body);
final snapToken = data['snap_token'];
```

### Step 3: Open Midtrans Snap
Gunakan Midtrans Flutter SDK untuk membuka payment popup dengan `snapToken`.

### Step 4: Check Payment Status (After Payment)
```dart
// Polling atau check setelah payment callback
final response = await http.post(
  Uri.parse('http://127.0.0.1:8000/api/payment/check-status'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: jsonEncode({
    'order_number': orderNumber,
  }),
);

final data = jsonDecode(response.body);
final paymentStatus = data['payment_status']; // 'paid', 'pending', 'failed', 'expired'
final statusUpdated = data['status_updated']; // true jika status berubah
```

### Step 5: Polling (Optional)
Jika status masih `pending`, lakukan polling setiap 5 detik:
```dart
Timer.periodic(Duration(seconds: 5), (timer) async {
  final status = await checkPaymentStatus(orderNumber);
  if (status == 'paid' || status == 'failed' || status == 'expired') {
    timer.cancel();
    // Update UI
  }
});
```

---

## 📝 Notes

1. **Webhook**: Status pembayaran akan otomatis terupdate via webhook dari Midtrans. Endpoint check-status adalah fallback jika webhook belum terpanggil.

2. **Order ID Format**: Midtrans menggunakan format `{order_number}-{timestamp}` untuk order_id. Sistem akan otomatis mencari transaksi dengan berbagai timestamp.

3. **Status Update**: 
   - `payment_status` hanya bisa diupdate oleh Midtrans webhook atau check-status API
   - `order_status` bisa diupdate oleh admin untuk fulfillment workflow

4. **Error Handling**: Selalu cek `success` field dalam response. Jika `false`, baca `message` untuk detail error.

5. **Rate Limiting**: Jangan terlalu sering call check-status API. Gunakan polling dengan interval minimal 5 detik.

---

## 🧪 Testing

### Test dengan Midtrans Simulator:
1. Generate snap token
2. Buka Midtrans payment popup
3. Pilih payment method di simulator
4. Complete payment
5. Check status via API
6. Status akan otomatis menjadi `paid`

### Webhook URL untuk Testing:
```
http://your-ngrok-url.ngrok-free.dev/api/payment/midtrans-webhook
```

---

## 📚 Contoh Flutter Code

```dart
class PaymentService {
  final String baseUrl = 'http://127.0.0.1:8000/api';
  final String token;

  PaymentService(this.token);

  Future<String> generateSnapToken(String orderNumber) async {
    final response = await http.post(
      Uri.parse('$baseUrl/payment/snap-token'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({'order_number': orderNumber}),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return data['snap_token'];
    } else {
      throw Exception('Failed to generate snap token');
    }
  }

  Future<Map<String, dynamic>> checkPaymentStatus(String orderNumber) async {
    final response = await http.post(
      Uri.parse('$baseUrl/payment/check-status'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({'order_number': orderNumber}),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Failed to check payment status');
    }
  }
}
```


# 📱 API Documentation - Midtrans Payment Integration

## Base URL
```
Development: http://127.0.0.1:8000/api
Production: https://yourdomain.com/api
```

## Authentication
Semua endpoint payment memerlukan **Bearer Token** dari Laravel Sanctum.

### Headers yang diperlukan:
```http
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
Generate token untuk Midtrans Snap payment. Token ini digunakan untuk membuka Midtrans payment popup.

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

**Response Success (200):**
```json
{
  "success": true,
  "snap_token": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "Order payment is not pending. Current payment status: paid"
}
```

**Response Error (403):**
```json
{
  "success": false,
  "message": "Unauthorized access to this order"
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Order not found"
}
```

**Status Codes:**
- `200` - Success, snap_token berhasil di-generate
- `400` - Order payment is not pending (sudah paid/failed/expired)
- `403` - Unauthorized (order doesn't belong to user)
- `404` - Order not found
- `422` - Validation error
- `500` - Server error

**Notes:**
- Hanya order dengan `payment_status = 'pending'` yang bisa generate token
- Token digunakan untuk membuka Midtrans payment popup
- Setiap generate token akan membuat order_id baru dengan timestamp untuk menghindari duplicate

---

### 2. Check Payment Status
Check status pembayaran dari Midtrans dan **otomatis update database** jika ada perubahan. Endpoint ini query langsung ke Midtrans API untuk mendapatkan status terbaru.

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

**Response Success - Status Updated (200):**
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

**Response Success - Status Up to Date (200):**
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

**Response Success - Transaction Not Found (200):**
```json
{
  "success": true,
  "payment_status": "pending",
  "order_status": "new",
  "message": "Status retrieved from database. Transaction not found in Midtrans.",
  "note": "If payment was completed via Midtrans simulator, webhook should update automatically. Please check webhook configuration in Midtrans dashboard.",
  "webhook_url": "http://127.0.0.1:8000/payment/midtrans-webhook"
}
```

**Response Error (404):**
```json
{
  "success": false,
  "message": "Order not found"
}
```

**Response Error (500):**
```json
{
  "success": false,
  "message": "Failed to check payment status: {error_message}"
}
```

**Payment Status Values:**
- `pending` - Payment is pending (belum dibayar)
- `paid` - Payment is confirmed (sudah dibayar)
- `failed` - Payment failed/cancelled
- `expired` - Payment expired

**Order Status Values:**
- `new` - New order (waiting for admin processing)
- `processing` - Order is being processed
- `shipped` - Order has been shipped
- `completed` - Order completed
- `refunded` - Order refunded

**Midtrans Transaction Status Mapping:**
- `settlement` → `payment_status: "paid"`
- `capture` dengan `fraud_status: "accept"` → `payment_status: "paid"`
- `capture` dengan `fraud_status: "challenge"` → `payment_status: "pending"`
- `pending` → `payment_status: "pending"`
- `expire` → `payment_status: "expired"`
- `cancel` / `deny` → `payment_status: "failed"`

**Status Codes:**
- `200` - Success
- `404` - Order not found
- `500` - Server error

**Important Notes:**
- Endpoint ini **otomatis update database** jika status berubah
- Field `status_updated: true` menandakan status baru saja diupdate
- Endpoint ini query langsung ke Midtrans API, jadi tidak perlu webhook
- Bisa digunakan untuk polling/auto-check status

---

## 🛒 Cart & Checkout Endpoints

### Get Cart
```http
GET /api/cart
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "quantity": 2,
      "product": {
        "id": 1,
        "name": "Product Name",
        "price": "500000.00",
        "stock": 10,
        "image": "products/image.jpg"
      },
      "subtotal": "1000000.00"
    }
  ]
}
```

### Add to Cart
```http
POST /api/cart
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "id": 1,
    "product_id": 1,
    "quantity": 2,
    "subtotal": "1000000.00"
  }
}
```

**Response Error (422) - Insufficient Stock:**
```json
{
  "success": false,
  "message": "Insufficient stock",
  "errors": {
    "quantity": ["Available stock: 5"]
  }
}
```

### Update Cart Item
```http
PUT /api/cart/{id}
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "quantity": 3
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cart updated",
  "data": {
    "id": 1,
    "product": {
      "id": 1,
      "name": "Product Name",
      "price": "500000.00"
    },
    "quantity": 3,
    "subtotal": "1500000.00"
  }
}
```

### Remove from Cart
```http
DELETE /api/cart/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Item removed from cart"
}
```

---

### Checkout (Create Order)
```http
POST /api/checkout
Authorization: Bearer {token}
```

**Request Body:**
```json
{}
```
*(Empty body - cart items are used automatically)*

**Response Success (201):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20260113-0014",
    "payment_status": "pending",
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
}
```

**Response Error (422) - Empty Cart:**
```json
{
  "success": false,
  "message": "Cart is empty",
  "errors": {
    "cart": ["Please add items to cart before checkout"]
  }
}
```

**Response Error (422) - Insufficient Stock:**
```json
{
  "success": false,
  "message": "Some items have insufficient stock",
  "errors": {
    "stock": [
      {
        "product": "Product Name",
        "requested": 10,
        "available": 5
      }
    ]
  }
}
```

**Important Notes:**
- Endpoint ini akan **clear cart** setelah order berhasil dibuat
- Order akan dibuat dengan `payment_status: "pending"` dan `order_status: "new"`
- Response berisi `order_number` yang diperlukan untuk payment
- Extract `order_number` dari `data.order_number` untuk langkah selanjutnya

---

## 📦 Order Endpoints

### Get Orders
Get list orders milik user yang sedang login.

```http
GET /api/orders
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `payment_status` - Filter by payment status (pending, paid, failed, expired)
- `order_status` - Filter by order status (new, processing, shipped, completed, refunded)

**Example:**
```
GET /api/orders?payment_status=paid&order_status=new
```

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
      "updated_at": "2026-01-13T16:10:15.000000Z",
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
  ],
  "links": {...},
  "meta": {...}
}
```

### Get Order Detail
Get detail order berdasarkan ID.

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
  "updated_at": "2026-01-13T16:10:15.000000Z",
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
        "image": "products/image.jpg",
        "category": {
          "id": 1,
          "name": "Category Name"
        }
      }
    }
  ],
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

## 🔄 Complete Payment Flow untuk Flutter

### Step 1: Create Order
Gunakan endpoint checkout yang sudah ada:
```http
POST /api/checkout
Authorization: Bearer {token}
```

**Response akan berisi `order_number` yang diperlukan untuk payment.**

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

if (response.statusCode == 200) {
  final data = jsonDecode(response.body);
  final snapToken = data['snap_token'];
  // Gunakan snapToken untuk membuka Midtrans payment popup
} else {
  // Handle error
  final error = jsonDecode(response.body);
  print('Error: ${error['message']}');
}
```

### Step 3: Open Midtrans Snap
Gunakan Midtrans Flutter SDK untuk membuka payment popup dengan `snapToken`.

**Contoh dengan midtrans_snap_flutter:**
```dart
import 'package:midtrans_snap_flutter/midtrans_snap_flutter.dart';

// Set client key (dari config)
MidtransSnapFlutter.setClientKey('YOUR_MIDTRANS_CLIENT_KEY');

// Open payment popup
final result = await MidtransSnapFlutter.startPayment(
  snapToken: snapToken,
  onSuccess: (result) {
    // Payment successful
    print('Payment success: $result');
    // Check status untuk update database
    checkPaymentStatus(orderNumber);
  },
  onPending: (result) {
    // Payment pending
    print('Payment pending: $result');
    // Check status untuk update database
    checkPaymentStatus(orderNumber);
  },
  onError: (result) {
    // Payment failed
    print('Payment error: $result');
  },
  onClose: () {
    // User closed payment popup
    print('Payment popup closed');
  },
);
```

### Step 4: Check Payment Status (Auto-Update)
Setelah payment callback, check status untuk **otomatis update database**:

```dart
Future<void> checkPaymentStatus(String orderNumber) async {
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

  if (response.statusCode == 200) {
    final data = jsonDecode(response.body);
    final paymentStatus = data['payment_status']; // 'paid', 'pending', 'failed', 'expired'
    final statusUpdated = data['status_updated']; // true jika status berubah
    
    if (statusUpdated) {
      print('Payment status updated to: $paymentStatus');
      // Update UI, refresh order list, dll
    }
    
    return paymentStatus;
  } else {
    throw Exception('Failed to check payment status');
  }
}
```

### Step 5: Polling (Optional - untuk auto-update)
Jika status masih `pending`, lakukan polling setiap 5 detik untuk auto-update:

```dart
import 'dart:async';

Timer? pollingTimer;

void startPaymentStatusPolling(String orderNumber) {
  pollingTimer = Timer.periodic(Duration(seconds: 5), (timer) async {
    try {
      final status = await checkPaymentStatus(orderNumber);
      
      // Stop polling jika status sudah final
      if (status == 'paid' || status == 'failed' || status == 'expired') {
        timer.cancel();
        pollingTimer = null;
        // Update UI - payment completed
        updatePaymentUI(status);
      }
    } catch (e) {
      print('Error polling payment status: $e');
      // Continue polling on error
    }
  });
}

void stopPaymentStatusPolling() {
  pollingTimer?.cancel();
  pollingTimer = null;
}
```

---

## 📝 Complete Flutter Implementation Example

```dart
import 'dart:async';
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:midtrans_snap_flutter/midtrans_snap_flutter.dart';

class PaymentService {
  final String baseUrl = 'http://127.0.0.1:8000/api';
  final String token;
  Timer? pollingTimer;

  PaymentService(this.token);

  /// Generate Snap Token untuk Midtrans payment
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
      if (data['success'] == true) {
        return data['snap_token'];
      } else {
        throw Exception(data['message'] ?? 'Failed to generate snap token');
      }
    } else {
      final error = jsonDecode(response.body);
      throw Exception(error['message'] ?? 'Failed to generate snap token');
    }
  }

  /// Check payment status dan auto-update database
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
      final data = jsonDecode(response.body);
      return {
        'success': true,
        'payment_status': data['payment_status'],
        'order_status': data['order_status'],
        'transaction_status': data['transaction_status'],
        'status_updated': data['status_updated'] ?? false,
        'message': data['message'],
      };
    } else {
      final error = jsonDecode(response.body);
      throw Exception(error['message'] ?? 'Failed to check payment status');
    }
  }

  /// Start payment dengan Midtrans Snap
  Future<void> startPayment(String orderNumber, String clientKey) async {
    try {
      // Step 1: Generate Snap Token
      final snapToken = await generateSnapToken(orderNumber);
      
      // Step 2: Set Midtrans client key
      MidtransSnapFlutter.setClientKey(clientKey);
      
      // Step 3: Open Midtrans payment popup
      await MidtransSnapFlutter.startPayment(
        snapToken: snapToken,
        onSuccess: (result) async {
          print('Payment success: $result');
          // Check status untuk auto-update database
          await checkPaymentStatus(orderNumber);
          // Start polling untuk memastikan status terupdate
          startPaymentStatusPolling(orderNumber);
        },
        onPending: (result) async {
          print('Payment pending: $result');
          // Check status untuk auto-update database
          await checkPaymentStatus(orderNumber);
          // Start polling untuk menunggu status berubah
          startPaymentStatusPolling(orderNumber);
        },
        onError: (result) {
          print('Payment error: $result');
          // Payment failed, tidak perlu polling
        },
        onClose: () {
          print('Payment popup closed');
          // User tutup popup, tetap start polling untuk check status
          startPaymentStatusPolling(orderNumber);
        },
      );
    } catch (e) {
      print('Error starting payment: $e');
      rethrow;
    }
  }

  /// Start polling payment status (auto-update setiap 5 detik)
  void startPaymentStatusPolling(String orderNumber, {int maxAttempts = 20}) {
    int attempts = 0;
    
    pollingTimer = Timer.periodic(Duration(seconds: 5), (timer) async {
      attempts++;
      
      // Stop jika sudah max attempts (100 detik)
      if (attempts > maxAttempts) {
        timer.cancel();
        pollingTimer = null;
        return;
      }
      
      try {
        final result = await checkPaymentStatus(orderNumber);
        final paymentStatus = result['payment_status'];
        final statusUpdated = result['status_updated'];
        
        print('Polling attempt $attempts: $paymentStatus (updated: $statusUpdated)');
        
        // Stop polling jika status sudah final
        if (paymentStatus == 'paid' || 
            paymentStatus == 'failed' || 
            paymentStatus == 'expired') {
          timer.cancel();
          pollingTimer = null;
          // Callback untuk update UI
          onPaymentStatusFinal(paymentStatus);
        }
      } catch (e) {
        print('Error polling payment status: $e');
        // Continue polling on error
      }
    });
  }

  /// Stop polling
  void stopPaymentStatusPolling() {
    pollingTimer?.cancel();
    pollingTimer = null;
  }

  /// Callback ketika payment status sudah final
  void onPaymentStatusFinal(String status) {
    // Override di widget untuk handle status final
    print('Payment status final: $status');
  }
}
```

**Usage di Widget:**
```dart
class PaymentPage extends StatefulWidget {
  final String orderNumber;
  final String token;
  
  @override
  _PaymentPageState createState() => _PaymentPageState();
}

class _PaymentPageState extends State<PaymentPage> {
  late PaymentService paymentService;
  String paymentStatus = 'pending';
  
  @override
  void initState() {
    super.initState();
    paymentService = PaymentService(widget.token);
  }
  
  Future<void> handlePayment() async {
    try {
      await paymentService.startPayment(
        widget.orderNumber,
        'YOUR_MIDTRANS_CLIENT_KEY', // Dari config
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }
  
  @override
  void dispose() {
    paymentService.stopPaymentStatusPolling();
    super.dispose();
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          Text('Payment Status: $paymentStatus'),
          ElevatedButton(
            onPressed: handlePayment,
            child: Text('Pay Now'),
          ),
        ],
      ),
    );
  }
}
```

---

## 🔍 Status Mapping Reference

### Midtrans Transaction Status → Payment Status

| Midtrans Status | Fraud Status | Payment Status | Description |
|----------------|--------------|----------------|-------------|
| `settlement` | - | `paid` | Payment confirmed |
| `capture` | `accept` | `paid` | Payment captured and accepted |
| `capture` | `challenge` | `pending` | Payment challenged (needs verification) |
| `capture` | `null` | `pending` | Payment captured but fraud status unknown |
| `pending` | - | `pending` | Payment pending |
| `expire` | - | `expired` | Payment expired |
| `cancel` | - | `failed` | Payment cancelled |
| `deny` | - | `failed` | Payment denied |

### Payment Status → Order Status (Admin Controlled)

| Payment Status | Order Status | Can Admin Update? |
|----------------|--------------|-------------------|
| `paid` | `new` | ✅ Yes (to processing/shipped/completed) |
| `paid` | `processing` | ✅ Yes (to shipped/completed) |
| `paid` | `shipped` | ✅ Yes (to completed) |
| `paid` | `completed` | ❌ No (already completed) |
| `pending` | `new` | ❌ No (must wait for payment) |
| `failed` | `new` | ❌ No (payment failed) |
| `expired` | `new` | ❌ No (payment expired) |

**Important:** Admin hanya bisa update `order_status` jika `payment_status = 'paid'`.

---

## ⚠️ Error Handling

### Common Errors dan Solutions

1. **401 Unauthorized**
   - Token expired atau invalid
   - Solution: Re-login untuk mendapatkan token baru

2. **403 Forbidden**
   - Order tidak milik user yang sedang login
   - Solution: Pastikan order_number benar dan milik user

3. **404 Not Found**
   - Order tidak ditemukan
   - Solution: Pastikan order_number benar

4. **400 Bad Request**
   - Order payment status bukan 'pending'
   - Solution: Cek status order, mungkin sudah paid/failed

5. **500 Server Error**
   - Server error atau Midtrans API error
   - Solution: Check logs, retry setelah beberapa detik

---

## 🧪 Testing dengan Midtrans Simulator

### Test Scenarios:

1. **Success Payment:**
   - Pilih payment method di simulator
   - Complete payment
   - Status akan otomatis menjadi `paid` via check-status API

2. **Pending Payment:**
   - Pilih payment method yang pending (e.g., Bank Transfer)
   - Status akan tetap `pending`
   - Polling akan terus check sampai status berubah

3. **Failed Payment:**
   - Cancel payment atau pilih method yang failed
   - Status akan menjadi `failed`

4. **Expired Payment:**
   - Biarkan payment expired
   - Status akan menjadi `expired`

---

## 📊 Response Fields Explanation

### Check Payment Status Response:

```json
{
  "success": true,                    // Boolean: apakah request berhasil
  "payment_status": "paid",           // String: status pembayaran (pending/paid/failed/expired)
  "order_status": "new",              // String: status order (new/processing/shipped/completed/refunded)
  "transaction_status": "settlement",  // String: status dari Midtrans (settlement/capture/pending/expire/cancel/deny)
  "status_updated": true,              // Boolean: apakah status baru saja diupdate (true = baru diupdate, false = sudah up to date)
  "message": "Payment status updated successfully"  // String: pesan informasi
}
```

**Field `status_updated`:**
- `true` = Status baru saja diupdate dari database (dari pending → paid, dll)
- `false` = Status sudah up to date (tidak ada perubahan)

---

## 🚀 Best Practices

1. **Polling Interval:**
   - Gunakan interval minimal 5 detik untuk avoid rate limiting
   - Stop polling jika status sudah final (paid/failed/expired)

2. **Error Handling:**
   - Selalu handle error dengan try-catch
   - Tampilkan error message ke user
   - Retry mechanism untuk network errors

3. **Status Update:**
   - Check status setelah payment callback (onSuccess/onPending)
   - Polling hanya jika status masih pending
   - Update UI real-time saat status berubah

4. **Token Management:**
   - Simpan token dengan secure storage
   - Refresh token jika expired
   - Handle 401 errors dengan re-login

5. **User Experience:**
   - Tampilkan loading indicator saat check status
   - Tampilkan status update notification
   - Auto-refresh order list setelah payment success

---

## 📞 Support

Jika ada masalah:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Midtrans Dashboard untuk transaction status
3. Verify webhook URL configuration (jika menggunakan webhook)
4. Test dengan Midtrans Payment Simulator

---

## ✅ Checklist untuk AI Agent

### Authentication
- [ ] Implementasi register endpoint
- [ ] Implementasi login endpoint
- [ ] Simpan token dengan secure storage
- [ ] Handle token expiration dan re-login
- [ ] Implementasi logout

### Products & Cart
- [ ] Implementasi get products list
- [ ] Implementasi get product detail
- [ ] Implementasi add to cart
- [ ] Implementasi update cart item
- [ ] Implementasi remove from cart
- [ ] Implementasi get cart

### Checkout & Payment
- [ ] Implementasi checkout (create order)
- [ ] Extract `order_number` dari checkout response
- [ ] Implementasi generate snap token
- [ ] Implementasi check payment status dengan auto-update
- [ ] Implementasi polling mechanism (optional)
- [ ] Handle semua payment callbacks (onSuccess, onPending, onError, onClose)
- [ ] Verify status otomatis menjadi 'paid' setelah payment success

### Orders
- [ ] Implementasi get orders list
- [ ] Implementasi get order detail
- [ ] Implementasi filter orders by payment_status dan order_status
- [ ] Implementasi cancel order (jika payment pending)

### Error Handling & UX
- [ ] Error handling untuk semua scenarios
- [ ] Update UI real-time saat status berubah
- [ ] Stop polling saat status sudah final
- [ ] Loading indicators untuk semua async operations
- [ ] User-friendly error messages

### Testing
- [ ] Test dengan Midtrans simulator
- [ ] Test semua payment scenarios (success, pending, failed, expired)
- [ ] Test polling mechanism
- [ ] Test error scenarios (network error, token expired, dll)

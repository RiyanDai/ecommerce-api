# E-Commerce API Documentation

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
API menggunakan **Laravel Sanctum** untuk authentication. Setelah login/register, Anda akan mendapatkan token yang harus disertakan di header setiap request.

### Header yang Diperlukan
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

---

## Endpoints

### 1. Authentication

#### Register (Customer Only)
```http
POST /api/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "customer"
    },
    "token": "1|xxxxxxxxxxxxx..."
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

---

#### Login (Admin & Customer)
```http
POST /api/login
```

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "role": "admin"
    },
    "token": "1|xxxxxxxxxxxxx..."
  }
}
```

**Error Response (401):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "email": ["Email or password is incorrect."]
  }
}
```

---

#### Logout
```http
POST /api/logout
```

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logout successful",
  "data": null
}
```

---

#### Get Current User
```http
GET /api/user
```

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Current user fetched",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

---

### 2. Categories (Public)

#### List All Categories
```http
GET /api/categories
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Categories fetched",
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics",
      "description": "Electronics category"
    }
  ]
}
```

---

### 3. Products (Public)

#### List Products
```http
GET /api/products
```

**Query Parameters:**
- `search` (optional): Search by product name
- `category_id` (optional): Filter by category
- `page` (optional): Page number for pagination (default: 1)

**Example:**
```
GET /api/products?search=phone&category_id=1&page=1
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Products fetched",
  "data": {
    "data": [
      {
        "id": 1,
        "category": {
          "id": 1,
          "name": "Electronics",
          "slug": "electronics"
        },
        "name": "Smartphone X",
        "slug": "smartphone-x-xxxx",
        "description": "Smartphone X description",
        "price": "5000000.00",
        "stock": 10,
        "is_active": true,
        "image_url": "http://127.0.0.1:8000/storage/products/filename.jpg",
        "created_at": "2026-01-08T04:50:17.000000Z",
        "updated_at": "2026-01-08T04:50:17.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 20,
    "last_page": 2
  }
}
```

---

#### Get Product Detail
```http
GET /api/products/{id}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Product detail fetched",
  "data": {
    "id": 1,
    "category": {
      "id": 1,
      "name": "Electronics",
      "slug": "electronics"
    },
    "name": "Smartphone X",
    "slug": "smartphone-x-xxxx",
    "description": "Smartphone X description",
    "price": "5000000.00",
    "stock": 10,
    "is_active": true,
    "image_url": "http://127.0.0.1:8000/storage/products/filename.jpg",
    "created_at": "2026-01-08T04:50:17.000000Z",
    "updated_at": "2026-01-08T04:50:17.000000Z"
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

### 4. Cart (Customer - Requires Auth)

#### View Cart
```http
GET /api/cart
```

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Cart fetched",
  "data": {
    "items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "Smartphone X",
          "price": "5000000.00",
          "image_url": "http://127.0.0.1:8000/storage/products/filename.jpg"
        },
        "quantity": 2,
        "subtotal": 10000000
      }
    ],
    "total": 10000000
  }
}
```

---

#### Add to Cart
```http
POST /api/cart
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Product added to cart",
  "data": {
    "id": 1,
    "product": {
      "id": 1,
      "name": "Smartphone X",
      "price": "5000000.00"
    },
    "quantity": 2,
    "subtotal": 10000000
  }
}
```

**Error Response (422) - Insufficient Stock:**
```json
{
  "success": false,
  "message": "Insufficient stock",
  "errors": {
    "quantity": ["Available stock: 5"]
  }
}
```

---

#### Update Cart Item
```http
PUT /api/cart/{id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "quantity": 3
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Cart updated",
  "data": {
    "id": 1,
    "product": {
      "id": 1,
      "name": "Smartphone X",
      "price": "5000000.00"
    },
    "quantity": 3,
    "subtotal": 15000000
  }
}
```

---

#### Remove from Cart
```http
DELETE /api/cart/{id}
```

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item removed from cart",
  "data": null
}
```

---

### 5. Checkout (Customer - Requires Auth)

#### Checkout
```http
POST /api/checkout
```

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{}
```
(Empty body - cart items are used automatically)

**Success Response (201):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-20260108-0001",
    "status": "pending",
    "total_amount": "10000000.00",
    "order_items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "Smartphone X",
          "price": "5000000.00"
        },
        "quantity": 2,
        "price": "5000000.00",
        "subtotal": "10000000.00"
      }
    ],
    "created_at": "2026-01-08T04:50:17.000000Z"
  }
}
```

**Error Response (422) - Empty Cart:**
```json
{
  "success": false,
  "message": "Cart is empty",
  "errors": {
    "cart": ["Please add items to cart before checkout"]
  }
}
```

**Error Response (422) - Insufficient Stock:**
```json
{
  "success": false,
  "message": "Some items have insufficient stock",
  "errors": {
    "stock": [
      {
        "product": "Smartphone X",
        "requested": 5,
        "available": 2
      }
    ]
  }
}
```

---

### 6. Admin - Products (Admin Only)

#### Create Product
```http
POST /api/admin/products
```

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
```
category_id: 1
name: "New Product"
slug: "new-product" (optional)
description: "Product description"
price: 500000
stock: 10
is_active: 1
image: [file]
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Product created",
  "data": {
    "id": 1,
    "name": "New Product",
    "price": "500000.00",
    "image_url": "http://127.0.0.1:8000/storage/products/filename.jpg"
  }
}
```

---

#### Update Product
```http
PUT /api/admin/products/{id}
```

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data
```

**Request Body (Form Data - all fields optional):**
```
category_id: 1
name: "Updated Product"
price: 600000
stock: 15
is_active: 1
image: [file]
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Product updated",
  "data": { ... }
}
```

---

#### Delete Product
```http
DELETE /api/admin/products/{id}
```

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Product deleted",
  "data": null
}
```

---

#### Add Stock
```http
POST /api/admin/products/{id}/add-stock
```

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Request Body:**
```json
{
  "quantity": 10,
  "description": "Restock from supplier"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Stock added",
  "data": {
    "id": 1,
    "name": "Product Name",
    "stock": 20
  }
}
```

---

### 7. Admin - Orders (Admin Only)

#### List Orders
```http
GET /api/admin/orders
```

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `status` (optional): Filter by status (pending, paid, shipped, completed, cancelled)
- `page` (optional): Page number

**Success Response (200):**
```json
{
  "success": true,
  "message": "Orders fetched",
  "data": {
    "data": [
      {
        "id": 1,
        "order_number": "ORD-20260108-0001",
        "status": "pending",
        "total_amount": "10000000.00",
        "user": {
          "id": 2,
          "name": "Customer One",
          "email": "customer1@example.com"
        },
        "order_items": [ ... ],
        "created_at": "2026-01-08T04:50:17.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 10
  }
}
```

---

#### Get Order Detail
```http
GET /api/admin/orders/{id}
```

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Order detail fetched",
  "data": {
    "id": 1,
    "order_number": "ORD-20260108-0001",
    "status": "pending",
    "total_amount": "10000000.00",
    "user": { ... },
    "order_items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "Smartphone X",
          "price": "5000000.00"
        },
        "quantity": 2,
        "price": "5000000.00",
        "subtotal": "10000000.00"
      }
    ]
  }
}
```

---

#### Update Order Status
```http
PUT /api/admin/orders/{id}/status
```

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Request Body:**
```json
{
  "status": "completed"
}
```

**Valid Status Values:**
- `pending`
- `paid`
- `shipped`
- `completed`
- `cancelled`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Order status updated",
  "data": {
    "id": 1,
    "order_number": "ORD-20260108-0001",
    "status": "completed",
    ...
  }
}
```

**Note:** 
- When status changes to `completed`: Stock is automatically reduced
- When status changes to `cancelled` (if was completed): Stock is automatically returned

---

### 8. Admin - Stock History (Admin Only)

#### Get Stock History
```http
GET /api/admin/stock-history
```

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `product_id` (optional): Filter by product
- `type` (optional): Filter by type (in, out, adjustment)
- `order_id` (optional): Filter by order
- `page` (optional): Page number

**Success Response (200):**
```json
{
  "success": true,
  "message": "Stock history fetched",
  "data": {
    "data": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "name": "Smartphone X"
        },
        "user": {
          "id": 1,
          "name": "Admin User"
        },
        "change": -2,
        "stock_before": 10,
        "stock_after": 8,
        "type": "out",
        "description": "Order #ORD-20260108-0001 completed",
        "order_id": 1,
        "created_at": "2026-01-08T04:50:17.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 20
  }
}
```

---

### 9. Admin - Dashboard (Admin Only)

#### Get Dashboard Statistics
```http
GET /api/admin/dashboard
```

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Dashboard statistics fetched",
  "data": {
    "products": {
      "total": 20,
      "active": 18,
      "low_stock": 3,
      "low_stock_items": [
        {
          "id": 1,
          "name": "Product Name",
          "stock": 5
        }
      ]
    },
    "orders": {
      "total": 50,
      "pending": 10,
      "completed": 35
    },
    "revenue": {
      "total": 500000000,
      "formatted": "500,000,000.00"
    },
    "sales_by_status": [
      {
        "status": "completed",
        "count": 35,
        "total": 500000000
      }
    ],
    "recent_orders": [ ... ]
  }
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Forbidden",
  "errors": {
    "role": ["Admin only"]
  }
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Server error",
  "errors": {
    "error": ["Error message"]
  }
}
```

---

## Flutter Implementation Example

### 1. Setup HTTP Client

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class ApiService {
  static const String baseUrl = 'http://127.0.0.1:8000/api';
  static String? token;

  static Map<String, String> get headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    if (token != null) 'Authorization': 'Bearer $token',
  };

  static Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> body) async {
    final response = await http.post(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> get(String endpoint) async {
    final response = await http.get(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> put(String endpoint, Map<String, dynamic> body) async {
    final response = await http.put(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  static Future<Map<String, dynamic>> delete(String endpoint) async {
    final response = await http.delete(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
    );
    return jsonDecode(response.body);
  }
}
```

### 2. Authentication Example

```dart
// Login
Future<void> login(String email, String password) async {
  final response = await ApiService.post('/login', {
    'email': email,
    'password': password,
  });

  if (response['success'] == true) {
    ApiService.token = response['data']['token'];
    // Save token to SharedPreferences
    // Navigate to home
  } else {
    // Show error message
    print(response['message']);
  }
}

// Register
Future<void> register(String name, String email, String password) async {
  final response = await ApiService.post('/register', {
    'name': name,
    'email': email,
    'password': password,
    'password_confirmation': password,
  });

  if (response['success'] == true) {
    ApiService.token = response['data']['token'];
    // Save token and navigate
  }
}
```

### 3. Get Products Example

```dart
Future<List<Map<String, dynamic>>> getProducts({
  String? search,
  int? categoryId,
  int page = 1,
}) async {
  String query = '?page=$page';
  if (search != null) query += '&search=$search';
  if (categoryId != null) query += '&category_id=$categoryId';

  final response = await ApiService.get('/products$query');
  
  if (response['success'] == true) {
    return List<Map<String, dynamic>>.from(response['data']['data']);
  }
  return [];
}
```

### 4. Cart Example

```dart
// Add to cart
Future<void> addToCart(int productId, int quantity) async {
  final response = await ApiService.post('/cart', {
    'product_id': productId,
    'quantity': quantity,
  });

  if (response['success'] == true) {
    // Show success message
  } else {
    // Show error (e.g., insufficient stock)
    print(response['errors']['quantity'][0]);
  }
}

// Get cart
Future<Map<String, dynamic>> getCart() async {
  final response = await ApiService.get('/cart');
  return response['data'];
}

// Checkout
Future<Map<String, dynamic>> checkout() async {
  final response = await ApiService.post('/checkout', {});
  return response['data'];
}
```

### 5. Error Handling

```dart
try {
  final response = await ApiService.get('/products');
  
  if (response['success'] == true) {
    // Handle success
  } else {
    // Handle API error
    print(response['message']);
    if (response['errors'] != null) {
      // Handle validation errors
      response['errors'].forEach((key, value) {
        print('$key: ${value[0]}');
      });
    }
  }
} catch (e) {
  // Handle network error
  print('Network error: $e');
}
```

---

## Notes

1. **Token Storage**: Simpan token di SharedPreferences atau secure storage setelah login
2. **Token Refresh**: Token tidak expire secara default, tapi bisa di-refresh jika diperlukan
3. **Image URLs**: Image URLs sudah full URL, langsung bisa digunakan di Image.network()
4. **Pagination**: Semua list endpoints support pagination dengan parameter `page`
5. **Error Handling**: Selalu cek `success` field sebelum menggunakan `data`
6. **Stock Management**: Stock otomatis dikurangi saat order status = completed
7. **Cart**: Cart otomatis dikosongkan setelah checkout berhasil

---

## Test Credentials

### Admin
- Email: `admin@example.com`
- Password: `password`

### Customer
- Email: `customer1@example.com`
- Password: `password`

---

## Support

Untuk pertanyaan atau issue, silakan hubungi developer.


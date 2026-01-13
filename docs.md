# Dokumentasi E-Commerce Laravel

## 📋 Daftar Isi
1. [Overview Proyek](#overview-proyek)
2. [Persyaratan Sistem](#persyaratan-sistem)
3. [Instalasi Step-by-Step](#instalasi-step-by-step)
4. [Konfigurasi Database](#konfigurasi-database)
5. [Skema Database](#skema-database)
6. [Fitur Aplikasi](#fitur-aplikasi)
7. [Struktur Proyek](#struktur-proyek)
8. [Menjalankan Aplikasi](#menjalankan-aplikasi)

---

## 📖 Overview Proyek

Aplikasi E-Commerce ini dibangun menggunakan **Laravel 10** dengan fitur lengkap untuk manajemen produk, keranjang belanja, pemesanan, dan manajemen stok. Aplikasi ini memiliki dua role utama:
- **Customer**: Untuk pembeli yang dapat melihat produk, menambah ke keranjang, dan melakukan checkout
- **Admin**: Untuk administrator yang dapat mengelola produk, stok, dan pesanan

---

## 💻 Persyaratan Sistem

Sebelum memulai instalasi, pastikan sistem Anda memiliki:

- **PHP** >= 8.1
- **Composer** (Package Manager untuk PHP)
- **Node.js** dan **NPM** (untuk asset compilation)
- **MySQL** atau **MariaDB** (Database)
- **XAMPP** atau **Laragon** (untuk Windows) atau **LAMP** (untuk Linux)
- **Git** (opsional, untuk version control)

---

## 🚀 Instalasi Step-by-Step

### Langkah 1: Persiapan Environment

1. **Pastikan XAMPP sudah terinstall dan berjalan**
   - Apache harus running
   - MySQL harus running

2. **Buka terminal/command prompt** dan navigasi ke folder htdocs:
   ```bash
   cd C:\xampp\htdocs
   ```

### Langkah 2: Clone atau Download Project

Jika menggunakan Git:
```bash
git clone <repository-url> ecommerce
cd ecommerce
```

Atau jika sudah ada folder project, langsung masuk ke folder:
```bash
cd ecommerce
```

### Langkah 3: Install Dependencies dengan Composer

```bash
composer install
```

Perintah ini akan menginstall semua package PHP yang diperlukan seperti:
- Laravel Framework
- Laravel Sanctum (untuk API authentication)
- Dan dependencies lainnya

### Langkah 4: Install Dependencies Node.js

```bash
npm install
```

Perintah ini akan menginstall:
- Vite (untuk asset bundling)
- Axios (untuk HTTP requests)

### Langkah 5: Setup Environment File

1. **Copy file `.env.example` menjadi `.env`**:
   ```bash
   copy .env.example .env
   ```
   (Untuk Linux/Mac: `cp .env.example .env`)

2. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

3. **Edit file `.env`** dan sesuaikan konfigurasi database:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecommerce_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   **Catatan**: Sesuaikan `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai dengan konfigurasi MySQL Anda.

### Langkah 6: Buat Database

1. Buka **phpMyAdmin** (http://localhost/phpmyadmin)
2. Buat database baru dengan nama `ecommerce_db` (atau sesuai yang Anda tulis di `.env`)
3. Set charset: `utf8mb4`
4. Set collation: `utf8mb4_unicode_ci`

### Langkah 7: Jalankan Migration Database

```bash
php artisan migrate
```

Perintah ini akan membuat semua tabel yang diperlukan di database:
- `users` - Tabel pengguna
- `categories` - Tabel kategori produk
- `products` - Tabel produk
- `carts` - Tabel keranjang belanja
- `orders` - Tabel pesanan
- `order_items` - Tabel detail item pesanan
- `stock_histories` - Tabel riwayat perubahan stok
- Dan tabel lainnya

### Langkah 8: Seed Data Dummy (Opsional)

Untuk mengisi data awal seperti kategori, produk, dan user admin:

```bash
php artisan db:seed
```

Atau seed secara spesifik:
```bash
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=ProductSeeder
```

### Langkah 9: Buat Storage Link (untuk upload gambar)

```bash
php artisan storage:link
```

Perintah ini membuat symbolic link dari `storage/app/public` ke `public/storage` agar gambar produk dapat diakses melalui web.

### Langkah 10: Compile Assets (Development)

Untuk development, jalankan:
```bash
npm run dev
```

Atau untuk production:
```bash
npm run build
```

**Catatan**: Biarkan terminal ini tetap berjalan saat development.

### Langkah 11: Jalankan Server Development

Buka terminal baru dan jalankan:
```bash
php artisan serve
```

Aplikasi akan berjalan di: **http://localhost:8000**

---

## 🗄️ Konfigurasi Database

### File Konfigurasi: `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_db
DB_USERNAME=root
DB_PASSWORD=
```

### File Konfigurasi: `config/database.php`

File ini berisi konfigurasi default database. Biasanya tidak perlu diubah jika sudah mengatur di `.env`.

---

## 📊 Skema Database

### ERD (Entity Relationship Diagram)

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   users     │         │  categories  │         │  products   │
├─────────────┤         ├──────────────┤         ├─────────────┤
│ id          │         │ id           │◄────────│ id          │
│ name        │         │ name         │         │ category_id │
│ email       │         │ slug         │         │ name        │
│ phone       │         │ description  │         │ slug        │
│ password    │         │ timestamps   │         │ description │
│ role        │         └──────────────┘         │ price       │
│ timestamps  │                                  │ stock       │
└──────┬──────┘                                  │ is_active   │
       │                                         │ image       │
       │                                         │ timestamps  │
       │                                         └──────┬──────┘
       │                                                │
       │         ┌──────────────┐                      │
       │         │    carts     │                      │
       │         ├──────────────┤                      │
       │         │ id           │                      │
       │         │ user_id      │                      │
       │         │ product_id   │───────────────────────┘
       │         │ quantity     │
       │         │ timestamps  │
       │         └──────────────┘
       │
       │         ┌──────────────┐
       │         │   orders     │
       │         ├──────────────┤
       │         │ id           │
       │         │ user_id      │
       │         │ order_number │
       │         │ status       │
       │         │ total_amount │
       │         │ timestamps   │
       │         └──────┬───────┘
       │                │
       │                │         ┌──────────────┐
       │                │         │ order_items  │
       │                │         ├──────────────┤
       │                │         │ id           │
       │                │         │ order_id     │
       │                │         │ product_id   │
       │                │         │ quantity     │
       │                │         │ price        │
       │                │         │ subtotal     │
       │                │         │ timestamps   │
       │                │         └──────────────┘
       │                │
       │                │         ┌──────────────┐
       │                └─────────│stock_histories│
       │                          ├──────────────┤
       │                          │ id           │
       │                          │ product_id   │
       │                          │ user_id      │
       │                          │ order_id     │
       │                          │ change       │
       │                          │ stock_before │
       │                          │ stock_after  │
       │                          │ type         │
       │                          │ description  │
       │                          │ timestamps   │
       │                          └──────────────┘
```

### Detail Tabel Database

#### 1. Tabel `users`
Menyimpan data pengguna (admin dan customer)

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT (PK) | Primary key, auto increment |
| name | VARCHAR(255) | Nama lengkap pengguna |
| email | VARCHAR(255) | Email (unique) |
| phone | VARCHAR(255) | Nomor telepon (nullable) |
| email_verified_at | TIMESTAMP | Waktu verifikasi email (nullable) |
| password | VARCHAR(255) | Password yang di-hash |
| role | ENUM | 'admin' atau 'customer' (default: 'customer') |
| remember_token | VARCHAR(100) | Token untuk remember me |
| created_at | TIMESTAMP | Waktu dibuat |
| updated_at | TIMESTAMP | Waktu diupdate |

**Relasi:**
- `hasMany` → orders, carts, stock_histories

---

#### 2. Tabel `categories`
Menyimpan kategori produk

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT (PK) | Primary key, auto increment |
| name | VARCHAR(255) | Nama kategori |
| slug | VARCHAR(255) | URL-friendly name (unique) |
| description | TEXT | Deskripsi kategori (nullable) |
| created_at | TIMESTAMP | Waktu dibuat |
| updated_at | TIMESTAMP | Waktu diupdate |

**Relasi:**
- `hasMany` → products

---

#### 3. Tabel `products`
Menyimpan data produk

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT (PK) | Primary key, auto increment |
| category_id | BIGINT (FK) | Foreign key ke categories |
| name | VARCHAR(255) | Nama produk |
| slug | VARCHAR(255) | URL-friendly name (unique) |
| description | TEXT | Deskripsi produk (nullable) |
| price | DECIMAL(15,2) | Harga produk |
| stock | UNSIGNED INTEGER | Stok tersedia (default: 0) |
| is_active | BOOLEAN | Status aktif/tidak aktif (default: true) |
| image | VARCHAR(255) | Path gambar produk (nullable) |
| created_at | TIMESTAMP | Waktu dibuat |
| updated_at | TIMESTAMP | Waktu diupdate |

**Relasi:**
- `belongsTo` → category
- `hasMany` → order_items, carts, stock_histories

**Index:**
- Foreign key: `category_id` → `categories.id` (CASCADE on update/delete)

---

#### 4. Tabel `carts`
Menyimpan item di keranjang belanja

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT (PK) | Primary key, auto increment |
| user_id | BIGINT (FK) | Foreign key ke users |
| product_id | BIGINT (FK) | Foreign key ke products |
| quantity | UNSIGNED INTEGER | Jumlah produk (default: 1) |
| created_at | TIMESTAMP | Waktu dibuat |
| updated_at | TIMESTAMP | Waktu diupdate |

**Relasi:**
- `belongsTo` → user, product

**Constraint:**
- Unique: `(user_id, product_id)` - Satu user hanya bisa punya satu record per produk di cart

**Index:**
- Foreign key: `user_id` → `users.id` (CASCADE)
- Foreign key: `product_id` → `products.id` (CASCADE)

---

#### 5. Tabel `orders`
Menyimpan data pesanan

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT (PK) | Primary key, auto increment |
| user_id | BIGINT (FK) | Foreign key ke users |
| order_number | VARCHAR(255) | Nomor pesanan (unique) |
| status | ENUM | 'pending', 'paid', 'shipped', 'completed', 'cancelled' (default: 'pending') |
| total_amount | DECIMAL(15,2) | Total harga pesanan (default: 0) |
| created_at | TIMESTAMP | Waktu dibuat |
| updated_at | TIMESTAMP | Waktu diupdate |

**Relasi:**
- `belongsTo` → user
- `hasMany` → order_items, stock_histories

**Index:**
- Foreign key: `user_id` → `users.id` (CASCADE on update, RESTRICT on delete)
- Unique: `order_number`

---

#### 6. Tabel `order_items`
Menyimpan detail item dalam pesanan

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT (PK) | Primary key, auto increment |
| order_id | BIGINT (FK) | Foreign key ke orders |
| product_id | BIGINT (FK) | Foreign key ke products |
| quantity | UNSIGNED INTEGER | Jumlah produk |
| price | DECIMAL(15,2) | Harga saat pesanan dibuat (snapshot) |
| subtotal | DECIMAL(15,2) | quantity × price |
| created_at | TIMESTAMP | Waktu dibuat |
| updated_at | TIMESTAMP | Waktu diupdate |

**Relasi:**
- `belongsTo` → order, product

**Index:**
- Foreign key: `order_id` → `orders.id` (CASCADE)
- Foreign key: `product_id` → `products.id` (CASCADE on update, RESTRICT on delete)

**Catatan:** Field `price` menyimpan snapshot harga saat checkout, sehingga jika harga produk berubah di masa depan, harga di pesanan tetap sama.

---

#### 7. Tabel `stock_histories`
Menyimpan riwayat perubahan stok produk

| Kolom | Tipe Data | Keterangan |
|-------|-----------|------------|
| id | BIGINT (PK) | Primary key, auto increment |
| product_id | BIGINT (FK) | Foreign key ke products |
| user_id | BIGINT (FK) | Foreign key ke users (siapa yang melakukan perubahan) |
| order_id | BIGINT (FK) | Foreign key ke orders (nullable, jika perubahan karena order) |
| change | INTEGER | Perubahan stok (bisa positif atau negatif) |
| stock_before | INTEGER | Stok sebelum perubahan |
| stock_after | INTEGER | Stok setelah perubahan |
| type | ENUM | 'in' (masuk), 'out' (keluar), 'adjustment' (penyesuaian) |
| description | VARCHAR(255) | Keterangan perubahan (nullable) |
| created_at | TIMESTAMP | Waktu dibuat |
| updated_at | TIMESTAMP | Waktu diupdate |

**Relasi:**
- `belongsTo` → product, user, order

**Index:**
- Foreign key: `product_id` → `products.id` (CASCADE)
- Foreign key: `user_id` → `users.id` (CASCADE on update, RESTRICT on delete)
- Foreign key: `order_id` → `orders.id` (CASCADE on update, NULL on delete)

**Use Case:**
- Saat admin menambah stok manual → type: 'in'
- Saat customer checkout → type: 'out', order_id terisi
- Saat admin adjust stok → type: 'adjustment'

---

## ✨ Fitur Aplikasi

### Fitur Customer (Pembeli)

1. **Autentikasi**
   - Register akun baru
   - Login/Logout
   - Update profil
   - Ganti password

2. **Browsing Produk**
   - Lihat daftar produk
   - Filter berdasarkan kategori
   - Lihat detail produk

3. **Keranjang Belanja**
   - Tambah produk ke keranjang
   - Update jumlah produk
   - Hapus produk dari keranjang
   - Lihat total harga

4. **Checkout & Pemesanan**
   - Proses checkout
   - Lihat daftar pesanan
   - Lihat detail pesanan
   - Batalkan pesanan (jika status pending)

### Fitur Admin

1. **Dashboard**
   - Overview statistik penjualan
   - Grafik dan laporan

2. **Manajemen Produk**
   - Tambah produk baru
   - Edit produk
   - Hapus produk
   - Upload gambar produk
   - Tambah stok produk

3. **Manajemen Pesanan**
   - Lihat semua pesanan
   - Update status pesanan (pending → paid → shipped → completed)
   - Lihat detail pesanan

4. **Manajemen Stok**
   - Lihat riwayat perubahan stok
   - Track perubahan stok per produk
   - Lihat perubahan stok berdasarkan order

---

## 📁 Struktur Proyek

```
ecommerce/
├── app/
│   ├── Console/              # Artisan commands
│   ├── Exceptions/           # Exception handlers
│   ├── Http/
│   │   ├── Controllers/      # Controller files
│   │   │   ├── Admin/        # Admin controllers
│   │   │   └── Customer/     # Customer controllers
│   │   ├── Middleware/       # Custom middleware
│   │   ├── Requests/         # Form request validation
│   │   └── Resources/        # API resources
│   ├── Models/               # Eloquent models
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Cart.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   └── StockHistory.php
│   └── Providers/            # Service providers
├── bootstrap/                # Bootstrap files
├── config/                   # Configuration files
├── database/
│   ├── factories/           # Model factories
│   ├── migrations/          # Database migrations
│   └── seeders/             # Database seeders
├── public/                   # Public assets (web root)
│   ├── index.php            # Entry point
│   └── storage/              # Storage symlink
├── resources/
│   ├── css/                 # CSS files
│   ├── js/                  # JavaScript files
│   └── views/               # Blade templates
│       ├── admin/           # Admin views
│       ├── auth/            # Auth views
│       ├── cart/            # Cart views
│       ├── checkout/        # Checkout views
│       ├── orders/          # Order views
│       ├── profile/         # Profile views
│       └── shop/            # Shop views
├── routes/
│   ├── api.php              # API routes
│   └── web.php              # Web routes
├── storage/                  # Storage files
│   ├── app/                 # App storage
│   │   └── public/          # Public storage (images)
│   └── logs/                # Log files
├── tests/                    # Test files
├── vendor/                   # Composer dependencies
├── .env                      # Environment configuration
├── .env.example              # Environment template
├── composer.json             # PHP dependencies
├── package.json              # Node.js dependencies
├── vite.config.js            # Vite configuration
└── artisan                   # Laravel CLI
```

---

## 🎯 Menjalankan Aplikasi

### Development Mode

1. **Jalankan Vite (untuk compile assets)**:
   ```bash
   npm run dev
   ```
   Biarkan terminal ini tetap berjalan.

2. **Jalankan Laravel Server** (terminal baru):
   ```bash
   php artisan serve
   ```

3. **Akses aplikasi**:
   - Customer: http://localhost:8000
   - Admin: http://localhost:8000/admin/login

### Production Mode

1. **Optimize autoloader**:
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

2. **Compile assets**:
   ```bash
   npm run build
   ```

3. **Cache configuration**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Set environment**:
   Pastikan `APP_ENV=production` dan `APP_DEBUG=false` di file `.env`

---

## 🔐 Default Credentials (Setelah Seeding)

**Admin:**
- Email: `admin@example.com`
- Password: `password`

**Customer:**
- Email: `customer@example.com`
- Password: `password`

**⚠️ PENTING:** Ganti password default setelah pertama kali login!

---

## 📝 Catatan Penting

1. **Storage Link**: Pastikan sudah menjalankan `php artisan storage:link` agar gambar produk dapat diakses.

2. **File Permissions** (Linux/Mac):
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

3. **CORS**: Jika menggunakan API dari frontend terpisah, pastikan konfigurasi CORS di `config/cors.php` sudah benar.

4. **Session Driver**: Default menggunakan `file`. Untuk production, pertimbangkan menggunakan `database` atau `redis`.

5. **Queue**: Untuk fitur async (jika ada), jalankan queue worker:
   ```bash
   php artisan queue:work
   ```

---

## 🐛 Troubleshooting

### Error: "SQLSTATE[HY000] [1045] Access denied"
- **Solusi**: Periksa username dan password database di file `.env`

### Error: "SQLSTATE[42S02] Base table or view not found"
- **Solusi**: Jalankan `php artisan migrate` untuk membuat tabel

### Error: "The stream or file could not be opened"
- **Solusi**: Pastikan folder `storage/logs` dan `storage/framework` memiliki permission write

### Error: "Vite manifest not found"
- **Solusi**: Jalankan `npm run dev` atau `npm run build`

### Error: "Storage link tidak berfungsi"
- **Solusi**: Hapus link lama dan buat ulang:
  ```bash
  rm public/storage
  php artisan storage:link
  ```

---

## 📚 Referensi

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Vite Documentation](https://vitejs.dev/)

---

## 👨‍💻 Author

Dokumentasi ini dibuat untuk keperluan presentasi proyek E-Commerce Laravel.

**Selamat Presentasi! 🎉**


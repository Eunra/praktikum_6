# Sistem CRUD dengan PHP Native

Sistem manajemen barang dan transaksi yang dibangun menggunakan PHP native dengan fitur CRUD lengkap.

## Fitur Utama

- **Sistem Login & Session Management**

  - Login dengan username dan password
  - Role-based access (Admin & Kasir)
  - Session management yang aman

- **Manajemen Barang (CRUD)**

  - Tambah, edit, hapus data barang
  - Informasi lengkap: kode, nama, harga beli/jual, kategori, deskripsi
  - Validasi data input

- **Sistem Transaksi**

  - Pembuatan transaksi penjualan
  - Multiple item dalam satu transaksi
  - Perhitungan otomatis total, kembalian
  - Update stok otomatis saat transaksi

- **Manajemen Stok**

  - Monitoring stok barang real-time
  - Alert stok rendah
  - Update stok masuk (hanya admin)
  - Riwayat pergerakan stok

- **Laporan Penjualan**
  - Filter berdasarkan tanggal
  - Grafik penjualan harian
  - Produk terlaris
  - Export ke Excel
  - Fitur cetak laporan

## Teknologi yang Digunakan

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: Bootstrap 5 (CDN)
- **Charts**: Chart.js
- **Icons**: Bootstrap Icons

## Struktur Database

### Tabel `users`

- `id` (Primary Key)
- `username` (Unique)
- `password` (MD5 Hash)
- `nama_lengkap`
- `role` (admin/kasir)
- `created_at`

### Tabel `barang`

- `id` (Primary Key)
- `kode_barang` (Unique)
- `nama_barang`
- `harga_beli`
- `harga_jual`
- `kategori`
- `deskripsi`
- `created_at`, `updated_at`

### Tabel `stok_barang`

- `id` (Primary Key)
- `barang_id` (Foreign Key)
- `stok_awal`
- `stok_masuk`
- `stok_keluar`
- `stok_saat_ini`
- `tanggal_update`

### Tabel `transaksi`

- `id` (Primary Key)
- `kode_transaksi` (Unique)
- `tanggal_transaksi`
- `total_harga`
- `jumlah_bayar`
- `kembalian`
- `user_id` (Foreign Key)
- `created_at`

### Tabel `detail_transaksi`

- `id` (Primary Key)
- `transaksi_id` (Foreign Key)
- `barang_id` (Foreign Key)
- `jumlah`
- `harga_satuan`
- `subtotal`

## Instalasi

1. **Clone atau download proyek**

   ```bash
   git clone [repository-url]
   cd crud_saya
   ```

2. **Setup Database**

   - Buat database MySQL dengan nama `crud_system`
   - Import file `database.sql` untuk membuat tabel dan data awal
   - Sesuaikan konfigurasi database di `config/database.php`

3. **Konfigurasi Database**
   Edit file `config/database.php`:

   ```php
   private $host = 'localhost';
   private $db_name = 'crud_system';
   private $username = 'root';
   private $password = '';
   ```

4. **Setup Web Server**

   - Pastikan PHP 7.4+ dan MySQL terinstall
   - Setup virtual host atau gunakan built-in PHP server:

   ```bash
   php -S localhost:8000
   ```

5. **Akses Aplikasi**
   - Buka browser dan akses `http://localhost:8000`
   - Login dengan akun default:
     - **Admin**: username `admin`, password `admin123`
     - **Kasir**: username `kasir1`, password `kasir123`

## Struktur File

```
crud_saya/
├── config/
│   ├── database.php      # Konfigurasi koneksi database
│   └── session.php       # Fungsi session management
├── includes/
│   └── navbar.php        # Navigation bar
├── database.sql          # Script database
├── index.php             # Halaman utama (redirect)
├── login.php             # Halaman login
├── dashboard.php         # Dashboard utama
├── barang.php            # CRUD barang
├── transaksi.php         # Buat transaksi
├── transaksi_detail.php  # Detail transaksi
├── stok.php              # Manajemen stok (admin only)
├── laporan.php           # Laporan penjualan
└── logout.php            # Logout
```

## Fitur Keamanan

- **Password Hashing**: Menggunakan MD5 (untuk demo, production gunakan bcrypt)
- **Session Management**: Session timeout dan validasi
- **Role-based Access**: Admin dan Kasir memiliki akses berbeda
- **Input Validation**: Validasi input form
- **SQL Injection Prevention**: Menggunakan prepared statements

## Demo Account

### Admin Account

- **Username**: `admin`
- **Password**: `admin123`
- **Akses**: Semua fitur termasuk manajemen stok

### Kasir Account

- **Username**: `kasir1`
- **Password**: `kasir123`
- **Akses**: Dashboard, barang, transaksi, laporan

## Cara Penggunaan

1. **Login** dengan akun yang tersedia
2. **Dashboard** menampilkan statistik dan transaksi terbaru
3. **Data Barang** untuk mengelola master data barang
4. **Transaksi** untuk membuat penjualan baru
5. **Stok Barang** (admin) untuk monitoring dan update stok
6. **Laporan** untuk melihat laporan penjualan dengan filter tanggal

## Pengembangan Lebih Lanjut

- Implementasi password hashing yang lebih aman (bcrypt)
- Tambah fitur backup database
- Implementasi API untuk mobile app
- Tambah fitur multi-currency
- Implementasi sistem inventory yang lebih advanced
- Tambah fitur laporan keuangan yang lebih detail

## Lisensi

Proyek ini dibuat untuk keperluan pembelajaran dan dapat digunakan secara bebas.

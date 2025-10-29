-- Database untuk sistem CRUD dengan PHP Native
-- Membuat database
CREATE DATABASE IF NOT EXISTS crud_system;
USE crud_system;

-- Tabel user untuk login
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') DEFAULT 'kasir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel barang
CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(20) NOT NULL UNIQUE,
    nama_barang VARCHAR(100) NOT NULL,
    harga_beli DECIMAL(10,2) NOT NULL,
    harga_jual DECIMAL(10,2) NOT NULL,
    kategori VARCHAR(50),
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel stok barang
CREATE TABLE IF NOT EXISTS stok_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT NOT NULL,
    stok_awal INT DEFAULT 0,
    stok_masuk INT DEFAULT 0,
    stok_keluar INT DEFAULT 0,
    stok_saat_ini INT DEFAULT 0,
    tanggal_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE
);

-- Tabel transaksi
CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(20) NOT NULL UNIQUE,
    tanggal_transaksi DATE NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    jumlah_bayar DECIMAL(10,2) NOT NULL,
    kembalian DECIMAL(10,2) NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabel detail transaksi
CREATE TABLE IF NOT EXISTS detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    barang_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id)
);

-- Insert data user default
INSERT INTO users (username, password, nama_lengkap, role) VALUES 
('admin', MD5('admin123'), 'Administrator', 'admin'),
('kasir1', MD5('kasir123'), 'Kasir Pertama', 'kasir');

-- Insert data barang contoh
INSERT INTO barang (kode_barang, nama_barang, harga_beli, harga_jual, kategori, deskripsi) VALUES 
('BRG001', 'Laptop Asus', 8000000, 9500000, 'Elektronik', 'Laptop Asus Core i5'),
('BRG002', 'Mouse Wireless', 150000, 200000, 'Aksesoris', 'Mouse wireless dengan baterai'),
('BRG003', 'Keyboard Mechanical', 500000, 650000, 'Aksesoris', 'Keyboard mechanical RGB'),
('BRG004', 'Monitor 24 inch', 2500000, 3000000, 'Elektronik', 'Monitor LED 24 inch Full HD');

-- Insert data stok barang
INSERT INTO stok_barang (barang_id, stok_awal, stok_masuk, stok_saat_ini) VALUES 
(1, 10, 10, 10),
(2, 50, 50, 50),
(3, 25, 25, 25),
(4, 15, 15, 15);

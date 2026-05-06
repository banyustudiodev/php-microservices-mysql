1 - Buat folder utama dengan nama:
php-microservices-mysql

2 - Kemudian buat struktur folder seperti berikut:

php-microservices-mysql/
│
├── api-gateway/
│   └── index.php
│
├── user-service/
│   ├── db.php
│   └── index.php
│
├── product-service/
│   ├── db.php
│   └── index.php
│
└── order-service/
    ├── db.php
    └── index.php

3 - Penjelasan struktur:
| Folder          | Keterangan                              |
| --------------- | --------------------------------------- |
| api-gateway     | Mengatur request dari client ke service |
| user-service    | Mengelola data user                     |
| product-service | Mengelola data produk                   |
| order-service   | Mengelola data pesanan                  |

4- Pembuatan Database
Masuk ke phpMyAdmin atau MySQL terminal.
Buat tiga database berikut:

CREATE DATABASE db_user_service;
CREATE DATABASE db_product_service;
CREATE DATABASE db_order_service;

5- Pembuatan Tabel User Service
Gunakan database db_user_service.

USE db_user_service;

Buat tabel users.

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

Masukkan data awal.

INSERT INTO users (name, email) VALUES
('Budi Santoso', 'budi@example.com'),
('Siti Aminah', 'siti@example.com');

Cek data:
SELECT * FROM users;

6 - Pembuatan Tabel Product Service

Gunakan database db_product_service.
USE db_product_service;

Buat tabel products.

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

Masukkan data awal.

INSERT INTO products (name, price, stock) VALUES
('Laptop Lenovo ThinkPad', 8500000, 10),
('Mouse Wireless Logitech', 175000, 50),
('Keyboard Mechanical', 450000, 25);

7 - Pembuatan Tabel Order Service

Gunakan database db_order_service.

USE db_order_service;

Buat tabel orders.

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

Masukkan data awal.

INSERT INTO orders (user_id, product_id, quantity, status) VALUES
(1, 1, 1, 'PAID'),
(2, 3, 2, 'PENDING');

Cek data:

SELECT * FROM orders;

Catatan penting:

Pada microservices, tabel orders tidak menggunakan foreign key langsung ke tabel users atau products, karena tabel tersebut berada pada database service lain. Order Service hanya menyimpan user_id dan product_id sebagai referensi.
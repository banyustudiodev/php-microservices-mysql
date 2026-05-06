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

Pembuatan Tabel User Service
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
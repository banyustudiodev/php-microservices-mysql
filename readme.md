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

8 -  Membuat User Service

file kode untuk: user-service/db.php

<?php

$host = "localhost";
$dbname = "db_user_service";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Koneksi database User Service gagal",
        "error" => $e->getMessage()
    ]);
    exit;
}

file kode untuk : user-service/index.php

<?php

header("Content-Type: application/json");

require_once "db.php";

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($method === "GET" && $path === "/users") {
    $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "User Service",
        "data" => $users
    ]);
    exit;
}

if ($method === "GET" && $path === "/users/detail") {
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            "message" => "Parameter id wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "message" => "User tidak ditemukan"
        ]);
        exit;
    }

    echo json_encode([
        "service" => "User Service",
        "data" => $user
    ]);
    exit;
}

if ($method === "POST" && $path === "/users") {
    $input = json_decode(file_get_contents("php://input"), true);

    $name = $input["name"] ?? null;
    $email = $input["email"] ?? null;

    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode([
            "message" => "Name dan email wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
    $stmt->execute([$name, $email]);

    http_response_code(201);
    echo json_encode([
        "message" => "User berhasil dibuat",
        "data" => [
            "id" => $pdo->lastInsertId(),
            "name" => $name,
            "email" => $email
        ]
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    "message" => "Endpoint User Service tidak ditemukan"
]);

9 - Jalankan userservices pada Terminal 

cd user-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8001


Uji melalui browser atau Thunder Client:
GET http://localhost:8001/users
GET http://localhost:8001/users/detail?id=1

Contoh pengujian POST:
POST http://localhost:8001/users

Body JSON:
{
  "name": "Andi Pratama",
  "email": "andi@example.com"
}

10 - Membuat Product Service

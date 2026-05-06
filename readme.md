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

edit product-service/db.php

Isi kode berikut:

<?php

$host = "localhost";
$dbname = "db_product_service";
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
        "message" => "Koneksi database Product Service gagal",
        "error" => $e->getMessage()
    ]);
    exit;
}

edit product-service/index.php

Isi kode berikut:

<?php

header("Content-Type: application/json");

require_once "db.php";

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($method === "GET" && $path === "/products") {
    $stmt = $pdo->query("SELECT id, name, price, stock, created_at FROM products ORDER BY id ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "Product Service",
        "data" => $products
    ]);
    exit;
}

if ($method === "GET" && $path === "/products/detail") {
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            "message" => "Parameter id wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, price, stock, created_at FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            "message" => "Produk tidak ditemukan"
        ]);
        exit;
    }

    echo json_encode([
        "service" => "Product Service",
        "data" => $product
    ]);
    exit;
}

if ($method === "POST" && $path === "/products") {
    $input = json_decode(file_get_contents("php://input"), true);

    $name = $input["name"] ?? null;
    $price = $input["price"] ?? null;
    $stock = $input["stock"] ?? 0;

    if (!$name || !$price) {
        http_response_code(400);
        echo json_encode([
            "message" => "Name dan price wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, price, stock) VALUES (?, ?, ?)");
    $stmt->execute([$name, $price, $stock]);

    http_response_code(201);
    echo json_encode([
        "message" => "Produk berhasil dibuat",
        "data" => [
            "id" => $pdo->lastInsertId(),
            "name" => $name,
            "price" => $price,
            "stock" => $stock
        ]
    ]);
    exit;
}

if ($method === "POST" && $path === "/products/reduce-stock") {
    $input = json_decode(file_get_contents("php://input"), true);

    $productId = $input["product_id"] ?? null;
    $quantity = $input["quantity"] ?? null;

    if (!$productId || !$quantity) {
        http_response_code(400);
        echo json_encode([
            "message" => "product_id dan quantity wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode([
            "message" => "Produk tidak ditemukan"
        ]);
        exit;
    }

    if ($product["stock"] < $quantity) {
        http_response_code(400);
        echo json_encode([
            "message" => "Stok tidak mencukupi"
        ]);
        exit;
    }

    $newStock = $product["stock"] - $quantity;

    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$newStock, $productId]);

    echo json_encode([
        "message" => "Stok berhasil dikurangi",
        "data" => [
            "product_id" => $productId,
            "old_stock" => $product["stock"],
            "new_stock" => $newStock
        ]
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    "message" => "Endpoint Product Service tidak ditemukan"
]);

11 - Menjalankan Product Service

Pada terminal
cd product-service
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8002

Uji endpoint:

GET http://localhost:8002/products
GET http://localhost:8002/products/detail?id=1
POST http://localhost:8002/products

Body JSON:

{
  "name": "Monitor 24 Inch",
  "price": 1600000,
  "stock": 12
}

Uji pengurangan stok:

POST http://localhost:8002/products/reduce-stock

Body JSON:

{
  "product_id": 1,
  "quantity": 2
}

12 - Membuat Order Service
Order Service akan memvalidasi user dan produk melalui API.

Artinya, Order Service tidak membaca langsung db_user_service atau db_product_service.

ubah file order-service/db.php

Isi kode berikut:

<?php

$host = "localhost";
$dbname = "db_order_service";
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
        "message" => "Koneksi database Order Service gagal",
        "error" => $e->getMessage()
    ]);
    exit;
}

update order-service/index.php

Isi kode berikut:

<?php

header("Content-Type: application/json");

require_once "db.php";

function callService($url, $method = "GET", $data = null)
{
    $options = [
        "http" => [
            "method" => $method,
            "header" => "Content-Type: application/json\r\n",
            "ignore_errors" => true
        ]
    ];

    if ($data !== null) {
        $options["http"]["content"] = json_encode($data);
    }

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    return json_decode($response, true);
}

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if ($method === "GET" && $path === "/orders") {
    $stmt = $pdo->query("SELECT id, user_id, product_id, quantity, status, created_at FROM orders ORDER BY id ASC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "service" => "Order Service",
        "data" => $orders
    ]);
    exit;
}

if ($method === "GET" && $path === "/orders/detail") {
    $id = $_GET["id"] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            "message" => "Parameter id wajib diisi"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, user_id, product_id, quantity, status, created_at FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            "message" => "Order tidak ditemukan"
        ]);
        exit;
    }

    $userResponse = callService("http://localhost:8001/users/detail?id=" . $order["user_id"]);
    $productResponse = callService("http://localhost:8002/products/detail?id=" . $order["product_id"]);

    echo json_encode([
        "service" => "Order Service",
        "data" => [
            "order" => $order,
            "user" => $userResponse["data"] ?? null,
            "product" => $productResponse["data"] ?? null
        ]
    ]);
    exit;
}

if ($method === "POST" && $path === "/orders") {
    $input = json_decode(file_get_contents("php://input"), true);

    $userId = $input["user_id"] ?? null;
    $productId = $input["product_id"] ?? null;
    $quantity = $input["quantity"] ?? null;

    if (!$userId || !$productId || !$quantity) {
        http_response_code(400);
        echo json_encode([
            "message" => "user_id, product_id, dan quantity wajib diisi"
        ]);
        exit;
    }

    $userResponse = callService("http://localhost:8001/users/detail?id=" . $userId);

    if (!$userResponse || !isset($userResponse["data"])) {
        http_response_code(400);
        echo json_encode([
            "message" => "User tidak valid atau User Service tidak dapat diakses"
        ]);
        exit;
    }

    $productResponse = callService("http://localhost:8002/products/detail?id=" . $productId);

    if (!$productResponse || !isset($productResponse["data"])) {
        http_response_code(400);
        echo json_encode([
            "message" => "Produk tidak valid atau Product Service tidak dapat diakses"
        ]);
        exit;
    }

    $product = $productResponse["data"];

    if ($product["stock"] < $quantity) {
        http_response_code(400);
        echo json_encode([
            "message" => "Stok produk tidak mencukupi"
        ]);
        exit;
    }

    $reduceStockResponse = callService(
        "http://localhost:8002/products/reduce-stock",
        "POST",
        [
            "product_id" => $productId,
            "quantity" => $quantity
        ]
    );

    if (!$reduceStockResponse || !isset($reduceStockResponse["data"])) {
        http_response_code(500);
        echo json_encode([
            "message" => "Order gagal karena stok produk tidak dapat diperbarui"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, product_id, quantity, status)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([$userId, $productId, $quantity, "PENDING"]);

    http_response_code(201);
    echo json_encode([
        "message" => "Order berhasil dibuat",
        "data" => [
            "id" => $pdo->lastInsertId(),
            "user" => $userResponse["data"],
            "product" => $product,
            "quantity" => $quantity,
            "status" => "PENDING",
            "stock_update" => $reduceStockResponse["data"]
        ]
    ]);
    exit;
}

if ($method === "POST" && $path === "/orders/update-status") {
    $input = json_decode(file_get_contents("php://input"), true);

    $orderId = $input["order_id"] ?? null;
    $status = $input["status"] ?? null;

    if (!$orderId || !$status) {
        http_response_code(400);
        echo json_encode([
            "message" => "order_id dan status wajib diisi"
        ]);
        exit;
    }

    $allowedStatus = ["PENDING", "PAID", "CANCELLED", "SHIPPED"];

    if (!in_array($status, $allowedStatus)) {
        http_response_code(400);
        echo json_encode([
            "message" => "Status tidak valid",
            "allowed_status" => $allowedStatus
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            "message" => "Order tidak ditemukan"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    echo json_encode([
        "message" => "Status order berhasil diperbarui",
        "data" => [
            "order_id" => $orderId,
            "status" => $status
        ]
    ]);
    exit;
}

http_response_code(404);
echo json_encode([
    "message" => "Endpoint Order Service tidak ditemukan"
]);
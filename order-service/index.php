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
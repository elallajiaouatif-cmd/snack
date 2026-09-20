<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');
    $total = floatval($data['total'] ?? 0);
    $payment_method = trim($data['payment_method'] ?? '');
    $items = trim($data['items'] ?? '');

    if (!empty($name) && !empty($phone) && !empty($address) && $total > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, total_price, payment_method, items_details) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $name, $phone, $address, $total, $payment_method, $items])) {
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['success' => false]);
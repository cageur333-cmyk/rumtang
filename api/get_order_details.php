<?php
// api/get_order_details.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$pdo = Database::getConnection();
Auth::requireLogin($pdo, true);

try {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception("ID order tidak valid.");
    }

    $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmtOrder->execute([':id' => $id]);
    $order = $stmtOrder->fetch();
    if (!$order) {
        throw new Exception("Order tidak ditemukan.");
    }

    $stmtItems = $pdo->prepare("
        SELECT oi.*, i.name as item_name, c.name as category_name 
        FROM order_items oi
        JOIN items i ON oi.item_id = i.id
        JOIN categories c ON i.category_id = c.id
        WHERE oi.order_id = :id
        ORDER BY oi.id ASC
    ");
    $stmtItems->execute([':id' => $id]);
    $items = $stmtItems->fetchAll();

    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}


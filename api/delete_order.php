<?php
// api/delete_order.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$pdo = Database::getConnection();

Auth::requireAdmin($pdo);
Auth::requirePostWithCsrf($pdo, false, '/order.php');

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception("ID order tidak valid.");
    }

    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
    $stmt->execute([':id' => $id]);

    Auth::setFlash($pdo, 'success', "Order berhasil dihapus.");
    header("Location: " . Auth::url('/order.php'));
    exit;

} catch (Exception $e) {
    Auth::setFlash($pdo, 'error', $e->getMessage());
    header("Location: " . Auth::url('/order.php'));
    exit;
}


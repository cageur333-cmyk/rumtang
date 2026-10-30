<?php
// api/delete_order.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

try {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception("ID order tidak valid.");
    }

    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $_SESSION['flash_success'] = "Order berhasil dihapus.";
    header("Location: ../order.php");
    exit;

} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: ../order.php");
    exit;
}


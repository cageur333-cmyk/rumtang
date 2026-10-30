<?php
// api/delete_transaction.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

try {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception("ID transaksi tidak valid.");
    }

    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $msg = "Transaksi #TRX-" . str_pad($id, 4, '0', STR_PAD_LEFT) . " berhasil dihapus.";

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['flash_success'] = $msg;
    $redirectUrl = !empty($_GET['redirect_to']) ? $_GET['redirect_to'] : '../transaksi.php';
    header("Location: " . $redirectUrl);
    exit;

} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: ../transaksi.php");
    exit;
}


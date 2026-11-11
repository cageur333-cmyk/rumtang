<?php
// api/delete_transaction.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$pdo = Database::getConnection();

Auth::requireAdmin($pdo);
$redirectUrl = Auth::safeRedirect($_POST['redirect_to'] ?? '', '/transaksi.php');
Auth::requirePostWithCsrf($pdo, false, '/transaksi.php');

$isAjax = Auth::isAjaxRequest();

try {
    $id = (int)($_POST['id'] ?? 0);
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

    Auth::setFlash($pdo, 'success', $msg);
    header("Location: " . $redirectUrl);
    exit;

} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    Auth::setFlash($pdo, 'error', $e->getMessage());
    header("Location: " . Auth::url('/transaksi.php'));
    exit;
}


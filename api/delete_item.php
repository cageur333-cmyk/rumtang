<?php
// api/delete_item.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$pdo = Database::getConnection();

Auth::requireAdmin($pdo);
Auth::requirePostWithCsrf($pdo, false, '/inventaris.php');

try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        throw new Exception("ID barang tidak valid.");
    }

    // Check if item has transactions
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE item_id = :id");
    $stmtCheck->execute([':id' => $id]);
    $txCount = $stmtCheck->fetchColumn();

    if ($txCount > 0) {
        throw new Exception("Barang tidak dapat dihapus karena sudah memiliki {$txCount} riwayat transaksi. Untuk menjaga keutuhan laporan, barang yang sudah bertransaksi tidak boleh dihapus.");
    }

    $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
    $stmt->execute([':id' => $id]);

    Auth::setFlash($pdo, 'success', "Barang berhasil dihapus.");
    header("Location: " . Auth::url('/inventaris.php'));
    exit;

} catch (Exception $e) {
    Auth::setFlash($pdo, 'error', $e->getMessage());
    header("Location: " . Auth::url('/inventaris.php'));
    exit;
}


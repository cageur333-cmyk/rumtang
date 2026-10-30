<?php
// api/delete_item.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

try {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
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

    $_SESSION['flash_success'] = "Barang berhasil dihapus.";
    header("Location: ../inventaris.php");
    exit;

} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: ../inventaris.php");
    exit;
}


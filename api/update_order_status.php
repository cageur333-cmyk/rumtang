<?php
// api/update_order_status.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request tidak diizinkan.");
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = strtoupper(trim($_POST['status'] ?? ''));
    $autoKeluar = !empty($_POST['auto_keluar']) && $_POST['auto_keluar'] == '1';

    if ($orderId <= 0 || !in_array($newStatus, ['PENDING', 'DISETUJUI', 'DITOLAK', 'SELESAI'])) {
        throw new Exception("Parameter update order tidak valid.");
    }

    $pdo->beginTransaction();

    // Fetch order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        throw new Exception("Order tidak ditemukan.");
    }

    // Update order status
    $stmtUp = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
    $stmtUp->execute([':status' => $newStatus, ':id' => $orderId]);

    // If marked SELESAI or autoKeluar checked, automatically create KELUAR transactions!
    $issuedCount = 0;
    if ($newStatus === 'SELESAI' && $autoKeluar) {
        $stmtItems = $pdo->prepare("
            SELECT oi.*, i.name as item_name 
            FROM order_items oi 
            JOIN items i ON oi.item_id = i.id 
            WHERE oi.order_id = :order_id
        ");
        $stmtItems->execute([':order_id' => $orderId]);
        $orderItems = $stmtItems->fetchAll();

        $stmtTrans = $pdo->prepare("
            INSERT INTO transactions (trans_no, tanggal, jenis, item_id, jumlah, satuan, penerima_pemohon, keperluan_lokasi, keterangan)
            VALUES (:trans_no, :tanggal, 'KELUAR', :item_id, :jumlah, :satuan, :penerima, :lokasi, :keterangan)
        ");

        $today = date('Y-m-d');
        foreach ($orderItems as $item) {
            $maxId = (int)$pdo->query("SELECT MAX(id) FROM transactions")->fetchColumn();
            $transNo = sprintf("TRX-%04d", $maxId + 1);

            $stmtTrans->execute([
                ':trans_no' => $transNo,
                ':tanggal' => $today,
                ':item_id' => $item['item_id'],
                ':jumlah' => $item['jumlah'],
                ':satuan' => $item['satuan'],
                ':penerima' => $order['pemohon'],
                ':lokasi' => $order['lokasi_tujuan'],
                ':keterangan' => "Realisasi Order #{$order['order_no']} " . ($item['keterangan'] ? "({$item['keterangan']})" : ""),
            ]);
            $issuedCount++;
        }
    }

    $pdo->commit();

    $msg = "Status order #{$order['order_no']} berhasil diubah menjadi {$newStatus}!";
    if ($issuedCount > 0) {
        $msg .= " Otomatis mencatat {$issuedCount} transaksi Barang Keluar.";
    }

    $_SESSION['flash_success'] = $msg;
    header("Location: ../order.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: ../order.php");
    exit;
}


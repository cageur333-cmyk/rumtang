<?php
// api/save_order.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request tidak diizinkan.");
    }

    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $pemohon = trim($_POST['pemohon'] ?? '');
    $lokasi = trim($_POST['lokasi_tujuan'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');
    
    $itemIds = $_POST['item_ids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $satuans = $_POST['satuans'] ?? [];
    $keterangans = $_POST['keterangans'] ?? [];

    if (empty($pemohon)) {
        throw new Exception("Nama pemohon order wajib diisi.");
    }
    if (empty($lokasi)) {
        throw new Exception("Lokasi tujuan order wajib diisi.");
    }
    if (empty($itemIds) || !is_array($itemIds) || count($itemIds) === 0) {
        throw new Exception("Pilih minimal satu barang yang ingin diorder.");
    }

    $pdo->beginTransaction();

    // Generate Order No e.g. ORD-202610-002
    $yearMonth = date('Ym', strtotime($tanggal));
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_no LIKE :pattern");
    $stmtCount->execute([':pattern' => "ORD-{$yearMonth}-%"]);
    $orderCount = (int)$stmtCount->fetchColumn() + 1;
    $orderNo = sprintf("ORD-%s-%03d", $yearMonth, $orderCount);

    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (order_no, tanggal, pemohon, lokasi_tujuan, status, catatan)
        VALUES (:order_no, :tanggal, :pemohon, :lokasi_tujuan, 'PENDING', :catatan)
    ");
    $stmtOrder->execute([
        ':order_no' => $orderNo,
        ':tanggal' => $tanggal,
        ':pemohon' => $pemohon,
        ':lokasi_tujuan' => $lokasi,
        ':catatan' => $catatan
    ]);
    $orderId = $pdo->lastInsertId();

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, item_id, jumlah, satuan, keterangan)
        VALUES (:order_id, :item_id, :jumlah, :satuan, :keterangan)
    ");

    $validItemCount = 0;
    for ($i = 0; $i < count($itemIds); $i++) {
        $itemId = (int)$itemIds[$i];
        $qty = (int)($quantities[$i] ?? 0);
        $sat = trim($satuans[$i] ?? 'PCS');
        $ket = trim($keterangans[$i] ?? '');

        if ($itemId > 0 && $qty > 0) {
            $stmtItem->execute([
                ':order_id' => $orderId,
                ':item_id' => $itemId,
                ':jumlah' => $qty,
                ':satuan' => $sat,
                ':keterangan' => $ket
            ]);
            $validItemCount++;
        }
    }

    if ($validItemCount === 0) {
        throw new Exception("Semua barang yang dipilih memiliki jumlah 0 atau tidak valid.");
    }

    $pdo->commit();

    $_SESSION['flash_success'] = "Order #{$orderNo} berhasil dibuat dengan {$validItemCount} item barang!";
    header("Location: ../order.php");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: ../order.php?action=new");
    exit;
}


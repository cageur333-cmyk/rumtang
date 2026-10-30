<?php
// api/save_transaction.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request tidak diizinkan.");
    }

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $jenis = strtoupper(trim($_POST['jenis'] ?? ''));
    $itemId = (int)($_POST['item_id'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? 'PCS');
    $penerima = trim($_POST['penerima_pemohon'] ?? '');
    $lokasi = trim($_POST['keperluan_lokasi'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($tanggal) || !in_array($jenis, ['MASUK', 'KELUAR'])) {
        throw new Exception("Tanggal dan jenis transaksi (MASUK/KELUAR) harus valid.");
    }
    if ($itemId <= 0) {
        throw new Exception("Silakan pilih barang yang valid.");
    }
    if ($jumlah <= 0) {
        throw new Exception("Jumlah (Qty) barang harus lebih dari 0.");
    }
    if (empty($penerima)) {
        throw new Exception("Penerima / Pemohon wajib diisi.");
    }
    if (empty($lokasi)) {
        throw new Exception("Lokasi / Keperluan wajib diisi.");
    }

    // Verify item exists and get current stock
    $stmtItem = $pdo->prepare("SELECT * FROM v_inventory_stock WHERE id = :id");
    $stmtItem->execute([':id' => $itemId]);
    $item = $stmtItem->fetch();
    if (!$item) {
        throw new Exception("Data barang tidak ditemukan di sistem.");
    }

    // Check stock if KELUAR
    if ($jenis === 'KELUAR') {
        $currentStock = (int)$item['stok_akhir'];
        // If editing an existing transaction, adjust check
        if ($id) {
            $stmtOld = $pdo->prepare("SELECT jenis, jumlah FROM transactions WHERE id = :id");
            $stmtOld->execute([':id' => $id]);
            $oldTx = $stmtOld->fetch();
            if ($oldTx && $oldTx['jenis'] === 'KELUAR') {
                $currentStock += (int)$oldTx['jumlah'];
            }
        }

        if ($jumlah > $currentStock) {
            throw new Exception("Stok tidak mencukupi! Stok saat ini untuk '{$item['name']}' adalah {$currentStock} {$item['satuan']}, tidak dapat mengeluarkan {$jumlah} {$satuan}.");
        }
    }

    if ($id) {
        // Update existing transaction
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET tanggal = :tanggal, jenis = :jenis, item_id = :item_id, jumlah = :jumlah, 
                satuan = :satuan, penerima_pemohon = :penerima, keperluan_lokasi = :lokasi, keterangan = :keterangan
            WHERE id = :id
        ");
        $stmt->execute([
            ':tanggal' => $tanggal,
            ':jenis' => $jenis,
            ':item_id' => $itemId,
            ':jumlah' => $jumlah,
            ':satuan' => $satuan,
            ':penerima' => $penerima,
            ':lokasi' => $lokasi,
            ':keterangan' => $keterangan,
            ':id' => $id,
        ]);
        $msg = "Transaksi #TRX-" . str_pad($id, 4, '0', STR_PAD_LEFT) . " berhasil diperbarui!";
    } else {
        // Insert new transaction
        // Get next trans_no
        $maxId = (int)$pdo->query("SELECT MAX(id) FROM transactions")->fetchColumn();
        $nextNo = $maxId + 1;
        $transNo = sprintf("TRX-%04d", $nextNo);

        $stmt = $pdo->prepare("
            INSERT INTO transactions (trans_no, tanggal, jenis, item_id, jumlah, satuan, penerima_pemohon, keperluan_lokasi, keterangan)
            VALUES (:trans_no, :tanggal, :jenis, :item_id, :jumlah, :satuan, :penerima, :lokasi, :keterangan)
        ");
        $stmt->execute([
            ':trans_no' => $transNo,
            ':tanggal' => $tanggal,
            ':jenis' => $jenis,
            ':item_id' => $itemId,
            ':jumlah' => $jumlah,
            ':satuan' => $satuan,
            ':penerima' => $penerima,
            ':lokasi' => $lokasi,
            ':keterangan' => $keterangan,
        ]);
        $msg = "Transaksi {$jenis} ({$item['name']} - {$jumlah} {$satuan}) berhasil dicatat!";
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['flash_success'] = $msg;
    $redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : '../transaksi.php';
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
    $redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : '../transaksi.php';
    header("Location: " . $redirectUrl);
    exit;
}


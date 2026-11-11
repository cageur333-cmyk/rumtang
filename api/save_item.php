<?php
// api/save_item.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$pdo = Database::getConnection();

Auth::requireAdmin($pdo);
Auth::requirePostWithCsrf($pdo, false, '/inventaris.php');

$isAjax = Auth::isAjaxRequest();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode request tidak diizinkan.");
    }

    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $stokAwal = (int)($_POST['stok_awal'] ?? 0);
    $satuan = strtoupper(trim($_POST['satuan'] ?? 'PCS'));
    $stokMin = (int)($_POST['stok_min'] ?? 2);

    if (empty($name)) {
        throw new Exception("Nama barang wajib diisi.");
    }
    if ($categoryId <= 0) {
        throw new Exception("Pilih kategori barang yang sesuai.");
    }
    if ($stokAwal < 0) {
        throw new Exception("Stok awal tidak boleh bernilai negatif.");
    }
    if ($stokMin < 0) {
        $stokMin = 0;
    }

    // Check duplicate name
    if ($id) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE name = :name AND id != :id");
        $checkStmt->execute([':name' => $name, ':id' => $id]);
    } else {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE name = :name");
        $checkStmt->execute([':name' => $name]);
    }
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception("Barang dengan nama '{$name}' sudah ada di database.");
    }

    if ($id) {
        $stmt = $pdo->prepare("
            UPDATE items 
            SET category_id = :category_id, name = :name, stok_awal = :stok_awal, 
                satuan = :satuan, stok_min = :stok_min
            WHERE id = :id
        ");
        $stmt->execute([
            ':category_id' => $categoryId,
            ':name' => $name,
            ':stok_awal' => $stokAwal,
            ':satuan' => $satuan,
            ':stok_min' => $stokMin,
            ':id' => $id
        ]);
        $msg = "Data barang '{$name}' berhasil diperbarui.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO items (category_id, name, stok_awal, satuan, stok_min)
            VALUES (:category_id, :name, :stok_awal, :satuan, :stok_min)
        ");
        $stmt->execute([
            ':category_id' => $categoryId,
            ':name' => $name,
            ':stok_awal' => $stokAwal,
            ':satuan' => $satuan,
            ':stok_min' => $stokMin
        ]);
        $msg = "Barang baru '{$name}' berhasil ditambahkan ke inventaris.";
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    Auth::setFlash($pdo, 'success', $msg);
    header("Location: " . Auth::url('/inventaris.php'));
    exit;

} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    Auth::setFlash($pdo, 'error', $e->getMessage());
    header("Location: " . Auth::url('/inventaris.php'));
    exit;
}


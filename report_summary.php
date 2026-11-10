<?php
// report_summary.php - CLI Helper untuk ringkasan laporan mingguan Rumtang
require_once __DIR__ . '/config/database.php';
$pdo = Database::getConnection();

// 1. Barang Kritis (Habis & Menipis)
$criticalItems = $pdo->query("
    SELECT name, category_name, stok_awal, total_masuk, total_keluar, stok_akhir, satuan, stok_min, status_stok 
    FROM v_inventory_stock 
    WHERE status_stok IN ('MENIPIS', 'HABIS') 
    ORDER BY status_stok ASC, stok_akhir ASC
")->fetchAll();

// 2. Mutasi 7 Hari Terakhir
$recentTransactions = $pdo->query("
    SELECT t.*, i.name as item_name, c.name as category_name
    FROM transactions t
    JOIN items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE t.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY t.tanggal DESC, t.id DESC
")->fetchAll();

$totalMasuk7d = 0;
$totalKeluar7d = 0;
foreach ($recentTransactions as $tx) {
    if ($tx['jenis'] === 'MASUK') $totalMasuk7d += $tx['jumlah'];
    if ($tx['jenis'] === 'KELUAR') $totalKeluar7d += $tx['jumlah'];
}

// 3. Penggunaan per Lokasi (7 Hari Terakhir)
$locSummary7d = $pdo->query("
    SELECT keperluan_lokasi, SUM(jumlah) as total_qty, COUNT(id) as total_tx
    FROM transactions
    WHERE jenis = 'KELUAR' AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY keperluan_lokasi
    ORDER BY total_qty DESC
")->fetchAll();

// 4. Pending Orders
$pendingOrders = $pdo->query("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status = 'PENDING'
    GROUP BY o.id
    ORDER BY o.tanggal DESC
")->fetchAll();

echo "=== RINGKASAN MINGGUAN RUMTANG ===\n";
echo "Tanggal Cetak: " . date('d F Y, H:i') . " WIB\n\n";

echo "--- 1. DAFTAR BARANG KRITIS & RESTOCK DIPERLUKAN (" . count($criticalItems) . " Barang) ---\n";
if (empty($criticalItems)) {
    echo "Semua stok barang berada pada level AMAN.\n";
} else {
    foreach ($criticalItems as $it) {
        printf("- [%s] %s | Sisa: %d %s (Min: %d) -> Saran Order: +%d %s\n",
            $it['status_stok'],
            $it['name'],
            $it['stok_akhir'],
            $it['satuan'],
            $it['stok_min'],
            max(5, ($it['stok_min'] * 2) - $it['stok_akhir']),
            $it['satuan']
        );
    }
}

echo "\n--- 2. MUTASI 7 HARI TERAKHIR ---\n";
echo "Total Barang Masuk : " . $totalMasuk7d . " Qty\n";
echo "Total Barang Keluar: " . $totalKeluar7d . " Qty\n";
echo "Jumlah Transaksi   : " . count($recentTransactions) . " Transaksi\n";

echo "\n--- 3. PENGGUNAAN PER LOKASI (7 HARI TERAKHIR) ---\n";
if (empty($locSummary7d)) {
    echo "Belum ada transaksi keluar tercatat dalam 7 hari terakhir.\n";
} else {
    foreach ($locSummary7d as $loc) {
        echo "- " . $loc['keperluan_lokasi'] . ": " . $loc['total_qty'] . " Qty (" . $loc['total_tx'] . "x pengambilan)\n";
    }
}

echo "\n--- 4. ORDER PENDING MENUNGGU PERSETUJUAN ---\n";
if (empty($pendingOrders)) {
    echo "Tidak ada permohonan order yang pending.\n";
} else {
    foreach ($pendingOrders as $po) {
        echo "- Order #" . $po['order_no'] . " oleh " . $po['pemohon'] . " (" . $po['lokasi_tujuan'] . ") - " . $po['item_count'] . " items\n";
    }
}

// 5. Generate File Excel Resmi Otomatis
require_once __DIR__ . '/api/export_excel.php';
$reportsDir = __DIR__ . '/reports';
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0777, true);
}
$excelFilename = "Laporan_Mingguan_Rumtang_" . date('Y-m-d') . ".xls";
$excelPath = $reportsDir . '/' . $excelFilename;
file_put_contents($excelPath, generateRumtangExcelXml($pdo));

echo "\n--- 5. DOKUMEN EXCEL RESMI ---\n";
echo "File Excel berhasil digenerate di: " . realpath($excelPath) . "\n";
echo "URL Unduhan via Web: http://localhost/rumtang/api/export_excel.php\n";

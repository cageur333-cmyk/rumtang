<?php
// api/export_csv.php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

$type = $_GET['type'] ?? 'stock';

if ($type === 'transactions') {
    $filename = "rekap_transaksi_rumtang_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Output BOM for Excel UTF-8 support
    fputs($output, "\xEF\xBB\xBF");
    
    // Header
    fputcsv($output, ['NO', 'KODE TRX', 'TANGGAL', 'JENIS', 'NAMA BARANG', 'KATEGORI', 'JUMLAH (QTY)', 'SATUAN', 'PENERIMA / PEMOHON', 'KEPERLUAN / LOKASI', 'KETERANGAN']);

    $sql = "
        SELECT 
            t.id, t.trans_no, t.tanggal, t.jenis, i.name as item_name, c.name as category_name, 
            t.jumlah, t.satuan, t.penerima_pemohon, t.keperluan_lokasi, t.keterangan
        FROM transactions t
        JOIN items i ON t.item_id = i.id
        JOIN categories c ON i.category_id = c.id
        ORDER BY t.tanggal DESC, t.id DESC
    ";
    $stmt = $pdo->query($sql);
    $no = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $no++,
            $row['trans_no'] ?: 'TRX-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT),
            $row['tanggal'],
            $row['jenis'],
            $row['item_name'],
            $row['category_name'],
            $row['jumlah'],
            $row['satuan'],
            $row['penerima_pemohon'],
            $row['keperluan_lokasi'],
            $row['keterangan']
        ]);
    }
    fclose($output);
    exit;

} else {
    // Export Stock
    $filename = "inventaris_stok_rumtang_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    // Header
    fputcsv($output, ['NO', 'NAMA BARANG', 'KATEGORI', 'STOK AWAL', 'TOTAL MASUK', 'TOTAL KELUAR', 'STOK AKHIR', 'SATUAN', 'STATUS STOK']);

    $stmt = $pdo->query("SELECT * FROM v_inventory_stock ORDER BY category_id ASC, id ASC");
    $no = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $no++,
            $row['name'],
            $row['category_name'],
            $row['stok_awal'],
            $row['total_masuk'],
            $row['total_keluar'],
            $row['stok_akhir'],
            $row['satuan'],
            $row['status_stok']
        ]);
    }
    fclose($output);
    exit;
}


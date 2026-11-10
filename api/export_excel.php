<?php
// api/export_excel.php - Generator File Excel Resmi (.xls) Rumtang
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

// Function to generate the XML Spreadsheet content
function generateRumtangExcelXml(PDO $pdo): string {
    // 1. Fetch Master Inventory Stock
    $stockData = $pdo->query("SELECT * FROM v_inventory_stock ORDER BY category_id ASC, id ASC")->fetchAll();
    
    // Group by category
    $grouped = [];
    foreach ($stockData as $s) {
        $grouped[$s['category_name']][] = $s;
    }

    // 2. Fetch Critical Items
    $criticalData = $pdo->query("
        SELECT * FROM v_inventory_stock 
        WHERE status_stok IN ('MENIPIS', 'HABIS') 
        ORDER BY status_stok ASC, stok_akhir ASC
    ")->fetchAll();

    // 3. Fetch Transactions (Recent 100 or All)
    $txData = $pdo->query("
        SELECT t.*, i.name as item_name, c.name as category_name 
        FROM transactions t
        JOIN items i ON t.item_id = i.id
        JOIN categories c ON i.category_id = c.id
        ORDER BY t.tanggal DESC, t.id DESC
    ")->fetchAll();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
    $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
    $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    $xml .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

    // Styles
    $xml .= ' <Styles>' . "\n";
    $xml .= '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="10" ss:Color="#1e293b"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="MainTitle"><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="14" ss:Bold="1" ss:Color="#0f172a"/></Style>' . "\n";
    $xml .= '  <Style ss:ID="SubTitle"><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="10" ss:Color="#64748b"/></Style>' . "\n";
    
    // Header Style
    $xml .= '  <Style ss:ID="HeaderCol">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>' . "\n";
    $xml .= '   <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders>' . "\n";
    $xml .= '   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#ffffff"/>' . "\n";
    $xml .= '   <Interior ss:Color="#059669" ss:Pattern="Solid"/>' . "\n";
    $xml .= '  </Style>' . "\n";

    // Category Section Header
    $xml .= '  <Style ss:ID="CatHeader">' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#047857"/>' . "\n";
    $xml .= '   <Interior ss:Color="#ecfdf5" ss:Pattern="Solid"/>' . "\n";
    $xml .= '   <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#a7f3d0"/></Borders>' . "\n";
    $xml .= '  </Style>' . "\n";

    // Data Rows
    $xml .= '  <Style ss:ID="DataCell"><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="DataCenter"><Alignment ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="DataNumber"><Alignment ss:Horizontal="Right"/><NumberFormat ss:Format="#,##0"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="DataBoldNumber"><Alignment ss:Horizontal="Right"/><Font ss:Bold="1"/><NumberFormat ss:Format="#,##0"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/></Borders></Style>' . "\n";
    
    // Status Styles
    $xml .= '  <Style ss:ID="StatusAman"><Alignment ss:Horizontal="Center"/><Font ss:Bold="1" ss:Color="#047857"/><Interior ss:Color="#d1fae5" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="StatusMenipis"><Alignment ss:Horizontal="Center"/><Font ss:Bold="1" ss:Color="#b45309"/><Interior ss:Color="#fef3c7" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="StatusHabis"><Alignment ss:Horizontal="Center"/><Font ss:Bold="1" ss:Color="#b91c1c"/><Interior ss:Color="#fee2e2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#f1f5f9"/></Borders></Style>' . "\n";

    // Subtotal & Total
    $xml .= '  <Style ss:ID="SubtotalRow"><Alignment ss:Horizontal="Right"/><Font ss:Bold="1" ss:Color="#334155"/><Interior ss:Color="#f8fafc" ss:Pattern="Solid"/><NumberFormat ss:Format="#,##0"/><Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#cbd5e1"/></Borders></Style>' . "\n";
    $xml .= '  <Style ss:ID="GrandTotalRow"><Alignment ss:Horizontal="Right"/><Font ss:Bold="1" ss:Color="#ffffff"/><Interior ss:Color="#0f172a" ss:Pattern="Solid"/><NumberFormat ss:Format="#,##0"/></Style>' . "\n";
    $xml .= ' </Styles>' . "\n";

    // -------------------------------------------------------------
    // SHEET 1: REKAP STOK INVENTARIS
    // -------------------------------------------------------------
    $xml .= ' <Worksheet ss:Name="Rekap Stok Inventaris">' . "\n";
    $xml .= '  <Table ss:DefaultRowHeight="20">' . "\n";
    $xml .= '   <Column ss:Width="35"/>' . "\n";  // No
    $xml .= '   <Column ss:Width="200"/>' . "\n"; // Nama Barang
    $xml .= '   <Column ss:Width="120"/>' . "\n"; // Kategori
    $xml .= '   <Column ss:Width="65"/>' . "\n";  // Satuan
    $xml .= '   <Column ss:Width="75"/>' . "\n";  // Stok Awal
    $xml .= '   <Column ss:Width="80"/>' . "\n";  // Total Masuk
    $xml .= '   <Column ss:Width="80"/>' . "\n";  // Total Keluar
    $xml .= '   <Column ss:Width="80"/>' . "\n";  // Stok Akhir
    $xml .= '   <Column ss:Width="65"/>' . "\n";  // Min Stok
    $xml .= '   <Column ss:Width="85"/>' . "\n";  // Status

    // Titles
    $xml .= '   <Row ss:Height="24"><Cell ss:StyleID="MainTitle" ss:MergeAcross="9"><Data ss:Type="String">LAPORAN REKAPITULASI INVENTARIS STOK RUMTANG</Data></Cell></Row>' . "\n";
    $xml .= '   <Row ss:Height="18"><Cell ss:StyleID="SubTitle" ss:MergeAcross="9"><Data ss:Type="String">Tanggal Cetak: ' . date('d F Y, H:i') . ' WIB | Database: MySQL rumtang_db</Data></Cell></Row>' . "\n";
    $xml .= '   <Row ss:Height="10"></Row>' . "\n";

    // Header Row
    $xml .= '   <Row ss:Height="26">' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">NO</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">NAMA BARANG</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">KATEGORI</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">SATUAN</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">STOK AWAL</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">TOTAL MASUK</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">TOTAL KELUAR</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">STOK AKHIR</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">MIN STOK</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">STATUS</Data></Cell>' . "\n";
    $xml .= '   </Row>' . "\n";

    $globalNo = 1;
    $totalAwal = 0; $totalMasuk = 0; $totalKeluar = 0; $totalAkhir = 0;

    foreach ($grouped as $catName => $items) {
        $xml .= '   <Row ss:Height="22"><Cell ss:StyleID="CatHeader" ss:MergeAcross="9"><Data ss:Type="String">KATEGORI: ' . htmlspecialchars($catName) . ' (' . count($items) . ' Barang)</Data></Cell></Row>' . "\n";
        
        $subAwal = 0; $subMasuk = 0; $subKeluar = 0; $subAkhir = 0;
        foreach ($items as $it) {
            $statusStyle = 'StatusAman';
            if ($it['status_stok'] === 'MENIPIS') $statusStyle = 'StatusMenipis';
            if ($it['status_stok'] === 'HABIS') $statusStyle = 'StatusHabis';

            $subAwal += $it['stok_awal'];
            $subMasuk += $it['total_masuk'];
            $subKeluar += $it['total_keluar'];
            $subAkhir += $it['stok_akhir'];

            $totalAwal += $it['stok_awal'];
            $totalMasuk += $it['total_masuk'];
            $totalKeluar += $it['total_keluar'];
            $totalAkhir += $it['stok_akhir'];

            $xml .= '   <Row>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $globalNo++ . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($it['name']) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($it['category_name']) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="String">' . htmlspecialchars($it['satuan']) . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataNumber"><Data ss:Type="Number">' . $it['stok_awal'] . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataNumber"><Data ss:Type="Number">' . $it['total_masuk'] . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataNumber"><Data ss:Type="Number">' . $it['total_keluar'] . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataBoldNumber"><Data ss:Type="Number">' . $it['stok_akhir'] . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $it['stok_min'] . '</Data></Cell>' . "\n";
            $xml .= '    <Cell ss:StyleID="' . $statusStyle . '"><Data ss:Type="String">' . $it['status_stok'] . '</Data></Cell>' . "\n";
            $xml .= '   </Row>' . "\n";
        }

        // Subtotal row
        $xml .= '   <Row ss:Height="22">' . "\n";
        $xml .= '    <Cell ss:StyleID="SubtotalRow" ss:MergeAcross="3"><Data ss:Type="String">SUBTOTAL ' . htmlspecialchars($catName) . ':</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="SubtotalRow"><Data ss:Type="Number">' . $subAwal . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="SubtotalRow"><Data ss:Type="Number">' . $subMasuk . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="SubtotalRow"><Data ss:Type="Number">' . $subKeluar . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="SubtotalRow"><Data ss:Type="Number">' . $subAkhir . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="SubtotalRow"><Data ss:Type="String">-</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="SubtotalRow"><Data ss:Type="String">-</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";
    }

    // Grand Total
    $xml .= '   <Row ss:Height="26">' . "\n";
    $xml .= '    <Cell ss:StyleID="GrandTotalRow" ss:MergeAcross="3"><Data ss:Type="String">TOTAL KESELURUHAN INVENTARIS:</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="GrandTotalRow"><Data ss:Type="Number">' . $totalAwal . '</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="GrandTotalRow"><Data ss:Type="Number">' . $totalMasuk . '</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="GrandTotalRow"><Data ss:Type="Number">' . $totalKeluar . '</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="GrandTotalRow"><Data ss:Type="Number">' . $totalAkhir . '</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="GrandTotalRow"><Data ss:Type="String">-</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="GrandTotalRow"><Data ss:Type="String">-</Data></Cell>' . "\n";
    $xml .= '   </Row>' . "\n";

    $xml .= '  </Table>' . "\n";
    $xml .= ' </Worksheet>' . "\n";

    // -------------------------------------------------------------
    // SHEET 2: REKOMENDASI RESTOCK (BARANG KRITIS & HABIS)
    // -------------------------------------------------------------
    $xml .= ' <Worksheet ss:Name="Rekomendasi Restock">' . "\n";
    $xml .= '  <Table ss:DefaultRowHeight="20">' . "\n";
    $xml .= '   <Column ss:Width="35"/>' . "\n";
    $xml .= '   <Column ss:Width="200"/>' . "\n";
    $xml .= '   <Column ss:Width="120"/>' . "\n";
    $xml .= '   <Column ss:Width="70"/>' . "\n";
    $xml .= '   <Column ss:Width="85"/>' . "\n";
    $xml .= '   <Column ss:Width="75"/>' . "\n";
    $xml .= '   <Column ss:Width="85"/>' . "\n";
    $xml .= '   <Column ss:Width="110"/>' . "\n";

    $xml .= '   <Row ss:Height="24"><Cell ss:StyleID="MainTitle" ss:MergeAcross="7"><Data ss:Type="String">DAFTAR BARANG YANG WAJIB DI-RESTOCK / ORDER</Data></Cell></Row>' . "\n";
    $xml .= '   <Row ss:Height="18"><Cell ss:StyleID="SubTitle" ss:MergeAcross="7"><Data ss:Type="String">Daftar barang persediaan yang statusnya HABIS atau MENIPIS di bawah batas stok aman</Data></Cell></Row>' . "\n";
    $xml .= '   <Row ss:Height="10"></Row>' . "\n";

    $xml .= '   <Row ss:Height="26">' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">NO</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">NAMA BARANG</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">KATEGORI</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">SATUAN</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">SISA STOK</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">BATAS MIN</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">STATUS</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">SARAN ORDER (QTY)</Data></Cell>' . "\n";
    $xml .= '   </Row>' . "\n";

    $cNo = 1;
    foreach ($criticalData as $crit) {
        $statusStyle = ($crit['status_stok'] === 'HABIS') ? 'StatusHabis' : 'StatusMenipis';
        $saranOrder = max(5, ($crit['stok_min'] * 2) - $crit['stok_akhir']);

        $xml .= '   <Row>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $cNo++ . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($crit['name']) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($crit['category_name']) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="String">' . htmlspecialchars($crit['satuan']) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataBoldNumber"><Data ss:Type="Number">' . $crit['stok_akhir'] . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $crit['stok_min'] . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="' . $statusStyle . '"><Data ss:Type="String">' . $crit['status_stok'] . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataBoldNumber"><Data ss:Type="Number">' . $saranOrder . '</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";
    }

    $xml .= '  </Table>' . "\n";
    $xml .= ' </Worksheet>' . "\n";

    // -------------------------------------------------------------
    // SHEET 3: LOG MUTASI TRANSAKSI
    // -------------------------------------------------------------
    $xml .= ' <Worksheet ss:Name="Log Transaksi">' . "\n";
    $xml .= '  <Table ss:DefaultRowHeight="20">' . "\n";
    $xml .= '   <Column ss:Width="35"/>' . "\n";
    $xml .= '   <Column ss:Width="85"/>' . "\n";
    $xml .= '   <Column ss:Width="80"/>' . "\n";
    $xml .= '   <Column ss:Width="70"/>' . "\n";
    $xml .= '   <Column ss:Width="180"/>' . "\n";
    $xml .= '   <Column ss:Width="50"/>' . "\n";
    $xml .= '   <Column ss:Width="65"/>' . "\n";
    $xml .= '   <Column ss:Width="110"/>' . "\n";
    $xml .= '   <Column ss:Width="120"/>' . "\n";
    $xml .= '   <Column ss:Width="160"/>' . "\n";

    $xml .= '   <Row ss:Height="24"><Cell ss:StyleID="MainTitle" ss:MergeAcross="9"><Data ss:Type="String">RIWAYAT MUTASI BARANG MASUK &amp; KELUAR</Data></Cell></Row>' . "\n";
    $xml .= '   <Row ss:Height="18"><Cell ss:StyleID="SubTitle" ss:MergeAcross="9"><Data ss:Type="String">Pencatatan lengkap penerimaan restock dan pengeluaran barang operasional</Data></Cell></Row>' . "\n";
    $xml .= '   <Row ss:Height="10"></Row>' . "\n";

    $xml .= '   <Row ss:Height="26">' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">NO</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">KODE TRX</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">TANGGAL</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">JENIS</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">NAMA BARANG</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">QTY</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">SATUAN</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">PEMOHON</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">LOKASI</Data></Cell>' . "\n";
    $xml .= '    <Cell ss:StyleID="HeaderCol"><Data ss:Type="String">KETERANGAN</Data></Cell>' . "\n";
    $xml .= '   </Row>' . "\n";

    $tNo = 1;
    foreach ($txData as $tx) {
        $xml .= '   <Row>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $tNo++ . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="String">' . htmlspecialchars($tx['trans_no'] ?: 'TRX-' . str_pad($tx['id'], 4, '0', STR_PAD_LEFT)) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="String">' . $tx['tanggal'] . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="' . ($tx['jenis'] === 'MASUK' ? 'StatusAman' : 'StatusMenipis') . '"><Data ss:Type="String">' . $tx['jenis'] . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($tx['item_name']) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataBoldNumber"><Data ss:Type="Number">' . $tx['jumlah'] . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCenter"><Data ss:Type="String">' . htmlspecialchars($tx['satuan']) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($tx['penerima_pemohon']) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($tx['keperluan_lokasi']) . '</Data></Cell>' . "\n";
        $xml .= '    <Cell ss:StyleID="DataCell"><Data ss:Type="String">' . htmlspecialchars($tx['keterangan'] ?: '-') . '</Data></Cell>' . "\n";
        $xml .= '   </Row>' . "\n";
    }

    $xml .= '  </Table>' . "\n";
    $xml .= ' </Worksheet>' . "\n";

    $xml .= '</Workbook>' . "\n";
    return $xml;
}

// Check if running directly as standalone CLI or HTTP request
if (php_sapi_name() === 'cli') {
    if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
        // Generate file to reports/ folder
        $reportsDir = __DIR__ . '/../reports';
        if (!is_dir($reportsDir)) {
            mkdir($reportsDir, 0777, true);
        }
        $filename = "Laporan_Inventaris_Rumtang_" . date('Y-m-d_His') . ".xls";
        $filePath = $reportsDir . '/' . $filename;
        file_put_contents($filePath, generateRumtangExcelXml($pdo));
        echo "SUCCESS: File Excel berhasil dibuat di: " . realpath($filePath) . "\n";
    }
} else {
    // HTTP Download directly
    $filename = "Laporan_Inventaris_Rumtang_" . date('Y-m-d') . ".xls";
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo generateRumtangExcelXml($pdo);
    exit;
}

<?php
// laporan.php
$pageTitle = "Laporan & Rekapitulasi";
$pageHeading = "Laporan Rekapitulasi Stok";
$pageSubheading = "Cetak Dokumen Resmi & Rekap Penggunaan Inventaris Rumtang";

require_once __DIR__ . '/includes/header.php';

// Filter
$filterCat = $_GET['cat'] ?? 'ALL';
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Fetch Stock data grouped by category
$catWhere = ($filterCat !== 'ALL') ? "WHERE category_id = " . (int)$filterCat : "";
$stockData = $pdo->query("
    SELECT * FROM v_inventory_stock 
    {$catWhere}
    ORDER BY category_id ASC, id ASC
")->fetchAll();

// Group items by category name
$groupedItems = [];
foreach ($stockData as $row) {
    $groupedItems[$row['category_name']][] = $row;
}

// User Consumption Summary
$userSummary = $pdo->query("
    SELECT penerima_pemohon, COUNT(id) as total_tx, SUM(jumlah) as total_qty
    FROM transactions
    WHERE jenis = 'KELUAR'
    GROUP BY penerima_pemohon
    ORDER BY total_qty DESC
")->fetchAll();

// Location Consumption Summary
$locSummary = $pdo->query("
    SELECT keperluan_lokasi, COUNT(id) as total_tx, SUM(jumlah) as total_qty
    FROM transactions
    WHERE jenis = 'KELUAR'
    GROUP BY keperluan_lokasi
    ORDER BY total_qty DESC
")->fetchAll();
?>

<div class="space-y-6">
    <!-- Filter Controls Card (Hidden on Print) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm no-print">
        <form method="GET" action="laporan.php" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Filter Kategori</label>
                    <select name="cat" onchange="this.form.submit()" class="px-3 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="ALL">Semua Kategori (Lengkap)</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $filterCat == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="api/export_excel.php" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl shadow-sm transition-all">
                    <i class="fa-solid fa-file-excel"></i>
                    Download Excel (.xls) Multi-Sheet
                </a>
                <a href="api/export_csv.php?type=stock" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    <i class="fa-solid fa-file-csv text-slate-600"></i>
                    Export CSV
                </a>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-bold text-white bg-slate-900 hover:bg-black rounded-xl shadow-md transition-all">
                    <i class="fa-solid fa-print"></i>
                    Cetak / PDF
                </button>
            </div>
        </form>
    </div>

    <!-- Official Report Document Paper Sheet -->
    <div class="bg-white p-6 sm:p-10 rounded-2xl border border-slate-200 shadow-sm text-slate-800">
        <!-- Letterhead Header -->
        <div class="border-b-2 border-slate-900 pb-5 mb-6 text-center">
            <div class="flex items-center justify-center gap-3 mb-1">
                <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center text-sm font-bold">
                    RT
                </div>
                <h2 class="text-xl sm:text-2xl font-black uppercase tracking-wider text-slate-900">
                    DEPARTEMEN RUMAH TANGGA & LOGISTIK OPERASIONAL
                </h2>
            </div>
            <p class="text-xs sm:text-sm font-bold uppercase tracking-widest text-emerald-700">
                LAPORAN REKAPITULASI INVENTARIS STOK RUMTANG DAN CHEMICAL
            </p>
            <p class="text-[11px] text-slate-500 mt-1">
                Dicetak pada: <?= date('d F Y, H:i') ?> WIB &bull; Sistem Terintegrasi Database Rumtang
            </p>
        </div>

        <!-- Inventory Table by Category -->
        <div class="space-y-6">
            <?php 
            $grandAwal = 0;
            $grandMasuk = 0;
            $grandKeluar = 0;
            $grandAkhir = 0;
            $globalNo = 1;

            foreach ($groupedItems as $categoryName => $catItems): 
                $subAwal = 0;
                $subMasuk = 0;
                $subKeluar = 0;
                $subAkhir = 0;
            ?>
                <div>
                    <div class="bg-slate-100 px-3 py-2 rounded-lg font-extrabold text-xs text-slate-800 uppercase tracking-wider border border-slate-200 mb-2 flex items-center justify-between">
                        <span><?= htmlspecialchars($categoryName) ?></span>
                        <span class="text-slate-500 font-normal"><?= count($catItems) ?> Item</span>
                    </div>

                    <table class="w-full text-left text-xs border border-slate-200">
                        <thead class="bg-slate-50 text-[10px] font-bold text-slate-600 uppercase border-b border-slate-200">
                            <tr>
                                <th class="py-2 px-3 text-center w-10">No</th>
                                <th class="py-2 px-3">Nama Barang</th>
                                <th class="py-2 px-3 text-center">Satuan</th>
                                <th class="py-2 px-3 text-center">Stok Awal</th>
                                <th class="py-2 px-3 text-center">Total Masuk</th>
                                <th class="py-2 px-3 text-center">Total Keluar</th>
                                <th class="py-2 px-3 text-center">Stok Akhir</th>
                                <th class="py-2 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <?php foreach ($catItems as $it): 
                                $subAwal += $it['stok_awal'];
                                $subMasuk += $it['total_masuk'];
                                $subKeluar += $it['total_keluar'];
                                $subAkhir += $it['stok_akhir'];

                                $grandAwal += $it['stok_awal'];
                                $grandMasuk += $it['total_masuk'];
                                $grandKeluar += $it['total_keluar'];
                                $grandAkhir += $it['stok_akhir'];
                            ?>
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2 px-3 text-center text-slate-400 font-mono text-[11px]"><?= $globalNo++ ?></td>
                                    <td class="py-2 px-3 font-bold text-slate-900"><?= htmlspecialchars($it['name']) ?></td>
                                    <td class="py-2 px-3 text-center font-mono text-[11px] text-slate-500"><?= htmlspecialchars($it['satuan']) ?></td>
                                    <td class="py-2 px-3 text-center text-slate-700"><?= $it['stok_awal'] ?></td>
                                    <td class="py-2 px-3 text-center text-teal-700 font-bold"><?= $it['total_masuk'] ?></td>
                                    <td class="py-2 px-3 text-center text-rose-700 font-bold"><?= $it['total_keluar'] ?></td>
                                    <td class="py-2 px-3 text-center font-extrabold <?= $it['stok_akhir'] <= 0 ? 'text-rose-600' : 'text-slate-900' ?>">
                                        <?= $it['stok_akhir'] ?>
                                    </td>
                                    <td class="py-2 px-3 text-center whitespace-nowrap">
                                        <?= getStatusBadge($it['status_stok']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-50/80 font-bold text-xs border-t border-slate-200">
                            <tr>
                                <td colspan="3" class="py-2 px-3 text-right uppercase text-slate-600">Subtotal <?= htmlspecialchars($categoryName) ?>:</td>
                                <td class="py-2 px-3 text-center"><?= $subAwal ?></td>
                                <td class="py-2 px-3 text-center text-teal-700"><?= $subMasuk ?></td>
                                <td class="py-2 px-3 text-center text-rose-700"><?= $subKeluar ?></td>
                                <td class="py-2 px-3 text-center font-black"><?= $subAkhir ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endforeach; ?>

            <!-- Grand Total Bar -->
            <div class="bg-slate-900 text-white p-4 rounded-xl flex items-center justify-between text-xs sm:text-sm font-bold mt-4 shadow-sm">
                <span>TOTAL KESELURUHAN INVENTARIS:</span>
                <div class="flex items-center gap-6">
                    <span>Awal: <strong><?= number_format($grandAwal) ?></strong></span>
                    <span class="text-teal-300">Masuk: <strong><?= number_format($grandMasuk) ?></strong></span>
                    <span class="text-rose-300">Keluar: <strong><?= number_format($grandKeluar) ?></strong></span>
                    <span class="text-emerald-400 font-extrabold text-base">Sisa Fisik: <?= number_format($grandAkhir) ?></span>
                </div>
            </div>
        </div>

        <!-- Summary Statistics Side by Side -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8 pt-6 border-t border-slate-200">
            <!-- Lokasi Pengeluaran -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-map-pin text-emerald-600 mr-1"></i> Rekap Mutasi Berdasarkan Lokasi
                </h4>
                <table class="w-full text-xs border border-slate-200 rounded-lg overflow-hidden">
                    <thead class="bg-slate-50 font-bold text-slate-500 uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-2 px-3">Lokasi / Unit</th>
                            <th class="py-2 px-3 text-center">Transaksi</th>
                            <th class="py-2 px-3 text-right">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach (array_slice($locSummary, 0, 7) as $ls): ?>
                            <tr>
                                <td class="py-1.5 px-3 font-semibold text-slate-800"><?= htmlspecialchars($ls['keperluan_lokasi']) ?></td>
                                <td class="py-1.5 px-3 text-center text-slate-500"><?= $ls['total_tx'] ?> kali</td>
                                <td class="py-1.5 px-3 text-right font-extrabold text-slate-900"><?= $ls['total_qty'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Penerima / Pemohon -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    <i class="fa-solid fa-user-check text-emerald-600 mr-1"></i> Rekap Mutasi Berdasarkan Pemohon
                </h4>
                <table class="w-full text-xs border border-slate-200 rounded-lg overflow-hidden">
                    <thead class="bg-slate-50 font-bold text-slate-500 uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-2 px-3">Nama Pemohon</th>
                            <th class="py-2 px-3 text-center">Transaksi</th>
                            <th class="py-2 px-3 text-right">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach (array_slice($userSummary, 0, 7) as $us): ?>
                            <tr>
                                <td class="py-1.5 px-3 font-semibold text-slate-800"><?= htmlspecialchars($us['penerima_pemohon']) ?></td>
                                <td class="py-1.5 px-3 text-center text-slate-500"><?= $us['total_tx'] ?> kali</td>
                                <td class="py-1.5 px-3 text-right font-extrabold text-slate-900"><?= $us['total_qty'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Official Signatures Block -->
        <div class="mt-12 pt-8 border-t border-slate-200 grid grid-cols-2 text-center text-xs text-slate-700">
            <div>
                <p class="font-medium text-slate-500">Dibuat Oleh,</p>
                <p class="font-bold text-slate-900 mt-1">Petugas Gudang / Rumtang</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-900 underline">( REZKI / ADMIN )</p>
                <p class="text-[11px] text-slate-400">Logistik & Perlengkapan</p>
            </div>
            <div>
                <p class="font-medium text-slate-500">Diketahui Oleh,</p>
                <p class="font-bold text-slate-900 mt-1">Koordinator Operasional</p>
                <div class="h-20"></div>
                <p class="font-bold text-slate-900 underline">( ............................................ )</p>
                <p class="text-[11px] text-slate-400">Head of Operational</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


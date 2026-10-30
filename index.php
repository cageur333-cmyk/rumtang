<?php
// index.php
$pageTitle = "Dashboard Overview";
$pageHeading = "Dashboard Report Rumtang";
$pageSubheading = "Monitoring Inventaris, Chemical & Log Transaksi Real-Time";

require_once __DIR__ . '/includes/header.php';

// Calculate KPI Metrics
$totalItems = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$totalStokAwal = $pdo->query("SELECT SUM(stok_awal) FROM items")->fetchColumn() ?: 0;
$totalMasuk = $pdo->query("SELECT SUM(jumlah) FROM transactions WHERE jenis = 'MASUK'")->fetchColumn() ?: 0;
$totalKeluar = $pdo->query("SELECT SUM(jumlah) FROM transactions WHERE jenis = 'KELUAR'")->fetchColumn() ?: 0;
$totalStokAkhir = $pdo->query("SELECT SUM(stok_akhir) FROM v_inventory_stock")->fetchColumn() ?: 0;

$countAman = $pdo->query("SELECT COUNT(*) FROM v_inventory_stock WHERE status_stok = 'AMAN'")->fetchColumn();
$countMenipis = $pdo->query("SELECT COUNT(*) FROM v_inventory_stock WHERE status_stok = 'MENIPIS'")->fetchColumn();
$countHabis = $pdo->query("SELECT COUNT(*) FROM v_inventory_stock WHERE status_stok = 'HABIS'")->fetchColumn();

// Critical Low Stock Items
$criticalItems = $pdo->query("
    SELECT * FROM v_inventory_stock 
    WHERE status_stok IN ('MENIPIS', 'HABIS') 
    ORDER BY stok_akhir ASC, name ASC 
    LIMIT 6
")->fetchAll();

// Recent 8 Transactions
$recentTransactions = $pdo->query("
    SELECT t.*, i.name as item_name, c.name as category_name 
    FROM transactions t
    JOIN items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    ORDER BY t.tanggal DESC, t.id DESC 
    LIMIT 8
")->fetchAll();

// Data for Monthly Trends (MASUK vs KELUAR)
$monthlyQuery = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal, '%Y-%m') as bulan,
        SUM(CASE WHEN jenis = 'MASUK' THEN jumlah ELSE 0 END) as masuk,
        SUM(CASE WHEN jenis = 'KELUAR' THEN jumlah ELSE 0 END) as keluar
    FROM transactions
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan ASC
")->fetchAll();

$monthLabels = [];
$monthMasuk = [];
$monthKeluar = [];
foreach ($monthlyQuery as $m) {
    // Format YYYY-MM into Indonesian e.g. Juli 2026
    $dateObj = DateTime::createFromFormat('Y-m', $m['bulan']);
    $mNames = ['01'=>'Jan', '02'=>'Feb', '03'=>'Mar', '04'=>'Apr', '05'=>'Mei', '06'=>'Jun', '07'=>'Jul', '08'=>'Agu', '09'=>'Sep', '10'=>'Okt', '11'=>'Nov', '12'=>'Des'];
    $monthLabels[] = ($mNames[$dateObj->format('m')] ?? $dateObj->format('M')) . ' ' . $dateObj->format('Y');
    $monthMasuk[] = (int)$m['masuk'];
    $monthKeluar[] = (int)$m['keluar'];
}

// Data for Top 8 Most Used Items
$topUsedItems = $pdo->query("
    SELECT i.name, SUM(t.jumlah) as total_keluar, t.satuan
    FROM transactions t
    JOIN items i ON t.item_id = i.id
    WHERE t.jenis = 'KELUAR'
    GROUP BY i.id, i.name, t.satuan
    ORDER BY total_keluar DESC
    LIMIT 8
")->fetchAll();

$topItemNames = [];
$topItemTotals = [];
foreach ($topUsedItems as $t) {
    $topItemNames[] = $t['name'];
    $topItemTotals[] = (int)$t['total_keluar'];
}

// Data for Location Distribution
$locationDist = $pdo->query("
    SELECT keperluan_lokasi, SUM(jumlah) as total_qty
    FROM transactions
    WHERE jenis = 'KELUAR'
    GROUP BY keperluan_lokasi
    ORDER BY total_qty DESC
    LIMIT 6
")->fetchAll();

$locLabels = [];
$locTotals = [];
foreach ($locationDist as $loc) {
    $locLabels[] = $loc['keperluan_lokasi'];
    $locTotals[] = (int)$loc['total_qty'];
}
?>

<div class="space-y-6 sm:space-y-8">
    <!-- Top Statistics Cards Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Card 1: Total Barang & Stok Akhir -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Stok Fisik</p>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1"><?= number_format($totalStokAkhir) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">Total Varian: <strong class="text-slate-800"><?= $totalItems ?> Barang</strong></span>
                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md font-bold">Stok Awal: <?= $totalStokAwal ?></span>
            </div>
        </div>

        <!-- Card 2: Total Barang Masuk -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Barang Masuk</p>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-teal-600 mt-1"><?= number_format($totalMasuk) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-arrow-down-long"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Restock & Pembelian</span>
                <span class="text-teal-700 font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-xs"></i> Terdata
                </span>
            </div>
        </div>

        <!-- Card 3: Total Barang Keluar -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Barang Keluar</p>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-rose-600 mt-1"><?= number_format($totalKeluar) ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-arrow-up-long"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Pemakaian Operasional</span>
                <span class="text-rose-600 font-semibold"><?= count($topUsedItems) ?>+ Varian aktif</span>
            </div>
        </div>

        <!-- Card 4: Status Kritis / Menipis -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Stok Kritis / Menipis</p>
                    <h3 class="text-2xl sm:text-3xl font-extrabold text-amber-600 mt-1"><?= $countMenipis + $countHabis ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-amber-700 font-medium"><?= $countMenipis ?> Menipis &bull; <?= $countHabis ?> Kosong</span>
                <a href="order.php?action=new" class="font-bold text-emerald-700 hover:text-emerald-800 underline">Order Sekarang</a>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart 1: Tren Pergerakan Barang Masuk vs Keluar -->
        <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Pergerakan Barang Masuk & Keluar</h2>
                    <p class="text-xs text-slate-500">Statistik perbandingan volume restock vs pengeluaran per bulan</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-medium">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Masuk</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span> Keluar</span>
                </div>
            </div>
            <div class="h-72 w-full">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Distribusi Pengeluaran per Lokasi -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Distribusi Lokasi Penggunaan</h2>
                <p class="text-xs text-slate-500 mb-4">Porsi pengeluaran barang berdasarkan unit/lokasi kerja</p>
                <div class="h-56 relative flex items-center justify-center">
                    <canvas id="locationPieChart"></canvas>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs text-slate-600">
                <?php foreach (array_slice($locationDist, 0, 4) as $ld): ?>
                    <div class="flex items-center justify-between p-1.5 bg-slate-50 rounded-lg">
                        <span class="font-medium truncate"><?= htmlspecialchars($ld['keperluan_lokasi']) ?></span>
                        <span class="font-bold text-slate-900"><?= $ld['total_qty'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Second Row: Top Items & Stock Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Consumed Items Chart -->
        <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Top 8 Barang Paling Banyak Digunakan</h2>
                    <p class="text-xs text-slate-500">Barang dengan frekuensi dan kuantitas pengeluaran tertinggi</p>
                </div>
                <a href="inventaris.php" class="text-xs font-bold text-emerald-600 hover:text-emerald-700">Lihat Semua &rarr;</a>
            </div>
            <div class="h-64 w-full">
                <canvas id="topItemsChart"></canvas>
            </div>
        </div>

        <!-- Critical Low Stock Alert Card -->
        <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                        Perlu Restock Segera
                    </h2>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                        <?= count($criticalItems) ?> Item
                    </span>
                </div>
                <p class="text-xs text-slate-500 mb-4">Barang di bawah batas minimum atau persediaan habis</p>

                <div class="space-y-3">
                    <?php if (empty($criticalItems)): ?>
                        <div class="text-center py-8 text-slate-400 text-xs">
                            <i class="fa-solid fa-circle-check text-3xl text-emerald-500 mb-2"></i>
                            <p>Semua stok berada pada level aman!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($criticalItems as $crit): ?>
                            <div class="p-3 rounded-xl border border-slate-100 bg-slate-50/70 flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-800"><?= htmlspecialchars($crit['name']) ?></h4>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($crit['category_name']) ?></span>
                                        <span class="text-slate-300">&bull;</span>
                                        <span class="text-[11px] text-slate-500">Min: <?= $crit['stok_min'] ?> <?= $crit['satuan'] ?></span>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-extrabold <?= $crit['stok_akhir'] <= 0 ? 'text-rose-600' : 'text-amber-600' ?>">
                                        <?= $crit['stok_akhir'] ?> <span class="text-xs font-normal"><?= $crit['satuan'] ?></span>
                                    </div>
                                    <div class="mt-1">
                                        <?= getStatusBadge($crit['status_stok']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100">
                <a href="order.php?action=new" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                    <i class="fa-solid fa-cart-shopping"></i>
                    Buat Form Order / Pengadaan
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Aktivitas Transaksi Terbaru</h2>
                <p class="text-xs text-slate-500">Log mutasi pergerakan barang masuk dan keluar terakhir</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="api/export_csv.php?type=transactions" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i>
                    Export CSV
                </a>
                <a href="transaksi.php" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-all">
                    <span>Lihat Semua (<?= $pdo->query("SELECT count(*) FROM transactions")->fetchColumn() ?>)</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm text-slate-600">
                <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200/70">
                    <tr>
                        <th class="py-3.5 px-4 sm:px-6">Kode TRX</th>
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4">Jenis</th>
                        <th class="py-3.5 px-4">Nama Barang</th>
                        <th class="py-3.5 px-4">Jumlah</th>
                        <th class="py-3.5 px-4">Penerima/Pemohon</th>
                        <th class="py-3.5 px-4">Lokasi / Keperluan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 sm:px-6 font-mono text-xs text-slate-500 font-semibold">
                                <?= htmlspecialchars($tx['trans_no'] ?: 'TRX-' . str_pad($tx['id'], 4, '0', STR_PAD_LEFT)) ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 whitespace-nowrap">
                                <?= date('d M Y', strtotime($tx['tanggal'])) ?>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <?php if ($tx['jenis'] === 'MASUK'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                        <i class="fa-solid fa-arrow-down text-[10px]"></i> MASUK
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        <i class="fa-solid fa-arrow-up text-[10px]"></i> KELUAR
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <?= htmlspecialchars($tx['item_name']) ?>
                                <span class="block text-[11px] text-slate-400 font-normal"><?= htmlspecialchars($tx['category_name']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-extrabold text-slate-900"><?= $tx['jumlah'] ?></span>
                                <span class="text-xs text-slate-500 font-normal"><?= htmlspecialchars($tx['satuan']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 font-semibold whitespace-nowrap">
                                <?= htmlspecialchars($tx['penerima_pemohon']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 font-medium text-slate-700 text-xs">
                                    <?= htmlspecialchars($tx['keperluan_lokasi']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart Initialization Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Monthly Trend Chart
    const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [
                {
                    label: 'Barang Masuk',
                    data: <?= json_encode($monthMasuk) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 8,
                    maxBarThickness: 32
                },
                {
                    label: 'Barang Keluar',
                    data: <?= json_encode($monthKeluar) ?>,
                    backgroundColor: 'rgba(244, 63, 94, 0.85)',
                    borderRadius: 8,
                    maxBarThickness: 32
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                }
            }
        }
    });

    // 2. Location Distribution Doughnut Chart
    const locCtx = document.getElementById('locationPieChart').getContext('2d');
    new Chart(locCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($locLabels) ?>,
            datasets: [{
                data: <?= json_encode($locTotals) ?>,
                backgroundColor: [
                    '#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#64748b'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        font: { family: 'Plus Jakarta Sans', size: 11 }
                    }
                }
            }
        }
    });

    // 3. Top Used Items Chart
    const topCtx = document.getElementById('topItemsChart').getContext('2d');
    new Chart(topCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($topItemNames) ?>,
            datasets: [{
                label: 'Total Digunakan',
                data: <?= json_encode($topItemTotals) ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 6,
                maxBarThickness: 24
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


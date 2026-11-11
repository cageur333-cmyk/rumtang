<?php
// includes/sidebar.php
$reqPath = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$reqBase = basename($reqPath);
if ($reqBase === '' || $reqBase === 'rumtang') {
    $currentScript = 'index.php';
} elseif (str_ends_with($reqBase, '.php')) {
    $currentScript = $reqBase;
} else {
    $currentScript = $reqBase . '.php';
}

// Count critical items for sidebar badge
try {
    $criticalCount = $pdo->query("SELECT COUNT(*) FROM v_inventory_stock WHERE status_stok IN ('MENIPIS', 'HABIS')")->fetchColumn();
    $pendingOrderCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'PENDING'")->fetchColumn();
} catch (Exception $e) {
    $criticalCount = 0;
    $pendingOrderCount = 0;
}
?>
<!-- Sidebar Backdrop for mobile -->
<div id="sidebarBackdrop" onclick="toggleSidebar()" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden hidden transition-opacity"></div>

<!-- Sidebar Component -->
<aside id="mainSidebar" class="fixed inset-y-0 left-0 z-50 flex flex-col flex-shrink-0 w-64 bg-slate-900 border-r border-slate-800 transition-transform duration-300 ease-in-out -translate-x-full lg:static lg:translate-x-0">
    <!-- Brand Header -->
    <div class="flex items-center justify-between h-20 px-6 bg-slate-950/60 border-b border-slate-800/80">
        <a href="index.php" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                <i class="fa-solid fa-boxes-stacked text-lg"></i>
            </div>
            <div>
                <span class="text-lg font-bold text-white tracking-tight flex items-center gap-1.5">
                    RUMTANG
                    <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">PRO</span>
                </span>
                <p class="text-xs text-slate-400 font-medium">Inventory & Orders</p>
            </div>
        </a>
        <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <!-- Navigation Links -->
    <div class="flex flex-col justify-between flex-1 px-4 py-6 overflow-y-auto">
        <nav class="space-y-1.5">
            <div class="px-3 pb-2 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                Menu Utama
            </div>

            <!-- Dashboard -->
            <a href="index.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium transition-all <?= $currentScript == 'index.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                <i class="fa-solid fa-chart-pie w-5 text-center text-base <?= $currentScript == 'index.php' ? 'text-white' : 'text-emerald-400' ?>"></i>
                <span>Dashboard</span>
            </a>

            <!-- Inventaris & Stok -->
            <a href="inventaris.php" class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium transition-all <?= $currentScript == 'inventaris.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-warehouse w-5 text-center text-base <?= $currentScript == 'inventaris.php' ? 'text-white' : 'text-emerald-400' ?>"></i>
                    <span>Stok & Barang</span>
                </div>
                <?php if ($criticalCount > 0): ?>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30" title="<?= $criticalCount ?> item stok kritis/menipis">
                        <?= $criticalCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Transaksi Masuk & Keluar -->
            <a href="transaksi.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium transition-all <?= $currentScript == 'transaksi.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                <i class="fa-solid fa-arrow-right-arrow-left w-5 text-center text-base <?= $currentScript == 'transaksi.php' ? 'text-white' : 'text-emerald-400' ?>"></i>
                <span>Log Transaksi</span>
            </a>

            <!-- Order / Permohonan Barang -->
            <a href="order.php" class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium transition-all <?= $currentScript == 'order.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-cart-shopping w-5 text-center text-base <?= $currentScript == 'order.php' ? 'text-white' : 'text-emerald-400' ?>"></i>
                    <span>Order Barang</span>
                </div>
                <?php if ($pendingOrderCount > 0): ?>
                    <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30" title="<?= $pendingOrderCount ?> order butuh persetujuan">
                        <?= $pendingOrderCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="pt-5 px-3 pb-2 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                Laporan & Rekap
            </div>

            <!-- Laporan & Cetak -->
            <a href="laporan.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium transition-all <?= $currentScript == 'laporan.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                <i class="fa-solid fa-file-invoice w-5 text-center text-base <?= $currentScript == 'laporan.php' ? 'text-white' : 'text-emerald-400' ?>"></i>
                <span>Laporan & Cetak</span>
            </a>

            <?php if (!empty($isAdmin)): ?>
            <div class="pt-5 px-3 pb-2 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">
                Administrasi
            </div>

            <!-- Kelola Akun -->
            <a href="pengguna.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium transition-all <?= $currentScript == 'pengguna.php' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' ?>">
                <i class="fa-solid fa-users-gear w-5 text-center text-base <?= $currentScript == 'pengguna.php' ? 'text-white' : 'text-emerald-400' ?>"></i>
                <span>Kelola Akun</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="pt-6 border-t border-slate-800/80 mt-6 space-y-1">
            <button type="button" onclick="openChangePwModal()" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:bg-slate-800/80 hover:text-white transition-all text-left">
                <i class="fa-solid fa-key w-5 text-center text-sm text-slate-400"></i>
                <span>Ganti Password</span>
            </button>
            <form method="POST" action="logout.php">
                <?= Auth::csrfInput($pdo) ?>
                <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-rose-300 hover:bg-rose-500/10 hover:text-rose-200 transition-all text-left">
                    <i class="fa-solid fa-right-from-bracket w-5 text-center text-sm"></i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </div>
</aside>


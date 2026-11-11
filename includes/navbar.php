<?php
// includes/navbar.php
setlocale(LC_TIME, 'id_ID.utf8', 'Indonesian_indonesia.1252');
$todayFormatted = date('l, d F Y');
// Indonesian day translation
$dayMap = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$monthMap = [
    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
    'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
    'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
];
$dayName = $dayMap[date('l')] ?? date('l');
$monthName = $monthMap[date('F')] ?? date('F');
$displayDate = $dayName . ', ' . date('d') . ' ' . $monthName . ' ' . date('Y');
?>
<header class="sticky top-0 z-30 flex items-center justify-between h-20 px-4 sm:px-6 lg:px-8 bg-white/80 backdrop-blur-md border-b border-slate-200">
    <div class="flex items-center gap-4">
        <!-- Mobile Sidebar Toggle -->
        <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600 rounded-xl hover:bg-slate-100 hover:text-slate-900 focus:outline-none">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>

        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">
                <?= isset($pageHeading) ? $pageHeading : 'Dashboard Inventaris Rumtang' ?>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 hidden sm:block">
                <?= isset($pageSubheading) ? $pageSubheading : 'Sistem Kontrol Stok Chemical & Perlengkapan Rumah Tangga' ?>
            </p>
        </div>
    </div>

    <!-- Right Controls -->
    <div class="flex items-center gap-3 sm:gap-4">
        <!-- Date Badge -->
        <div class="hidden md:flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold">
            <i class="fa-regular fa-calendar-days text-emerald-600"></i>
            <span><?= $displayDate ?></span>
        </div>

        <!-- Action Buttons (Admin Only) / Read-Only Indicator (Viewer) -->
        <?php if (!empty($isAdmin)): ?>
            <div class="flex items-center gap-2">
                <!-- Fast Order Button -->
                <a href="order.php?action=new" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs sm:text-sm font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-all shadow-sm">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span class="hidden sm:inline">Order Barang</span>
                </a>

                <!-- Fast Transaction Button -->
                <a href="transaksi.php?action=new" class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition-all hover:shadow-lg">
                    <i class="fa-solid fa-plus"></i>
                    <span class="hidden sm:inline">Input Transaksi</span>
                </a>
            </div>
        <?php else: ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold">
                <i class="fa-solid fa-eye text-[11px]"></i>
                <span class="hidden sm:inline">Mode Lihat (Read-Only)</span>
            </span>
        <?php endif; ?>

        <!-- Profile Avatar & Dropdown -->
        <?php
        $userDisplayName = $currentUser['full_name'] ?? 'Pengguna';
        $words = preg_split('/\s+/', trim($userDisplayName));
        $initials = strtoupper(substr($words[0] ?? 'RT', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
        $roleLabel = !empty($isAdmin) ? 'Administrator' : 'Viewer • Read-Only';
        ?>
        <div class="relative pl-2 sm:pl-3 border-l border-slate-200">
            <button type="button" onclick="toggleUserMenu()" id="userMenuBtn" class="flex items-center gap-2.5 text-left rounded-xl p-1 hover:bg-slate-100 transition-colors focus:outline-none">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full <?= !empty($isAdmin) ? 'bg-slate-800' : 'bg-blue-700' ?> text-white flex items-center justify-center font-bold text-xs sm:text-sm shadow">
                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="hidden xl:block">
                    <p class="text-xs font-bold text-slate-800 leading-tight"><?= htmlspecialchars($userDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-[11px] text-slate-500 leading-tight"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 hidden sm:inline"></i>
            </button>

            <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50">
                <div class="px-4 py-2.5 border-b border-slate-100">
                    <p class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($userDisplayName, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-[11px] text-slate-500 font-mono">@<?= htmlspecialchars($currentUser['username'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; <?= !empty($isAdmin) ? 'Admin' : 'Viewer' ?></p>
                </div>
                <button type="button" onclick="openChangePwModal(); closeUserMenu();" class="w-full px-4 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                    <i class="fa-solid fa-key text-slate-400 w-4 text-center"></i>
                    <span>Ganti Password</span>
                </button>
                <?php if (!empty($isAdmin)): ?>
                    <a href="pengguna.php" class="w-full px-4 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2.5">
                        <i class="fa-solid fa-users-gear text-emerald-600 w-4 text-center"></i>
                        <span>Kelola Akun</span>
                    </a>
                <?php endif; ?>
                <div class="border-t border-slate-100 mt-1 pt-1">
                    <form method="POST" action="logout.php">
                        <?= Auth::csrfInput($pdo) ?>
                        <button type="submit" class="w-full px-4 py-2.5 text-left text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center gap-2.5">
                            <i class="fa-solid fa-right-from-bracket w-4 text-center"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>


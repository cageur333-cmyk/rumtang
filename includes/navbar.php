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

        <!-- Action Buttons -->
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

        <!-- Profile Avatar -->
        <div class="flex items-center gap-3 pl-2 sm:pl-3 border-l border-slate-200">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-sm shadow">
                RT
            </div>
            <div class="hidden xl:block text-left">
                <p class="text-xs font-bold text-slate-800 leading-tight">Admin Rumtang</p>
                <p class="text-[11px] text-slate-500 leading-tight">Operasional & Logistik</p>
            </div>
        </div>
    </div>
</header>


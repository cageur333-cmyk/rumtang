<?php
// inventaris.php
$pageTitle = "Inventaris & Stok Barang";
$pageHeading = "Master Data Stok & Barang";
$pageSubheading = "Kelola Inventaris Chemical, Alat Kebersihan, dan Stok Lainnya";

require_once __DIR__ . '/includes/header.php';

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();

// Fetch All Items with Realtime Stock from View
$items = $pdo->query("
    SELECT * FROM v_inventory_stock 
    ORDER BY category_id ASC, id ASC
")->fetchAll();

// Get counts per category
$catCounts = [];
foreach ($categories as $cat) {
    $catCounts[$cat['id']] = 0;
}
foreach ($items as $item) {
    if (isset($catCounts[$item['category_id']])) {
        $catCounts[$item['category_id']]++;
    }
}
?>

<div class="space-y-6">
    <!-- Header Controls & Filters -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <!-- Category Tab Buttons -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 lg:pb-0">
            <button onclick="filterCategory('ALL')" id="tab-ALL" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
                Semua (<?= count($items) ?>)
            </button>
            <?php foreach ($categories as $cat): ?>
                <button onclick="filterCategory('<?= $cat['id'] ?>')" id="tab-<?= $cat['id'] ?>" class="tab-btn px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all whitespace-nowrap">
                    <?= htmlspecialchars($cat['name']) ?> (<?= $catCounts[$cat['id']] ?? 0 ?>)
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Right Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="api/export_csv.php?type=stock" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                Export Excel/CSV
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                <i class="fa-solid fa-print"></i>
                Cetak
            </button>
            <button onclick="openAddItemModal()" class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition-all">
                <i class="fa-solid fa-circle-plus"></i>
                Tambah Barang Baru
            </button>
        </div>
    </div>

    <!-- Search & Secondary Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative w-full sm:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Cari nama barang..." class="w-full pl-9 pr-4 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>

        <!-- Status Filter Dropdown -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Status Stok:</label>
            <select id="statusFilter" onchange="applyFilters()" class="text-xs sm:text-sm py-2 px-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="ALL">Semua Status</option>
                <option value="AMAN">Aman</option>
                <option value="MENIPIS">Menipis</option>
                <option value="HABIS">Habis</option>
            </select>
        </div>
    </div>

    <!-- Items Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm" id="inventoryTable">
                <thead class="bg-slate-50/90 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Nama Barang</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4 text-center">Stok Awal</th>
                        <th class="py-3.5 px-4 text-center text-teal-600">Total Masuk</th>
                        <th class="py-3.5 px-4 text-center text-rose-600">Total Keluar</th>
                        <th class="py-3.5 px-4 text-center">Stok Akhir</th>
                        <th class="py-3.5 px-4 text-center">Satuan</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium" id="tableBody">
                    <?php $no = 1; foreach ($items as $item): ?>
                        <tr class="item-row hover:bg-slate-50/70 transition-colors" 
                            data-category="<?= $item['category_id'] ?>" 
                            data-status="<?= $item['status_stok'] ?>"
                            data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>">
                            <td class="py-3.5 px-4 text-center text-slate-400 font-mono text-xs"><?= $no++ ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <?= htmlspecialchars($item['name']) ?>
                                <?php if ($item['stok_min'] > 0): ?>
                                    <span class="block text-[11px] font-normal text-slate-400">Min: <?= $item['stok_min'] ?> <?= $item['satuan'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($item['category_name']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center text-slate-600"><?= $item['stok_awal'] ?></td>
                            <td class="py-3.5 px-4 text-center font-bold text-teal-600"><?= $item['total_masuk'] ?></td>
                            <td class="py-3.5 px-4 text-center font-bold text-rose-600"><?= $item['total_keluar'] ?></td>
                            <td class="py-3.5 px-4 text-center font-extrabold text-base <?= $item['stok_akhir'] <= 0 ? 'text-rose-600' : ($item['stok_akhir'] <= $item['stok_min'] ? 'text-amber-600' : 'text-slate-900') ?>">
                                <?= $item['stok_akhir'] ?>
                            </td>
                            <td class="py-3.5 px-4 text-center text-slate-500 font-mono text-xs"><?= htmlspecialchars($item['satuan']) ?></td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <?= getStatusBadge($item['status_stok']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap no-print">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Quick Transaction Button -->
                                    <button onclick="openQuickTxModal(<?= htmlspecialchars(json_encode($item)) ?>)" title="Catat Mutasi Masuk/Keluar" class="p-1.5 text-emerald-600 hover:text-white hover:bg-emerald-600 rounded-lg transition-colors border border-emerald-200">
                                        <i class="fa-solid fa-arrow-right-arrow-left text-xs"></i>
                                    </button>
                                    <!-- Edit Button -->
                                    <button onclick="openEditItemModal(<?= htmlspecialchars(json_encode($item)) ?>)" title="Edit Barang" class="p-1.5 text-blue-600 hover:text-white hover:bg-blue-600 rounded-lg transition-colors border border-blue-200">
                                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    </button>
                                    <!-- Delete Button -->
                                    <button onclick="confirmDeleteItem(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')" title="Hapus Barang" class="p-1.5 text-rose-600 hover:text-white hover:bg-rose-600 rounded-lg transition-colors border border-rose-200">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Tambah / Edit Barang -->
<div id="itemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 id="modalTitle" class="text-base sm:text-lg font-bold text-slate-800">Tambah Barang Baru</h3>
            <button onclick="closeItemModal()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="api/save_item.php" method="POST" class="mt-4 space-y-4">
            <input type="hidden" name="id" id="itemId">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Barang *</label>
                <input type="text" name="name" id="itemName" required placeholder="Contoh: Floor cleaner aroma lavender" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori *</label>
                <select name="category_id" id="itemCategory" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Satuan *</label>
                    <input type="text" name="satuan" id="itemSatuan" required placeholder="GALON, PCS, KALENG, dll" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Batas Min. Stok</label>
                    <input type="number" name="stok_min" id="itemStokMin" value="2" min="0" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div id="stokAwalWrapper">
                <label class="block text-xs font-bold text-slate-700 mb-1">Stok Awal Fisik</label>
                <input type="number" name="stok_awal" id="itemStokAwal" value="0" min="0" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <p class="text-[11px] text-slate-400 mt-1">Stok awal dasar sebelum ada mutasi transaksi.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeItemModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Quick Mutasi Masuk / Keluar -->
<div id="quickTxModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-800">Catat Transaksi Barang</h3>
                <p id="quickTxItemSubtitle" class="text-xs text-emerald-600 font-semibold"></p>
            </div>
            <button onclick="closeQuickTxModal()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="api/save_transaction.php" method="POST" class="mt-4 space-y-4">
            <input type="hidden" name="item_id" id="qtx_itemId">
            <input type="hidden" name="satuan" id="qtx_satuan">
            <input type="hidden" name="redirect_to" value="../inventaris.php">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal *</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Transaksi *</label>
                    <select name="jenis" id="qtx_jenis" required onchange="handleQuickTxJenisChange()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-bold">
                        <option value="MASUK" class="text-teal-600">MASUK (Restock)</option>
                        <option value="KELUAR" class="text-rose-600">KELUAR (Pemakaian)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah (Qty) *</label>
                <div class="flex items-center gap-2">
                    <input type="number" name="jumlah" id="qtx_jumlah" required min="1" value="1" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-bold">
                    <span id="qtx_unit_badge" class="px-3 py-2 bg-slate-100 rounded-xl text-xs font-bold text-slate-600">PCS</span>
                </div>
                <p id="qtx_stock_info" class="text-[11px] text-slate-500 mt-1"></p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Penerima / Pemohon *</label>
                <input type="text" name="penerima_pemohon" required placeholder="Contoh: REZKI, RIKI, ADE, dll" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keperluan / Lokasi Tujuan *</label>
                <input type="text" name="keperluan_lokasi" required placeholder="Contoh: DIKA 1, DIKA 2, TONDANO, TANGERANG" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeQuickTxModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentCategory = 'ALL';

function filterCategory(catId) {
    currentCategory = catId;
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-emerald-600', 'text-white', 'shadow-md', 'shadow-emerald-600/20');
        btn.classList.add('bg-slate-100', 'text-slate-600');
    });
    const activeBtn = document.getElementById('tab-' + catId);
    if (activeBtn) {
        activeBtn.classList.remove('bg-slate-100', 'text-slate-600');
        activeBtn.classList.add('bg-emerald-600', 'text-white', 'shadow-md', 'shadow-emerald-600/20');
    }
    applyFilters();
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase().trim();
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('.item-row');

    rows.forEach(row => {
        const cat = row.getAttribute('data-category');
        const st = row.getAttribute('data-status');
        const name = row.getAttribute('data-name');

        const matchCat = (currentCategory === 'ALL' || cat === currentCategory);
        const matchStatus = (status === 'ALL' || st === status);
        const matchSearch = (name.includes(search));

        if (matchCat && matchStatus && matchSearch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Modal Handlers
function openAddItemModal() {
    document.getElementById('modalTitle').innerText = 'Tambah Barang Baru';
    document.getElementById('itemId').value = '';
    document.getElementById('itemName').value = '';
    document.getElementById('itemCategory').value = '';
    document.getElementById('itemSatuan').value = 'PCS';
    document.getElementById('itemStokMin').value = '2';
    document.getElementById('itemStokAwal').value = '0';
    document.getElementById('stokAwalWrapper').style.display = 'block';
    document.getElementById('itemModal').classList.remove('hidden');
}

function openEditItemModal(item) {
    document.getElementById('modalTitle').innerText = 'Edit Data Barang';
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemName').value = item.name;
    document.getElementById('itemCategory').value = item.category_id;
    document.getElementById('itemSatuan').value = item.satuan;
    document.getElementById('itemStokMin').value = item.stok_min;
    document.getElementById('itemStokAwal').value = item.stok_awal;
    document.getElementById('stokAwalWrapper').style.display = 'block';
    document.getElementById('itemModal').classList.remove('hidden');
}

function closeItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
}

let activeQuickItem = null;
function openQuickTxModal(item) {
    activeQuickItem = item;
    document.getElementById('qtx_itemId').value = item.id;
    document.getElementById('qtx_satuan').value = item.satuan;
    document.getElementById('qtx_unit_badge').innerText = item.satuan;
    document.getElementById('quickTxItemSubtitle').innerText = item.name + ' (' + item.category_name + ')';
    document.getElementById('qtx_stock_info').innerText = 'Stok saat ini: ' + item.stok_akhir + ' ' + item.satuan;
    document.getElementById('quickTxModal').classList.remove('hidden');
}

function closeQuickTxModal() {
    document.getElementById('quickTxModal').classList.add('hidden');
}

function handleQuickTxJenisChange() {
    const jenis = document.getElementById('qtx_jenis').value;
    if (activeQuickItem) {
        if (jenis === 'KELUAR') {
            document.getElementById('qtx_stock_info').innerHTML = '<span class="text-rose-600 font-bold">Stok tersedia: ' + activeQuickItem.stok_akhir + ' ' + activeQuickItem.satuan + '</span>';
        } else {
            document.getElementById('qtx_stock_info').innerText = 'Stok saat ini: ' + activeQuickItem.stok_akhir + ' ' + activeQuickItem.satuan;
        }
    }
}

function confirmDeleteItem(id, name) {
    Swal.fire({
        title: 'Hapus barang ini?',
        text: 'Apakah Anda yakin ingin menghapus barang "' + name + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'api/delete_item.php?id=' + id;
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


<?php
// transaksi.php
$pageTitle = "Log Transaksi Barang";
$pageHeading = "Log Mutasi Masuk & Keluar";
$pageSubheading = "Pencatatan Riwayat Penerimaan dan Pengeluaran Perlengkapan Rumtang";

require_once __DIR__ . '/includes/header.php';

// Filter parameters
$filterJenis = $_GET['jenis'] ?? 'ALL';
$filterLokasi = $_GET['lokasi'] ?? 'ALL';
$filterStartDate = $_GET['start_date'] ?? '';
$filterEndDate = $_GET['end_date'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query
$where = ["1=1"];
$params = [];

if ($filterJenis !== 'ALL' && in_array($filterJenis, ['MASUK', 'KELUAR'])) {
    $where[] = "t.jenis = :jenis";
    $params[':jenis'] = $filterJenis;
}
if ($filterLokasi !== 'ALL' && !empty($filterLokasi)) {
    $where[] = "t.keperluan_lokasi = :lokasi";
    $params[':lokasi'] = $filterLokasi;
}
if (!empty($filterStartDate)) {
    $where[] = "t.tanggal >= :start_date";
    $params[':start_date'] = $filterStartDate;
}
if (!empty($filterEndDate)) {
    $where[] = "t.tanggal <= :end_date";
    $params[':end_date'] = $filterEndDate;
}
if (!empty($search)) {
    $where[] = "(i.name LIKE :s OR t.trans_no LIKE :s OR t.penerima_pemohon LIKE :s OR t.keperluan_lokasi LIKE :s)";
    $params[':s'] = "%{$search}%";
}

$whereClause = implode(" AND ", $where);

// Fetch transactions
$sql = "
    SELECT t.*, i.name as item_name, c.name as category_name, i.satuan as default_satuan
    FROM transactions t
    JOIN items i ON t.item_id = i.id
    JOIN categories c ON i.category_id = c.id
    WHERE {$whereClause}
    ORDER BY t.tanggal DESC, t.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get unique locations for dropdown
$locations = $pdo->query("SELECT DISTINCT keperluan_lokasi FROM transactions ORDER BY keperluan_lokasi ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch all items for input modal
$allItems = $pdo->query("
    SELECT id, name, category_name, satuan, stok_akhir 
    FROM v_inventory_stock 
    ORDER BY category_name ASC, name ASC
")->fetchAll();

// Quick Summary of filtered data
$filteredMasukQty = 0;
$filteredKeluarQty = 0;
foreach ($transactions as $t) {
    if ($t['jenis'] === 'MASUK') $filteredMasukQty += $t['jumlah'];
    if ($t['jenis'] === 'KELUAR') $filteredKeluarQty += $t['jumlah'];
}
?>

<div class="space-y-6">
    <!-- Action Banner & Filter Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900">Filter & Pencarian Transaksi</h3>
                <p class="text-xs text-slate-500">Menampilkan total <strong><?= count($transactions) ?></strong> data mutasi barang</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="api/export_csv.php?type=transactions" class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i>
                    Export CSV
                </a>
                <?php if ($isAdmin): ?>
                <button onclick="openNewTxModal()" class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition-all">
                    <i class="fa-solid fa-plus"></i>
                    Catat Transaksi Baru
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="transaksi.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t border-slate-100">
            <!-- Search -->
            <div class="lg:col-span-1">
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Cari Kata Kunci</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Barang / Pemohon / TRX" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- Jenis Transaksi -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Jenis Transaksi</label>
                <select name="jenis" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="ALL">Semua Jenis</option>
                    <option value="MASUK" <?= $filterJenis === 'MASUK' ? 'selected' : '' ?>>MASUK (Penerimaan)</option>
                    <option value="KELUAR" <?= $filterJenis === 'KELUAR' ? 'selected' : '' ?>>KELUAR (Pengeluaran)</option>
                </select>
            </div>

            <!-- Lokasi / Keperluan -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Lokasi / Keperluan</label>
                <select name="lokasi" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="ALL">Semua Lokasi</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= htmlspecialchars($loc) ?>" <?= $filterLokasi === $loc ? 'selected' : '' ?>>
                            <?= htmlspecialchars($loc) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tanggal Mulai -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($filterStartDate) ?>" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- Tanggal Sampai & Submit -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($filterEndDate) ?>" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <button type="submit" class="px-3.5 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition-all h-[34px]">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <a href="transaksi.php" title="Reset Filter" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all h-[34px] flex items-center justify-center">
                    <i class="fa-solid fa-arrow-rotate-left"></i>
                </a>
            </div>
        </form>

        <!-- Quick Summary Ribbon -->
        <div class="flex items-center gap-4 text-xs font-semibold pt-1 text-slate-600">
            <span>Hasil Filter:</span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 border border-teal-200">
                <i class="fa-solid fa-arrow-down text-[10px]"></i> Total Masuk: <strong><?= number_format($filteredMasukQty) ?> Qty</strong>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                <i class="fa-solid fa-arrow-up text-[10px]"></i> Total Keluar: <strong><?= number_format($filteredKeluarQty) ?> Qty</strong>
            </span>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50/90 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Kode TRX</th>
                        <th class="py-3.5 px-4">Tanggal</th>
                        <th class="py-3.5 px-4 text-center">Jenis</th>
                        <th class="py-3.5 px-4">Nama Barang</th>
                        <th class="py-3.5 px-4 text-center">Jumlah</th>
                        <th class="py-3.5 px-4 text-center">Satuan</th>
                        <th class="py-3.5 px-4">Penerima / Pemohon</th>
                        <th class="py-3.5 px-4">Keperluan / Lokasi</th>
                        <?php if ($isAdmin): ?>
                            <th class="py-3.5 px-4 text-right no-print">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="<?= $isAdmin ? 10 : 9 ?>" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300"></i>
                                <p class="text-xs">Tidak ada data transaksi yang cocok dengan filter pencarian.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $rowNo = 1; foreach ($transactions as $tx): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 text-center text-slate-400 font-mono text-xs"><?= $rowNo++ ?></td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-500 font-semibold whitespace-nowrap">
                                    <?= htmlspecialchars($tx['trans_no'] ?: 'TRX-' . str_pad($tx['id'], 4, '0', STR_PAD_LEFT)) ?>
                                </td>
                                <td class="py-3 px-4 text-slate-700 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($tx['tanggal'])) ?>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    <?php if ($tx['jenis'] === 'MASUK'): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-100 text-teal-800 border border-teal-200">
                                            <i class="fa-solid fa-arrow-down text-[9px]"></i> MASUK
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                            <i class="fa-solid fa-arrow-up text-[9px]"></i> KELUAR
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    <?= htmlspecialchars($tx['item_name']) ?>
                                    <span class="block text-[11px] text-slate-400 font-normal"><?= htmlspecialchars($tx['category_name']) ?></span>
                                </td>
                                <td class="py-3 px-4 text-center font-extrabold text-sm <?= $tx['jenis'] === 'MASUK' ? 'text-teal-600' : 'text-rose-600' ?>">
                                    <?= $tx['jumlah'] ?>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-xs text-slate-500"><?= htmlspecialchars($tx['satuan']) ?></td>
                                <td class="py-3 px-4 text-slate-700 font-semibold whitespace-nowrap">
                                    <?= htmlspecialchars($tx['penerima_pemohon']) ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 font-medium text-slate-700 text-xs">
                                        <?= htmlspecialchars($tx['keperluan_lokasi']) ?>
                                    </span>
                                    <?php if (!empty($tx['keterangan']) && $tx['keterangan'] !== 'Import data awal'): ?>
                                        <span class="block text-[11px] text-slate-400 italic"><?= htmlspecialchars($tx['keterangan']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($isAdmin): ?>
                                <td class="py-3 px-4 text-right whitespace-nowrap no-print">
                                    <button onclick="confirmDeleteTx(<?= $tx['id'] ?>)" title="Hapus Transaksi" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modal Input Transaksi Baru -->
<div id="newTxModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-800">Catat Mutasi Transaksi</h3>
                <p class="text-xs text-slate-500">Input penerimaan barang masuk atau pemakaian keluar</p>
            </div>
            <button onclick="closeNewTxModal()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="api/save_transaction.php" method="POST" class="mt-4 space-y-4">
            <?= Auth::csrfInput($pdo) ?>
            <input type="hidden" name="redirect_to" value="../transaksi.php">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Transaksi *</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Transaksi *</label>
                    <select name="jenis" id="modal_jenis" required onchange="onTxItemOrTypeChange()" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-bold">
                        <option value="MASUK">MASUK (Penerimaan / Restock)</option>
                        <option value="KELUAR" selected>KELUAR (Pengeluaran / Dipakai)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Barang *</label>
                <select name="item_id" id="modal_item_id" required onchange="onTxItemOrTypeChange()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">-- Pilih Barang --</option>
                    <?php 
                    $currentGroup = '';
                    foreach ($allItems as $itm): 
                        if ($itm['category_name'] !== $currentGroup):
                            if ($currentGroup !== '') echo '</optgroup>';
                            $currentGroup = $itm['category_name'];
                            echo '<optgroup label="' . htmlspecialchars($currentGroup) . '">';
                        endif;
                    ?>
                        <option value="<?= $itm['id'] ?>" data-satuan="<?= htmlspecialchars($itm['satuan']) ?>" data-stock="<?= $itm['stok_akhir'] ?>">
                            <?= htmlspecialchars($itm['name']) ?> (Sisa: <?= $itm['stok_akhir'] ?> <?= $itm['satuan'] ?>)
                        </option>
                    <?php endforeach; echo '</optgroup>'; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah (Qty) *</label>
                    <input type="number" name="jumlah" id="modal_jumlah" required min="1" value="1" oninput="onTxItemOrTypeChange()" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Satuan *</label>
                    <input type="text" name="satuan" id="modal_satuan" required value="PCS" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase">
                </div>
            </div>

            <!-- Live Stock Notice -->
            <div id="stockNoticeBox" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs hidden">
                <span id="stockNoticeText"></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Penerima / Pemohon *</label>
                    <input type="text" name="penerima_pemohon" list="penerimaList" required placeholder="Nama orang" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase">
                    <datalist id="penerimaList">
                        <option value="REZKI">
                        <option value="RIKI">
                        <option value="BIONDI">
                        <option value="JACOB">
                        <option value="SEPTIAN">
                        <option value="IRFAN">
                        <option value="ADE">
                        <option value="DITO">
                        <option value="ONIP">
                        <option value="WINARNO">
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Keperluan / Lokasi *</label>
                    <input type="text" name="keperluan_lokasi" list="lokasiList" required placeholder="Lokasi unit" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase">
                    <datalist id="lokasiList">
                        <option value="DIKA 1">
                        <option value="DIKA 2">
                        <option value="TONDANO">
                        <option value="TANGERANG">
                        <option value="BEKASI">
                        <option value="KARAWANG">
                        <option value="MATRAMAN">
                        <option value="TOKPED">
                    </datalist>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Keterangan Tambahan (Opsional)</label>
                <input type="text" name="keterangan" placeholder="Contoh: Kebutuhan insidental pembersihan area lobi" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeNewTxModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                <button type="submit" id="btnSubmitTx" class="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openNewTxModal() {
    document.getElementById('newTxModal').classList.remove('hidden');
    onTxItemOrTypeChange();
}

function closeNewTxModal() {
    document.getElementById('newTxModal').classList.add('hidden');
}

function onTxItemOrTypeChange() {
    const itemSelect = document.getElementById('modal_item_id');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const jenis = document.getElementById('modal_jenis').value;
    const qtyInput = document.getElementById('modal_jumlah');
    const qty = parseInt(qtyInput.value) || 0;
    const noticeBox = document.getElementById('stockNoticeBox');
    const noticeText = document.getElementById('stockNoticeText');
    const btnSubmit = document.getElementById('btnSubmitTx');

    if (!selectedOption || !selectedOption.value) {
        noticeBox.classList.add('hidden');
        btnSubmit.disabled = false;
        return;
    }

    const defaultSatuan = selectedOption.getAttribute('data-satuan');
    const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
    
    // Auto populate unit
    document.getElementById('modal_satuan').value = defaultSatuan;

    noticeBox.classList.remove('hidden');

    if (jenis === 'KELUAR') {
        if (qty > currentStock) {
            noticeBox.className = "p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700";
            noticeText.innerHTML = '<i class="fa-solid fa-circle-exclamation mr-1"></i> <strong>Peringatan!</strong> Stok tidak mencukupi. Stok saat ini: <strong>' + currentStock + ' ' + defaultSatuan + '</strong>. Tidak dapat mengeluarkan ' + qty + ' ' + defaultSatuan + '.';
            btnSubmit.disabled = true;
            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            noticeBox.className = "p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800";
            noticeText.innerHTML = '<i class="fa-solid fa-circle-info mr-1"></i> Stok saat ini: <strong>' + currentStock + ' ' + defaultSatuan + '</strong>. Sisa stok setelah transaksi: <strong>' + (currentStock - qty) + ' ' + defaultSatuan + '</strong>.';
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    } else {
        noticeBox.className = "p-3 rounded-xl bg-teal-50 border border-teal-200 text-xs text-teal-800";
        noticeText.innerHTML = '<i class="fa-solid fa-circle-plus mr-1"></i> Stok saat ini: <strong>' + currentStock + ' ' + defaultSatuan + '</strong>. Stok baru setelah masuk: <strong>' + (currentStock + qty) + ' ' + defaultSatuan + '</strong>.';
        btnSubmit.disabled = false;
        btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function confirmDeleteTx(id) {
    Swal.fire({
        title: 'Hapus data transaksi ini?',
        text: 'Menghapus transaksi akan otomatis mengembalikan perhitungan stok barang terkait.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            submitProtectedPost('api/delete_transaction.php', { id: id });
        }
    });
}

// Auto open modal if action=new in URL
<?php if ($isAdmin && isset($_GET['action']) && $_GET['action'] === 'new'): ?>
document.addEventListener('DOMContentLoaded', function() {
    openNewTxModal();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


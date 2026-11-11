<?php
// order.php
$pageTitle = "Order & Permohonan Barang";
$pageHeading = "Order & Permohonan Pengadaan";
$pageSubheading = "Formulir Pemesanan Barang & Pengelolaan Permohonan Operasional";

require_once __DIR__ . '/includes/header.php';

// Fetch all orders
$orders = $pdo->query("
    SELECT o.*, 
           COUNT(oi.id) as total_items, 
           COALESCE(SUM(oi.jumlah), 0) as total_qty
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.tanggal DESC, o.id DESC
")->fetchAll();

// Fetch items for form
$itemsList = $pdo->query("
    SELECT id, name, category_name, satuan, stok_akhir, status_stok 
    FROM v_inventory_stock 
    ORDER BY category_name ASC, name ASC
")->fetchAll();

$isNewAction = $isAdmin && isset($_GET['action']) && $_GET['action'] === 'new';
?>

<div class="space-y-6">
    <!-- Top Action Card -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">Manajemen Order Barang</h3>
            <p class="text-xs text-slate-500">Ajukan permohonan kebutuhan chemical & alat kebersihan via sistem web</p>
        </div>
        <?php if ($isAdmin): ?>
        <div class="flex items-center gap-2.5">
            <button onclick="toggleOrderForm()" id="btnToggleOrder" class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition-all">
                <i class="fa-solid fa-cart-plus"></i>
                <span><?= $isNewAction ? 'Tutup Formulir' : '+ Buat Order Barang Baru' ?></span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Order Submission Form (Toggleable) -->
    <div id="orderFormContainer" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm <?= $isNewAction ? '' : 'hidden' ?>">
        <div class="pb-4 mb-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-file-pen"></i>
                </div>
                <div>
                    <h4 class="text-sm sm:text-base font-bold text-slate-800">Form Pengajuan Order Barang</h4>
                    <p class="text-xs text-slate-400">Pilih barang dan jumlah yang dibutuhkan untuk operasional</p>
                </div>
            </div>
            <button type="button" onclick="toggleOrderForm()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="api/save_order.php" method="POST" id="mainOrderForm" class="space-y-6">
            <?= Auth::csrfInput($pdo) ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Permohonan *</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemohon *</label>
                    <input type="text" name="pemohon" list="pemohonList" required placeholder="Contoh: REZKI, RIKI, BIONDI" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase">
                    <datalist id="pemohonList">
                        <option value="REZKI">
                        <option value="RIKI">
                        <option value="BIONDI">
                        <option value="JACOB">
                        <option value="SEPTIAN">
                        <option value="IRFAN">
                        <option value="ADE">
                        <option value="DITO">
                        <option value="ONIP">
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Lokasi Tujuan / Divisi *</label>
                    <input type="text" name="lokasi_tujuan" list="orderLokasiList" required placeholder="Contoh: DIKA 1, DIKA 2, TONDANO" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase">
                    <datalist id="orderLokasiList">
                        <option value="DIKA 1">
                        <option value="DIKA 2">
                        <option value="TONDANO">
                        <option value="TANGERANG">
                        <option value="BEKASI">
                        <option value="KARAWANG">
                        <option value="MATRAMAN">
                    </datalist>
                </div>
            </div>

            <!-- Items Table in Order -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Daftar Barang yang Dipesan *</label>
                    <button type="button" onclick="addOrderItemRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-all">
                        <i class="fa-solid fa-plus text-[10px]"></i> Tambah Baris Barang
                    </button>
                </div>

                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs sm:text-sm" id="orderItemsTable">
                        <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase">
                            <tr>
                                <th class="py-2.5 px-3">Nama Barang</th>
                                <th class="py-2.5 px-3 w-28 text-center">Jumlah (Qty)</th>
                                <th class="py-2.5 px-3 w-24 text-center">Satuan</th>
                                <th class="py-2.5 px-3">Keterangan / Alasan</th>
                                <th class="py-2.5 px-3 w-12 text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsBody" class="divide-y divide-slate-100">
                            <!-- Row 1 -->
                            <tr class="order-item-row">
                                <td class="p-2.5">
                                    <select name="item_ids[]" required onchange="handleItemSelect(this)" class="w-full px-2.5 py-1.5 text-xs sm:text-sm rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <option value="">-- Pilih Barang --</option>
                                        <?php 
                                        $currCat = '';
                                        foreach ($itemsList as $it): 
                                            if ($it['category_name'] !== $currCat):
                                                if ($currCat !== '') echo '</optgroup>';
                                                $currCat = $it['category_name'];
                                                echo '<optgroup label="' . htmlspecialchars($currCat) . '">';
                                            endif;
                                        ?>
                                            <option value="<?= $it['id'] ?>" data-satuan="<?= htmlspecialchars($it['satuan']) ?>" data-stock="<?= $it['stok_akhir'] ?>">
                                                <?= htmlspecialchars($it['name']) ?> (Sisa: <?= $it['stok_akhir'] ?> <?= $it['satuan'] ?>)
                                            </option>
                                        <?php endforeach; echo '</optgroup>'; ?>
                                    </select>
                                </td>
                                <td class="p-2.5">
                                    <input type="number" name="quantities[]" min="1" value="1" required class="w-full px-2 py-1.5 text-xs sm:text-sm text-center font-bold rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </td>
                                <td class="p-2.5">
                                    <input type="text" name="satuans[]" value="PCS" readonly class="row-satuan w-full px-2 py-1.5 text-xs sm:text-sm text-center font-mono rounded-lg bg-slate-50 border border-slate-200 text-slate-500">
                                </td>
                                <td class="p-2.5">
                                    <input type="text" name="keterangans[]" placeholder="Contoh: Stok pantry habis" class="w-full px-2.5 py-1.5 text-xs sm:text-sm rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </td>
                                <td class="p-2.5 text-center">
                                    <button type="button" onclick="removeOrderItemRow(this)" class="text-slate-400 hover:text-rose-600 p-1">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan untuk Order (Opsional)</label>
                <textarea name="catatan" rows="2" placeholder="Tuliskan catatan khusus atau prioritas order..." class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="toggleOrderForm()" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                <button type="submit" class="px-6 py-2.5 text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Kirim Order Barang
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Orders List Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Riwayat Permohonan & Order Barang</h3>
                <p class="text-xs text-slate-500">Pantau status persetujuan dan realisasi pengeluaran barang</p>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700">
                Total <?= count($orders) ?> Order
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">No. Order</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Pemohon</th>
                        <th class="py-3 px-4">Lokasi Tujuan</th>
                        <th class="py-3 px-4 text-center">Jumlah Barang</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Catatan</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-slate-300"></i>
                                <p class="text-xs">Belum ada riwayat order barang.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-emerald-700 whitespace-nowrap">
                                    <?= htmlspecialchars($ord['order_no']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($ord['tanggal'])) ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    <?= htmlspecialchars($ord['pemohon']) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 font-medium text-slate-700 text-xs">
                                        <?= htmlspecialchars($ord['lokasi_tujuan']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-slate-800"><?= $ord['total_items'] ?> Item</span>
                                    <span class="block text-[11px] text-slate-400"><?= $ord['total_qty'] ?> Total Qty</span>
                                </td>
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <?= getStatusBadge($ord['status']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 max-w-xs truncate">
                                    <?= htmlspecialchars($ord['catatan'] ?: '-') ?>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- View / Manage Button -->
                                        <button onclick="viewOrderDetail(<?= $ord['id'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                            <i class="fa-solid fa-eye text-slate-500"></i> <?= $isAdmin ? 'Detail &amp; Kelola' : 'Lihat Detail' ?>
                                        </button>
                                        <!-- Delete Button (if pending or rejected) -->
                                        <?php if ($isAdmin && ($ord['status'] === 'PENDING' || $ord['status'] === 'DITOLAK')): ?>
                                            <button onclick="confirmDeleteOrder(<?= $ord['id'] ?>, '<?= htmlspecialchars(addslashes($ord['order_no']), ENT_QUOTES, 'UTF-8') ?>')" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail & Kelola Order -->
<div id="orderDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 id="modalOrderNo" class="text-base sm:text-lg font-bold text-slate-900">Detail Order</h3>
                <p id="modalOrderMeta" class="text-xs text-slate-500"></p>
            </div>
            <button onclick="closeOrderDetailModal()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Order Items Container -->
        <div class="mt-4">
            <h5 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Rincian Barang yang Diminta:</h5>
            <div id="modalOrderItemsList" class="space-y-2 border border-slate-100 rounded-xl p-3 bg-slate-50/60">
                <p class="text-xs text-slate-400 text-center py-4">Memuat data barang...</p>
            </div>
        </div>

        <!-- Note -->
        <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-600" id="modalOrderNoteBox">
            <span class="font-bold text-slate-700">Catatan Pemohon:</span> <span id="modalOrderNote">-</span>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Action Form to Change Status -->
        <form action="api/update_order_status.php" method="POST" id="formOrderStatus" class="mt-5 pt-4 border-t border-slate-100 space-y-3">
            <?= Auth::csrfInput($pdo) ?>
            <input type="hidden" name="order_id" id="modalOrderId">

            <label class="block text-xs font-bold text-slate-700">Ubah Status Order:</label>
            <div class="grid grid-cols-3 gap-2">
                <button type="submit" name="status" value="DISETUJUI" class="py-2 px-3 text-xs font-bold rounded-xl bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Setujui
                </button>
                <button type="submit" name="status" value="DITOLAK" class="py-2 px-3 text-xs font-bold rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-600 hover:text-white transition-all flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-xmark"></i> Tolak
                </button>
                <button type="submit" name="status" value="SELESAI" class="py-2 px-3 text-xs font-bold rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-md transition-all flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-check-double"></i> Selesaikan
                </button>
            </div>

            <!-- Auto Issue Goods Checkbox -->
            <div class="pt-2">
                <label class="flex items-start gap-2 cursor-pointer p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80">
                    <input type="checkbox" name="auto_keluar" value="1" checked class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500">
                    <span class="text-xs text-emerald-900">
                        <strong>Otomatis Potong Stok (Catat Barang Keluar)</strong> saat order diselesaikan, agar stok di database langsung berkurang otomatis.
                    </span>
                </label>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<template id="orderItemRowTemplate">
    <tr class="order-item-row">
        <td class="p-2.5">
            <select name="item_ids[]" required onchange="handleItemSelect(this)" class="w-full px-2.5 py-1.5 text-xs sm:text-sm rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">-- Pilih Barang --</option>
                <?php 
                $currCat = '';
                foreach ($itemsList as $it): 
                    if ($it['category_name'] !== $currCat):
                        if ($currCat !== '') echo '</optgroup>';
                        $currCat = $it['category_name'];
                        echo '<optgroup label="' . htmlspecialchars($currCat) . '">';
                    endif;
                ?>
                    <option value="<?= $it['id'] ?>" data-satuan="<?= htmlspecialchars($it['satuan']) ?>" data-stock="<?= $it['stok_akhir'] ?>">
                        <?= htmlspecialchars($it['name']) ?> (Sisa: <?= $it['stok_akhir'] ?> <?= $it['satuan'] ?>)
                    </option>
                <?php endforeach; echo '</optgroup>'; ?>
            </select>
        </td>
        <td class="p-2.5">
            <input type="number" name="quantities[]" min="1" value="1" required class="w-full px-2 py-1.5 text-xs sm:text-sm text-center font-bold rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </td>
        <td class="p-2.5">
            <input type="text" name="satuans[]" value="PCS" readonly class="row-satuan w-full px-2 py-1.5 text-xs sm:text-sm text-center font-mono rounded-lg bg-slate-50 border border-slate-200 text-slate-500">
        </td>
        <td class="p-2.5">
            <input type="text" name="keterangans[]" placeholder="Contoh: Stok habis" class="w-full px-2.5 py-1.5 text-xs sm:text-sm rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </td>
        <td class="p-2.5 text-center">
            <button type="button" onclick="removeOrderItemRow(this)" class="text-slate-400 hover:text-rose-600 p-1">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    </tr>
</template>

<script>
<?php if ($isAdmin): ?>
function toggleOrderForm() {
    const container = document.getElementById('orderFormContainer');
    const btn = document.getElementById('btnToggleOrder');
    if (container.classList.contains('hidden')) {
        container.classList.remove('hidden');
        btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Tutup Formulir';
        container.scrollIntoView({ behavior: 'smooth' });
    } else {
        container.classList.add('hidden');
        btn.innerHTML = '<i class="fa-solid fa-cart-plus"></i> + Buat Order Barang Baru';
    }
}

function handleItemSelect(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const row = selectElem.closest('.order-item-row');
    if (selectedOption && selectedOption.value) {
        const satuan = selectedOption.getAttribute('data-satuan');
        row.querySelector('.row-satuan').value = satuan;
    }
}

function addOrderItemRow() {
    const template = document.getElementById('orderItemRowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('orderItemsBody').appendChild(clone);
}

function removeOrderItemRow(button) {
    const rows = document.querySelectorAll('.order-item-row');
    if (rows.length > 1) {
        button.closest('.order-item-row').remove();
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Perhatian',
            text: 'Minimal harus ada 1 item barang dalam order ini.',
            timer: 2000,
            showConfirmButton: false
        });
    }
}

<?php endif; ?>
// Fetch and View Order Details
function viewOrderDetail(orderId) {
    const modalOrderIdEl = document.getElementById('modalOrderId');
    if (modalOrderIdEl) {
        modalOrderIdEl.value = orderId;
    }
    document.getElementById('orderDetailModal').classList.remove('hidden');
    document.getElementById('modalOrderItemsList').innerHTML = '<p class="text-xs text-slate-400 text-center py-4">Memuat data...</p>';

    fetch('api/get_order_details.php?id=' + orderId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const ord = data.order;
                document.getElementById('modalOrderNo').innerText = 'Order #' + ord.order_no;
                document.getElementById('modalOrderMeta').innerText = 'Pemohon: ' + ord.pemohon + ' | Lokasi: ' + ord.lokasi_tujuan + ' | ' + ord.tanggal;
                document.getElementById('modalOrderNote').innerText = ord.catatan || '-';

                let html = '';
                data.items.forEach((item, idx) => {
                    html += `
                        <div class="flex items-center justify-between p-2.5 bg-white rounded-lg border border-slate-100 text-xs">
                            <div>
                                <strong class="text-slate-800">${item.item_name}</strong>
                                <span class="block text-[11px] text-slate-400">${item.category_name} ${item.keterangan ? '&bull; ' + item.keterangan : ''}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-extrabold text-sm text-emerald-700">${item.jumlah}</span>
                                <span class="text-slate-500 font-mono text-[11px]">${item.satuan}</span>
                            </div>
                        </div>
                    `;
                });
                document.getElementById('modalOrderItemsList').innerHTML = html;
            } else {
                document.getElementById('modalOrderItemsList').innerHTML = '<p class="text-xs text-rose-500 text-center py-2">' + data.message + '</p>';
            }
        })
        .catch(err => {
            document.getElementById('modalOrderItemsList').innerHTML = '<p class="text-xs text-rose-500 text-center py-2">Gagal memuat rincian.</p>';
        });
}

function closeOrderDetailModal() {
    document.getElementById('orderDetailModal').classList.add('hidden');
}

<?php if ($isAdmin): ?>
function confirmDeleteOrder(orderId, orderNo) {
    Swal.fire({
        title: 'Hapus order ' + orderNo + '?',
        text: 'Data order ini akan dihapus dari riwayat.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            submitProtectedPost('api/delete_order.php', { id: orderId });
        }
    });
}
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


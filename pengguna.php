<?php
// pengguna.php - Kelola Akun Pengguna (Khusus Administrator)
$pageTitle = "Kelola Akun Pengguna";
$pageHeading = "Manajemen Akses & Akun";
$pageSubheading = "Atur Akun Administrator & Viewer (Read-Only) Sistem Rumtang";
$requireAdminPage = true;

require_once __DIR__ . '/includes/header.php';

$users = $pdo->query("
    SELECT id, username, full_name, role, is_active, last_login_at, created_at
    FROM users
    ORDER BY role ASC, id ASC
")->fetchAll();
?>

<div class="space-y-6">
    <!-- Top Info & Add User Button -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base sm:text-lg font-bold text-slate-900">Daftar Pengguna Terdaftar</h3>
            <p class="text-xs text-slate-500">
                <strong>Admin</strong> memiliki akses penuh ubah/hapus data, sedangkan <strong>Viewer</strong> hanya dapat melihat dashboard, log, &amp; download laporan.
            </p>
        </div>
        <button onclick="openAddUserModal()" class="inline-flex items-center gap-2 px-4 py-2 text-xs sm:text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 transition-all whitespace-nowrap">
            <i class="fa-solid fa-user-plus"></i>
            Tambah Akun Baru
        </button>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50/90 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-4">Username</th>
                        <th class="py-3.5 px-4">Nama Lengkap</th>
                        <th class="py-3.5 px-4 text-center">Role Akses</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Login Terakhir (UTC)</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php foreach ($users as $u): ?>
                        <?php $isSelf = (int)$u['id'] === (int)$currentUser['id']; ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                <?= htmlspecialchars($u['username']) ?>
                                <?php if ($isSelf): ?>
                                    <span class="ml-1.5 text-[10px] font-sans font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Anda</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <?= htmlspecialchars($u['full_name']) ?>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-shield-halved text-[10px]"></i> Admin
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                        <i class="fa-solid fa-eye text-[10px]"></i> Viewer (Read-Only)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <?php if ((int)$u['is_active'] === 1): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-800">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 text-xs whitespace-nowrap">
                                <?= $u['last_login_at'] ? htmlspecialchars($u['last_login_at']) : '<span class="italic text-slate-400">Belum pernah</span>' ?>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Reset Password -->
                                    <button onclick="openResetPwModal(<?= (int)$u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')" title="Reset Password" class="px-2.5 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-600 hover:text-white rounded-lg border border-blue-200 transition-colors">
                                        <i class="fa-solid fa-key mr-1"></i> Reset PW
                                    </button>

                                    <?php if (!$isSelf): ?>
                                        <!-- Toggle Role -->
                                        <form method="POST" action="api/manage_users.php" class="inline">
                                            <?= Auth::csrfInput($pdo) ?>
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'viewer' : 'admin' ?>">
                                            <button type="submit" title="Ubah Role" class="px-2.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                                <?= $u['role'] === 'admin' ? 'Jadikan Viewer' : 'Jadikan Admin' ?>
                                            </button>
                                        </form>

                                        <!-- Toggle Active -->
                                        <form method="POST" action="api/manage_users.php" class="inline">
                                            <?= Auth::csrfInput($pdo) ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <button type="submit" class="px-2.5 py-1.5 text-xs font-semibold <?= (int)$u['is_active'] === 1 ? 'text-rose-700 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white border border-emerald-200' ?> rounded-lg transition-colors">
                                                <?= (int)$u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun Baru -->
<div id="addUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base sm:text-lg font-bold text-slate-800">Tambah Akun Baru</h3>
            <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form method="POST" action="api/manage_users.php" class="mt-4 space-y-4">
            <?= Auth::csrfInput($pdo) ?>
            <input type="hidden" name="action" value="create">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Username *</label>
                <input type="text" name="username" required pattern="[a-zA-Z0-9._-]{3,30}" placeholder="Contoh: manajer atau staff1" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <p class="text-[11px] text-slate-400 mt-1">3-30 karakter tanpa spasi (huruf, angka, titik, _, -).</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                <input type="text" name="full_name" required placeholder="Contoh: Pak Budi (Koordinator)" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Role Akses *</label>
                <select name="role" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="viewer">Viewer (Hanya Lihat &amp; Download Laporan)</option>
                    <option value="admin">Admin (Akses Penuh Kelola Data)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Password Awal *</label>
                <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Buat Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Password User -->
<div id="resetPwModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-slate-800">Reset Password Akun</h3>
                <p id="resetPwSubtitle" class="text-xs text-emerald-600 font-semibold"></p>
            </div>
            <button onclick="document.getElementById('resetPwModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form method="POST" action="api/manage_users.php" class="mt-4 space-y-4">
            <?= Auth::csrfInput($pdo) ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetPwUserId">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru *</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('resetPwModal').classList.add('hidden')" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                <button type="submit" class="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md">Simpan Password Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
}

function openResetPwModal(userId, username) {
    document.getElementById('resetPwUserId').value = userId;
    document.getElementById('resetPwSubtitle').innerText = 'Username: ' + username;
    document.getElementById('resetPwModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

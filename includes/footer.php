<?php
// includes/footer.php
$flash = Auth::consumeFlash($pdo);
$flashSuccess = $flash['success'] ?? null;
$flashError = $flash['error'] ?? null;
?>
            </main>
            
            <!-- Footer -->
            <footer class="w-full py-4 px-6 sm:px-8 border-t border-slate-200 bg-white text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2 no-print">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-slate-700">Rumtang Inventory System</span> &bull; 
                    <span>Chemicals & Cleaning Supplies</span>
                </div>
                <div>
                    Departemen Rumah Tangga &amp; Logistik
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal Ganti Password (Semua Role) -->
    <div id="changePasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden no-print">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-800">Ganti Password Akun</h3>
                    <p class="text-xs text-slate-500">Username: <strong class="font-mono text-slate-700"><?= htmlspecialchars($currentUser['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
                </div>
                <button type="button" onclick="closeChangePwModal()" class="text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" action="api/change_password.php" class="mt-4 space-y-4">
                <?= Auth::csrfInput($pdo) ?>
                <input type="hidden" name="redirect_to" value="/<?= htmlspecialchars($currentScript ?? 'index.php', ENT_QUOTES, 'UTF-8') ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Saat Ini *</label>
                    <input type="password" name="current_password" required autocomplete="current-password" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru *</label>
                    <input type="password" name="new_password" required minlength="6" autocomplete="new-password" placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Ulangi Password Baru *</label>
                    <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password" placeholder="Ketik ulang password baru" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeChangePwModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 text-xs sm:text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md">Simpan Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function toggleUserMenu() {
            const dd = document.getElementById('userMenuDropdown');
            if (dd) dd.classList.toggle('hidden');
        }

        function closeUserMenu() {
            const dd = document.getElementById('userMenuDropdown');
            if (dd) dd.classList.add('hidden');
        }

        document.addEventListener('click', function (e) {
            const dd = document.getElementById('userMenuDropdown');
            const btn = document.getElementById('userMenuBtn');
            if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
                dd.classList.add('hidden');
            }
        });

        function openChangePwModal() {
            const modal = document.getElementById('changePasswordModal');
            if (modal) modal.classList.remove('hidden');
        }

        function closeChangePwModal() {
            const modal = document.getElementById('changePasswordModal');
            if (modal) modal.classList.add('hidden');
        }

        function submitProtectedPost(actionUrl, fields) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfMeta.getAttribute('content') || '';
                form.appendChild(csrfInput);
            }

            Object.keys(fields || {}).forEach((key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }

        // Toggle Sidebar on Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        // Flash message with SweetAlert2
        <?php if ($flashSuccess): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= addslashes($flashSuccess) ?>',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        <?php endif; ?>

        <?php if ($flashError): ?>
        Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: '<?= addslashes($flashError) ?>',
            confirmButtonColor: '#10b981'
        });
        <?php endif; ?>
    </script>
</body>
</html>


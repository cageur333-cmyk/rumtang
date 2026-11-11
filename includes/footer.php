<?php
// includes/footer.php
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
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

    <!-- Scripts -->
    <script>
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


<?php
// login.php - Halaman Login Rumtang Dashboard
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = Database::getConnection();

// Jika sudah login, langsung arahkan ke dashboard
if (Auth::currentUser($pdo) !== null) {
    header('Location: ' . Auth::url('/index.php'));
    exit;
}

$errorMsg = null;
$usernameInput = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $usernameInput = trim((string)($_POST['username'] ?? ''));
    $passwordInput = (string)($_POST['password'] ?? '');

    $result = Auth::attemptLogin($pdo, $usernameInput, $passwordInput);
    if ($result['success']) {
        header('Location: ' . Auth::url('/index.php'));
        exit;
    }
    $errorMsg = $result['message'] ?? 'Login gagal.';
}

$loggedOutNotice = isset($_GET['logged_out']) && $_GET['logged_out'] === '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | Rumtang Dashboard - Sistem Inventaris &amp; Order</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Subtle Ambient Glow -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Brand Header -->
        <div class="text-center mb-7">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 text-white shadow-xl shadow-emerald-600/30 mb-4">
                <i class="fa-solid fa-boxes-stacked text-2xl"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center justify-center gap-2">
                RUMTANG
                <span class="text-[11px] font-extrabold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">PRO</span>
            </h1>
            <p class="text-sm text-slate-400 mt-1">Sistem Inventaris Chemical &amp; Perlengkapan Rumah Tangga</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-200/80">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-900">Masuk ke Dashboard</h2>
                <p class="text-xs text-slate-500 mt-0.5">Silakan login menggunakan akun yang terdaftar untuk mengakses data.</p>
            </div>

            <?php if ($errorMsg): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 mt-0.5 text-sm"></i>
                    <span><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php elseif ($loggedOutNotice): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-sm"></i>
                    <span>Anda telah berhasil keluar dari sistem.</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars(Auth::url('/login.php'), ENT_QUOTES, 'UTF-8') ?>" class="space-y-4">
                <div>
                    <label for="username" class="block text-xs font-bold text-slate-700 mb-1.5">Username</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= htmlspecialchars($usernameInput, ENT_QUOTES, 'UTF-8') ?>"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="Masukkan username"
                            class="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-slate-800"
                        >
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan password"
                            class="w-full pl-10 pr-10 py-2.5 text-sm rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-slate-800"
                        >
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1" title="Tampilkan/sembunyikan password">
                            <i id="pwEyeIcon" class="fa-regular fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Masuk Sekarang</span>
                </button>
            </form>
        </div>

        <p class="text-center text-[11px] text-slate-500 mt-6">
            Departemen Rumah Tangga &amp; Logistik &bull; Akses Terbatas
        </p>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const icon = document.getElementById('pwEyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

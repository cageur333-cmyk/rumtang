<?php
// logout.php - Akhiri Sesi Login (POST + CSRF Protected)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = Database::getConnection();
$user = Auth::currentUser($pdo);

if ($user === null) {
    header('Location: ' . Auth::url('/login.php'));
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    Auth::requirePostWithCsrf($pdo, false, '/index.php');
    Auth::logout($pdo);
    header('Location: ' . Auth::url('/login.php?logged_out=1'));
    exit;
}

// Jika diakses langsung via GET, tampilkan konfirmasi aman untuk mencegah logout-CSRF
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Keluar | Rumtang Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-lg border border-slate-200 text-center space-y-4">
        <h2 class="text-base font-bold text-slate-900">Keluar dari Rumtang Dashboard?</h2>
        <p class="text-xs text-slate-500">Anda sedang masuk sebagai <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
        <div class="flex items-center justify-center gap-2 pt-2">
            <a href="<?= htmlspecialchars(Auth::url('/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl">Batal</a>
            <form method="POST" action="<?= htmlspecialchars(Auth::url('/logout.php'), ENT_QUOTES, 'UTF-8') ?>">
                <?= Auth::csrfInput($pdo) ?>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl">Ya, Keluar</button>
            </form>
        </div>
    </div>
</body>
</html>

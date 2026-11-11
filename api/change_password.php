<?php
// api/change_password.php - Ganti Password Mandiri (Admin & Viewer)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = Database::getConnection();
$currentUser = Auth::requireLogin($pdo);
$redirectTarget = Auth::safeRedirect($_POST['redirect_to'] ?? '/index.php', '/index.php');
Auth::requirePostWithCsrf($pdo, false, $redirectTarget);

try {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        throw new Exception('Semua kolom password wajib diisi.');
    }
    if (strlen($newPassword) < 6) {
        throw new Exception('Password baru minimal 6 karakter.');
    }
    if ($newPassword !== $confirmPassword) {
        throw new Exception('Konfirmasi password baru tidak cocok.');
    }

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $currentUser['id']]);
    $userRow = $stmt->fetch();

    if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
        throw new Exception('Password saat ini tidak sesuai.');
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")->execute([
        ':hash' => $newHash,
        ':id' => $currentUser['id'],
    ]);

    // Hapus sesi lain milik user ini agar perangkat lama otomatis keluar
    $pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid AND id != :sid")->execute([
        ':uid' => $currentUser['id'],
        ':sid' => $currentUser['session_id'],
    ]);

    Auth::setFlash($pdo, 'success', 'Password berhasil diperbarui.');
    header('Location: ' . $redirectTarget);
    exit;

} catch (Exception $e) {
    Auth::setFlash($pdo, 'error', $e->getMessage());
    header('Location: ' . $redirectTarget);
    exit;
}

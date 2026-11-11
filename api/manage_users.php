<?php
// api/manage_users.php - Manajemen Akun (Khusus Administrator)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = Database::getConnection();
$currentUser = Auth::requireAdmin($pdo);
Auth::requirePostWithCsrf($pdo, false, '/pengguna.php');

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $role = strtolower(trim((string)($_POST['role'] ?? 'viewer')));
        $password = (string)($_POST['password'] ?? '');

        if (!preg_match('/^[a-z0-9._-]{3,30}$/', $username)) {
            throw new Exception('Username harus 3-30 karakter (huruf kecil, angka, titik, garis bawah, atau strip).');
        }
        if ($fullName === '') {
            throw new Exception('Nama lengkap wajib diisi.');
        }
        if (!in_array($role, ['admin', 'viewer'], true)) {
            throw new Exception('Role tidak valid.');
        }
        if (strlen($password) < 6) {
            throw new Exception('Password minimal 6 karakter.');
        }

        $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE LOWER(username) = :u");
        $existsStmt->execute([':u' => $username]);
        if ((int)$existsStmt->fetchColumn() > 0) {
            throw new Exception("Username '{$username}' sudah terdaftar.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (username, full_name, role, password_hash, is_active)
            VALUES (:u, :fn, :r, :ph, 1)
        ");
        $stmt->execute([
            ':u' => $username,
            ':fn' => $fullName,
            ':r' => $role,
            ':ph' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        Auth::setFlash($pdo, 'success', "Akun baru '{$username}' ({$role}) berhasil dibuat.");
    } elseif ($action === 'reset_password') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $newPassword = (string)($_POST['new_password'] ?? '');

        if ($targetId <= 0) {
            throw new Exception('ID akun tidak valid.');
        }
        if (strlen($newPassword) < 6) {
            throw new Exception('Password baru minimal 6 karakter.');
        }

        $stmtCheck = $pdo->prepare("SELECT username FROM users WHERE id = :id");
        $stmtCheck->execute([':id' => $targetId]);
        $targetUsername = $stmtCheck->fetchColumn();
        if (!$targetUsername) {
            throw new Exception('Akun tidak ditemukan.');
        }

        $pdo->prepare("UPDATE users SET password_hash = :ph WHERE id = :id")->execute([
            ':ph' => password_hash($newPassword, PASSWORD_BCRYPT),
            ':id' => $targetId,
        ]);

        // Putus sesi aktif user target (kecuali sesi admin sendiri jika mereset diri sendiri)
        $pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid AND id != :sid")->execute([
            ':uid' => $targetId,
            ':sid' => $currentUser['session_id'],
        ]);

        Auth::setFlash($pdo, 'success', "Password akun '{$targetUsername}' berhasil direset.");
    } elseif ($action === 'toggle_active') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId <= 0) {
            throw new Exception('ID akun tidak valid.');
        }
        if ($targetId === (int)$currentUser['id']) {
            throw new Exception('Anda tidak dapat menonaktifkan akun yang sedang Anda gunakan.');
        }

        $stmtUser = $pdo->prepare("SELECT username, is_active FROM users WHERE id = :id");
        $stmtUser->execute([':id' => $targetId]);
        $target = $stmtUser->fetch();
        if (!$target) {
            throw new Exception('Akun tidak ditemukan.');
        }

        $newStatus = (int)$target['is_active'] === 1 ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_active = :st WHERE id = :id")->execute([
            ':st' => $newStatus,
            ':id' => $targetId,
        ]);

        if ($newStatus === 0) {
            $pdo->prepare("DELETE FROM user_sessions WHERE user_id = :uid")->execute([':uid' => $targetId]);
        }

        $label = $newStatus === 1 ? 'diaktifkan kembali' : 'dinonaktifkan';
        Auth::setFlash($pdo, 'success', "Akun '{$target['username']}' berhasil {$label}.");
    } elseif ($action === 'change_role') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        $newRole = strtolower(trim((string)($_POST['role'] ?? '')));

        if ($targetId <= 0 || !in_array($newRole, ['admin', 'viewer'], true)) {
            throw new Exception('Parameter ubah role tidak valid.');
        }
        if ($targetId === (int)$currentUser['id'] && $newRole !== 'admin') {
            throw new Exception('Anda tidak dapat menurunkan role akun Anda sendiri.');
        }

        $stmtUser = $pdo->prepare("SELECT username FROM users WHERE id = :id");
        $stmtUser->execute([':id' => $targetId]);
        $targetUsername = $stmtUser->fetchColumn();
        if (!$targetUsername) {
            throw new Exception('Akun tidak ditemukan.');
        }

        $pdo->prepare("UPDATE users SET role = :r WHERE id = :id")->execute([
            ':r' => $newRole,
            ':id' => $targetId,
        ]);

        Auth::setFlash($pdo, 'success', "Role akun '{$targetUsername}' diubah menjadi " . strtoupper($newRole) . '.');
    } else {
        throw new Exception('Aksi tidak dikenali.');
    }

    header('Location: ' . Auth::url('/pengguna.php'));
    exit;

} catch (Exception $e) {
    Auth::setFlash($pdo, 'error', $e->getMessage());
    header('Location: ' . Auth::url('/pengguna.php'));
    exit;
}

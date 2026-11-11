<?php
// includes/auth.php - Autentikasi Berbasis Database Session & Cookie HttpOnly (Vercel + Lokal Compatible)

class Auth
{
    public const COOKIE_NAME = 'rumtang_session';
    public const SESSION_TTL_SECONDS = 604800; // 7 hari
    public const MAX_LOGIN_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 600; // 10 menit

    private static ?array $cachedUser = null;
    private static bool $userResolved = false;

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    }

    public static function basePath(): string
    {
        if (getenv('VERCEL') || getenv('VERCEL_ENV')) {
            return '';
        }

        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $appRoot = realpath(dirname(__DIR__));
        if ($docRoot !== false && $appRoot !== false) {
            $docNorm = rtrim(str_replace('\\', '/', $docRoot), '/');
            $appNorm = rtrim(str_replace('\\', '/', $appRoot), '/');

            $cmpDoc = PHP_OS_FAMILY === 'Windows' ? strtolower($docNorm) : $docNorm;
            $cmpApp = PHP_OS_FAMILY === 'Windows' ? strtolower($appNorm) : $appNorm;

            if ($cmpApp === $cmpDoc) {
                return '';
            }
            if (str_starts_with($cmpApp, $cmpDoc . '/')) {
                return substr($appNorm, strlen($docNorm));
            }
        }

        return '';
    }

    public static function url(string $path): string
    {
        $normalized = '/' . ltrim($path, '/');
        return self::basePath() . $normalized;
    }

    public static function clientIp(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded !== '') {
            $parts = explode(',', $forwarded);
            $first = trim($parts[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
    }

    public static function isAjaxRequest(): bool
    {
        $xrw = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        return $xrw === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    public static function currentUser(PDO $pdo): ?array
    {
        if (self::$userResolved) {
            return self::$cachedUser;
        }
        self::$userResolved = true;

        $rawToken = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!is_string($rawToken) || !preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            self::$cachedUser = null;
            return null;
        }

        $tokenHash = hash('sha256', $rawToken);
        $nowUtc = gmdate('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.username,
                u.full_name,
                u.role,
                u.is_active,
                s.id AS session_id,
                s.token_hash,
                s.csrf_token,
                s.flash_success,
                s.flash_error,
                s.expires_at
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.token_hash = :token_hash
              AND s.expires_at > :now_utc
              AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':now_utc' => $nowUtc,
        ]);

        $row = $stmt->fetch();
        self::$cachedUser = $row ?: null;
        return self::$cachedUser;
    }

    public static function isAdmin(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    public static function csrfToken(PDO $pdo): string
    {
        $user = self::currentUser($pdo);
        return $user['csrf_token'] ?? '';
    }

    public static function csrfInput(PDO $pdo): string
    {
        $token = htmlspecialchars(self::csrfToken($pdo), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function requireLogin(PDO $pdo, bool $jsonOnly = false): array
    {
        $user = self::currentUser($pdo);
        if ($user === null) {
            if ($jsonOnly || self::isAjaxRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Sesi login tidak valid atau telah berakhir. Silakan login kembali.',
                ]);
                exit;
            }

            header('Location: ' . self::url('/login.php'));
            exit;
        }

        return $user;
    }

    public static function requireAdmin(PDO $pdo, bool $jsonOnly = false): array
    {
        $user = self::requireLogin($pdo, $jsonOnly);
        if (!self::isAdmin($user)) {
            $msg = 'Akses ditolak. Hanya akun Administrator yang dapat mengubah atau menghapus data.';
            if ($jsonOnly || self::isAjaxRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }

            self::setFlash($pdo, 'error', $msg);
            header('Location: ' . self::url('/index.php'));
            exit;
        }

        return $user;
    }

    public static function requirePostWithCsrf(PDO $pdo, bool $jsonOnly = false, string $fallbackPath = '/index.php'): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            self::respondError($pdo, 405, 'Metode request tidak diizinkan.', $fallbackPath, $jsonOnly);
        }

        $expected = self::csrfToken($pdo);
        $submitted = (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($expected === '' || $submitted === '' || !hash_equals($expected, $submitted)) {
            self::respondError($pdo, 403, 'Token keamanan (CSRF) tidak valid atau halaman kedaluwarsa. Muat ulang halaman lalu coba lagi.', $fallbackPath, $jsonOnly);
        }
    }

    public static function setFlash(PDO $pdo, string $type, string $message): void
    {
        $user = self::currentUser($pdo);
        if ($user === null) {
            return;
        }

        $col = $type === 'success' ? 'flash_success' : 'flash_error';
        $stmt = $pdo->prepare("UPDATE user_sessions SET {$col} = :msg WHERE id = :sid");
        $stmt->execute([
            ':msg' => $message,
            ':sid' => $user['session_id'],
        ]);

        self::$cachedUser[$col] = $message;
    }

    public static function consumeFlash(PDO $pdo): array
    {
        $user = self::currentUser($pdo);
        if ($user === null) {
            return ['success' => null, 'error' => null];
        }

        $success = $user['flash_success'] ?? null;
        $error = $user['flash_error'] ?? null;

        if ($success !== null || $error !== null) {
            $stmt = $pdo->prepare("UPDATE user_sessions SET flash_success = NULL, flash_error = NULL WHERE id = :sid");
            $stmt->execute([':sid' => $user['session_id']]);
            self::$cachedUser['flash_success'] = null;
            self::$cachedUser['flash_error'] = null;
        }

        return [
            'success' => $success,
            'error' => $error,
        ];
    }

    public static function safeRedirect(string $requested, string $default = '/index.php'): string
    {
        $allowed = [
            '/index.php',
            '/inventaris.php',
            '/transaksi.php',
            '/order.php',
            '/laporan.php',
            '/pengguna.php',
        ];

        $raw = trim($requested);
        if ($raw === '') {
            return self::url($default);
        }

        // Tolak URL eksternal jika memiliki scheme atau host
        $parts = parse_url($raw);
        if (!is_array($parts) || !empty($parts['scheme']) || !empty($parts['host'])) {
            return self::url($default);
        }
        $path = $parts['path'] ?? '';
        while (str_starts_with($path, '../') || str_starts_with($path, './')) {
            $path = preg_replace('#^(\.\./|\./)#', '', $path);
        }

        // Handle local prefix like /rumtang/inventaris.php
        $base = self::basePath();
        if ($base !== '' && str_starts_with($path, $base . '/')) {
            $path = substr($path, strlen($base));
        }

        $normalized = '/' . ltrim($path, '/');
        if (in_array($normalized, $allowed, true)) {
            $query = !empty($parts['query']) ? '?' . $parts['query'] : '';
            return self::url($normalized) . $query;
        }

        return self::url($default);
    }

    public static function respondError(PDO $pdo, int $statusCode, string $message, string $redirectPath = '/index.php', bool $jsonOnly = false): void
    {
        if ($jsonOnly || self::isAjaxRequest()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        self::setFlash($pdo, 'error', $message);
        header('Location: ' . self::safeRedirect($redirectPath));
        exit;
    }

    public static function attemptLogin(PDO $pdo, string $username, string $password): array
    {
        $usernameNorm = strtolower(trim($username));
        if ($usernameNorm === '' || $password === '') {
            return ['success' => false, 'message' => 'Username dan password wajib diisi.'];
        }

        $nowTs = time();
        $nowUtc = gmdate('Y-m-d H:i:s', $nowTs);
        $attemptKey = hash('sha256', self::clientIp() . '|' . $usernameNorm);

        // Check brute-force lock
        $stmtAttempt = $pdo->prepare("SELECT attempts, locked_until, updated_at FROM login_attempts WHERE attempt_key = :k LIMIT 1");
        $stmtAttempt->execute([':k' => $attemptKey]);
        $attemptRow = $stmtAttempt->fetch();

        if ($attemptRow && !empty($attemptRow['locked_until'])) {
            $lockedTs = strtotime($attemptRow['locked_until'] . ' UTC');
            if ($lockedTs !== false && $lockedTs > $nowTs) {
                $minsLeft = (int)ceil(($lockedTs - $nowTs) / 60);
                return [
                    'success' => false,
                    'message' => "Terlalu banyak percobaan login gagal. Coba lagi dalam {$minsLeft} menit.",
                ];
            }
        }

        // Lookup user
        $stmtUser = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = :u LIMIT 1");
        $stmtUser->execute([':u' => $usernameNorm]);
        $user = $stmtUser->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $prevAttempts = 0;
            if ($attemptRow) {
                $lastUpdateTs = strtotime($attemptRow['updated_at'] . ' UTC') ?: 0;
                $prevAttempts = ($nowTs - $lastUpdateTs <= 900) ? (int)$attemptRow['attempts'] : 0;
            }
            $newAttempts = $prevAttempts + 1;
            $lockedUntil = $newAttempts >= self::MAX_LOGIN_ATTEMPTS
                ? gmdate('Y-m-d H:i:s', $nowTs + self::LOCKOUT_SECONDS)
                : null;

            $stmtUpsert = $pdo->prepare("
                INSERT INTO login_attempts (attempt_key, attempts, locked_until, updated_at)
                VALUES (:k, :a, :l, :u)
                ON DUPLICATE KEY UPDATE attempts = VALUES(attempts), locked_until = VALUES(locked_until), updated_at = VALUES(updated_at)
            ");
            $stmtUpsert->execute([
                ':k' => $attemptKey,
                ':a' => $newAttempts,
                ':l' => $lockedUntil,
                ':u' => $nowUtc,
            ]);

            if ($lockedUntil !== null) {
                return [
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan gagal. Akun dikunci sementara selama 10 menit.',
                ];
            }

            $remaining = self::MAX_LOGIN_ATTEMPTS - $newAttempts;
            return [
                'success' => false,
                'message' => "Username atau password salah (sisa percobaan: {$remaining}x).",
            ];
        }

        if ((int)$user['is_active'] !== 1) {
            return [
                'success' => false,
                'message' => 'Akun Anda sedang dinonaktifkan. Hubungi Administrator.',
            ];
        }

        // Reset failed login attempts & prune expired sessions
        $pdo->prepare("DELETE FROM login_attempts WHERE attempt_key = :k")->execute([':k' => $attemptKey]);
        $pdo->prepare("DELETE FROM user_sessions WHERE expires_at <= :now_utc")->execute([':now_utc' => $nowUtc]);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $csrfToken = bin2hex(random_bytes(32));
        $expiresTs = $nowTs + self::SESSION_TTL_SECONDS;
        $expiresUtc = gmdate('Y-m-d H:i:s', $expiresTs);

        $stmtInsert = $pdo->prepare("
            INSERT INTO user_sessions (user_id, token_hash, csrf_token, ip_address, user_agent, expires_at)
            VALUES (:uid, :th, :csrf, :ip, :ua, :exp)
        ");
        $stmtInsert->execute([
            ':uid' => $user['id'],
            ':th' => $tokenHash,
            ':csrf' => $csrfToken,
            ':ip' => substr(self::clientIp(), 0, 45),
            ':ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ':exp' => $expiresUtc,
        ]);

        $pdo->prepare("UPDATE users SET last_login_at = :now_utc WHERE id = :uid")->execute([
            ':now_utc' => $nowUtc,
            ':uid' => $user['id'],
        ]);

        setcookie(self::COOKIE_NAME, $rawToken, [
            'expires' => $expiresTs,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        self::$cachedUser = null;
        self::$userResolved = false;

        return ['success' => true, 'user' => $user];
    }

    public static function logout(PDO $pdo): void
    {
        $rawToken = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (is_string($rawToken) && preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            $tokenHash = hash('sha256', $rawToken);
            $pdo->prepare("DELETE FROM user_sessions WHERE token_hash = :th")->execute([':th' => $tokenHash]);
        }

        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        self::$cachedUser = null;
        self::$userResolved = true;
    }
}

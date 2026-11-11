<?php
// api/index.php - Vercel Serverless Entry Point & Router
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Base directory (project root)
$baseDir = dirname(__DIR__);

// Route dispatcher
switch ($path) {
    case '':
    case 'index':
    case 'index.php':
        require $baseDir . '/index.php';
        exit;

    case 'inventaris':
    case 'inventaris.php':
        require $baseDir . '/inventaris.php';
        exit;

    case 'transaksi':
    case 'transaksi.php':
        require $baseDir . '/transaksi.php';
        exit;

    case 'order':
    case 'order.php':
        require $baseDir . '/order.php';
        exit;

    case 'laporan':
    case 'laporan.php':
        require $baseDir . '/laporan.php';
        exit;

    case 'pengguna':
    case 'pengguna.php':
        require $baseDir . '/pengguna.php';
        exit;

    case 'login':
    case 'login.php':
        require $baseDir . '/login.php';
        exit;

    case 'logout':
    case 'logout.php':
        require $baseDir . '/logout.php';
        exit;
}

// Tolak akses langsung ke folder/file internal atau dotfile (.git, .env, config, seed, includes, reports, report_summary.php)
if (
    str_starts_with($path, '.') ||
    str_contains($path, '/.') ||
    str_contains($path, '..') ||
    preg_match('#^(config|seed|includes|reports)(/|$)#i', $path) ||
    strcasecmp($path, 'report_summary.php') === 0 ||
    strcasecmp($path, 'vercel.json') === 0 ||
    strcasecmp($path, 'README.md') === 0
) {
    http_response_code(403);
    echo '403 Forbidden';
    exit;
}

// API routes handling (hanya izinkan script di folder api/)
if (str_starts_with($path, 'api/')) {
    $script = substr($path, 4);
    if ($script !== '' && !str_contains($script, '/') && !str_contains($script, '\\') && str_ends_with($script, '.php')) {
        $apiFile = __DIR__ . '/' . $script;
        if (file_exists($apiFile) && is_file($apiFile)) {
            require $apiFile;
            exit;
        }
    }
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

// Check static assets in root only (do not bypass allowlist for arbitrary php)
$targetFile = $baseDir . '/' . $path;
if ($path !== '' && file_exists($targetFile) && is_file($targetFile) && !str_ends_with(strtolower($targetFile), '.php')) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($targetFile);
    exit;
}

// Default fallback
require $baseDir . '/index.php';

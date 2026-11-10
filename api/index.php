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
}

// API routes handling
if (str_starts_with($path, 'api/')) {
    $script = substr($path, 4);
    $apiFile = __DIR__ . '/' . $script;
    if (file_exists($apiFile) && is_file($apiFile)) {
        require $apiFile;
        exit;
    }
}

// Check if static or root PHP file
$targetFile = $baseDir . '/' . $path;
if (file_exists($targetFile) && is_file($targetFile)) {
    if (str_ends_with($targetFile, '.php')) {
        require $targetFile;
    } else {
        // Static file mime type
        $ext = pathinfo($targetFile, PATHINFO_EXTENSION);
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
    }
    exit;
}

// Default fallback
require $baseDir . '/index.php';

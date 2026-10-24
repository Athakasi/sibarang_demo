<?php
// ===================================================
// config/app.php
// Konfigurasi Utama, Environment Loader & Helper Global
// ===================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Definisikan Root Path
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/..'));
}

// 2. Fungsi sederhana untuk load file .env
function loadEnv($envFile) {
    if (!file_exists($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Hapus tanda petik jika ada
            $value = trim($value, "\"'");

            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv(sprintf('%s=%s', $key, $value));
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Muat file .env jika ada
loadEnv(ROOT_PATH . '/.env');

// Helper untuk mengambil variabel env
function env($key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }
    return $value;
}

// 3. Konfigurasi Aplikasi
define('APP_NAME', env('APP_NAME', 'SI-BARANG'));
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN));

// Error handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// 4. Deteksi Base URL secara otomatis & akurat
function detectBaseUrl() {
    $envUrl = env('APP_URL');
    if (!empty($envUrl)) {
        return rtrim($envUrl, '/');
    }

    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    // Cari tahu relative path dari Document Root ke ROOT_PATH
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = ROOT_PATH;

    $subfolder = '';
    if ($docRoot && strpos($appRoot, $docRoot) === 0) {
        $subfolder = substr($appRoot, strlen($docRoot));
        $subfolder = str_replace('\\', '/', $subfolder);
    }

    return rtrim($protocol . $host . $subfolder, '/');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', detectBaseUrl());
}

// 5. Helper Functions Global

/**
 * Buat URL absolut aplikasi
 */
function base_url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

/**
 * Buat URL untuk assets statis
 */
function asset_url($path = '') {
    $path = ltrim($path, '/');
    return base_url('assets/' . $path);
}

/**
 * Escape string untuk output HTML aman dari XSS
 */
function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Alias untuk h()
 */
function e($value) {
    return h($value);
}

/**
 * Redirect ke halaman tertentu
 */
function redirect($path) {
    $url = (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0)
        ? $path
        : base_url($path);
    header('Location: ' . $url);
    exit;
}

/**
 * Helper Session Flash Message
 */
function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function has_flash($type) {
    return isset($_SESSION['flash'][$type]);
}

/**
 * Helper CSRF Token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function validate_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        set_flash('error', '⚠️ Sesi keamanan form telah kedaluwarsa. Silakan muat ulang halaman.');
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid CSRF Token']);
            exit;
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? base_url('index.php');
        header('Location: ' . $referer);
        exit;
    }
}

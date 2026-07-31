<?php
/**
 * ====================================================
 * FILE KONFIGURASI GLOBAL & SESSION MANAGEMENT
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

// Memulai Session Keamanan Aplikasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Nama & Header Aplikasi
define('APP_NAME', 'SPK Penentuan Jurusan Kuliah Siswa SMA');
define('APP_SHORT_NAME', 'SPK TOPSIS');
define('APP_METHOD', 'TOPSIS');
define('APP_VERSION', '1.0.0');

// Deteksi Otomatis BASE_URL (Termasuk Port Khusus seperti 8080)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Bersihkan subfolder views / includes dari BASE_URL
$path_parts = explode('/', trim($script_name, '/'));
$clean_path = '';
foreach ($path_parts as $part) {
    if ($part === 'views' || $part === 'includes' || $part === 'config') {
        break;
    }
    if (!empty($part)) {
        $clean_path .= '/' . $part;
    }
}

$base_url = $protocol . "://" . $host . $clean_path . '/';
define('BASE_URL', $base_url);

/**
 * Proteksi Keamanan Halaman (Auth Checker)
 * Memastikan user sudah login dengan session user_id
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

<?php
/**
 * ====================================================
 * SKRIP LOGOUT SYSTEM
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

require_once __DIR__ . '/config/global.php';

// Hapus seluruh data session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login dengan status logout sukses
header("Location: " . BASE_URL . "login.php?logout=success");
exit;

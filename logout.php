<?php
require_once 'koneksi.php';
require_once 'sesi.php';

// Hapus remember_token di database jika user sedang login
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $koneksi->prepare("UPDATE pengguna SET remember_token = NULL WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Logout Token Clear Error: " . $e->getMessage());
    }
}

// Hapus cookie Remember Me
setcookie('remember_esport', '', time() - 3600, '/');

// Kosongkan seluruh variabel session
$_SESSION = [];

// Hapus cookie session dari browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
header("Location: login.php");
exit();

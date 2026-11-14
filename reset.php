<?php
require_once 'koneksi.php';

// Utilitas opsional untuk memastikan hash password akun uji sesuai dengan PHP lokal Anda
$hashAdmin   = password_hash('Password123!', PASSWORD_DEFAULT);
$hashAnggota = password_hash('Password123!', PASSWORD_DEFAULT);

try {
    $stmt = $koneksi->prepare("UPDATE pengguna SET hash_sandi = ?, remember_token = NULL WHERE username = ?");

    $u1 = 'admin_esport';
    $stmt->bind_param("ss", $hashAdmin, $u1);
    $stmt->execute();

    $u2 = 'pro_player';
    $stmt->bind_param("ss", $hashAnggota, $u2);
    $stmt->execute();

    echo "<div style='font-family:sans-serif; padding:24px; background:#12161f; color:#fff; max-width:520px; margin:40px auto; border-radius:8px;'>";
    echo "<h2 style='color:#00d4ff;'>Akun Uji Berhasil Disinkronkan</h2>";
    echo "<p>Kata sandi untuk kedua akun telah diperbarui menjadi <code>Password123!</code>:</p>";
    echo "<ul><li>Admin: <b>admin_esport</b></li><li>Anggota: <b>pro_player</b></li></ul>";
    echo "<a href='login.php' style='display:inline-block; padding:10px 16px; background:#00adb5; color:#fff; text-decoration:none; border-radius:4px;'>Buka Halaman Login</a>";
    echo "</div>";
} catch (Exception $e) {
    echo "Terjadi kesalahan: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}

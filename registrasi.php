<?php
require_once 'koneksi.php';
require_once 'sesi.php';
require_once 'validasi.php';

// Jika sudah login, langsung arahkan ke daftar turnamen
if (isset($_SESSION['user_id'])) {
    header('Location: daftar.php');
    exit();
}

$errors   = [];
$username = '';
$nama     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasi_csrf();

    $username = trim($_POST['username'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $sandi    = $_POST['sandi'] ?? '';

    if (!validasi_panjang($username, 3, 30) || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = "Username wajib 3–30 karakter (huruf, angka, atau garis bawah).";
    }
    if (!validasi_panjang($nama, 3, 100)) {
        $errors['nama'] = "Nama lengkap wajib diisi (3–100 karakter).";
    }
    if (strlen($sandi) < 6) {
        $errors['sandi'] = "Kata sandi minimal 6 karakter.";
    }

    if (empty($errors)) {
        try {
            $hash_sandi = password_hash($sandi, PASSWORD_DEFAULT);
            $peran = 'anggota';

            $stmt = $koneksi->prepare("INSERT INTO pengguna (username, nama, peran, hash_sandi) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $nama, $peran, $hash_sandi);
            $stmt->execute();

            set_flash('sukses', 'Registrasi berhasil! Silakan login menggunakan akun baru Anda.');
            header("Location: login.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            // Kode error MySQL 1062 menandakan duplikasi kolom UNIQUE (username sudah dipakai)
            if ($e->getCode() === 1062) {
                $errors['username'] = "Username sudah digunakan, silakan pilih username lain.";
            } else {
                error_log("Register Error: " . $e->getMessage());
                $errors['umum'] = "Terjadi kesalahan sistem saat registrasi.";
            }
        }
    }
}

$tema = ($_COOKIE['esport_theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($tema) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Esport Tournament Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <div class="card auth-card">
        <div class="auth-header">
            <h2>Registrasi Akun Baru</h2>
            <button type="button" id="btnThemeToggle" class="btn-icon" title="Ganti Tema (Cookie)">🌓</button>
        </div>

        <?php if (isset($errors['umum'])): ?>
            <div class="alert alert-error"><?= e($errors['umum']) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="formRegistrasi" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>" placeholder="contoh: gamer_pro" required>
                <?php if (isset($errors['username'])): ?>
                    <div class="error-text"><?= e($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="<?= e($nama) ?>" placeholder="Nama lengkap Anda" required>
                <?php if (isset($errors['nama'])): ?>
                    <div class="error-text"><?= e($errors['nama']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="sandi">Kata Sandi</label>
                <div class="password-wrapper">
                    <input type="password" id="sandi" name="sandi" placeholder="Minimal 6 karakter" required>
                    <button type="button" class="btn-toggle-pass" data-target="sandi">Lihat</button>
                </div>
                <div id="passwordStrength" class="helper-text"></div>
                <?php if (isset($errors['sandi'])): ?>
                    <div class="error-text"><?= e($errors['sandi']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Daftar Sekarang</button>
        </form>

        <p class="auth-footer">Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>

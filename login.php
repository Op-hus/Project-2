<?php
require_once 'koneksi.php';
require_once 'sesi.php';
cek_remember_cookie($koneksi);

if (isset($_SESSION['user_id'])) {
    header('Location: daftar.php');
    exit();
}

$error    = '';
$username = $_COOKIE['last_username'] ?? '';
$remember = isset($_COOKIE['last_username']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasi_csrf();

    $username = trim($_POST['username'] ?? '');
    $sandi    = $_POST['sandi'] ?? '';
    $remember = isset($_POST['remember_me']);

    if ($username !== '' && $sandi !== '') {
        $stmt = $koneksi->prepare("SELECT id, username, nama, peran, hash_sandi FROM pengguna WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && password_verify($sandi, $row['hash_sandi'])) {
            session_regenerate_id(true);

            // Simpan data pengguna ke dalam SESSION
            $_SESSION['user_id']       = $row['id'];
            $_SESSION['username']      = $row['username'];
            $_SESSION['nama']          = $row['nama'];
            $_SESSION['peran']         = $row['peran'];
            $_SESSION['login_time']    = date('H:i:s');
            $_SESSION['last_activity'] = time();

            // Simpan COOKIE username terakhir (7 hari)
            setcookie('last_username', $row['username'], [
                'expires'  => time() + (86400 * 7),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Jika pengguna mencentang "Ingat Saya", buat COOKIE autentikasi persisten (7 hari)
            if ($remember) {
                $raw_token    = bin2hex(random_bytes(32));
                $hashed_token = password_hash($raw_token, PASSWORD_DEFAULT);

                $upd = $koneksi->prepare("UPDATE pengguna SET remember_token = ? WHERE id = ?");
                $upd->bind_param("si", $hashed_token, $row['id']);
                $upd->execute();

                setcookie('remember_esport', $row['id'] . ':' . $raw_token, [
                    'expires'  => time() + (86400 * 7),
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                // Hapus cookie remember_esport jika tidak dicentang
                setcookie('remember_esport', '', time() - 3600, '/');
            }

            set_flash('sukses', 'Selamat datang kembali, ' . $row['nama'] . '!');
            header("Location: daftar.php");
            exit();
        }

        // Pesan error disamakan agar penyerang tidak bisa menebak username yang terdaftar
        $error = "Username atau kata sandi yang Anda masukkan salah.";
    } else {
        $error = "Harap isi username dan kata sandi.";
    }
}

$flash = get_flash();
$tema  = ($_COOKIE['esport_theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($tema) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Esport Tournament Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-container">
    <div class="card auth-card">
        <div class="auth-header">
            <h2>Login Esport Hub</h2>
            <button type="button" id="btnThemeToggle" class="btn-icon" title="Ganti Tema (Cookie)">🌓</button>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['tipe']) ?>"><?= e($flash['pesan']) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>" required>
            </div>

            <div class="form-group">
                <label for="sandi">Kata Sandi</label>
                <div class="password-wrapper">
                    <input type="password" id="sandi" name="sandi" required>
                    <button type="button" class="btn-toggle-pass" data-target="sandi">Lihat</button>
                </div>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="remember_me" <?= $remember ? 'checked' : '' ?>>
                    Ingat Saya (Simpan Cookie Login 7 Hari)
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>

        <p class="auth-footer">
            Belum punya akun? <a href="registrasi.php">Daftar sekarang</a>
        </p>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>

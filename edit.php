<?php
require_once 'koneksi.php';
require_once 'sesi.php';
require_once 'validasi.php';

// Proteksi otorisasi: hanya Admin yang boleh mengubah data turnamen (UPDATE)
cek_admin($koneksi);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(400);
    die("ID Turnamen tidak valid.");
}

$stmt = $koneksi->prepare("SELECT * FROM tournaments WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if (!$existing) {
    http_response_code(404);
    die("Data turnamen tidak ditemukan.");
}

$errors        = [];
$nama_turnamen = $existing['nama_turnamen'];
$genre_game    = $existing['genre_game'];
$biaya         = (string)$existing['biaya_pendaftaran'];
$max_tim       = (string)$existing['max_tim'];
$tanggal_mulai = $existing['tanggal_mulai'];

$genre_allowed = ['MOBA', 'FPS', 'Battle Royale', 'Fighting'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifikasi_csrf();

    $nama_turnamen = trim($_POST['nama_turnamen'] ?? '');
    $genre_game    = trim($_POST['genre_game'] ?? '');
    $biaya         = trim($_POST['biaya_pendaftaran'] ?? '');
    $max_tim       = trim($_POST['max_tim'] ?? '');
    $tanggal_mulai = trim($_POST['tanggal_mulai'] ?? '');

    if (!validasi_panjang($nama_turnamen, 3, 150)) {
        $errors['nama_turnamen'] = "Nama turnamen wajib diisi (3–150 karakter).";
    }
    if (!validasi_whitelist($genre_game, $genre_allowed)) {
        $errors['genre_game'] = "Pilih genre game yang valid dari daftar putih.";
    }
    if (!validasi_angka_min($biaya, 0)) {
        $errors['biaya_pendaftaran'] = "Biaya pendaftaran harus berupa angka bernilai 0 atau lebih.";
    }
    if (!validasi_rentang_int($max_tim, 4, 256)) {
        $errors['max_tim'] = "Maksimal tim wajib bilangan bulat antara 4 hingga 256.";
    }
    if (!validasi_tanggal_mulai($tanggal_mulai, $existing['tanggal_mulai'])) {
        $errors['tanggal_mulai'] = "Tanggal mulai tidak boleh merupakan tanggal yang sudah lewat.";
    }

    if (empty($errors)) {
        try {
            $biaya_val   = (float)$biaya;
            $max_tim_val = (int)$max_tim;

            $upd = $koneksi->prepare("UPDATE tournaments 
                                      SET nama_turnamen = ?, genre_game = ?, biaya_pendaftaran = ?, max_tim = ?, tanggal_mulai = ? 
                                      WHERE id = ?");
            $upd->bind_param("ssdisi", $nama_turnamen, $genre_game, $biaya_val, $max_tim_val, $tanggal_mulai, $id);
            $upd->execute();

            set_flash('sukses', 'Perubahan pada turnamen "' . $nama_turnamen . '" berhasil disimpan!');
            header("Location: daftar.php");
            exit();
        } catch (Exception $e) {
            error_log("Update Turnamen Error: " . $e->getMessage());
            $errors['umum'] = "Gagal memperbarui data turnamen.";
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
    <title>Edit Turnamen - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="card form-card">
        <div class="auth-header">
            <h2>Edit Turnamen #<?= (int)$id ?></h2>
            <button type="button" id="btnThemeToggle" class="btn-icon" title="Ganti Tema">🌓</button>
        </div>

        <?php if (isset($errors['umum'])): ?>
            <div class="alert alert-error"><?= e($errors['umum']) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="formTurnamen" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="nama_turnamen">Nama Turnamen <small id="charCount" class="helper-text">(0/150)</small></label>
                <input type="text" id="nama_turnamen" name="nama_turnamen" value="<?= e($nama_turnamen) ?>" maxlength="150">
                <?php if (isset($errors['nama_turnamen'])): ?>
                    <div class="error-text"><?= e($errors['nama_turnamen']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="genre_game">Genre Game</label>
                <select id="genre_game" name="genre_game">
                    <option value="">-- Pilih Genre Game --</option>
                    <?php foreach ($genre_allowed as $g): ?>
                        <option value="<?= e($g) ?>" <?= $genre_game === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['genre_game'])): ?>
                    <div class="error-text"><?= e($errors['genre_game']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="biaya_pendaftaran">Biaya Pendaftaran (Rp) <small id="biayaPreview" class="helper-text"></small></label>
                <input type="number" id="biaya_pendaftaran" step="1000" name="biaya_pendaftaran" value="<?= e($biaya) ?>">
                <?php if (isset($errors['biaya_pendaftaran'])): ?>
                    <div class="error-text"><?= e($errors['biaya_pendaftaran']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="max_tim">Maksimal Tim (4–256)</label>
                <input type="number" id="max_tim" name="max_tim" value="<?= e($max_tim) ?>">
                <?php if (isset($errors['max_tim'])): ?>
                    <div class="error-text"><?= e($errors['max_tim']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="tanggal_mulai">Tanggal Mulai</label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?= e($tanggal_mulai) ?>">
                <?php if (isset($errors['tanggal_mulai'])): ?>
                    <div class="error-text"><?= e($errors['tanggal_mulai']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="daftar.php" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>

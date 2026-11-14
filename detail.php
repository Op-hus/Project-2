<?php
require_once 'koneksi.php';
require_once 'sesi.php';

cek_login($koneksi);

// Validasi parameter id dari URL wajib berupa bilangan bulat (integer)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    http_response_code(400);
    die("ID Turnamen tidak valid.");
}

$stmt = $koneksi->prepare("SELECT t.*, p.nama AS pembuat, p.username 
                           FROM tournaments t 
                           JOIN pengguna p ON t.pengguna_id = p.id 
                           WHERE t.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    http_response_code(404);
    die("Data turnamen tidak ditemukan.");
}

$tema = ($_COOKIE['esport_theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($tema) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Turnamen - <?= e($data['nama_turnamen']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="card detail-card">
        <div class="auth-header">
            <h2>Detail Turnamen Kompetitif</h2>
            <button type="button" id="btnThemeToggle" class="btn-icon" title="Ganti Tema">🌓</button>
        </div>

        <div class="detail-row"><span class="detail-label">Nama Turnamen</span><span><?= e($data['nama_turnamen']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Genre Game</span><span class="genre-pill"><?= e($data['genre_game']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Biaya Pendaftaran</span><span><?= (float)$data['biaya_pendaftaran'] == 0 ? 'Gratis (Rp 0)' : 'Rp ' . number_format((float)$data['biaya_pendaftaran'], 0, ',', '.') ?></span></div>
        <div class="detail-row"><span class="detail-label">Maksimal Tim</span><span><?= (int)$data['max_tim'] ?> Tim</span></div>
        <div class="detail-row"><span class="detail-label">Tanggal Mulai</span><span><?= e($data['tanggal_mulai']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Penyelenggara / Penginput</span><span><?= e($data['pembuat']) ?> (@<?= e($data['username']) ?>)</span></div>
        <div class="detail-row"><span class="detail-label">Dibuat Pada</span><span><?= e($data['created_at']) ?></span></div>
        <?php if (!empty($data['updated_at'])): ?>
            <div class="detail-row"><span class="detail-label">Terakhir Diperbarui</span><span><?= e($data['updated_at']) ?></span></div>
        <?php endif; ?>

        <div class="detail-actions">
            <a href="daftar.php" class="btn btn-secondary">&larr; Kembali ke Daftar</a>
            <?php if ($_SESSION['peran'] === 'admin'): ?>
                <a href="edit.php?id=<?= (int)$data['id'] ?>" class="btn btn-warning">Edit Data</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>

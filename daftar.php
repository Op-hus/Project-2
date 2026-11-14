<?php
require_once 'koneksi.php';
require_once 'sesi.php';

// Cek autentikasi
cek_login($koneksi);

$genre_allowed = ['MOBA', 'FPS', 'Battle Royale', 'Fighting'];

// Kelola preferensi filter genre menggunakan COOKIE
if (isset($_GET['reset_filter'])) {
    setcookie('esport_last_genre', '', time() - 3600, '/');
    header('Location: daftar.php');
    exit();
}

$search = trim($_GET['q'] ?? '');
if (isset($_GET['genre'])) {
    $genre_filter = trim($_GET['genre']);
    if ($genre_filter !== '' && in_array($genre_filter, $genre_allowed, true)) {
        // Simpan pilihan filter genre ke dalam COOKIE selama 24 jam
        setcookie('esport_last_genre', $genre_filter, [
            'expires'  => time() + 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        $genre_filter = '';
        setcookie('esport_last_genre', '', time() - 3600, '/');
    }
} else {
    $cookie_genre = $_COOKIE['esport_last_genre'] ?? '';
    $genre_filter = in_array($cookie_genre, $genre_allowed, true) ? $cookie_genre : '';
}

$sql = "SELECT t.*, p.nama AS pembuat 
        FROM tournaments t 
        JOIN pengguna p ON t.pengguna_id = p.id 
        WHERE 1=1";
$types  = "";
$params = [];

if ($search !== '') {
    $sql .= " AND (t.nama_turnamen LIKE ? OR p.nama LIKE ?)";
    $like = "%" . $search . "%";
    $types .= "ss";
    $params[] = $like;
    $params[] = $like;
}

if ($genre_filter !== '') {
    $sql .= " AND t.genre_game = ?";
    $types .= "s";
    $params[] = $genre_filter;
}

$sql .= " ORDER BY t.created_at DESC, t.id DESC";

$stmt = $koneksi->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$flash = get_flash();
$tema  = ($_COOKIE['esport_theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= e($tema) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Turnamen - Esport Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <header class="topbar">
        <div>
            <h1>Esport &amp; Gaming Tournament Hub</h1>
            <small class="status-badges">
                <span class="badge badge-session">Session Aktif: <?= e($_SESSION['username'] ?? $_SESSION['nama'] ?? 'User') ?> (Login <?= e($_SESSION['login_time'] ?? '-') ?>)</span>
                <?php if (isset($_COOKIE['remember_esport'])): ?>
                    <span class="badge badge-cookie">Cookie: Remember Me Aktif</span>
                <?php endif; ?>
                <?php if ($genre_filter !== ''): ?>
                    <span class="badge badge-cookie">Cookie Filter: <?= e($genre_filter) ?></span>
                <?php endif; ?>
            </small>
        </div>

        <div class="user-controls">
            <button type="button" id="btnThemeToggle" class="btn btn-secondary" title="Ubah Tema & Simpan Cookie">🌓 Tema</button>
            <span>Halo, <strong><?= e($_SESSION['nama']) ?></strong> <span class="role-tag role-<?= e($_SESSION['peran']) ?>"><?= e(strtoupper($_SESSION['peran'])) ?></span></span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['tipe']) ?>" id="flashAlert"><?= e($flash['pesan']) ?></div>
    <?php endif; ?>

    <section class="toolbar">
        <div class="toolbar-left">
            <?php if ($_SESSION['peran'] === 'admin'): ?>
                <a href="tambah.php" class="btn btn-primary">+ Tambah Turnamen Baru</a>
            <?php endif; ?>
        </div>

        <form method="GET" action="daftar.php" class="filter-form">
            <input type="text" id="liveSearchInput" name="q" value="<?= e($search) ?>" placeholder="Cari nama turnamen / panitia...">
            <select id="liveGenreSelect" name="genre">
                <option value="">Semua Genre</option>
                <?php foreach ($genre_allowed as $g): ?>
                    <option value="<?= e($g) ?>" <?= $genre_filter === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter Server</button>
            <?php if ($search !== '' || $genre_filter !== ''): ?>
                <a href="daftar.php?reset_filter=1" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </section>

    <div class="table-wrapper">
        <table id="tabelTurnamen">
            <thead>
                <tr>
                    <th>Nama Turnamen</th>
                    <th>Genre Game</th>
                    <th>Biaya Pendaftaran</th>
                    <th>Max Tim</th>
                    <th>Tanggal Mulai</th>
                    <th>Diinput Oleh</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr data-genre="<?= e($row['genre_game']) ?>">
                            <td><strong><?= e($row['nama_turnamen']) ?></strong></td>
                            <td><span class="genre-pill"><?= e($row['genre_game']) ?></span></td>
                            <td><?= (float)$row['biaya_pendaftaran'] == 0 ? '<span class="gratis-tag">Gratis</span>' : 'Rp ' . number_format((float)$row['biaya_pendaftaran'], 0, ',', '.') ?></td>
                            <td><?= (int)$row['max_tim'] ?> Tim</td>
                            <td><?= e($row['tanggal_mulai']) ?></td>
                            <td><?= e($row['pembuat']) ?></td>
                            <td class="aksi-cell">
                                <a href="detail.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-info">Detail</a>
                                <?php if ($_SESSION['peran'] === 'admin'): ?>
                                    <a href="edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-danger btn-hapus-trigger"
                                            data-id="<?= (int)$row['id'] ?>"
                                            data-nama="<?= e($row['nama_turnamen']) ?>">
                                        Hapus
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr id="emptyServerRow"><td colspan="7" class="text-center">Belum ada data turnamen yang sesuai.</td></tr>
                <?php endif; ?>
                <tr id="emptyLiveRow" style="display:none;"><td colspan="7" class="text-center">Tidak ada turnamen yang cocok dengan pencarian langsung.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php if ($_SESSION['peran'] === 'admin'): ?>
<!-- Modal Konfirmasi Hapus (JavaScript + Form POST + CSRF Token) -->
<div id="modalHapus" class="modal-backdrop" style="display:none;">
    <div class="modal-box">
        <h3>Konfirmasi Penghapusan</h3>
        <p>Apakah Anda yakin ingin menghapus turnamen <strong id="hapusNamaTurnamen"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
        <form method="POST" action="hapus.php" class="modal-actions">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" id="hapusIdTurnamen" value="">
            <button type="button" id="btnBatalHapus" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-danger">Ya, Hapus Turnamen</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="assets/js/app.js"></script>
</body>
</html>

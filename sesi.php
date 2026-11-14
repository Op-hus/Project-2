<?php
// Konfigurasi keamanan cookie session sebelum session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // Mencegah akses cookie session lewat JavaScript (mitigasi XSS)
        'samesite' => 'Lax'      // Mitigasi dasar serangan CSRF
    ]);
    session_start();
}

// Batas waktu idle session (30 menit = 1800 detik)
define('SESSION_TIMEOUT', 1800);

/**
 * Sanitasi keluaran HTML untuk mencegah serangan Cross-Site Scripting (XSS)
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Menyimpan pesan notifikasi sementara di dalam SESSION
 */
function set_flash(string $tipe, string $pesan): void {
    $_SESSION['flash'] = [
        'tipe'  => $tipe,
        'pesan' => $pesan
    ];
}

/**
 * Mengambil dan langsung menghapus Flash Message dari SESSION
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Membuat atau mengambil CSRF Token unik per session untuk melindungi form POST
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memvalidasi CSRF Token yang dikirimkan melalui form POST
 */
function verifikasi_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Permintaan tidak valid (CSRF Token tidak cocok). Silakan muat ulang halaman.");
    }
}

/**
 * Auto-Login menggunakan COOKIE 'remember_esport' jika SESSION belum aktif
 */
function cek_remember_cookie(mysqli $koneksi): void {
    if (isset($_SESSION['user_id'])) {
        return;
    }

    if (!empty($_COOKIE['remember_esport'])) {
        $parts = explode(':', $_COOKIE['remember_esport'], 2);
        if (count($parts) === 2) {
            [$user_id, $token] = $parts;
            $user_id = (int)$user_id;

            if ($user_id > 0 && $token !== '') {
                $stmt = $koneksi->prepare("SELECT id, username, nama, peran, remember_token FROM pengguna WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();

                if ($row && !empty($row['remember_token']) && password_verify($token, $row['remember_token'])) {
                    // Regenerasi ID session saat pemulihan dari cookie
                    session_regenerate_id(true);
                    $_SESSION['user_id']       = $row['id'];
                    $_SESSION['username']      = $row['username'];
                    $_SESSION['nama']          = $row['nama'];
                    $_SESSION['peran']         = $row['peran'];
                    $_SESSION['login_time']    = date('H:i:s');
                    $_SESSION['last_activity'] = time();
                    $_SESSION['via_cookie']    = true;
                    return;
                }
            }
        }
        // Hapus cookie jika tidak valid / kadaluarsa
        setcookie('remember_esport', '', time() - 3600, '/');
    }
}

function cek_login(?mysqli $koneksi = null): void {
    if ($koneksi !== null) {
        cek_remember_cookie($koneksi);
    }

    if (!isset($_SESSION['user_id'])) {
        set_flash('error', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
        header('Location: login.php');
        exit();
    }

    // Cek apakah session melebihi batas waktu idle
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        set_flash('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 30 menit. Silakan login ulang.');
        header('Location: login.php');
        exit();
    }

    $_SESSION['last_activity'] = time();
}

function cek_admin(?mysqli $koneksi = null): void {
    cek_login($koneksi);
    if (($_SESSION['peran'] ?? '') !== 'admin') {
        http_response_code(403);
        die("Akses Ditolak (403): Fitur ini hanya dapat diakses oleh pengguna dengan peran Admin.");
    }
}

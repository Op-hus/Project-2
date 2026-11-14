<?php
require_once 'koneksi.php';
require_once 'sesi.php';

// Proteksi otorisasi: hanya Admin yang boleh menghapus data (DELETE)
cek_admin($koneksi);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Metode tidak diizinkan. Penghapusan data harus melalui formulir POST.");
}

verifikasi_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    set_flash('error', 'ID turnamen tidak valid.');
    header('Location: daftar.php');
    exit();
}

try {
    $stmt = $koneksi->prepare("DELETE FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        set_flash('sukses', 'Turnamen berhasil dihapus dari sistem.');
    } else {
        set_flash('error', 'Data turnamen tidak ditemukan atau sudah terhapus.');
    }
} catch (Exception $e) {
    error_log("Delete Turnamen Error: " . $e->getMessage());
    set_flash('error', 'Terjadi kesalahan saat menghapus data turnamen.');
}

header('Location: daftar.php');
exit();

<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'esport_hub';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($host, $user, $pass, $db);
    $koneksi->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Maaf, terjadi gangguan. Silakan coba beberapa saat lagi.");
}

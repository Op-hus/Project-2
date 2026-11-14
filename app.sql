CREATE DATABASE IF NOT EXISTS esport_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE esport_hub;

-- 1. Tabel pengguna (menyimpan akun, peran, hash sandi, dan token cookie Remember Me)
CREATE TABLE IF NOT EXISTS pengguna (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  peran ENUM('admin', 'anggota') NOT NULL DEFAULT 'anggota',
  hash_sandi VARCHAR(255) NOT NULL,
  remember_token VARCHAR(255) NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel tournaments (entitas utama dengan relasi FOREIGN KEY ke pengguna)
CREATE TABLE IF NOT EXISTS tournaments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pengguna_id INT UNSIGNED NOT NULL,
  nama_turnamen VARCHAR(150) NOT NULL,
  genre_game ENUM('MOBA', 'FPS', 'Battle Royale', 'Fighting') NOT NULL,
  biaya_pendaftaran DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  max_tim INT NOT NULL,
  tanggal_mulai DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (pengguna_id) REFERENCES pengguna (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal Pengguna (Kata sandi untuk kedua akun: Password123!)
INSERT INTO pengguna (username, nama, peran, hash_sandi) VALUES
('admin_esport', 'Admin Utama Esport', 'admin', '$2y$12$00CgFNvTrTPXe5dGSEbb2u/mV/nuwpXzfEEyc2K7odAPm6S6jEjwS'),
('pro_player', 'Fadil - Pro Player', 'anggota', '$2y$12$00CgFNvTrTPXe5dGSEbb2u/mV/nuwpXzfEEyc2K7odAPm6S6jEjwS');

-- Data Awal Turnamen (5 data awal)
INSERT INTO tournaments (pengguna_id, nama_turnamen, genre_game, biaya_pendaftaran, max_tim, tanggal_mulai) VALUES
(1, 'MLBB Championship Season 12', 'MOBA', 50000.00, 64, '2026-12-15'),
(1, 'Valorant Indonesia Masters 2026', 'FPS', 100000.00, 32, '2026-12-20'),
(1, 'PUBG Mobile Regional Cup', 'Battle Royale', 0.00, 128, '2026-11-28'),
(1, 'Tekken 8 Open Tournament', 'Fighting', 25000.00, 16, '2026-12-05'),
(1, 'Free Fire Community Clash', 'Battle Royale', 0.00, 64, '2026-12-10');

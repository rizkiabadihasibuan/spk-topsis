-- ====================================================
-- SKRIP DATABASE SPK TOPSIS PENENTUAN JURUSAN SMA
-- Database: db_spk_topsis
-- ====================================================

CREATE DATABASE IF NOT EXISTS `db_spk_topsis` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_spk_topsis`;

-- --------------------------------------------------------
-- 1. Tabel Users (Admin & Siswa)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_users` (
  `id_user` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `nisn` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('admin', 'siswa') NOT NULL DEFAULT 'siswa',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_nisn` (`nisn`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Tabel Kriteria
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_kriteria` (
  `id_kriteria` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_kriteria` VARCHAR(10) NOT NULL,
  `nama_kriteria` VARCHAR(100) NOT NULL,
  `bobot` DECIMAL(5,4) NOT NULL,
  `jenis` ENUM('benefit', 'cost') NOT NULL DEFAULT 'benefit',
  `keterangan` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kriteria`),
  UNIQUE KEY `uk_kode_kriteria` (`kode_kriteria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Tabel Sub Kriteria
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_sub_kriteria` (
  `id_sub_kriteria` INT(11) NOT NULL AUTO_INCREMENT,
  `id_kriteria` INT(11) NOT NULL,
  `nama_sub` VARCHAR(100) NOT NULL,
  `nilai_bobot` DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (`id_sub_kriteria`),
  INDEX `idx_sub_kriteria` (`id_kriteria`),
  CONSTRAINT `fk_sub_kriteria` FOREIGN KEY (`id_kriteria`) REFERENCES `tb_kriteria` (`id_kriteria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Tabel Alternatif Jurusan
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_alternatif` (
  `id_alternatif` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_alternatif` VARCHAR(10) NOT NULL,
  `nama_jurusan` VARCHAR(100) NOT NULL,
  `fakultas` VARCHAR(100) DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `prospek_kerja` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_alternatif`),
  UNIQUE KEY `uk_kode_alternatif` (`kode_alternatif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Tabel Penilaian (Matriks Keputusan X Dinamis)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_penilaian` (
  `id_penilaian` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `id_alternatif` INT(11) NOT NULL,
  `id_kriteria` INT(11) NOT NULL,
  `nilai` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_penilaian`),
  UNIQUE KEY `uk_penilaian_user_alt_krit` (`id_user`, `id_alternatif`, `id_kriteria`),
  INDEX `idx_penilaian_user` (`id_user`),
  INDEX `idx_penilaian_alt` (`id_alternatif`),
  INDEX `idx_penilaian_krit` (`id_kriteria`),
  CONSTRAINT `fk_penilaian_user` FOREIGN KEY (`id_user`) REFERENCES `tb_users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_penilaian_alt` FOREIGN KEY (`id_alternatif`) REFERENCES `tb_alternatif` (`id_alternatif`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_penilaian_krit` FOREIGN KEY (`id_kriteria`) REFERENCES `tb_kriteria` (`id_kriteria`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Tabel Hasil TOPSIS (Snapshot / Log Perangkai)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tb_hasil_topsis` (
  `id_hasil` INT(11) NOT NULL AUTO_INCREMENT,
  `id_user` INT(11) NOT NULL,
  `id_alternatif` INT(11) NOT NULL,
  `jarak_d_plus` DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
  `jarak_d_minus` DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
  `nilai_v` DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
  `ranking` INT(11) NOT NULL DEFAULT 0,
  `tanggal_hitung` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_hasil`),
  INDEX `idx_hasil_user` (`id_user`),
  INDEX `idx_hasil_alt` (`id_alternatif`),
  INDEX `idx_hasil_v` (`nilai_v`),
  CONSTRAINT `fk_hasil_user` FOREIGN KEY (`id_user`) REFERENCES `tb_users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_hasil_alt` FOREIGN KEY (`id_alternatif`) REFERENCES `tb_alternatif` (`id_alternatif`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Sampel Data Admin & Siswa (Password Admin: admin123 | Password Siswa: siswa123)
INSERT INTO `tb_users` (`id_user`, `username`, `password`, `nama_lengkap`, `nisn`, `role`) VALUES
(1, 'admin', '$2y$10$dpO1i1Lalhq6D8XK/OKyEef9JiQbj4eXkIW2lZI5ZbIAQ.1WT.mGK', 'Guru BK / Administrator', NULL, 'admin'),
(2, 'siswa1', '$2y$10$vDxa8VJO0x8paFzBVOVSvOuNsiE775AzWSMENtguGiaNx/vq1N0VS', 'Budi Santoso', '0051234567', 'siswa')
ON DUPLICATE KEY UPDATE `id_user`=`id_user`;

-- --------------------------------------------------------
-- Data Awal Laporan: Kriteria & Bobot
-- --------------------------------------------------------
INSERT INTO `tb_kriteria` (`id_kriteria`, `kode_kriteria`, `nama_kriteria`, `bobot`, `jenis`, `keterangan`) VALUES
(1, 'C1', 'Nilai Rapor Matematika & Logika', 0.2500, 'benefit', 'Rata-rata nilai rapor Matematika & Logika (Semester 1-5)'),
(2, 'C2', 'Nilai Rapor Bahasa Inggris & Komunikasi', 0.2000, 'benefit', 'Rata-rata nilai rapor Bahasa Inggris & Bahasa Indonesia'),
(3, 'C3', 'Skor Tes Potensi Akademik (TPA)', 0.2000, 'benefit', 'Hasil pengujian kemampuan penalaran & akademik siswa'),
(4, 'C4', 'Minat & Psikotes Siswa', 0.1500, 'benefit', 'Skor asesmen kecenderungan minat jurusan & psikologi'),
(5, 'C5', 'Estimasi Biaya Kuliah / UKT', 0.1000, 'cost', 'Skala beban estimasi biaya kuliah per semester'),
(6, 'C6', 'Prospek & Serapan Kerja', 0.1000, 'benefit', 'Tingkat peluang & daya serap lulusan jurusan di dunia kerja')
ON DUPLICATE KEY UPDATE `kode_kriteria`=`kode_kriteria`;

-- --------------------------------------------------------
-- Data Awal Laporan: Alternatif Jurusan Kuliah
-- --------------------------------------------------------
INSERT INTO `tb_alternatif` (`id_alternatif`, `kode_alternatif`, `nama_jurusan`, `fakultas`, `deskripsi`, `prospek_kerja`) VALUES
(1, 'A1', 'Teknik Informatika', 'Fakultas Ilmu Komputer', 'Program studi komputasi, pemrograman, dan rekayasa perangkat lunak', 'Software Engineer, Data Scientist, Web Developer'),
(2, 'A2', 'Sistem Informasi', 'Fakultas Ilmu Komputer', 'Program studi pengintegrasian teknologi informasi dengan manajemen bisnis', 'System Analyst, IT Project Manager, Business Analyst'),
(3, 'A3', 'Kedokteran', 'Fakultas Kedokteran', 'Program studi ilmu kesehatan, diagnosis, dan penyembuhan penyakit', 'Dokter Umum, Dokter Spesialis, Peneliti Medis'),
(4, 'A4', 'Manajemen & Bisnis', 'Fakultas Ekonomi dan Bisnis', 'Program studi pengelolaan organisasi, keuangan, pemasaran, dan SDM', 'Manager, Entrepreneur, Financial Analyst'),
(5, 'A5', 'Ilmu Hukum', 'Fakultas Hukum', 'Program studi sistem hukum, perundang-undangan, dan advokasi', 'Pengacara, Hakim, Jaksa, Legal Corporate'),
(6, 'A6', 'Psikologi', 'Fakultas Psikologi', 'Program studi perilaku manusia, mental, dan proses mental individu', 'HRD, Konselor, Psikolog, Trainer'),
(7, 'A7', 'Desain Komunikasi Visual', 'Fakultas Seni & Desain', 'Program studi komunikasi visual, desain grafis, ilustrasi, dan media kreatif', 'UI/UX Designer, Graphic Designer, Creative Director'),
(8, 'A8', 'Teknik Sipil', 'Fakultas Teknik', 'Program studi perancangan, pembangunan, dan pemeliharaan infrastruktur', 'Civil Engineer, Project Manager Infrastructure, Konsultan Konstruksi')
ON DUPLICATE KEY UPDATE `kode_alternatif`=`kode_alternatif`;

-- --------------------------------------------------------
-- Data Awal Laporan: Matriks PenilaianKeputusan (tb_penilaian)
-- --------------------------------------------------------
INSERT INTO `tb_penilaian` (`id_user`, `id_alternatif`, `id_kriteria`, `nilai`) VALUES
-- A1 (Teknik Informatika)
(1, 1, 1, 88.00), (1, 1, 2, 82.00), (1, 1, 3, 85.00), (1, 1, 4, 90.00), (1, 1, 5, 75.00), (1, 1, 6, 95.00),
-- A2 (Sistem Informasi)
(1, 2, 1, 82.00), (1, 2, 2, 85.00), (1, 2, 3, 80.00), (1, 2, 4, 88.00), (1, 2, 5, 70.00), (1, 2, 6, 90.00),
-- A3 (Kedokteran)
(1, 3, 1, 92.00), (1, 3, 2, 88.00), (1, 3, 3, 90.00), (1, 3, 4, 85.00), (1, 3, 5, 95.00), (1, 3, 6, 98.00),
-- A4 (Manajemen & Bisnis)
(1, 4, 1, 78.00), (1, 4, 2, 84.00), (1, 4, 3, 78.00), (1, 4, 4, 80.00), (1, 4, 5, 65.00), (1, 4, 6, 85.00),
-- A5 (Ilmu Hukum)
(1, 5, 1, 70.00), (1, 5, 2, 86.00), (1, 5, 3, 75.00), (1, 5, 4, 82.00), (1, 5, 5, 60.00), (1, 5, 6, 80.00),
-- A6 (Psikologi)
(1, 6, 1, 72.00), (1, 6, 2, 85.00), (1, 6, 3, 76.00), (1, 6, 4, 85.00), (1, 6, 5, 60.00), (1, 6, 6, 82.00),
-- A7 (Desain Komunikasi Visual)
(1, 7, 1, 68.00), (1, 7, 2, 80.00), (1, 7, 3, 72.00), (1, 7, 4, 95.00), (1, 7, 5, 80.00), (1, 7, 6, 88.00),
-- A8 (Teknik Sipil)
(1, 8, 1, 85.00), (1, 8, 2, 78.00), (1, 8, 3, 82.00), (1, 8, 4, 75.00), (1, 8, 5, 75.00), (1, 8, 6, 88.00)
ON DUPLICATE KEY UPDATE `nilai`=VALUES(`nilai`);

-- --------------------------------------------------------
-- 7. Tabel Riwayat Perhitungan Header
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `riwayat_perhitungan` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_perhitungan` VARCHAR(50) NOT NULL,
  `metode` VARCHAR(50) NOT NULL DEFAULT 'TOPSIS',
  `tanggal_perhitungan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `jumlah_alternatif` INT(11) NOT NULL,
  `jumlah_kriteria` INT(11) NOT NULL,
  `alternatif_terbaik` VARCHAR(255) NOT NULL,
  `nilai_preferensi_terbaik` DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kode_perhitungan` (`kode_perhitungan`),
  INDEX `idx_tanggal` (`tanggal_perhitungan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 8. Tabel Riwayat Detail Perhitungan
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `riwayat_detail` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `riwayat_id` INT(11) NOT NULL,
  `alternatif_id` INT(11) NOT NULL,
  `nilai_d_plus` DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
  `nilai_d_minus` DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
  `nilai_preferensi` DECIMAL(10,6) NOT NULL DEFAULT 0.000000,
  `ranking` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_riwayat` (`riwayat_id`),
  INDEX `idx_alternatif` (`alternatif_id`),
  CONSTRAINT `fk_riwayat_perhitungan` FOREIGN KEY (`riwayat_id`) REFERENCES `riwayat_perhitungan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_riwayat_alternatif` FOREIGN KEY (`alternatif_id`) REFERENCES `tb_alternatif` (`id_alternatif`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


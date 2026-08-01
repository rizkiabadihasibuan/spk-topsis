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
-- Data Awal Laporan: Kriteria & Bobot (Gabungan Data Real & Rating Akreditasi)
-- --------------------------------------------------------
INSERT INTO `tb_kriteria` (`id_kriteria`, `kode_kriteria`, `nama_kriteria`, `bobot`, `jenis`, `keterangan`) VALUES
(1, 'C1', 'Nilai Rapor Akademik', 0.2500, 'benefit', 'Rata-rata nilai rapor mata pelajaran utama (Data Real 0-100)'),
(2, 'C2', 'Skor Tes Potensi Akademik (TPA)', 0.2000, 'benefit', 'Hasil tes penalaran & kemampuan akademik siswa (Data Real 0-100)'),
(3, 'C3', 'Akreditasi Jurusan & Kampus', 0.1500, 'benefit', 'Rating Akreditasi BAN-PT/LAM (5: Unggul/A, 4: Baik Sekali/B, 3: Baik/C, 2: Cukup, 1: Belum)'),
(4, 'C4', 'Minat & Asesmen Psikotes', 0.1500, 'benefit', 'Skor kecenderungan minat & kesesuaian bakat (Rating Skala 1-5)'),
(5, 'C5', 'Estimasi Biaya UKT per Semester', 0.1500, 'cost', 'Tingkat beban biaya UKT (Rating Cost 1: <2Jt, 2: 2-5Jt, 3: 5-8Jt, 4: 8-12Jt, 5: >12Jt)'),
(6, 'C6', 'Prospek Kerja & Daya Serap', 0.1000, 'benefit', 'Peluang karir & serapan kerja lulusan (Rating 1: Sangat Rendah s.d 5: Sangat Luas)'),
(7, 'C7', 'Tingkat Persaingan Masuk', 0.1500, 'cost', 'Tingkat keketatan persaingan pendaftar (Rating Cost 1: <1:5 s.d 5: >1:50)')
ON DUPLICATE KEY UPDATE `kode_kriteria`=`kode_kriteria`;

-- --------------------------------------------------------
-- Data Awal Laporan: Sub-Kriteria (Panduan Rating Konversi)
-- --------------------------------------------------------
INSERT INTO `tb_sub_kriteria` (`id_sub_kriteria`, `id_kriteria`, `nama_sub`, `nilai_bobot`) VALUES
-- C3 (Akreditasi Jurusan & Kampus)
(1, 3, 'Unggul / Akreditasi A', 5.00),
(2, 3, 'Baik Sekali / Akreditasi B', 4.00),
(3, 3, 'Baik / Akreditasi C', 3.00),
(4, 3, 'Akreditasi Minimum / Cukup', 2.00),
(5, 3, 'Belum Terakreditasi', 1.00),
-- C4 (Minat & Asesmen Psikotes)
(6, 4, 'Sangat Berminat & Sesuai Psikotes', 5.00),
(7, 4, 'Berminat & Sesuai', 4.00),
(8, 4, 'Cukup Berminat', 3.00),
(9, 4, 'Kurang Berminat', 2.00),
(10, 4, 'Tidak Berminat', 1.00),
-- C5 (Estimasi Biaya UKT)
(11, 5, 'Sangat Murah (< Rp 2.000.000 / sem)', 1.00),
(12, 5, 'Murah (Rp 2.000.000 - Rp 5.000.000)', 2.00),
(13, 5, 'Sedang (Rp 5.000.000 - Rp 8.000.000)', 3.00),
(14, 5, 'Mahal (Rp 8.000.000 - Rp 12.000.000)', 4.00),
(15, 5, 'Sangat Mahal (> Rp 12.000.000 / sem)', 5.00),
-- C6 (Prospek Kerja & Daya Serap)
(16, 6, 'Sangat Luas & Tinggi (Daya Serap > 90%)', 5.00),
(17, 6, 'Luas & Tinggi (Daya Serap 75-90%)', 4.00),
(18, 6, 'Sedang / Cukup (Daya Serap 60-75%)', 3.00),
(19, 6, 'Rendah (Daya Serap < 60%)', 2.00),
(20, 6, 'Sangat Rendah', 1.00),
-- C7 (Tingkat Persaingan Masuk)
(21, 7, 'Sangat Tinggi / Ketat Sekali (Rasio > 1:50)', 5.00),
(22, 7, 'Tinggi (Rasio 1:20 - 1:50)', 4.00),
(23, 7, 'Sedang (Rasio 1:10 - 1:20)', 3.00),
(24, 7, 'Rendah (Rasio 1:5 - 1:10)', 2.00),
(25, 7, 'Sangat Rendah (Rasio < 1:5)', 1.00)
ON DUPLICATE KEY UPDATE `id_sub_kriteria`=`id_sub_kriteria`;

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
-- A1 (Teknik Informatika): Real Rapor & TPA + Rating Akreditasi/Minat/UKT/Prospek
(1, 1, 1, 88.50), (1, 1, 2, 85.00), (1, 1, 3, 5.00), (1, 1, 4, 5.00), (1, 1, 5, 3.00), (1, 1, 6, 5.00),
-- A2 (Sistem Informasi)
(1, 2, 1, 82.00), (1, 2, 2, 80.00), (1, 2, 3, 5.00), (1, 2, 4, 4.00), (1, 2, 5, 2.00), (1, 2, 6, 4.00),
-- A3 (Kedokteran)
(1, 3, 1, 95.00), (1, 3, 2, 92.00), (1, 3, 3, 5.00), (1, 3, 4, 5.00), (1, 3, 5, 5.00), (1, 3, 6, 5.00),
-- A4 (Manajemen & Bisnis)
(1, 4, 1, 78.00), (1, 4, 2, 78.00), (1, 4, 3, 4.00), (1, 4, 4, 4.00), (1, 4, 5, 2.00), (1, 4, 6, 4.00),
-- A5 (Ilmu Hukum)
(1, 5, 1, 72.00), (1, 5, 2, 75.00), (1, 5, 3, 4.00), (1, 5, 4, 4.00), (1, 5, 5, 2.00), (1, 5, 6, 4.00),
-- A6 (Psikologi)
(1, 6, 1, 75.00), (1, 6, 2, 76.00), (1, 6, 3, 4.00), (1, 6, 4, 4.00), (1, 6, 5, 2.00), (1, 6, 6, 3.00),
-- A7 (Desain Komunikasi Visual)
(1, 7, 1, 70.00), (1, 7, 2, 72.00), (1, 7, 3, 3.00), (1, 7, 4, 5.00), (1, 7, 5, 3.00), (1, 7, 6, 4.00),
-- A8 (Teknik Sipil)
(1, 8, 1, 85.00), (1, 8, 2, 82.00), (1, 8, 3, 4.00), (1, 8, 4, 3.00), (1, 8, 5, 3.00), (1, 8, 6, 4.00)
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


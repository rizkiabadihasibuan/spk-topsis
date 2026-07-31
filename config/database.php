<?php
/**
 * ====================================================
 * SKRIP KONFIGURASI KONEKSI DATABASE (PDO SMART AUTO-CREATE)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_spk_topsis');

/**
 * Fungsi Mengambil Objek Koneksi PDO Database
 * Secara otomatis membuat Database db_spk_topsis & Tabel jika belum ada
 * @return PDO|null
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $ports = ['3306', '3307'];
        $connected = false;

        foreach ($ports as $port) {
            try {
                // 1. Koneksi ke MySQL server tanpa menentukan dbname dulu
                $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

                // 2. Buat database db_spk_topsis jika belum ada
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `" . DB_NAME . "`");

                // 3. Otomatisasi Pembuatan Tabel jika belum ada
                initDatabaseTables($pdo);

                $connected = true;
                break;
            } catch (PDOException $e) {
                continue;
            }
        }

        if (!$connected) {
            return null;
        }
    }

    return $pdo;
}

/**
 * Fungsi Otomatis Inisialisasi Skema Tabel & Data Awal
 * @param PDO $pdo
 */
function initDatabaseTables($pdo) {
    try {
        // 1. Tabel Users
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tb_users` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 2. Tabel Kriteria
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tb_kriteria` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 3. Tabel Sub Kriteria
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tb_sub_kriteria` (
          `id_sub_kriteria` INT(11) NOT NULL AUTO_INCREMENT,
          `id_kriteria` INT(11) NOT NULL,
          `nama_sub` VARCHAR(100) NOT NULL,
          `nilai_bobot` DECIMAL(5,2) NOT NULL,
          PRIMARY KEY (`id_sub_kriteria`),
          INDEX `idx_sub_kriteria` (`id_kriteria`),
          CONSTRAINT `fk_sub_kriteria` FOREIGN KEY (`id_kriteria`) REFERENCES `tb_kriteria` (`id_kriteria`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 4. Tabel Alternatif
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tb_alternatif` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 5. Tabel Penilaian
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tb_penilaian` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 6. Tabel Riwayat Perhitungan Header
        $pdo->exec("CREATE TABLE IF NOT EXISTS `riwayat_perhitungan` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 7. Tabel Riwayat Detail Perhitungan
        $pdo->exec("CREATE TABLE IF NOT EXISTS `riwayat_detail` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Inisialisasi Akun Default jika tabel users masih kosong
        $count = $pdo->query("SELECT COUNT(*) FROM `tb_users`")->fetchColumn();
        if ($count == 0) {
            $passAdmin = password_hash('admin123', PASSWORD_DEFAULT);
            $passSiswa = password_hash('siswa123', PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO `tb_users` (`id_user`, `username`, `password`, `nama_lengkap`, `role`) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([1, 'admin', $passAdmin, 'Guru BK / Administrator', 'admin']);
            $stmt->execute([2, 'siswa1', $passSiswa, 'Budi Santoso', 'siswa']);
        }

        // Inisialisasi Data Awal Kriteria Laporan jika tabel tb_kriteria kosong
        $countKriteria = $pdo->query("SELECT COUNT(*) FROM `tb_kriteria`")->fetchColumn();
        if ($countKriteria == 0) {
            $pdo->exec("INSERT INTO `tb_kriteria` (`id_kriteria`, `kode_kriteria`, `nama_kriteria`, `bobot`, `jenis`, `keterangan`) VALUES
                (1, 'C1', 'Nilai Rapor Matematika & Logika', 0.2500, 'benefit', 'Rata-rata nilai rapor Matematika & Logika (Semester 1-5)'),
                (2, 'C2', 'Nilai Rapor Bahasa Inggris & Komunikasi', 0.2000, 'benefit', 'Rata-rata nilai rapor Bahasa Inggris & Bahasa Indonesia'),
                (3, 'C3', 'Skor Tes Potensi Akademik (TPA)', 0.2000, 'benefit', 'Hasil pengujian kemampuan penalaran & akademik siswa'),
                (4, 'C4', 'Minat & Psikotes Siswa', 0.1500, 'benefit', 'Skor asesmen kecenderungan minat jurusan & psikologi'),
                (5, 'C5', 'Estimasi Biaya Kuliah / UKT', 0.1000, 'cost', 'Skala beban estimasi biaya kuliah per semester'),
                (6, 'C6', 'Prospek & Serapan Kerja', 0.1000, 'benefit', 'Tingkat peluang & daya serap lulusan jurusan di dunia kerja')
            ");
        }

        // Inisialisasi Data Awal Alternatif Laporan jika tabel tb_alternatif kosong
        $countAlternatif = $pdo->query("SELECT COUNT(*) FROM `tb_alternatif`")->fetchColumn();
        if ($countAlternatif == 0) {
            $pdo->exec("INSERT INTO `tb_alternatif` (`id_alternatif`, `kode_alternatif`, `nama_jurusan`, `fakultas`, `deskripsi`, `prospek_kerja`) VALUES
                (1, 'A1', 'Teknik Informatika', 'Fakultas Ilmu Komputer', 'Program studi komputasi, pemrograman, dan rekayasa perangkat lunak', 'Software Engineer, Data Scientist, Web Developer'),
                (2, 'A2', 'Sistem Informasi', 'Fakultas Ilmu Komputer', 'Program studi pengintegrasian teknologi informasi dengan manajemen bisnis', 'System Analyst, IT Project Manager, Business Analyst'),
                (3, 'A3', 'Kedokteran', 'Fakultas Kedokteran', 'Program studi ilmu kesehatan, diagnosis, dan penyembuhan penyakit', 'Dokter Umum, Dokter Spesialis, Peneliti Medis'),
                (4, 'A4', 'Manajemen & Bisnis', 'Fakultas Ekonomi dan Bisnis', 'Program studi pengelolaan organisasi, keuangan, pemasaran, dan SDM', 'Manager, Entrepreneur, Financial Analyst'),
                (5, 'A5', 'Ilmu Hukum', 'Fakultas Hukum', 'Program studi sistem hukum, perundang-undangan, dan advokasi', 'Pengacara, Hakim, Jaksa, Legal Corporate'),
                (6, 'A6', 'Psikologi', 'Fakultas Psikologi', 'Program studi perilaku manusia, mental, dan proses mental individu', 'HRD, Konselor, Psikolog, Trainer'),
                (7, 'A7', 'Desain Komunikasi Visual', 'Fakultas Seni & Desain', 'Program studi komunikasi visual, desain grafis, ilustrasi, dan media kreatif', 'UI/UX Designer, Graphic Designer, Creative Director'),
                (8, 'A8', 'Teknik Sipil', 'Fakultas Teknik', 'Program studi perancangan, pembangunan, dan pemeliharaan infrastruktur', 'Civil Engineer, Project Manager Infrastructure, Konsultan Konstruksi')
            ");
        }

        // Inisialisasi Data Awal Penilaian Matriks Laporan jika tabel tb_penilaian kosong
        $countPenilaian = $pdo->query("SELECT COUNT(*) FROM `tb_penilaian`")->fetchColumn();
        if ($countPenilaian == 0) {
            $pdo->exec("INSERT INTO `tb_penilaian` (`id_user`, `id_alternatif`, `id_kriteria`, `nilai`) VALUES
                (1, 1, 1, 88.00), (1, 1, 2, 82.00), (1, 1, 3, 85.00), (1, 1, 4, 90.00), (1, 1, 5, 75.00), (1, 1, 6, 95.00),
                (1, 2, 1, 82.00), (1, 2, 2, 85.00), (1, 2, 3, 80.00), (1, 2, 4, 88.00), (1, 2, 5, 70.00), (1, 2, 6, 90.00),
                (1, 3, 1, 92.00), (1, 3, 2, 88.00), (1, 3, 3, 90.00), (1, 3, 4, 85.00), (1, 3, 5, 95.00), (1, 3, 6, 98.00),
                (1, 4, 1, 78.00), (1, 4, 2, 84.00), (1, 4, 3, 78.00), (1, 4, 4, 80.00), (1, 4, 5, 65.00), (1, 4, 6, 85.00),
                (1, 5, 1, 70.00), (1, 5, 2, 86.00), (1, 5, 3, 75.00), (1, 5, 4, 82.00), (1, 5, 5, 60.00), (1, 5, 6, 80.00),
                (1, 6, 1, 72.00), (1, 6, 2, 85.00), (1, 6, 3, 76.00), (1, 6, 4, 85.00), (1, 6, 5, 60.00), (1, 6, 6, 82.00),
                (1, 7, 1, 68.00), (1, 7, 2, 80.00), (1, 7, 3, 72.00), (1, 7, 4, 95.00), (1, 7, 5, 80.00), (1, 7, 6, 88.00),
                (1, 8, 1, 85.00), (1, 8, 2, 78.00), (1, 8, 3, 82.00), (1, 8, 4, 75.00), (1, 8, 5, 75.00), (1, 8, 6, 88.00)
            ");
        }
    } catch (PDOException $e) {
        error_log("Init Table Exception: " . $e->getMessage());
    }
}

// Inisialisasi variabel koneksi global
$db = getDBConnection();

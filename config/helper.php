<?php
/**
 * ====================================================
 * FILE HELPER FUNCTIONS (FUNGSI BANTUAN GLOBAL)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

/**
 * Sanitasi Input dari XSS (Cross Site Scripting)
 * @param string $data
 * @return string
 */
function sanitize($data) {
    if ($data === null || $data === '') return '';
    if (!is_string($data)) return $data;
    
    // Decode recursive html entities to clean raw string first
    $raw = $data;
    while ($raw !== ($decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) {
        $raw = $decoded;
    }
    return htmlspecialchars(trim($raw), ENT_QUOTES, 'UTF-8');
}

/**
 * Format Angka Desimal (misal Skor TOPSIS / Bobot)
 * @param float|int $val
 * @param int $decimals
 * @return string
 */
function formatNumber($val, $decimals = 4) {
    return number_format((float)$val, $decimals, '.', ',');
}

/**
 * Format Tanggal Indonesia
 * @param string $datetime
 * @return string
 */
function formatTanggalIndo($datetime) {
    if (empty($datetime)) return '-';
    
    $hariArray = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulanArray = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $time = strtotime($datetime);
    $hari = $hariArray[date('w', $time)];
    $tgl = date('j', $time);
    $bulan = $bulanArray[(int)date('n', $time)];
    $tahun = date('Y', $time);
    $jam = date('H:i', $time);

    return "$hari, $tgl $bulan $tahun ($jam WIB)";
}

/**
 * Penentu CSS Class Menu Active pada Sidebar
 * @param string $pageName
 * @param string $currentPage
 * @return string
 */
function isActiveMenu($pageName, $currentPage) {
    return ($pageName === $currentPage) ? 'active' : '';
}

/**
 * Mendapatkan Judul Halaman berdasarkan Parameter Route
 * @param string $page
 * @return string
 */
function getPageTitle($page) {
    $titles = [
        'dashboard'     => 'Dashboard Utama',
        'alternatif'    => 'Data Alternatif Jurusan',
        'kriteria'      => 'Data Kriteria & Bobot',
        'penilaian'     => 'Input Matriks Penilaian',
        'import-excel'  => 'Import Data Excel',
        'topsis'        => 'Perhitungan TOPSIS',
        'ranking'       => 'Perangkai Rekomendasi Jurusan',
        'riwayat'       => 'Riwayat Perhitungan TOPSIS',
        'laporan'       => 'Riwayat & Laporan Hasil',
        'blank'         => 'Modul Template'
    ];

    return $titles[$page] ?? 'Halaman Tidak Ditemukan';
}

/**
 * Helper Badge Atribut Kriteria (Benefit / Cost)
 * @param string $jenis
 * @return string
 */
function getBadgeJenis($jenis) {
    if (strtolower($jenis) === 'benefit') {
        return '<span class="badge badge-benefit shadow-sm"><i class="bi bi-arrow-up-circle-fill me-1"></i>Benefit</span>';
    } else {
        return '<span class="badge badge-cost shadow-sm"><i class="bi bi-arrow-down-circle-fill me-1"></i>Cost</span>';
    }
}

/**
 * Fungsi Redirect Aman (Safe HTTP Header / JS Fallback)
 * @param string $url
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
    } else {
        echo "<script>window.location.href = '" . addslashes($url) . "';</script>";
    }
    exit;
}

<?php
/**
 * ====================================================
 * SKRIP INDEX UTAMA & ROUTER SEDERHANA
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

// Start output buffering untuk mencegah 'Cannot modify header information'
ob_start();

// 1. Load Konfigurasi & Helper
require_once __DIR__ . '/config/global.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

// 2. Proteksi Autentikasi Session
checkAuth();

// 3. Tangkap Parameter Route Page
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'dashboard';

// 4. Daftar Route Halaman Terdaftar
$allowed_pages = [
    'dashboard'     => 'views/dashboard.php',
    'alternatif'    => 'views/alternatif.php',
    'kriteria'      => 'views/kriteria.php',
    'penilaian'     => 'views/penilaian.php',
    'import-excel'  => 'views/import_excel.php',
    'topsis'        => 'views/topsis.php',
    'ranking'       => 'views/ranking.php',
    'riwayat'       => 'views/riwayat.php',
    'laporan'       => 'views/riwayat.php',
    'blank'         => 'views/blank.php'
];

// 5. Muat Header Template Global
require_once __DIR__ . '/includes/header.php';

// 6. Muat Sidebar Template Global
require_once __DIR__ . '/includes/sidebar.php';

?>
<!-- Page Content Wrapper -->
<div id="page-content-wrapper" class="w-100 d-flex flex-column min-vh-100">
    <!-- Navbar Header -->
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <!-- Dynamic Main Content Area -->
    <main class="container-fluid p-4">
        <?php
        // Routing Handler Logika
        if (array_key_exists($page, $allowed_pages)) {
            $view_file = __DIR__ . '/' . $allowed_pages[$page];
            if (file_exists($view_file)) {
                require_once $view_file;
            } else {
                require_once __DIR__ . '/views/404.php';
            }
        } else {
            require_once __DIR__ . '/views/404.php';
        }
        ?>
    </main>

<?php
// 7. Muat Footer Template Global
require_once __DIR__ . '/includes/footer.php';

// Flush Output Buffer
ob_end_flush();

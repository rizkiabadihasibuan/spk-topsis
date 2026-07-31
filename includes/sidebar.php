<?php
/**
 * ====================================================
 * TEMPLATE SIDEBAR NAVIGATION
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */
$currentPage = $page ?? 'dashboard';
?>
<!-- Sidebar Wrapper -->
<div id="sidebar-wrapper">
    <!-- Brand / Logo Header SMA -->
    <div class="sidebar-heading d-flex align-items-center">
        <div class="brand-logo me-3 shadow-sm">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <h6 class="fw-bold text-white mb-0" style="letter-spacing: 0.3px; font-size: 0.95rem;">BK SMA TOPSIS</h6>
            <span class="badge bg-warning text-dark fw-bold px-2 py-0" style="font-size: 0.65rem;">Penentuan Jurusan Kuliah</span>
        </div>
    </div>

    <!-- Navigation Menu List -->
    <div class="sidebar-menu">
        <div class="menu-header">Menu Navigasi BK</div>

        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('dashboard', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Alternatif -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('alternatif', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=alternatif">
                    <i class="bi bi-journal-bookmark"></i>
                    <span>Alternatif</span>
                </a>
            </li>

            <!-- Kriteria -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('kriteria', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=kriteria">
                    <i class="bi bi-sliders"></i>
                    <span>Kriteria</span>
                </a>
            </li>

            <!-- Penilaian -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('penilaian', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=penilaian">
                    <i class="bi bi-pencil-square"></i>
                    <span>Penilaian</span>
                </a>
            </li>

            <!-- Import Excel -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('import-excel', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=import-excel">
                    <i class="bi bi-file-earmark-excel"></i>
                    <span>Import Excel</span>
                </a>
            </li>

            <!-- Perhitungan TOPSIS -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('topsis', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=topsis">
                    <i class="bi bi-calculator"></i>
                    <span>Perhitungan TOPSIS</span>
                </a>
            </li>

            <!-- Ranking -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('ranking', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=ranking">
                    <i class="bi bi-trophy"></i>
                    <span>Ranking</span>
                </a>
            </li>

            <!-- Riwayat Perhitungan -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('riwayat', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=riwayat">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Perhitungan</span>
                </a>
            </li>

            <!-- Laporan -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveMenu('laporan', $currentPage); ?>" href="<?= BASE_URL; ?>index.php?page=laporan">
                    <i class="bi bi-printer"></i>
                    <span>Laporan</span>
                </a>
            </li>
        </ul>

        <hr class="my-3 mx-2" style="border-color: rgba(255,255,255,0.1);">

        <!-- Logout Button -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-danger" href="javascript:void(0);" onclick="confirmLogout();">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<script>
function confirmLogout() {
    Swal.fire({
        title: "Konfirmasi Logout",
        text: "Apakah Anda yakin ingin keluar dari sistem?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Ya, Logout",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "<?= BASE_URL; ?>logout.php";
        }
    });
}
</script>

<?php
/**
 * ====================================================
 * TEMPLATE NAVBAR HEADER
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */
$currentUserNama = $_SESSION['nama'] ?? 'Guru BK / Admin';
$currentUserRole = $_SESSION['role'] ?? 'admin';
?>
<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-custom w-100 sticky-top">
    <div class="container-fluid">
        <!-- Sidebar Toggle Button & Brand Title -->
        <div class="d-flex align-items-center">
            <button class="navbar-toggler-btn me-2 me-md-3" id="menu-toggle" type="button" aria-label="Toggle Navigasi Menu" title="Toggle Navigasi">
                <i class="bi bi-list fs-4"></i>
            </button>
            
            <!-- Mobile Brand Title (Visible on Mobile) -->
            <div class="d-flex d-md-none align-items-center">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-1" style="font-size: 0.95rem;">
                    <i class="bi bi-mortarboard-fill text-warning me-1"></i> SPK TOPSIS BK
                </h6>
            </div>

            <!-- Desktop Brand Title -->
            <div class="d-none d-md-flex align-items-center">
                <div class="me-3 p-2 bg-primary-subtle rounded-3 text-primary">
                    <i class="bi bi-building-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <?= APP_NAME; ?>
                        <span class="sma-badge">SMA / Sederajat</span>
                    </h6>
                    <small class="text-muted" style="font-size: 0.78rem;">Sistem Pendukung Keputusan Bimbingan Konseling (BK) • Metode <?= APP_METHOD; ?></small>
                </div>
            </div>
        </div>

        <!-- Right Side User Widget & Profile Dropdown -->
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="d-none d-lg-block text-end">
                <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-1 rounded-pill fw-bold">
                    <i class="bi bi-award-fill text-warning me-1"></i> SPK TOPSIS BK
                </span>
            </div>

            <div class="dropdown">
                <a class="d-flex align-items-center text-decoration-none dropdown-toggle bg-light p-1 pe-3 rounded-pill border" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <!-- Default Avatar Icon -->
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 38px; height: 38px; font-weight: 600;">
                        <i class="bi bi-person-circle fs-5"></i>
                    </div>
                    <div class="d-none d-sm-block text-start me-1">
                        <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;"><?= sanitize($currentUserNama); ?></span>
                        <span class="badge bg-primary text-white" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;"><?= strtoupper(sanitize($currentUserRole)); ?> BK</span>
                    </div>
                </a>

                <!-- Profile Dropdown Menu -->
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="navbarDropdown">
                    <li><h6 class="dropdown-header">Akun Terhubung</h6></li>
                    <li>
                        <a class="dropdown-item py-2" href="javascript:void(0);">
                            <i class="bi bi-person me-2 text-primary"></i> Profil Saya
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2 text-danger" href="javascript:void(0);" onclick="confirmLogout();">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

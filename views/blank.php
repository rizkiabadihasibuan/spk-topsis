<?php
/**
 * ====================================================
 * HALAMAN TEMPLATE BLANK (ACUAN TAHAP BERIKUTNYA)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */
$moduleTitle = getPageTitle($page ?? 'blank');
?>

<!-- Module Header Breadcrumb Card -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-folder2-open me-2 text-primary"></i><?= sanitize($moduleTitle); ?>
            </h4>
            <p class="text-muted mb-0" style="font-size: 0.88rem;">
                Modul <strong><?= sanitize($page); ?></strong> - Siap untuk dikembangkan pada tahap selanjutnya.
            </p>
        </div>
        <div>
            <button class="btn btn-primary" disabled>
                <i class="bi bi-plus-lg me-1"></i> Tambah Data
            </button>
        </div>
    </div>
</div>

<!-- Placeholder Card Body -->
<div class="card card-custom p-5 text-center">
    <div class="py-4">
        <i class="bi bi-code-slash text-muted display-3 mb-3 d-block"></i>
        <h5 class="fw-bold text-dark mb-2">Modul Berhasil Dipersiapkan</h5>
        <p class="text-muted max-w-md mx-auto" style="max-width: 500px; font-size: 0.9rem;">
            Tata letak dasar untuk halaman <strong><?= sanitize($moduleTitle); ?></strong> sudah terhubung secara dinamis pada routing <code>index.php?page=<?= sanitize($page); ?></code>. Fitur modul ini siap dikoding pada tahap berikutnya.
        </p>
        <a href="<?= BASE_URL; ?>index.php?page=dashboard" class="btn btn-outline-primary mt-2">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

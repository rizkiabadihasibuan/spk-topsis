<?php
/**
 * ====================================================
 * HALAMAN ERROR 404 (PAGE NOT FOUND)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */
?>

<div class="card card-custom p-5 text-center my-4">
    <div class="py-5">
        <h1 class="display-1 fw-bold text-danger mb-0">404</h1>
        <h4 class="fw-bold text-dark mb-2">Halaman Tidak Ditemukan!</h4>
        <p class="text-muted max-w-md mx-auto mb-4" style="max-width: 480px;">
            Maaf, halaman <code>?page=<?= sanitize($_GET['page'] ?? ''); ?></code> yang Anda cari tidak tersedia atau telah dipindahkan.
        </p>
        <a href="<?= BASE_URL; ?>index.php?page=dashboard" class="btn btn-primary px-4 py-2">
            <i class="bi bi-house-door me-2"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

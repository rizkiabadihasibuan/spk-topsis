<?php
/**
 * ====================================================
 * TEMPLATE FOOTER GLOBAL
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */
?>
        <!-- Footer Area -->
        <footer class="footer mt-auto py-3 bg-white border-top text-center text-muted" style="font-size: 0.85rem;">
            <div class="container-fluid">
                <span>&copy; <?= date('Y'); ?> <strong><?= APP_NAME; ?></strong> (Metode <?= APP_METHOD; ?>) - Versi <?= APP_VERSION; ?></span>
            </div>
        </footer>
    </div> <!-- End Page Content Wrapper -->
</div> <!-- End #wrapper -->

<!-- Floating Back to Top Button for Mobile & Desktop -->
<button id="backToTop" class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center" aria-label="Kembali ke Atas" title="Kembali ke atas">
    <i class="bi bi-arrow-up-short fs-3"></i>
</button>

<!-- jQuery 3.7.1 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS & Bootstrap 5 Integration -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Chart.js 4.4 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- KaTeX Math JS & Auto-Render -->
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof renderMathInElement === "function") {
        renderMathInElement(document.body, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false},
                {left: '\\[', right: '\\]', display: true}
            ],
            throwOnError: false
        });
    }
});
</script>

<!-- Custom JS -->
<script src="<?= BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>

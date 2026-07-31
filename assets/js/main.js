/**
 * ====================================================
 * CUSTOM JAVASCRIPT & UTILITIES
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

document.addEventListener("DOMContentLoaded", function () {
    // 1. Sidebar Toggle Listener
    const sidebarToggleBtn = document.getElementById("menu-toggle");
    const sidebarWrapper = document.getElementById("sidebar-wrapper");

    if (sidebarToggleBtn && sidebarWrapper) {
        sidebarToggleBtn.addEventListener("click", function (e) {
            e.preventDefault();
            sidebarWrapper.classList.toggle("toggled");
        });
    }

    // 2. Inisialisasi Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 3. Helper Konfirmasi SweetAlert2 Hapus Data
    window.confirmDelete = function (url, message = "Data yang dihapus tidak dapat dikembalikan!") {
        Swal.fire({
            title: "Apakah Anda Yakin?",
            text: message,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc2626",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    };
});

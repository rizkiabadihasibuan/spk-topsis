/**
 * ====================================================
 * CUSTOM JAVASCRIPT & UTILITIES (MOBILE & INTERACTIVE)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

document.addEventListener("DOMContentLoaded", function () {
    // ----------------------------------------------------
    // 1. MOBILE SIDEBAR DRAWER & OVERLAY BACKDROP MANAGER
    // ----------------------------------------------------
    const sidebarToggleBtn = document.getElementById("menu-toggle");
    const sidebarCloseBtn  = document.getElementById("sidebar-close-btn");
    const sidebarWrapper   = document.getElementById("sidebar-wrapper");
    const sidebarOverlay   = document.getElementById("sidebar-overlay");

    function openSidebar() {
        if (sidebarWrapper) sidebarWrapper.classList.add("toggled");
        if (sidebarOverlay && window.innerWidth < 992) sidebarOverlay.classList.add("show");
    }

    function closeSidebar() {
        if (sidebarWrapper) sidebarWrapper.classList.remove("toggled");
        if (sidebarOverlay) sidebarOverlay.classList.remove("show");
    }

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (sidebarWrapper && sidebarWrapper.classList.contains("toggled")) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener("click", function (e) {
            e.preventDefault();
            closeSidebar();
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", function () {
            closeSidebar();
        });
    }

    // Auto-close sidebar on mobile when pressing Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && sidebarWrapper && sidebarWrapper.classList.contains("toggled")) {
            closeSidebar();
        }
    });

    // Auto-close sidebar when clicking menu links on mobile (< 992px)
    if (sidebarWrapper) {
        const sidebarLinks = sidebarWrapper.querySelectorAll(".nav-link");
        sidebarLinks.forEach(function (link) {
            link.addEventListener("click", function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });
    }

    // Touch Swipe Left to close sidebar on mobile
    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener("touchstart", function (e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    document.addEventListener("touchend", function (e) {
        touchEndX = e.changedTouches[0].screenX;
        // If swiping left while sidebar is open
        if (touchStartX - touchEndX > 70 && sidebarWrapper && sidebarWrapper.classList.contains("toggled")) {
            closeSidebar();
        }
    }, { passive: true });

    // ----------------------------------------------------
    // 2. FLOATING BACK TO TOP BUTTON MANAGER
    // ----------------------------------------------------
    const backToTopBtn = document.getElementById("backToTop");

    if (backToTopBtn) {
        window.addEventListener("scroll", function () {
            if (window.scrollY > 220) {
                backToTopBtn.classList.add("show");
            } else {
                backToTopBtn.classList.remove("show");
            }
        });

        backToTopBtn.addEventListener("click", function () {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }

    // ----------------------------------------------------
    // 3. DATATABLES DEFAULT RESPONSIVE CONFIGURATION
    // ----------------------------------------------------
    if (window.jQuery && $.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            autoWidth: false,
            responsive: true,
            language: {
                search: "Cari Data:",
                searchPlaceholder: "Ketik kata kunci...",
                lengthMenu: "Tampilkan _MENU_ entri",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                zeroRecords: "Tidak ada data yang ditemukan",
                paginate: {
                    first: "Awal",
                    last: "Akhir",
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>'
                }
            }
        });
    }

    // ----------------------------------------------------
    // 4. BOOTSTRAP TOOLTIPS INITIALIZATION
    // ----------------------------------------------------
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ----------------------------------------------------
    // 5. HELPER SWEETALERT2 CONFIRMATION DELETE
    // ----------------------------------------------------
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

    // ----------------------------------------------------
    // 6. HELPER PASSWORD VISIBILITY TOGGLE (FOR MOBILE FORMS)
    // ----------------------------------------------------
    window.togglePasswordVisibility = function (inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input && icon) {
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bi-eye-fill", "bi-eye");
                icon.classList.add("bi-eye-slash-fill");
            } else {
                input.type = "password";
                icon.classList.remove("bi-eye-slash-fill");
                icon.classList.add("bi-eye-fill");
            }
        }
    };
});


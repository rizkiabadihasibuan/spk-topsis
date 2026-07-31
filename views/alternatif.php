<?php
/**
 * ====================================================
 * MODUL CRUD ALTERNATIF JURUSAN (TAHAP 7)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

$swal_success = '';
$swal_error   = '';

// Ambil notifikasi dari session jika ada
if (isset($_SESSION['swal_success'])) {
    $swal_success = $_SESSION['swal_success'];
    unset($_SESSION['swal_success']);
}
if (isset($_SESSION['swal_error'])) {
    $swal_error = $_SESSION['swal_error'];
    unset($_SESSION['swal_error']);
}

// ----------------------------------------------------
// 1. HANDLER PEMROSESAN TAMBAH DATA (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $kode_alternatif = strtoupper(sanitize($_POST['kode_alternatif'] ?? ''));
    $nama_alternatif = sanitize($_POST['nama_alternatif'] ?? '');
    $deskripsi       = sanitize($_POST['deskripsi'] ?? '');

    // Validasi Field Wajib
    if (empty($kode_alternatif)) {
        $swal_error = 'Kode Alternatif tidak boleh kosong!';
    } elseif (empty($nama_alternatif)) {
        $swal_error = 'Nama Alternatif / Jurusan tidak boleh kosong!';
    } else {
        try {
            // Cek Duplikasi Kode Alternatif
            $stmtCek = $db->prepare("SELECT COUNT(*) FROM tb_alternatif WHERE kode_alternatif = :kode");
            $stmtCek->execute([':kode' => $kode_alternatif]);
            if ($stmtCek->fetchColumn() > 0) {
                $swal_error = 'Kode Alternatif "' . $kode_alternatif . '" sudah digunakan. Gunakan kode lain!';
            } else {
                // Simpan Data ke Database
                $stmtInsert = $db->prepare("INSERT INTO tb_alternatif (kode_alternatif, nama_jurusan, deskripsi) VALUES (:kode, :nama, :desk)");
                $stmtInsert->execute([
                    ':kode' => $kode_alternatif,
                    ':nama' => $nama_alternatif,
                    ':desk' => $deskripsi
                ]);

                $_SESSION['swal_success'] = 'Data alternatif "' . $nama_alternatif . '" berhasil ditambahkan!';
                header("Location: " . BASE_URL . "index.php?page=alternatif");
                exit;
            }
        } catch (PDOException $e) {
            $swal_error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// 2. HANDLER PEMROSESAN EDIT DATA (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id_alternatif   = (int)($_POST['id_alternatif'] ?? 0);
    $kode_alternatif = strtoupper(sanitize($_POST['kode_alternatif'] ?? ''));
    $nama_alternatif = sanitize($_POST['nama_alternatif'] ?? '');
    $deskripsi       = sanitize($_POST['deskripsi'] ?? '');

    // Validasi Field Wajib
    if ($id_alternatif <= 0) {
        $swal_error = 'ID Alternatif tidak valid!';
    } elseif (empty($kode_alternatif)) {
        $swal_error = 'Kode Alternatif tidak boleh kosong!';
    } elseif (empty($nama_alternatif)) {
        $swal_error = 'Nama Alternatif / Jurusan tidak boleh kosong!';
    } else {
        try {
            // Cek Duplikasi Kode (Kecuali milik ID yang sedang diedit)
            $stmtCek = $db->prepare("SELECT COUNT(*) FROM tb_alternatif WHERE kode_alternatif = :kode AND id_alternatif != :id");
            $stmtCek->execute([':kode' => $kode_alternatif, ':id' => $id_alternatif]);
            if ($stmtCek->fetchColumn() > 0) {
                $swal_error = 'Kode Alternatif "' . $kode_alternatif . '" sudah digunakan oleh data lain!';
            } else {
                // Pembaruan Data
                $stmtUpdate = $db->prepare("UPDATE tb_alternatif SET kode_alternatif = :kode, nama_jurusan = :nama, deskripsi = :desk WHERE id_alternatif = :id");
                $stmtUpdate->execute([
                    ':kode' => $kode_alternatif,
                    ':nama' => $nama_alternatif,
                    ':desk' => $deskripsi,
                    ':id'   => $id_alternatif
                ]);

                $_SESSION['swal_success'] = 'Data alternatif "' . $nama_alternatif . '" berhasil diperbarui!';
                header("Location: " . BASE_URL . "index.php?page=alternatif");
                exit;
            }
        } catch (PDOException $e) {
            $swal_error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// 3. HANDLER PEMROSESAN HAPUS DATA (GET)
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    if ($id_hapus > 0) {
        try {
            $stmtHapus = $db->prepare("DELETE FROM tb_alternatif WHERE id_alternatif = :id");
            $stmtHapus->execute([':id' => $id_hapus]);

            $_SESSION['swal_success'] = 'Data alternatif berhasil dihapus dari database!';
            header("Location: " . BASE_URL . "index.php?page=alternatif");
            exit;
        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Gagal menghapus data: ' . $e->getMessage();
            header("Location: " . BASE_URL . "index.php?page=alternatif");
            exit;
        }
    }
}

// ----------------------------------------------------
// 4. QUERY MENGAMBIL SELURUH DATA ALTERNATIF
// ----------------------------------------------------
$list_alternatif = [];
if ($db !== null) {
    try {
        $list_alternatif = $db->query("SELECT * FROM tb_alternatif ORDER BY id_alternatif ASC")->fetchAll();
    } catch (PDOException $e) {
        $swal_error = 'Gagal mengambil data dari database: ' . $e->getMessage();
    }
}
?>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-white p-2 rounded-3 border shadow-sm small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>index.php?page=dashboard" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a></li>
        <li class="breadcrumb-item text-muted">Master Data</li>
        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Data Alternatif</li>
    </ol>
</nav>

<!-- Page Header Card -->
<div class="card card-custom p-4 mb-4 shadow-sm">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 fw-bold">
                <i class="bi bi-mortarboard-fill me-1"></i> Data Master Pilihan Program Studi
            </span>
            <h3 class="fw-extrabold text-dark mb-1">
                <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i>Kelola Alternatif Jurusan Kuliah
            </h3>
            <p class="text-muted mb-0 small">
                Daftar program studi perguruan tinggi yang dijadikan opsi rekomendasi bagi siswa SMA dalam analisis TOPSIS.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Tambah Jurusan Baru
            </button>
        </div>
    </div>
</div>

<!-- Main Table Card Container -->
<div class="card card-custom p-4 shadow-sm rounded-3 mb-4">
    <div class="table-responsive">
        <table id="tableAlternatif" class="table table-hover table-striped align-middle border w-100" style="font-size: 0.9rem;">
            <thead class="table-dark" style="background-color: #1e293b;">
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th style="width: 15%;">Kode</th>
                    <th style="width: 30%;">Nama Alternatif / Jurusan</th>
                    <th style="width: 25%;">Deskripsi</th>
                    <th style="width: 15%;">Tanggal Dibuat</th>
                    <th class="text-center" style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list_alternatif)): ?>
                    <?php $no = 1; foreach ($list_alternatif as $alt): ?>
                        <tr>
                            <td class="text-center fw-medium"><?= $no++; ?></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace" style="font-size: 0.85rem;">
                                    <?= sanitize($alt['kode_alternatif']); ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark"><?= sanitize($alt['nama_jurusan']); ?></td>
                            <td class="text-muted small">
                                <?= !empty($alt['deskripsi']) ? sanitize($alt['deskripsi']) : '<span class="fst-italic text-muted">- Tidak ada deskripsi -</span>'; ?>
                            </td>
                            <td class="small text-muted"><?= formatTanggalIndo($alt['created_at']); ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Tombol Detail -->
                                    <button type="button" class="btn btn-info text-white btn-detail" 
                                            data-bs-toggle="modal" data-bs-target="#modalDetail"
                                            data-kode="<?= sanitize($alt['kode_alternatif']); ?>"
                                            data-nama="<?= sanitize($alt['nama_jurusan']); ?>"
                                            data-deskripsi="<?= sanitize($alt['deskripsi']); ?>"
                                            data-created="<?= formatTanggalIndo($alt['created_at']); ?>"
                                            data-updated="<?= formatTanggalIndo($alt['updated_at']); ?>"
                                            title="Detail Informasi">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Tombol Edit -->
                                    <button type="button" class="btn btn-warning text-white btn-edit" 
                                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                                            data-id="<?= $alt['id_alternatif']; ?>"
                                            data-kode="<?= sanitize($alt['kode_alternatif']); ?>"
                                            data-nama="<?= sanitize($alt['nama_jurusan']); ?>"
                                            data-deskripsi="<?= sanitize($alt['deskripsi']); ?>"
                                            title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <button type="button" class="btn btn-danger" 
                                            onclick="confirmHapus(<?= $alt['id_alternatif']; ?>, '<?= sanitize($alt['nama_jurusan']); ?>');"
                                            title="Hapus Data">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ====================================================
     MODAL 1: TAMBAH DATA ALTERNATIF
     ==================================================== -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold" id="modalTambahLabel">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Data Alternatif
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body p-4">
                    <!-- Kode Alternatif -->
                    <div class="mb-3">
                        <label for="tambah_kode" class="form-label fw-medium small text-dark">Kode Alternatif <span class="text-danger">*</span></label>
                        <input type="text" name="kode_alternatif" id="tambah_kode" class="form-control" placeholder="Contoh: A1, A2, A3" required style="text-transform: uppercase;">
                        <small class="text-muted" style="font-size: 0.75rem;">Kode unik alternatif jurusan (contoh: A1, A2, A3).</small>
                    </div>

                    <!-- Nama Alternatif -->
                    <div class="mb-3">
                        <label for="tambah_nama" class="form-label fw-medium small text-dark">Nama Alternatif / Jurusan Kuliah <span class="text-danger">*</span></label>
                        <input type="text" name="nama_alternatif" id="tambah_nama" class="form-control" placeholder="Contoh: Teknik Informatika" required>
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-2">
                        <label for="tambah_deskripsi" class="form-label fw-medium small text-dark">Deskripsi (Opsional)</label>
                        <textarea name="deskripsi" id="tambah_deskripsi" rows="3" class="form-control" placeholder="Tuliskan gambaran umum jurusan ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-medium">
                        <i class="bi bi-save me-1"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====================================================
     MODAL 2: EDIT DATA ALTERNATIF
     ==================================================== -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-white">
                <h6 class="modal-title fw-bold" id="modalEditLabel">
                    <i class="bi bi-pencil-square me-1"></i> Edit Data Alternatif
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_alternatif" id="edit_id">

                <div class="modal-body p-4">
                    <!-- Kode Alternatif -->
                    <div class="mb-3">
                        <label for="edit_kode" class="form-label fw-medium small text-dark">Kode Alternatif <span class="text-danger">*</span></label>
                        <input type="text" name="kode_alternatif" id="edit_kode" class="form-control" required style="text-transform: uppercase;">
                    </div>

                    <!-- Nama Alternatif -->
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label fw-medium small text-dark">Nama Alternatif / Jurusan Kuliah <span class="text-danger">*</span></label>
                        <input type="text" name="nama_alternatif" id="edit_nama" class="form-control" required>
                    </div>

                    <!-- Deskripsi -->
                    <div class="mb-2">
                        <label for="edit_deskripsi" class="form-label fw-medium small text-dark">Deskripsi (Opsional)</label>
                        <textarea name="deskripsi" id="edit_deskripsi" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-white px-4 fw-medium">
                        <i class="bi bi-check-lg me-1"></i> Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====================================================
     MODAL 3: DETAIL DATA ALTERNATIF
     ==================================================== -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title fw-bold" id="modalDetailLabel">
                    <i class="bi bi-info-circle me-1"></i> Detail Informasi Alternatif
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-borderless table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small" style="width: 35%;">Kode Alternatif</td>
                            <td class="fw-bold text-primary small" id="detail_kode">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Nama Jurusan</td>
                            <td class="fw-bold text-dark small" id="detail_nama">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Deskripsi</td>
                            <td class="text-secondary small" id="detail_deskripsi">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tanggal Dibuat</td>
                            <td class="text-muted small" id="detail_created">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Terakhir Diubah</td>
                            <td class="text-muted small" id="detail_updated">: -</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script DataTables & SweetAlert2 Event Listener -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Inisialisasi DataTables
    $('#tableAlternatif').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        responsive: true,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: 5 } // Matikan sorting untuk kolom Aksi
        ]
    });

    // 2. Listener Modal Edit Data
    $(document).on('click', '.btn-edit', function () {
        const id        = $(this).data('id');
        const kode      = $(this).data('kode');
        const nama      = $(this).data('nama');
        const deskripsi = $(this).data('deskripsi');

        $('#edit_id').val(id);
        $('#edit_kode').val(kode);
        $('#edit_nama').val(nama);
        $('#edit_deskripsi').val(deskripsi);
    });

    // 3. Listener Modal Detail Data
    $(document).on('click', '.btn-detail', function () {
        const kode      = $(this).data('kode');
        const nama      = $(this).data('nama');
        const deskripsi = $(this).data('deskripsi');
        const created   = $(this).data('created');
        const updated   = $(this).data('updated');

        $('#detail_kode').text(': ' + kode);
        $('#detail_nama').text(': ' + nama);
        $('#detail_deskripsi').text(': ' + (deskripsi ? deskripsi : '- Tidak ada deskripsi -'));
        $('#detail_created').text(': ' + created);
        $('#detail_updated').text(': ' + updated);
    });
});

// 4. Konfirmasi Hapus Data dengan SweetAlert2
function confirmHapus(id, nama) {
    Swal.fire({
        title: 'Konfirmasi Hapus Data',
        html: `Apakah Anda yakin ingin menghapus alternatif jurusan <strong>"${nama}"</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `<?= BASE_URL; ?>index.php?page=alternatif&action=hapus&id=${id}`;
        }
    });
}
</script>

<!-- SweetAlert2 Trigger Notifications -->
<?php if (!empty($swal_success)): ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= addslashes($swal_success); ?>',
            confirmButtonColor: '#2563eb',
            timer: 2500,
            timerProgressBar: true
        });
    });
    </script>
<?php endif; ?>

<?php if (!empty($swal_error)): ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?= addslashes($swal_error); ?>',
            confirmButtonColor: '#dc2626'
        });
    });
    </script>
<?php endif; ?>

<?php
/**
 * ====================================================
 * MODUL CRUD KRITERIA & BOBOT (TAHAP 8)
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
    $kode_kriteria = strtoupper(trim($_POST['kode_kriteria'] ?? ''));
    $nama_kriteria = html_entity_decode(trim($_POST['nama_kriteria'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $bobot         = trim($_POST['bobot'] ?? '');
    $jenis         = strtolower(trim($_POST['jenis'] ?? 'benefit'));
    $keterangan    = html_entity_decode(trim($_POST['keterangan'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Validasi Field Wajib & Angka Bobot
    if (empty($kode_kriteria)) {
        $swal_error = 'Kode Kriteria tidak boleh kosong!';
    } elseif (empty($nama_kriteria)) {
        $swal_error = 'Nama Kriteria tidak boleh kosong!';
    } elseif ($bobot === '' || !is_numeric($bobot) || (float)$bobot <= 0) {
        $swal_error = 'Bobot Kriteria wajib berupa angka bernilai positif (> 0)!';
    } elseif (!in_array($jenis, ['benefit', 'cost'])) {
        $swal_error = 'Jenis Atribut Kriteria hanya boleh Benefit atau Cost!';
    } else {
        try {
            // Cek Duplikasi Kode Kriteria
            $stmtCek = $db->prepare("SELECT COUNT(*) FROM tb_kriteria WHERE kode_kriteria = :kode");
            $stmtCek->execute([':kode' => $kode_kriteria]);
            if ($stmtCek->fetchColumn() > 0) {
                $swal_error = 'Kode Kriteria "' . $kode_kriteria . '" sudah digunakan. Gunakan kode lain!';
            } else {
                // Simpan Data ke Database
                $stmtInsert = $db->prepare("INSERT INTO tb_kriteria (kode_kriteria, nama_kriteria, bobot, jenis, keterangan) VALUES (:kode, :nama, :bobot, :jenis, :ket)");
                $stmtInsert->execute([
                    ':kode'  => $kode_kriteria,
                    ':nama'  => $nama_kriteria,
                    ':bobot' => (float)$bobot,
                    ':jenis' => $jenis,
                    ':ket'   => $keterangan
                ]);

                $_SESSION['swal_success'] = 'Data kriteria "' . $nama_kriteria . '" berhasil ditambahkan!';
                header("Location: " . BASE_URL . "index.php?page=kriteria");
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
    $id_kriteria   = (int)($_POST['id_kriteria'] ?? 0);
    $kode_kriteria = strtoupper(trim($_POST['kode_kriteria'] ?? ''));
    $nama_kriteria = html_entity_decode(trim($_POST['nama_kriteria'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $bobot         = trim($_POST['bobot'] ?? '');
    $jenis         = strtolower(trim($_POST['jenis'] ?? 'benefit'));
    $keterangan    = html_entity_decode(trim($_POST['keterangan'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Validasi Field Wajib
    if ($id_kriteria <= 0) {
        $swal_error = 'ID Kriteria tidak valid!';
    } elseif (empty($kode_kriteria)) {
        $swal_error = 'Kode Kriteria tidak boleh kosong!';
    } elseif (empty($nama_kriteria)) {
        $swal_error = 'Nama Kriteria tidak boleh kosong!';
    } elseif ($bobot === '' || !is_numeric($bobot) || (float)$bobot <= 0) {
        $swal_error = 'Bobot Kriteria wajib berupa angka bernilai positif (> 0)!';
    } elseif (!in_array($jenis, ['benefit', 'cost'])) {
        $swal_error = 'Jenis Atribut Kriteria hanya boleh Benefit atau Cost!';
    } else {
        try {
            // Cek Duplikasi Kode (Kecuali milik ID yang sedang diedit)
            $stmtCek = $db->prepare("SELECT COUNT(*) FROM tb_kriteria WHERE kode_kriteria = :kode AND id_kriteria != :id");
            $stmtCek->execute([':kode' => $kode_kriteria, ':id' => $id_kriteria]);
            if ($stmtCek->fetchColumn() > 0) {
                $swal_error = 'Kode Kriteria "' . $kode_kriteria . '" sudah digunakan oleh kriteria lain!';
            } else {
                // Pembaruan Data
                $stmtUpdate = $db->prepare("UPDATE tb_kriteria SET kode_kriteria = :kode, nama_kriteria = :nama, bobot = :bobot, jenis = :jenis, keterangan = :ket WHERE id_kriteria = :id");
                $stmtUpdate->execute([
                    ':kode'  => $kode_kriteria,
                    ':nama'  => $nama_kriteria,
                    ':bobot' => (float)$bobot,
                    ':jenis' => $jenis,
                    ':ket'   => $keterangan,
                    ':id'    => $id_kriteria
                ]);

                $_SESSION['swal_success'] = 'Data kriteria "' . $nama_kriteria . '" berhasil diperbarui!';
                header("Location: " . BASE_URL . "index.php?page=kriteria");
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
            $stmtHapus = $db->prepare("DELETE FROM tb_kriteria WHERE id_kriteria = :id");
            $stmtHapus->execute([':id' => $id_hapus]);

            $_SESSION['swal_success'] = 'Data kriteria berhasil dihapus dari database!';
            header("Location: " . BASE_URL . "index.php?page=kriteria");
            exit;
        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Gagal menghapus kriteria: ' . $e->getMessage();
            header("Location: " . BASE_URL . "index.php?page=kriteria");
            exit;
        }
    }
}

// ----------------------------------------------------
// 4. QUERY MENGAMBIL SELURUH DATA & TOTAL BOBOT
// ----------------------------------------------------
$list_kriteria = [];
$total_bobot   = 0.0;

if ($db !== null) {
    try {
        $list_kriteria = $db->query("SELECT * FROM tb_kriteria ORDER BY id_kriteria ASC")->fetchAll();
        $total_bobot   = (float)$db->query("SELECT SUM(bobot) FROM tb_kriteria")->fetchColumn();
    } catch (PDOException $e) {
        $swal_error = 'Gagal mengambil data kriteria: ' . $e->getMessage();
    }
}
?>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-white p-2 rounded-3 border shadow-sm small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>index.php?page=dashboard" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a></li>
        <li class="breadcrumb-item text-muted">Master Data</li>
        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Data Kriteria & Bobot</li>
    </ol>
</nav>

<!-- Page Header Card -->
<div class="card card-custom p-4 mb-3 shadow-sm">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 mb-2 fw-bold">
                <i class="bi bi-sliders me-1"></i> Parameter Evaluasi & Bobot Keputusan
            </span>
            <h3 class="fw-extrabold text-dark mb-1">
                <i class="bi bi-sliders2 me-2 text-primary"></i>Kelola Data Kriteria & Bobot Penilaian
            </h3>
            <p class="text-muted mb-0 small">
                Konfigurasi indikator penilaian (Nilai Rapor, TPA, Psikotes, Biaya UKT, Prospek) beserta pembobotannya untuk analisis TOPSIS.
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary shadow-sm fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kriteria Baru
            </button>
        </div>
    </div>
</div>

<!-- Informative Banner Total Bobot Kriteria -->
<div class="alert <?= (abs($total_bobot - 1.0) < 0.001 || abs($total_bobot - 100.0) < 0.001) ? 'alert-success border-success' : 'alert-warning border-warning'; ?> shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between">
    <div>
        <i class="bi <?= (abs($total_bobot - 1.0) < 0.001 || abs($total_bobot - 100.0) < 0.001) ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning'; ?> fs-5 me-2"></i>
        <strong>Informasi Total Bobot Kriteria Saat Ini:</strong> 
        <span class="badge bg-white text-dark border px-2 py-1 fs-6 ms-1"><?= formatNumber($total_bobot, 4); ?></span>
    </div>
    <small class="text-muted d-none d-md-block">
        *Idealnya total seluruh bobot bernilai <strong>1.00</strong> (atau 100%). Anda tetap dapat mengubahnya kapan saja.
    </small>
</div>

<!-- Main Table Card Container -->
<div class="card card-custom p-4 shadow-sm rounded-3 mb-4">
    <div class="table-responsive">
        <table id="tableKriteria" class="table table-hover table-striped align-middle border w-100" style="font-size: 0.9rem;">
            <thead class="table-dark" style="background-color: #1e293b;">
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th style="width: 10%;">Kode</th>
                    <th style="width: 25%;">Nama Kriteria</th>
                    <th style="width: 15%;">Bobot ($W$)</th>
                    <th style="width: 15%;">Jenis Atribut</th>
                    <th style="width: 20%;">Keterangan</th>
                    <th class="text-center" style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list_kriteria)): ?>
                    <?php $no = 1; foreach ($list_kriteria as $krit): ?>
                        <tr>
                            <td class="text-center fw-medium"><?= $no++; ?></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace" style="font-size: 0.85rem;">
                                    <?= sanitize($krit['kode_kriteria']); ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark"><?= sanitize($krit['nama_kriteria']); ?></td>
                            <td class="fw-bold text-dark font-monospace">
                                <?= formatNumber($krit['bobot'], 4); ?>
                            </td>
                            <td>
                                <?= getBadgeJenis($krit['jenis']); ?>
                            </td>
                            <td class="text-muted small">
                                <?= !empty($krit['keterangan']) ? sanitize($krit['keterangan']) : '<span class="fst-italic text-muted">-</span>'; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Tombol Detail -->
                                    <button type="button" class="btn btn-info text-white btn-detail" 
                                            data-bs-toggle="modal" data-bs-target="#modalDetail"
                                            data-kode="<?= sanitize($krit['kode_kriteria']); ?>"
                                            data-nama="<?= sanitize($krit['nama_kriteria']); ?>"
                                            data-bobot="<?= formatNumber($krit['bobot'], 4); ?>"
                                            data-jenis="<?= ucfirst(sanitize($krit['jenis'])); ?>"
                                            data-keterangan="<?= sanitize($krit['keterangan']); ?>"
                                            data-created="<?= formatTanggalIndo($krit['created_at']); ?>"
                                            data-updated="<?= formatTanggalIndo($krit['updated_at']); ?>"
                                            title="Detail Informasi">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Tombol Edit -->
                                    <button type="button" class="btn btn-warning text-white btn-edit" 
                                            data-bs-toggle="modal" data-bs-target="#modalEdit"
                                            data-id="<?= $krit['id_kriteria']; ?>"
                                            data-kode="<?= sanitize($krit['kode_kriteria']); ?>"
                                            data-nama="<?= sanitize($krit['nama_kriteria']); ?>"
                                            data-bobot="<?= (float)$krit['bobot']; ?>"
                                            data-jenis="<?= sanitize($krit['jenis']); ?>"
                                            data-keterangan="<?= sanitize($krit['keterangan']); ?>"
                                            title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <button type="button" class="btn btn-danger" 
                                            onclick="confirmHapusKriteria(<?= $krit['id_kriteria']; ?>, '<?= sanitize($krit['nama_kriteria']); ?>');"
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
     MODAL 1: TAMBAH DATA KRITERIA
     ==================================================== -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold" id="modalTambahLabel">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Data Kriteria
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body p-4">
                    <!-- Kode Kriteria -->
                    <div class="mb-3">
                        <label for="tambah_kode" class="form-label fw-medium small text-dark">Kode Kriteria <span class="text-danger">*</span></label>
                        <input type="text" name="kode_kriteria" id="tambah_kode" class="form-control" placeholder="Contoh: C1, C2, C3" required style="text-transform: uppercase;">
                        <small class="text-muted" style="font-size: 0.75rem;">Kode unik kriteria (contoh: C1, C2, C3).</small>
                    </div>

                    <!-- Nama Kriteria -->
                    <div class="mb-3">
                        <label for="tambah_nama" class="form-label fw-medium small text-dark">Nama Kriteria <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kriteria" id="tambah_nama" class="form-control" placeholder="Contoh: Nilai Matematika, Minat, Biaya" required>
                    </div>

                    <!-- Bobot Kriteria -->
                    <div class="mb-3">
                        <label for="tambah_bobot" class="form-label fw-medium small text-dark">Bobot Kriteria ($W$) <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="bobot" id="tambah_bobot" class="form-control" placeholder="Contoh: 0.25 atau 25" required>
                        <small class="text-muted" style="font-size: 0.75rem;">Angka desimal atau rasio bobot kepentingan (> 0).</small>
                    </div>

                    <!-- Jenis Atribut -->
                    <div class="mb-3">
                        <label for="tambah_jenis" class="form-label fw-medium small text-dark">Jenis Atribut <span class="text-danger">*</span></label>
                        <select name="jenis" id="tambah_jenis" class="form-select" required>
                            <option value="benefit">Benefit (Semakin tinggi semakin baik)</option>
                            <option value="cost">Cost (Semakin rendah semakin baik)</option>
                        </select>
                    </div>

                    <!-- Keterangan -->
                    <div class="mb-2">
                        <label for="tambah_keterangan" class="form-label fw-medium small text-dark">Keterangan (Opsional)</label>
                        <textarea name="keterangan" id="tambah_keterangan" rows="3" class="form-control" placeholder="Tuliskan catatan tambahan mengenai kriteria ini..."></textarea>
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
     MODAL 2: EDIT DATA KRITERIA
     ==================================================== -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-white">
                <h6 class="modal-title fw-bold" id="modalEditLabel">
                    <i class="bi bi-pencil-square me-1"></i> Edit Data Kriteria
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_kriteria" id="edit_id">

                <div class="modal-body p-4">
                    <!-- Kode Kriteria -->
                    <div class="mb-3">
                        <label for="edit_kode" class="form-label fw-medium small text-dark">Kode Kriteria <span class="text-danger">*</span></label>
                        <input type="text" name="kode_kriteria" id="edit_kode" class="form-control" required style="text-transform: uppercase;">
                    </div>

                    <!-- Nama Kriteria -->
                    <div class="mb-3">
                        <label for="edit_nama" class="form-label fw-medium small text-dark">Nama Kriteria <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kriteria" id="edit_nama" class="form-control" required>
                    </div>

                    <!-- Bobot Kriteria -->
                    <div class="mb-3">
                        <label for="edit_bobot" class="form-label fw-medium small text-dark">Bobot Kriteria ($W$) <span class="text-danger">*</span></label>
                        <input type="number" step="any" name="bobot" id="edit_bobot" class="form-control" required>
                    </div>

                    <!-- Jenis Atribut -->
                    <div class="mb-3">
                        <label for="edit_jenis" class="form-label fw-medium small text-dark">Jenis Atribut <span class="text-danger">*</span></label>
                        <select name="jenis" id="edit_jenis" class="form-select" required>
                            <option value="benefit">Benefit (Semakin tinggi semakin baik)</option>
                            <option value="cost">Cost (Semakin rendah semakin baik)</option>
                        </select>
                    </div>

                    <!-- Keterangan -->
                    <div class="mb-2">
                        <label for="edit_keterangan" class="form-label fw-medium small text-dark">Keterangan (Opsional)</label>
                        <textarea name="keterangan" id="edit_keterangan" rows="3" class="form-control"></textarea>
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
     MODAL 3: DETAIL DATA KRITERIA
     ==================================================== -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title fw-bold" id="modalDetailLabel">
                    <i class="bi bi-info-circle me-1"></i> Detail Informasi Kriteria
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-borderless table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted small" style="width: 35%;">Kode Kriteria</td>
                            <td class="fw-bold text-primary small" id="detail_kode">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Nama Kriteria</td>
                            <td class="fw-bold text-dark small" id="detail_nama">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Bobot ($W$)</td>
                            <td class="fw-bold text-dark font-monospace small" id="detail_bobot">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Jenis Atribut</td>
                            <td class="small" id="detail_jenis">: -</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Keterangan</td>
                            <td class="text-secondary small" id="detail_keterangan">: -</td>
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
    $('#tableKriteria').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        responsive: true,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: 6 } // Matikan sorting untuk kolom Aksi
        ]
    });

    // 2. Listener Modal Edit Data
    $(document).on('click', '.btn-edit', function () {
        const id         = $(this).data('id');
        const kode       = $(this).data('kode');
        const nama       = $(this).data('nama');
        const bobot      = $(this).data('bobot');
        const jenis      = $(this).data('jenis');
        const keterangan = $(this).data('keterangan');

        $('#edit_id').val(id);
        $('#edit_kode').val(kode);
        $('#edit_nama').val(nama);
        $('#edit_bobot').val(bobot);
        $('#edit_jenis').val(jenis);
        $('#edit_keterangan').val(keterangan);
    });

    // 3. Listener Modal Detail Data
    $(document).on('click', '.btn-detail', function () {
        const kode       = $(this).data('kode');
        const nama       = $(this).data('nama');
        const bobot      = $(this).data('bobot');
        const jenis      = $(this).data('jenis');
        const keterangan = $(this).data('keterangan');
        const created    = $(this).data('created');
        const updated    = $(this).data('updated');

        $('#detail_kode').text(': ' + kode);
        $('#detail_nama').text(': ' + nama);
        $('#detail_bobot').text(': ' + bobot);
        $('#detail_jenis').text(': ' + jenis);
        $('#detail_keterangan').text(': ' + (keterangan ? keterangan : '- Tidak ada keterangan -'));
        $('#detail_created').text(': ' + created);
        $('#detail_updated').text(': ' + updated);
    });
});

// 4. Konfirmasi Hapus Data dengan SweetAlert2
function confirmHapusKriteria(id, nama) {
    Swal.fire({
        title: 'Konfirmasi Hapus Data',
        html: `Apakah Anda yakin ingin menghapus kriteria <strong>"${nama}"</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `<?= BASE_URL; ?>index.php?page=kriteria&action=hapus&id=${id}`;
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

<?php
/**
 * ====================================================
 * MODUL CRUD PENILAIAN MATRIKS KEPUTUSAN TOPSIS (TAHAP 9)
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

$current_user_id = $_SESSION['user_id'] ?? 1;

// ----------------------------------------------------
// 1. QUERY MASTER DATA KRITERIA & ALTERNATIF
// ----------------------------------------------------
$list_kriteria   = [];
$list_alternatif = [];
$total_kriteria  = 0;

if ($db !== null) {
    try {
        $list_kriteria   = $db->query("SELECT * FROM tb_kriteria ORDER BY id_kriteria ASC")->fetchAll();
        $list_alternatif = $db->query("SELECT * FROM tb_alternatif ORDER BY id_alternatif ASC")->fetchAll();
        $total_kriteria  = count($list_kriteria);
    } catch (PDOException $e) {
        $swal_error = 'Gagal mengambil data master: ' . $e->getMessage();
    }
}

// ----------------------------------------------------
// 2. HANDLER PEMROSESAN SIMPAN / EDIT PENILAIAN (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan') {
    $id_alternatif = (int)($_POST['id_alternatif'] ?? 0);
    $nilai_input   = $_POST['nilai'] ?? []; // Array [id_kriteria => nilai]

    if ($id_alternatif <= 0) {
        $swal_error = 'Silakan pilih Alternatif Jurusan terlebih dahulu!';
    } elseif ($total_kriteria === 0) {
        $swal_error = 'Belum ada data Kriteria pada database. Tambahkan kriteria terlebih dahulu!';
    } else {
        $valid = true;
        $error_detail = '';

        // Validasi Seluruh Input Kriteria
        foreach ($list_kriteria as $krit) {
            $id_k = $krit['id_kriteria'];
            $val  = $nilai_input[$id_k] ?? '';

            if ($val === '' || !is_numeric($val) || (float)$val < 0) {
                $valid = false;
                $error_detail = 'Nilai untuk kriteria "' . $krit['nama_kriteria'] . '" wajib diisi angka non-negatif (>= 0)!';
                break;
            }
        }

        if (!$valid) {
            $swal_error = $error_detail;
        } else {
            try {
                // Upsert Nilai ke tb_penilaian Menggunakan PDO Prepared Statement
                $stmtUpsert = $db->prepare("
                    INSERT INTO tb_penilaian (id_user, id_alternatif, id_kriteria, nilai) 
                    VALUES (:user, :alt, :krit, :nilai)
                    ON DUPLICATE KEY UPDATE nilai = :nilai_up, updated_at = CURRENT_TIMESTAMP
                ");

                foreach ($list_kriteria as $krit) {
                    $id_k = $krit['id_kriteria'];
                    $val  = (float)$nilai_input[$id_k];

                    $stmtUpsert->execute([
                        ':user'     => $current_user_id,
                        ':alt'      => $id_alternatif,
                        ':krit'     => $id_k,
                        ':nilai'    => $val,
                        ':nilai_up' => $val
                    ]);
                }

                $_SESSION['swal_success'] = 'Matriks penilaian alternatif berhasil disimpan!';
                redirect(BASE_URL . "index.php?page=penilaian");
            } catch (PDOException $e) {
                $swal_error = 'Terjadi kesalahan sistem database: ' . $e->getMessage();
            }
        }
    }
}

// ----------------------------------------------------
// 3. HANDLER PEMROSESAN HAPUS PENILAIAN (GET)
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus_alt = (int)$_GET['id'];
    if ($id_hapus_alt > 0) {
        try {
            $stmtHapus = $db->prepare("DELETE FROM tb_penilaian WHERE id_alternatif = :id");
            $stmtHapus->execute([':id' => $id_hapus_alt]);

            $_SESSION['swal_success'] = 'Seluruh penilaian alternatif tersebut berhasil dihapus!';
            redirect(BASE_URL . "index.php?page=penilaian");
        } catch (PDOException $e) {
            $_SESSION['swal_error'] = 'Gagal menghapus penilaian: ' . $e->getMessage();
            redirect(BASE_URL . "index.php?page=penilaian");
        }
    }
}

// ----------------------------------------------------
// 4. QUERY MENGAMBIL DATA MATRIKS PENILAIAN TERISI
// ----------------------------------------------------
$summary_penilaian = [];
$matrix_nilai      = []; // Array [id_alternatif][id_kriteria] => nilai

if ($db !== null && $total_kriteria > 0) {
    try {
        // Query Ringkasan Per Alternatif
        $querySummary = "
            SELECT a.id_alternatif, a.kode_alternatif, a.nama_jurusan, 
                   COUNT(p.id_penilaian) AS total_dinilai, 
                   MAX(p.updated_at) AS last_update
            FROM tb_alternatif a
            LEFT JOIN tb_penilaian p ON a.id_alternatif = p.id_alternatif
            GROUP BY a.id_alternatif
            ORDER BY a.id_alternatif ASC
        ";
        $summary_penilaian = $db->query($querySummary)->fetchAll();

        // Query Seluruh Nilai Matriks Keputusan X
        $raw_nilai = $db->query("SELECT id_alternatif, id_kriteria, nilai FROM tb_penilaian")->fetchAll();
        foreach ($raw_nilai as $row) {
            $matrix_nilai[$row['id_alternatif']][$row['id_kriteria']] = (float)$row['nilai'];
        }
    } catch (PDOException $e) {
        $swal_error = 'Gagal memuat matriks penilaian: ' . $e->getMessage();
    }
}
?>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-white p-2 rounded-3 border shadow-sm small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>index.php?page=dashboard" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a></li>
        <li class="breadcrumb-item text-muted">Matriks Keputusan</li>
        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Input Penilaian</li>
    </ol>
</nav>

<!-- Page Header Card -->
<div class="card card-custom p-4 mb-4 shadow-sm">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-warning-subtle text-dark border border-warning px-3 py-1 mb-2 fw-bold">
                <i class="bi bi-pencil-fill me-1"></i> Evaluasi Matriks Keputusan $X$
            </span>
            <h3 class="fw-extrabold text-dark mb-1">
                <i class="bi bi-pencil-square me-2 text-primary"></i>Kelola Penilaian Matriks Evaluasi Siswa
            </h3>
            <p class="text-muted mb-0 small">
                Pengisian dan pembaharuan nilai evaluasi kuantitatif untuk setiap pilihan jurusan terhadap <strong><?= $total_kriteria; ?> Kriteria Aktif</strong>.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary shadow-sm fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalInput" id="btnBukaInputModal">
                <i class="bi bi-plus-lg me-1"></i> Input / Edit Penilaian Matriks
            </button>
        </div>
    </div>
</div>

<!-- Notice Jika Kriteria atau Alternatif Masih Kosong -->
<?php if (empty($list_kriteria) || empty($list_alternatif)): ?>
    <div class="alert alert-warning border-warning shadow-sm rounded-3 mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <strong>Perhatian:</strong>
        <?php if (empty($list_alternatif)): ?>
            Data Alternatif masih kosong. Silakan isi data alternatif pada menu <a href="<?= BASE_URL; ?>index.php?page=alternatif" class="alert-link">Data Alternatif</a> terlebih dahulu.
        <?php elseif (empty($list_kriteria)): ?>
            Data Kriteria masih kosong. Silakan isi data kriteria pada menu <a href="<?= BASE_URL; ?>index.php?page=kriteria" class="alert-link">Data Kriteria</a> terlebih dahulu.
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Main Table Card Container (Overview Matriks) -->
<div class="card card-custom p-4 shadow-sm rounded-3 mb-4">
    <div class="table-responsive">
        <table id="tablePenilaian" class="table table-hover table-striped align-middle border w-100" style="font-size: 0.9rem;">
            <thead class="table-dark" style="background-color: #1e293b;">
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th style="width: 15%;">Kode</th>
                    <th style="width: 35%;">Nama Alternatif / Jurusan</th>
                    <th style="width: 20%;">Status Kriteria Dinilai</th>
                    <th style="width: 15%;">Terakhir Diperbarui</th>
                    <th class="text-center" style="width: 10%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($summary_penilaian)): ?>
                    <?php $no = 1; foreach ($summary_penilaian as $row): 
                        $id_alt       = $row['id_alternatif'];
                        $total_dinilai= (int)$row['total_dinilai'];
                        $is_complete  = ($total_dinilai >= $total_kriteria && $total_kriteria > 0);
                        $scores_json  = json_encode($matrix_nilai[$id_alt] ?? (object)[]);
                    ?>
                        <tr>
                            <td class="text-center fw-medium"><?= $no++; ?></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 font-monospace" style="font-size: 0.85rem;">
                                    <?= sanitize($row['kode_alternatif']); ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark"><?= sanitize($row['nama_jurusan']); ?></td>
                            <td>
                                <?php if ($is_complete): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i><?= $total_dinilai; ?> / <?= $total_kriteria; ?> Kriteria Terisi
                                    </span>
                                <?php elseif ($total_dinilai > 0): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-clock-history me-1"></i><?= $total_dinilai; ?> / <?= $total_kriteria; ?> Kriteria (Parsial)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        <i class="bi bi-dash-circle me-1"></i>Belum Dinilai
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?= !empty($row['last_update']) ? formatTanggalIndo($row['last_update']) : '<span class="fst-italic text-muted">-</span>'; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Tombol Detail -->
                                    <button type="button" class="btn btn-info text-white btn-detail-penilaian" 
                                            data-bs-toggle="modal" data-bs-target="#modalDetail"
                                            data-id="<?= $id_alt; ?>"
                                            data-kode="<?= sanitize($row['kode_alternatif']); ?>"
                                            data-nama="<?= sanitize($row['nama_jurusan']); ?>"
                                            data-scores='<?= htmlspecialchars($scores_json, ENT_QUOTES, 'UTF-8'); ?>'
                                            title="Detail Matriks Penilaian">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Tombol Edit -->
                                    <button type="button" class="btn btn-warning text-white btn-edit-penilaian" 
                                            data-bs-toggle="modal" data-bs-target="#modalInput"
                                            data-id="<?= $id_alt; ?>"
                                            data-scores='<?= htmlspecialchars($scores_json, ENT_QUOTES, 'UTF-8'); ?>'
                                            title="Edit Penilaian">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <?php if ($total_dinilai > 0): ?>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="confirmHapusPenilaian(<?= $id_alt; ?>, '<?= sanitize($row['nama_jurusan']); ?>');"
                                                title="Hapus Penilaian">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline-secondary disabled" title="Belum ada data"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
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
     MODAL 1: INPUT / EDIT PENILAIAN (DINAMIS KRITERIA)
     ==================================================== -->
<div class="modal fade" id="modalInput" tabindex="-1" aria-labelledby="modalInputLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold" id="modalInputLabel">
                    <i class="bi bi-pencil-square me-1"></i> Form Input Matriks Penilaian Alternatif
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="action" value="simpan">
                
                <div class="modal-body p-4">
                    <!-- Dropdown Pilih Alternatif Jurusan -->
                    <div class="mb-4">
                        <label for="select_alternatif" class="form-label fw-bold text-dark">
                            Pilih Alternatif Jurusan Kuliah <span class="text-danger">*</span>
                        </label>
                        <select name="id_alternatif" id="select_alternatif" class="form-select form-select-lg" required>
                            <option value="">-- Pilih Jurusan Kuliah --</option>
                            <?php foreach ($list_alternatif as $alt): ?>
                                <option value="<?= $alt['id_alternatif']; ?>">
                                    [<?= sanitize($alt['kode_alternatif']); ?>] <?= sanitize($alt['nama_jurusan']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr class="my-3">

                    <!-- Form Dinamis Kriteria (Render Seluruh Kriteria Aktif dari DB) -->
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-sliders text-primary me-2"></i>Nilai Evaluasi Kriteria
                    </h6>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 15%;">Kode</th>
                                    <th style="width: 45%;">Nama Kriteria</th>
                                    <th style="width: 15%;">Jenis</th>
                                    <th style="width: 25%;">Nilai Evaluasi <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($list_kriteria)): ?>
                                    <?php foreach ($list_kriteria as $krit): $id_k = $krit['id_kriteria']; ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                                    <?= sanitize($krit['kode_kriteria']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-medium text-dark">
                                                <?= sanitize($krit['nama_kriteria']); ?>
                                                <small class="d-block text-muted" style="font-size: 0.75rem;">Bobot: <?= formatNumber($krit['bobot'], 4); ?></small>
                                            </td>
                                            <td><?= getBadgeJenis($krit['jenis']); ?></td>
                                            <td>
                                                <input type="number" step="any" min="0" max="100" 
                                                       name="nilai[<?= $id_k; ?>]" 
                                                       id="input_nilai_<?= $id_k; ?>" 
                                                       class="form-control input-score-field" 
                                                       placeholder="Contoh: 85" required>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data kriteria di database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-medium">
                        <i class="bi bi-save me-1"></i> Simpan Matriks Penilaian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====================================================
     MODAL 2: DETAIL MATRIKS PENILAIAN ALTERNATIF
     ==================================================== -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title fw-bold" id="modalDetailLabel">
                    <i class="bi bi-info-circle me-1"></i> Detail Matriks Penilaian Alternatif
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <span class="text-muted small d-block">Alternatif Jurusan:</span>
                    <h5 class="fw-bold text-dark mb-0" id="detail_nama_alt">-</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-dark" style="background-color: #1e293b;">
                            <tr>
                                <th style="width: 15%;">Kode</th>
                                <th style="width: 45%;">Nama Kriteria</th>
                                <th style="width: 20%;">Jenis Atribut</th>
                                <th class="text-end" style="width: 20%;">Nilai ($X$)</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetailPenilaian">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript DataTables & Dynamic Form Populator -->
<script>
const activeKriteriaList = <?= json_encode($list_kriteria); ?>;

document.addEventListener("DOMContentLoaded", function () {
    // 1. Inisialisasi DataTables
    $('#tablePenilaian').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        },
        responsive: true,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: 5 }
        ]
    });

    // Reset Form Input saat tombol "+ Input / Edit Penilaian" diklik
    $('#btnBukaInputModal').on('click', function() {
        $('#select_alternatif').val('').prop('disabled', false);
        $('.input-score-field').val('');
    });

    // 2. Listener Edit Penilaian
    $(document).on('click', '.btn-edit-penilaian', function () {
        const idAlt  = $(this).data('id');
        const scores = $(this).data('scores') || {};

        $('#select_alternatif').val(idAlt).prop('disabled', false);

        // Isi nilai masing-masing kriteria secara dinamis
        activeKriteriaList.forEach(function(krit) {
            const idK = krit.id_kriteria;
            const val = (scores[idK] !== undefined) ? scores[idK] : '';
            $('#input_nilai_' + idK).val(val);
        });
    });

    // 3. Listener Detail Penilaian
    $(document).on('click', '.btn-detail-penilaian', function () {
        const kodeAlt = $(this).data('kode');
        const namaAlt = $(this).data('nama');
        const scores  = $(this).data('scores') || {};

        $('#detail_nama_alt').html(`[<span class="font-monospace text-primary">${kodeAlt}</span>] ${namaAlt}`);

        let rowsHtml = '';
        if (activeKriteriaList.length > 0) {
            activeKriteriaList.forEach(function(krit) {
                const idK = krit.id_kriteria;
                const val = (scores[idK] !== undefined) ? scores[idK] : null;
                
                let valDisplay = (val !== null) 
                    ? `<span class="fw-bold font-monospace text-dark">${val}</span>` 
                    : `<span class="fst-italic text-muted">Belum diisi</span>`;

                let badgeJenis = (krit.jenis.toLowerCase() === 'benefit')
                    ? `<span class="badge bg-success-subtle text-success border border-success-subtle">Benefit</span>`
                    : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cost</span>`;

                rowsHtml += `
                    <tr>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">${krit.kode_kriteria}</span></td>
                        <td class="fw-medium">${krit.nama_kriteria}</td>
                        <td>${badgeJenis}</td>
                        <td class="text-end">${valDisplay}</td>
                    </tr>
                `;
            });
        } else {
            rowsHtml = `<tr><td colspan="4" class="text-center text-muted">Tidak ada kriteria.</td></tr>`;
        }

        $('#tbodyDetailPenilaian').html(rowsHtml);
    });
});

// 4. Konfirmasi Hapus Seluruh Penilaian Alternatif
function confirmHapusPenilaian(idAlt, namaAlt) {
    Swal.fire({
        title: 'Konfirmasi Hapus Penilaian',
        html: `Apakah Anda yakin ingin menghapus seluruh matriks nilai untuk jurusan <strong>"${namaAlt}"</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Penilaian!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `<?= BASE_URL; ?>index.php?page=penilaian&action=hapus&id=${idAlt}`;
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

<?php
/**
 * ====================================================
 * MODUL IMPORT EXCEL & CSV (TAHAP 10)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

require_once __DIR__ . '/../config/excel_helper.php';

// Handle Download Template File
if (isset($_GET['download'])) {
    $type = sanitize($_GET['download']);
    if (in_array($type, ['alternatif', 'kriteria', 'penilaian'])) {
        downloadTemplateExcel($type);
    }
}

$swal_success = '';
$swal_error   = '';

// Ambil notifikasi dari session
if (isset($_SESSION['swal_success'])) {
    $swal_success = $_SESSION['swal_success'];
    unset($_SESSION['swal_success']);
}
if (isset($_SESSION['swal_error'])) {
    $swal_error = $_SESSION['swal_error'];
    unset($_SESSION['swal_error']);
}

$active_tab   = sanitize($_GET['tab'] ?? 'alternatif');
$preview_data = [];
$total_valid  = 0;
$total_invalid= 0;
$is_preview   = false;

// ----------------------------------------------------
// 1. HANDLER UNGGAH FILE & PREVIEW DATA (POST action=preview)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'preview') {
    $import_type = sanitize($_POST['import_type'] ?? 'alternatif');
    $active_tab  = $import_type;

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $swal_error = 'Silakan pilih file Excel / CSV terlebih dahulu!';
    } else {
        $file = $_FILES['excel_file'];
        $fileName = $file['name'];
        $fileTmp  = $file['tmp_name'];
        $fileSize = $file['size'];
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // 1. Validasi Ekstensi & Ukuran File
        $allowed_ext = ['xlsx', 'xls', 'csv'];
        if (!in_array($ext, $allowed_ext)) {
            $swal_error = 'Format file tidak diperbolehkan! Hanya file .xlsx, .xls, atau .csv.';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $swal_error = 'Ukuran file terlalu besar! Maksimal 5 MB.';
        } else {
            // 2. Parsel Baris File
            $rows = readCsvOrExcelData($fileTmp);

            if (count($rows) <= 1) {
                $swal_error = 'File kosong atau hanya berisi baris header!';
            } else {
                $is_preview = true;
                $header     = array_shift($rows); // Hapus baris header
                $used_codes = [];

                // Pre-fetch Data dari Database untuk Validasi Referensi
                $db_codes_alt = [];
                $db_codes_krit= [];
                if ($db !== null) {
                    $db_codes_alt = $db->query("SELECT kode_alternatif, id_alternatif FROM tb_alternatif")->fetchAll(PDO::FETCH_KEY_PAIR);
                    $db_codes_krit= $db->query("SELECT kode_kriteria, id_kriteria FROM tb_kriteria")->fetchAll(PDO::FETCH_KEY_PAIR);
                }

                // Loop Validasi Setiap Baris Data
                foreach ($rows as $index => $row) {
                    $row_no = $index + 2; // Baris Excel (Header = 1)
                    $is_row_valid = true;
                    $reasons = [];

                    if ($import_type === 'alternatif') {
                        $kode = strtoupper(trim($row[0] ?? ''));
                        $nama = trim($row[1] ?? '');
                        $desk = trim($row[2] ?? '');

                        if (empty($kode)) {
                            $is_row_valid = false;
                            $reasons[] = 'Kode Alternatif tidak boleh kosong';
                        }
                        if (empty($nama)) {
                            $is_row_valid = false;
                            $reasons[] = 'Nama Alternatif tidak boleh kosong';
                        }
                        if (!empty($kode)) {
                            if (in_array($kode, $used_codes)) {
                                $is_row_valid = false;
                                $reasons[] = 'Kode "' . $kode . '" duplikat di dalam file';
                            } elseif (array_key_exists($kode, $db_codes_alt)) {
                                $is_row_valid = false;
                                $reasons[] = 'Kode "' . $kode . '" sudah ada di database';
                            } else {
                                $used_codes[] = $kode;
                            }
                        }

                        $preview_data[] = [
                            'no'     => $row_no,
                            'col1'   => $kode,
                            'col2'   => $nama,
                            'col3'   => $desk,
                            'valid'  => $is_row_valid,
                            'reason' => implode(', ', $reasons)
                        ];

                    } elseif ($import_type === 'kriteria') {
                        $kode  = strtoupper(trim($row[0] ?? ''));
                        $nama  = trim($row[1] ?? '');
                        $bobot = trim($row[2] ?? '');
                        $jenis = strtolower(trim($row[3] ?? ''));
                        $ket   = trim($row[4] ?? '');

                        if (empty($kode)) {
                            $is_row_valid = false;
                            $reasons[] = 'Kode Kriteria tidak boleh kosong';
                        }
                        if (empty($nama)) {
                            $is_row_valid = false;
                            $reasons[] = 'Nama Kriteria tidak boleh kosong';
                        }
                        if ($bobot === '' || !is_numeric($bobot) || (float)$bobot <= 0) {
                            $is_row_valid = false;
                            $reasons[] = 'Bobot harus berupa angka positif (> 0)';
                        }
                        if (!in_array($jenis, ['benefit', 'cost'])) {
                            $is_row_valid = false;
                            $reasons[] = 'Jenis harus "benefit" atau "cost"';
                        }
                        if (!empty($kode)) {
                            if (in_array($kode, $used_codes)) {
                                $is_row_valid = false;
                                $reasons[] = 'Kode "' . $kode . '" duplikat di dalam file';
                            } elseif (array_key_exists($kode, $db_codes_krit)) {
                                $is_row_valid = false;
                                $reasons[] = 'Kode "' . $kode . '" sudah ada di database';
                            } else {
                                $used_codes[] = $kode;
                            }
                        }

                        $preview_data[] = [
                            'no'     => $row_no,
                            'col1'   => $kode,
                            'col2'   => $nama,
                            'col3'   => $bobot,
                            'col4'   => $jenis,
                            'col5'   => $ket,
                            'valid'  => $is_row_valid,
                            'reason' => implode(', ', $reasons)
                        ];

                    } elseif ($import_type === 'penilaian') {
                        $kode_alt  = strtoupper(trim($row[0] ?? ''));
                        $kode_krit = strtoupper(trim($row[1] ?? ''));
                        $nilai     = trim($row[2] ?? '');

                        if (!array_key_exists($kode_alt, $db_codes_alt)) {
                            $is_row_valid = false;
                            $reasons[] = 'Kode Alternatif "' . $kode_alt . '" tidak ditemukan di DB';
                        }
                        if (!array_key_exists($kode_krit, $db_codes_krit)) {
                            $is_row_valid = false;
                            $reasons[] = 'Kode Kriteria "' . $kode_krit . '" tidak ditemukan di DB';
                        }
                        if ($nilai === '' || !is_numeric($nilai) || (float)$nilai < 0) {
                            $is_row_valid = false;
                            $reasons[] = 'Nilai harus angka non-negatif (>= 0)';
                        }

                        $preview_data[] = [
                            'no'     => $row_no,
                            'col1'   => $kode_alt,
                            'col2'   => $kode_krit,
                            'col3'   => $nilai,
                            'valid'  => $is_row_valid,
                            'reason' => implode(', ', $reasons)
                        ];
                    }

                    if ($is_row_valid) {
                        $total_valid++;
                    } else {
                        $total_invalid++;
                    }
                }

                // Simpan preview ke session sementara untuk eksekusi commit
                $_SESSION['import_preview'] = [
                    'type'  => $import_type,
                    'data'  => $preview_data,
                    'valid' => ($total_invalid === 0)
                ];
            }
        }
    }
}

// ----------------------------------------------------
// 2. HANDLER EKSEKUSI COMMIT KE DATABASE (POST action=commit)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'commit') {
    if (!isset($_SESSION['import_preview']) || empty($_SESSION['import_preview']['data'])) {
        $swal_error = 'Tidak ada data preview yang siap di-import!';
    } elseif (!$_SESSION['import_preview']['valid']) {
        $swal_error = 'Import dibatalkan karena terdapat baris data yang INVALID!';
    } else {
        $import_info = $_SESSION['import_preview'];
        $import_type = $import_info['type'];
        $rows_data   = $import_info['data'];
        $user_id     = $_SESSION['user_id'] ?? 1;

        if ($db !== null) {
            try {
                // MULAI TRANSAKSI DATABASE (TRANSACTION ROLLBACK PROTECTION)
                $db->beginTransaction();

                $inserted_count = 0;

                if ($import_type === 'alternatif') {
                    $stmt = $db->prepare("INSERT INTO tb_alternatif (kode_alternatif, nama_jurusan, deskripsi) VALUES (?, ?, ?)");
                    foreach ($rows_data as $r) {
                        $stmt->execute([$r['col1'], $r['col2'], $r['col3']]);
                        $inserted_count++;
                    }
                } elseif ($import_type === 'kriteria') {
                    $stmt = $db->prepare("INSERT INTO tb_kriteria (kode_kriteria, nama_kriteria, bobot, jenis, keterangan) VALUES (?, ?, ?, ?, ?)");
                    foreach ($rows_data as $r) {
                        $stmt->execute([$r['col1'], $r['col2'], (float)$r['col3'], strtolower($r['col4']), $r['col5']]);
                        $inserted_count++;
                    }
                } elseif ($import_type === 'penilaian') {
                    // Pre-fetch Map ID
                    $map_alt  = $db->query("SELECT kode_alternatif, id_alternatif FROM tb_alternatif")->fetchAll(PDO::FETCH_KEY_PAIR);
                    $map_krit = $db->query("SELECT kode_kriteria, id_kriteria FROM tb_kriteria")->fetchAll(PDO::FETCH_KEY_PAIR);

                    $stmt = $db->prepare("
                        INSERT INTO tb_penilaian (id_user, id_alternatif, id_kriteria, nilai)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), updated_at = CURRENT_TIMESTAMP
                    ");

                    foreach ($rows_data as $r) {
                        $id_alt  = $map_alt[$r['col1']] ?? 0;
                        $id_krit = $map_krit[$r['col2']] ?? 0;
                        $val     = (float)$r['col3'];

                        if ($id_alt > 0 && $id_krit > 0) {
                            $stmt->execute([$user_id, $id_alt, $id_krit, $val]);
                            $inserted_count++;
                        }
                    }
                }

                // COMMIT TRANSAKSI
                $db->commit();
                unset($_SESSION['import_preview']);

                $_SESSION['swal_success'] = 'Berhasil mengimpor ' . $inserted_count . ' data ' . ucfirst($import_type) . ' ke database!';
                header("Location: " . BASE_URL . "index.php?page=import-excel&tab=" . $import_type);
                exit;

            } catch (PDOException $e) {
                // ROLLBACK TRANSAKSI JIKA ADA GAGAL
                $db->rollBack();
                $swal_error = 'Gagal mengimpor data (Rollback): ' . $e->getMessage();
            }
        }
    }
}
?>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb bg-white p-2 rounded-3 border shadow-sm small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>index.php?page=dashboard" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a></li>
        <li class="breadcrumb-item text-muted">Utilitas</li>
        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Import Data Excel</li>
    </ol>
</nav>

<!-- Page Header Card -->
<div class="card card-custom p-4 mb-4 shadow-sm rounded-3">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-file-earmark-excel-fill me-2 text-success"></i>Import Data Massal (Excel / CSV)
            </h4>
            <p class="text-muted mb-0 small">
                Unggah file Excel atau CSV untuk mengimpor data Alternatif, Kriteria, maupun Matriks Penilaian secara massal dengan validasi transaksi database.
            </p>
        </div>
    </div>
</div>

<!-- Nav Tabs 3 Jenis Import -->
<ul class="nav nav-pills mb-4 gap-2" id="importTab" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= ($active_tab === 'alternatif') ? 'active fw-semibold' : 'bg-white border text-dark'; ?>" href="<?= BASE_URL; ?>index.php?page=import-excel&tab=alternatif">
            <i class="bi bi-journal-bookmark me-1"></i> 1. Import Alternatif
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= ($active_tab === 'kriteria') ? 'active fw-semibold' : 'bg-white border text-dark'; ?>" href="<?= BASE_URL; ?>index.php?page=import-excel&tab=kriteria">
            <i class="bi bi-sliders me-1"></i> 2. Import Kriteria
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= ($active_tab === 'penilaian') ? 'active fw-semibold' : 'bg-white border text-dark'; ?>" href="<?= BASE_URL; ?>index.php?page=import-excel&tab=penilaian">
            <i class="bi bi-pencil-square me-1"></i> 3. Import Penilaian
        </a>
    </li>
</ul>

<!-- Card Area Upload & Action -->
<div class="card card-custom p-4 shadow-sm rounded-3 mb-4">
    <div class="row align-items-center g-4">
        <!-- Kolom Kiri: Petunjuk & Download Template -->
        <div class="col-lg-6">
            <h6 class="fw-bold text-dark mb-2">
                <i class="bi bi-download me-2 text-primary"></i>Langkah 1: Download Template File
            </h6>
            <p class="text-muted small mb-3">
                Unduh template CSV/Excel resmi untuk jenis <strong>Import <?= ucfirst($active_tab); ?></strong> agar format kolom sesuai dengan database.
            </p>

            <a href="<?= BASE_URL; ?>index.php?page=import-excel&download=<?= $active_tab; ?>" class="btn btn-outline-success shadow-sm fw-medium px-3 py-2">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template <?= ucfirst($active_tab); ?> (.CSV / Excel)
            </a>
        </div>

        <!-- Kolom Kanan: Upload Form Zone -->
        <div class="col-lg-6">
            <h6 class="fw-bold text-dark mb-2">
                <i class="bi bi-upload me-2 text-primary"></i>Langkah 2: Unggah File (.xlsx / .xls / .csv)
            </h6>
            
            <form action="<?= BASE_URL; ?>index.php?page=import-excel&tab=<?= $active_tab; ?>" method="POST" enctype="multipart/form-data" autocomplete="off" id="formUploadPreview">
                <input type="hidden" name="action" value="preview">
                <input type="hidden" name="import_type" value="<?= $active_tab; ?>">

                <div class="input-group mb-2">
                    <input type="file" name="excel_file" id="excel_file" class="form-control" accept=".xlsx, .xls, .csv" required>
                    <button type="submit" class="btn btn-primary fw-semibold px-3">
                        <i class="bi bi-search me-1"></i> Upload & Preview
                    </button>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Maksimal ukuran file: 5 MB. Format didukung: .xlsx, .xls, .csv</small>
            </form>
        </div>
    </div>
</div>

<!-- ====================================================
     PREVIEW TABLE DATA HASIL UPLOAD
     ==================================================== -->
<?php if ($is_preview && !empty($preview_data)): ?>
    <div class="card card-custom p-4 shadow-sm rounded-3 mb-4 border-primary">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3 gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="bi bi-eye-fill me-2 text-primary"></i>Preview Data Hasil Pengunggahan File
                </h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 me-1">
                    Valid: <?= $total_valid; ?> Baris
                </span>
                <?php if ($total_invalid > 0): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                        Invalid: <?= $total_invalid; ?> Baris
                    </span>
                <?php endif; ?>
            </div>

            <div>
                <?php if ($total_invalid === 0): ?>
                    <form action="<?= BASE_URL; ?>index.php?page=import-excel&tab=<?= $active_tab; ?>" method="POST">
                        <input type="hidden" name="action" value="commit">
                        <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> Proses Import ke Database (<?= $total_valid; ?> Data)
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" class="btn btn-danger disabled px-4 py-2" title="Perbaiki baris invalid terlebih dahulu">
                        <i class="bi bi-x-circle-fill me-1"></i> Gagal (Terdapat Data Invalid)
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($total_invalid > 0): ?>
            <div class="alert alert-danger border-danger small mb-3">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <strong>Transaksi Dibatalkan:</strong> Terdapat <strong><?= $total_invalid; ?> baris data yang INVALID</strong>. Silakan perbaiki file Excel Anda dan unggah ulang!
            </div>
        <?php endif; ?>

        <!-- Table Preview Rows -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-dark" style="background-color: #1e293b;">
                    <tr>
                        <th class="text-center" style="width: 6%;">Baris</th>
                        <?php if ($active_tab === 'alternatif'): ?>
                            <th style="width: 20%;">Kode Alternatif</th>
                            <th style="width: 35%;">Nama Alternatif</th>
                            <th style="width: 25%;">Deskripsi</th>
                        <?php elseif ($active_tab === 'kriteria'): ?>
                            <th style="width: 15%;">Kode</th>
                            <th style="width: 25%;">Nama Kriteria</th>
                            <th style="width: 15%;">Bobot</th>
                            <th style="width: 15%;">Jenis</th>
                            <th style="width: 15%;">Keterangan</th>
                        <?php elseif ($active_tab === 'penilaian'): ?>
                            <th style="width: 25%;">Kode Alternatif</th>
                            <th style="width: 25%;">Kode Kriteria</th>
                            <th style="width: 30%;">Nilai Evaluasi</th>
                        <?php endif; ?>
                        <th class="text-center" style="width: 10%;">Status Baris</th>
                        <th style="width: 15%;">Keterangan / Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_data as $row): ?>
                        <tr class="<?= $row['valid'] ? '' : 'table-danger'; ?>">
                            <td class="text-center fw-bold"><?= $row['no']; ?></td>

                            <?php if ($active_tab === 'alternatif'): ?>
                                <td class="font-monospace fw-semibold"><?= sanitize($row['col1']); ?></td>
                                <td><?= sanitize($row['col2']); ?></td>
                                <td><?= sanitize($row['col3']); ?></td>
                            <?php elseif ($active_tab === 'kriteria'): ?>
                                <td class="font-monospace fw-semibold"><?= sanitize($row['col1']); ?></td>
                                <td><?= sanitize($row['col2']); ?></td>
                                <td><?= sanitize($row['col3']); ?></td>
                                <td><?= sanitize($row['col4']); ?></td>
                                <td><?= sanitize($row['col5']); ?></td>
                            <?php elseif ($active_tab === 'penilaian'): ?>
                                <td class="font-monospace fw-semibold"><?= sanitize($row['col1']); ?></td>
                                <td class="font-monospace fw-semibold"><?= sanitize($row['col2']); ?></td>
                                <td class="fw-bold text-primary"><?= sanitize($row['col3']); ?></td>
                            <?php endif; ?>

                            <td class="text-center">
                                <?php if ($row['valid']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-lg me-1"></i>VALID</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-lg me-1"></i>INVALID</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-danger fw-semibold">
                                <?= !empty($row['reason']) ? sanitize($row['reason']) : '<span class="text-success"><i class="bi bi-check2"></i> Siap Diimport</span>'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- SweetAlert2 Notifications -->
<?php if (!empty($swal_success)): ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= addslashes($swal_success); ?>',
            confirmButtonColor: '#2563eb',
            timer: 3000,
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

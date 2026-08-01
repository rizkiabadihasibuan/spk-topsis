<?php
/**
 * ====================================================
 * MODUL RIWAYAT PERHITUNGAN TOPSIS (FULL HISTORY SNAPSHOT SYSTEM)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

$swal_success = '';
$swal_error   = '';

// ----------------------------------------------------
// 1. HANDLER AJAX GET DETAIL RIWAYAT (JSON RESPONSE)
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'get_detail' && isset($_GET['id'])) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if (!$id || $db === null) {
        echo json_encode(['status' => 'error', 'message' => 'ID Riwayat tidak valid.']);
        exit;
    }

    try {
        // 1. Query Header
        $stmtH = $db->prepare("SELECT * FROM riwayat_perhitungan WHERE id = ?");
        $stmtH->execute([$id]);
        $header = $stmtH->fetch();

        // Fallback ke tabel hasil_perhitungan lama jika tidak ada di riwayat_perhitungan
        if (!$header) {
            $stmtH2 = $db->prepare("SELECT * FROM hasil_perhitungan WHERE id_hasil = ?");
            $stmtH2->execute([$id]);
            $headerOld = $stmtH2->fetch();
            if ($headerOld) {
                $header = [
                    'id'                       => $headerOld['id_hasil'],
                    'kode_perhitungan'         => 'TOPSIS-' . str_pad($headerOld['id_hasil'], 4, '0', STR_PAD_LEFT),
                    'metode'                   => 'TOPSIS',
                    'tanggal_perhitungan'      => $headerOld['tanggal_perhitungan'],
                    'jumlah_alternatif'        => $headerOld['jumlah_alternatif'],
                    'jumlah_kriteria'          => $headerOld['jumlah_kriteria'],
                    'alternatif_terbaik'       => $headerOld['alternatif_terbaik'],
                    'nilai_preferensi_terbaik' => $headerOld['nilai_preferensi_tertinggi']
                ];
            }
        }

        if (!$header) {
            echo json_encode(['status' => 'error', 'message' => 'Data riwayat tidak ditemukan.']);
            exit;
        }

        // 2. Query Details JOIN Alternatif
        $details = [];
        $stmtD = $db->prepare("
            SELECT d.*, a.kode_alternatif, a.nama_jurusan
            FROM riwayat_detail d
            JOIN tb_alternatif a ON d.alternatif_id = a.id_alternatif
            WHERE d.riwayat_id = ?
            ORDER BY d.ranking ASC
        ");
        $stmtD->execute([$id]);
        $details = $stmtD->fetchAll();

        // Fallback detail_hasil lama jika riwayat_detail kosong
        if (empty($details)) {
            $stmtD2 = $db->prepare("
                SELECT d.*, a.kode_alternatif, a.nama_jurusan
                FROM detail_hasil d
                JOIN tb_alternatif a ON d.id_alternatif = a.id_alternatif
                WHERE d.id_hasil = ?
                ORDER BY d.ranking ASC
            ");
            $stmtD2->execute([$id]);
            $rawOld = $stmtD2->fetchAll();
            foreach ($rawOld as $rowO) {
                $details[] = [
                    'ranking'          => $rowO['ranking'],
                    'kode_alternatif'  => $rowO['kode_alternatif'],
                    'nama_jurusan'     => $rowO['nama_jurusan'],
                    'nilai_d_plus'     => $rowO['d_plus'],
                    'nilai_d_minus'    => $rowO['d_minus'],
                    'nilai_preferensi' => $rowO['nilai_preferensi']
                ];
            }
        }

        echo json_encode([
            'status' => 'success',
            'header' => [
                'id'                       => $header['id'],
                'kode_perhitungan'         => $header['kode_perhitungan'],
                'metode'                   => $header['metode'] ?? 'TOPSIS',
                'tanggal'                  => date('d-m-Y H:i:s', strtotime($header['tanggal_perhitungan'])),
                'jumlah_alternatif'        => $header['jumlah_alternatif'],
                'jumlah_kriteria'          => $header['jumlah_kriteria'],
                'alternatif_terbaik'       => $header['alternatif_terbaik'],
                'nilai_tertinggi'          => number_format((float)$header['nilai_preferensi_terbaik'], 6)
            ],
            'details' => array_map(function($item) {
                $rank = (int)$item['ranking'];
                $statusBadge = '<span class="badge bg-secondary fw-semibold">Pertimbangan</span>';
                if ($rank === 1) {
                    $statusBadge = '<span class="badge bg-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Rekomendasi Terbaik</span>';
                } elseif ($rank === 2) {
                    $statusBadge = '<span class="badge bg-primary fw-semibold"><i class="bi bi-star-fill me-1"></i>Sangat Direkomendasikan</span>';
                } elseif ($rank === 3) {
                    $statusBadge = '<span class="badge bg-warning text-dark fw-semibold"><i class="bi bi-hand-thumbs-up-fill me-1"></i>Direkomendasikan</span>';
                }

                return [
                    'ranking'          => $rank,
                    'kode_alternatif'  => $item['kode_alternatif'],
                    'nama_jurusan'     => $item['nama_jurusan'],
                    'nilai_d_plus'     => number_format((float)$item['nilai_d_plus'], 6),
                    'nilai_d_minus'    => number_format((float)$item['nilai_d_minus'], 6),
                    'nilai_preferensi' => number_format((float)$item['nilai_preferensi'], 6),
                    'status_badge'     => $statusBadge
                ];
            }, $details)
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// ----------------------------------------------------
// 2. HANDLER EXPORT EXCEL PER-RIWAYAT
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_excel' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id && $db !== null) {
        try {
            $stmtH = $db->prepare("SELECT * FROM riwayat_perhitungan WHERE id = ?");
            $stmtH->execute([$id]);
            $header = $stmtH->fetch();

            if ($header) {
                $stmtD = $db->prepare("
                    SELECT d.*, a.kode_alternatif, a.nama_jurusan
                    FROM riwayat_detail d
                    JOIN tb_alternatif a ON d.alternatif_id = a.id_alternatif
                    WHERE d.riwayat_id = ?
                    ORDER BY d.ranking ASC
                ");
                $stmtD->execute([$id]);
                $details = $stmtD->fetchAll();

                $filename = "Riwayat_TOPSIS_" . $header['kode_perhitungan'] . "_" . date('Ymd_His') . ".csv";
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');

                $output = fopen('php://output', 'w');
                // BOM untuk Excel UTF-8
                fputs($output, "\xEF\xBB\xBF");

                fputcsv($output, ["KODE PERHITUNGAN", $header['kode_perhitungan']]);
                fputcsv($output, ["METODE", $header['metode']]);
                fputcsv($output, ["TANGGAL PERHITUNGAN", $header['tanggal_perhitungan']]);
                fputcsv($output, ["JUMLAH ALTERNATIF", $header['jumlah_alternatif']]);
                fputcsv($output, ["JUMLAH KRITERIA", $header['jumlah_kriteria']]);
                fputcsv($output, ["ALTERNATIF TERBAIK", $header['alternatif_terbaik']]);
                fputcsv($output, ["NILAI PREFERENSI TERBAIK", $header['nilai_preferensi_terbaik']]);
                fputcsv($output, []);

                fputcsv($output, ["RANKING", "KODE ALTERNATIF", "NAMA JURUSAN", "D+", "D-", "NILAI PREFERENSI (V)", "STATUS REKOMENDASI"]);

                foreach ($details as $row) {
                    $rank = (int)$row['ranking'];
                    $statusText = "Pertimbangan";
                    if ($rank === 1) $statusText = "Rekomendasi Terbaik";
                    elseif ($rank === 2) $statusText = "Sangat Direkomendasikan";
                    elseif ($rank === 3) $statusText = "Direkomendasikan";

                    fputcsv($output, [
                        $row['ranking'],
                        $row['kode_alternatif'],
                        $row['nama_jurusan'],
                        $row['nilai_d_plus'],
                        $row['nilai_d_minus'],
                        $row['nilai_preferensi'],
                        $statusText
                    ]);
                }
                fclose($output);
                exit;
            }
        } catch (Exception $e) {
            die("Gagal mengunduh Excel: " . $e->getMessage());
        }
    }
}

// ----------------------------------------------------
// 3. HANDLER AKSI: PROSES HITUNG TOPSIS & SIMPAN RIWAYAT
// ----------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'hitung_topsis') {
    if ($db !== null) {
        try {
            // Fetch Master Data
            $list_kriteria   = $db->query("SELECT * FROM tb_kriteria ORDER BY id_kriteria ASC")->fetchAll();
            $list_alternatif = $db->query("SELECT * FROM tb_alternatif ORDER BY id_alternatif ASC")->fetchAll();
            $raw_penilaian   = $db->query("SELECT id_alternatif, id_kriteria, nilai FROM tb_penilaian")->fetchAll();

            $total_k = count($list_kriteria);
            $total_a = count($list_alternatif);

            if ($total_k === 0 || $total_a === 0) {
                throw new Exception("Data Kriteria atau Alternatif masih kosong.");
            }

            // Map Penilaian
            $matrix_X  = [];
            $weights_W = [];
            $types_A   = [];
            foreach ($raw_penilaian as $r) {
                $matrix_X[$r['id_alternatif']][$r['id_kriteria']] = (float)$r['nilai'];
            }
            foreach ($list_kriteria as $k) {
                $weights_W[$k['id_kriteria']] = (float)$k['bobot'];
                $types_A[$k['id_kriteria']]   = strtolower(trim($k['jenis']));
            }

            // Full TOPSIS Engine
            $divider_sqrt = [];
            foreach ($list_kriteria as $k) {
                $id_k = $k['id_kriteria'];
                $sum_sq = 0.0;
                foreach ($list_alternatif as $a) {
                    $id_a = $a['id_alternatif'];
                    $val  = $matrix_X[$id_a][$id_k] ?? 0;
                    $sum_sq += ($val * $val);
                }
                $divider_sqrt[$id_k] = sqrt($sum_sq);
            }

            $matrix_Y = [];
            foreach ($list_alternatif as $a) {
                $id_a = $a['id_alternatif'];
                foreach ($list_kriteria as $k) {
                    $id_k = $k['id_kriteria'];
                    $x    = $matrix_X[$id_a][$id_k] ?? 0;
                    $div  = $divider_sqrt[$id_k];
                    $w    = $weights_W[$id_k] ?? 0;
                    $r    = ($div > 0) ? ($x / $div) : 0;
                    $matrix_Y[$id_a][$id_k] = $r * $w;
                }
            }

            $ideal_pos = [];
            $ideal_neg = [];
            foreach ($list_kriteria as $k) {
                $id_k = $k['id_kriteria'];
                $col_y = [];
                foreach ($list_alternatif as $a) {
                    $col_y[] = $matrix_Y[$a['id_alternatif']][$id_k];
                }
                $max_y = max($col_y);
                $min_y = min($col_y);
                $ideal_pos[$id_k] = ($types_A[$id_k] === 'benefit') ? $max_y : $min_y;
                $ideal_neg[$id_k] = ($types_A[$id_k] === 'benefit') ? $min_y : $max_y;
            }

            $calc_list = [];
            foreach ($list_alternatif as $a) {
                $id_a = $a['id_alternatif'];
                $sq_p = 0.0;
                $sq_m = 0.0;
                foreach ($list_kriteria as $k) {
                    $id_k = $k['id_kriteria'];
                    $y    = $matrix_Y[$id_a][$id_k];
                    $dp   = $y - $ideal_pos[$id_k];
                    $dm   = $y - $ideal_neg[$id_k];
                    $sq_p += ($dp * $dp);
                    $sq_m += ($dm * $dm);
                }
                $d_plus  = sqrt($sq_p);
                $d_minus = sqrt($sq_m);
                $denom   = $d_minus + $d_plus;
                $v_i     = ($denom > 0) ? ($d_minus / $denom) : 0;

                $calc_list[] = [
                    'id_alternatif' => $id_a,
                    'd_plus'        => $d_plus,
                    'd_minus'       => $d_minus,
                    'nilai_v'       => $v_i,
                    'nama_jurusan'  => $a['kode_alternatif'] . ' - ' . $a['nama_jurusan']
                ];
            }

            // Sorting Ranking
            usort($calc_list, function($a, $b) {
                return ($b['nilai_v'] <=> $a['nilai_v']);
            });

            $best_alt   = $calc_list[0]['nama_jurusan'];
            $best_score = $calc_list[0]['nilai_v'];

            // Generasi Kode Unik Perhitungan (TOPSIS-0001, TOPSIS-0002)
            $lastId = (int)$db->query("SELECT IFNULL(MAX(id), 0) FROM riwayat_perhitungan")->fetchColumn();
            $nextNum = $lastId + 1;
            $kodePerhitungan = 'TOPSIS-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            // Insert Database Transaction
            $db->beginTransaction();

            $stmtInsH = $db->prepare("
                INSERT INTO riwayat_perhitungan 
                (kode_perhitungan, metode, tanggal_perhitungan, jumlah_alternatif, jumlah_kriteria, alternatif_terbaik, nilai_preferensi_terbaik) 
                VALUES (?, 'TOPSIS', NOW(), ?, ?, ?, ?)
            ");
            $stmtInsH->execute([$kodePerhitungan, $total_a, $total_k, $best_alt, $best_score]);
            $riwayat_id_new = $db->lastInsertId();

            $stmtInsD = $db->prepare("
                INSERT INTO riwayat_detail 
                (riwayat_id, alternatif_id, nilai_d_plus, nilai_d_minus, nilai_preferensi, ranking) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($calc_list as $rank => $item) {
                $stmtInsD->execute([
                    $riwayat_id_new,
                    $item['id_alternatif'],
                    $item['d_plus'],
                    $item['d_minus'],
                    $item['nilai_v'],
                    $rank + 1
                ]);
            }

            $db->commit();
            $swal_success = "Proses Perhitungan TOPSIS ($kodePerhitungan) berhasil dijalankan dan disimpan ke Riwayat!";
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $swal_error = 'Gagal menyimpan perhitungan: ' . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// 4. HANDLER AKSI: HAPUS RIWAYAT
// ----------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($id && $db !== null) {
        try {
            $stmtDel = $db->prepare("DELETE FROM riwayat_perhitungan WHERE id = ?");
            $stmtDel->execute([$id]);

            // Hapus juga dari hasil_perhitungan lama jika ada
            $stmtDel2 = $db->prepare("DELETE FROM hasil_perhitungan WHERE id_hasil = ?");
            $stmtDel2->execute([$id]);

            $swal_success = 'Riwayat perhitungan berhasil dihapus.';
        } catch (PDOException $e) {
            $swal_error = 'Gagal menghapus riwayat: ' . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// 5. QUERY DATATABLE RIWAYAT DENGAN FILTER RENTANG TANGGAL
// ----------------------------------------------------
$tgl_mulai = isset($_GET['tgl_mulai']) ? sanitize($_GET['tgl_mulai']) : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? sanitize($_GET['tgl_akhir']) : '';

$sql_history = "SELECT * FROM riwayat_perhitungan WHERE 1=1";
$params_hist = [];

if (!empty($tgl_mulai)) {
    $sql_history .= " AND DATE(tanggal_perhitungan) >= ?";
    $params_hist[] = $tgl_mulai;
}
if (!empty($tgl_akhir)) {
    $sql_history .= " AND DATE(tanggal_perhitungan) <= ?";
    $params_hist[] = $tgl_akhir;
}

$sql_history .= " ORDER BY id DESC";

$list_riwayat = [];
if ($db !== null) {
    try {
        $stmtH = $db->prepare($sql_history);
        $stmtH->execute($params_hist);
        $list_riwayat = $stmtH->fetchAll();

        // Fallback: Jika riwayat_perhitungan kosong, ambil dari hasil_perhitungan
        if (empty($list_riwayat) && empty($tgl_mulai) && empty($tgl_akhir)) {
            $stmtOld = $db->query("SELECT * FROM hasil_perhitungan ORDER BY id_hasil DESC");
            $rawOld = $stmtOld->fetchAll();
            foreach ($rawOld as $rO) {
                $list_riwayat[] = [
                    'id'                       => $rO['id_hasil'],
                    'kode_perhitungan'         => 'TOPSIS-' . str_pad($rO['id_hasil'], 4, '0', STR_PAD_LEFT),
                    'metode'                   => 'TOPSIS',
                    'tanggal_perhitungan'      => $rO['tanggal_perhitungan'],
                    'jumlah_alternatif'        => $rO['jumlah_alternatif'],
                    'jumlah_kriteria'          => $rO['jumlah_kriteria'],
                    'alternatif_terbaik'       => $rO['alternatif_terbaik'],
                    'nilai_preferensi_terbaik' => $rO['nilai_preferensi_tertinggi']
                ];
            }
        }
    } catch (PDOException $e) {
        $swal_error = 'Gagal memuat riwayat: ' . $e->getMessage();
    }
}
?>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb" class="mb-3 no-print">
    <ol class="breadcrumb bg-white p-2 rounded-3 border shadow-sm small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>index.php?page=dashboard" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a></li>
        <li class="breadcrumb-item text-muted">Laporan</li>
        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Riwayat Perhitungan TOPSIS</li>
    </ol>
</nav>

<!-- Page Header Card -->
<div class="card card-custom p-4 mb-4 shadow-sm rounded-3 no-print">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Snapshot History & Audit Trail
            </span>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-journal-check me-2 text-primary"></i>Riwayat Perhitungan TOPSIS
            </h4>
            <p class="text-muted mb-0 small">
                Arsip rekam jejak kalkulasi matriks TOPSIS yang dapat dibuka, dicetak, dan diekspor kapan saja.
            </p>
        </div>
        <div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="hitung_topsis">
                <button type="submit" class="btn btn-primary shadow-sm fw-bold px-3.5 py-2 rounded-pill">
                    <i class="bi bi-cpu-fill me-1"></i> Proses Perhitungan TOPSIS
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Card Filter Tanggal & DataTables -->
<div class="card card-custom p-4 shadow-sm rounded-3 mb-4 no-print">
    <!-- Filter Rentang Tanggal -->
    <form method="GET" action="" class="row g-3 align-items-end mb-4 pb-3 border-bottom">
        <input type="hidden" name="page" value="riwayat">
        <div class="col-12 col-md-4">
            <label class="form-label fw-bold text-dark small mb-1"><i class="bi bi-calendar-event me-1 text-primary"></i>Tanggal Mulai</label>
            <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= sanitize($tgl_mulai); ?>">
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label fw-bold text-dark small mb-1"><i class="bi bi-calendar-event-fill me-1 text-primary"></i>Tanggal Akhir</label>
            <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= sanitize($tgl_akhir); ?>">
        </div>
        <div class="col-12 col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm fw-bold px-3 py-2 flex-grow-1">
                <i class="bi bi-filter me-1"></i> Filter Tanggal
            </button>
            <?php if (!empty($tgl_mulai) || !empty($tgl_akhir)): ?>
                <a href="<?= BASE_URL; ?>index.php?page=riwayat" class="btn btn-outline-secondary btn-sm px-3 py-2 fw-semibold">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table List Riwayat -->
    <div class="table-responsive">
        <table id="tableRiwayat" class="table table-hover align-middle w-100 border text-nowrap" style="min-width: 1050px;">
            <thead>
                <tr>
                    <th class="text-center text-nowrap" style="width: 5%;">No</th>
                    <th class="text-nowrap">Kode Perhitungan</th>
                    <th class="text-nowrap">Tanggal & Waktu</th>
                    <th class="text-center text-nowrap">Metode</th>
                    <th class="text-center text-nowrap">Alternatif</th>
                    <th class="text-center text-nowrap">Kriteria</th>
                    <th class="text-nowrap">Alternatif Terbaik</th>
                    <th class="text-center text-nowrap">Nilai Preferensi</th>
                    <th class="text-center text-nowrap" style="width: 160px; min-width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_riwayat)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted small">
                            <i class="bi bi-inbox display-6 text-muted mb-2 d-block"></i>
                            Belum ada riwayat perhitungan TOPSIS yang tersimpan. Klik <strong>Proses Perhitungan TOPSIS</strong> untuk memulai!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($list_riwayat as $r): ?>
                        <tr>
                            <td class="text-center fw-bold small"><?= $no++; ?></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-extrabold px-2.5 py-1">
                                    <i class="bi bi-hash me-0.5"></i><?= sanitize($r['kode_perhitungan']); ?>
                                </span>
                            </td>
                            <td class="small fw-semibold text-dark">
                                <i class="bi bi-clock me-1 text-muted"></i><?= date('d/m/Y H:i', strtotime($r['tanggal_perhitungan'])); ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-dark text-white fw-bold px-2 py-0.5" style="font-size: 0.7rem;"><?= sanitize($r['metode'] ?? 'TOPSIS'); ?></span>
                            </td>
                            <td class="text-center fw-bold text-dark"><?= (int)$r['jumlah_alternatif']; ?></td>
                            <td class="text-center fw-bold text-dark"><?= (int)$r['jumlah_kriteria']; ?></td>
                            <td class="fw-bold text-success">
                                <i class="bi bi-trophy-fill text-warning me-1"></i><?= sanitize($r['alternatif_terbaik']); ?>
                            </td>
                            <td class="text-center fw-extrabold text-primary">
                                <?= number_format((float)$r['nilai_preferensi_terbaik'], 6); ?>
                            </td>
                            <td class="text-center text-nowrap" style="width: 160px; min-width: 160px;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Lihat Detail Modal Button -->
                                    <button type="button" class="btn btn-outline-info fw-bold btn-view-detail" data-id="<?= $r['id']; ?>" title="Lihat Detail Snapshot">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    <!-- Export Excel Button -->
                                    <a href="<?= BASE_URL; ?>index.php?page=riwayat&action=export_excel&id=<?= $r['id']; ?>" class="btn btn-outline-success fw-bold" title="Export Excel">
                                        <i class="bi bi-file-earmark-excel-fill"></i>
                                    </a>

                                    <!-- Print Button -->
                                    <button type="button" class="btn btn-outline-secondary fw-bold btn-print-single" data-id="<?= $r['id']; ?>" title="Cetak Print">
                                        <i class="bi bi-printer-fill"></i>
                                    </button>

                                    <!-- Hapus Button -->
                                    <button type="button" class="btn btn-outline-danger fw-bold btn-delete-riwayat" data-id="<?= $r['id']; ?>" data-kode="<?= sanitize($r['kode_perhitungan']); ?>" title="Hapus Riwayat">
                                        <i class="bi bi-trash-fill"></i>
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
     MODAL DETAIL SNAPSHOT RIWAYAT TOPSIS
==================================================== -->
<div class="modal fade" id="modalDetailRiwayat" tabindex="-1" aria-labelledby="modalDetailRiwayatLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white p-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="bg-warning text-dark rounded-circle p-2 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-trophy-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="modalDetailRiwayatLabel">
                            Detail Snapshot Perhitungan TOPSIS
                        </h5>
                        <small class="text-white opacity-75" id="detailKodeText">Kode: -</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" id="modalBodyDetail">
                <div class="text-center py-5" id="loaderDetail">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="text-muted fw-semibold mb-0">Memuat snapshot data perhitungan...</p>
                </div>

                <div id="contentDetail" class="d-none">
                    <!-- Stat Card Header Ringkasan -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light border rounded-3 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase mb-1">Kode Perhitungan</small>
                                <span class="fw-extrabold text-primary" id="detailKodeHead">-</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light border rounded-3 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase mb-1">Tanggal Eksekusi</small>
                                <span class="fw-bold text-dark" id="detailTanggalHead">-</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-light border rounded-3 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase mb-1">Metode / Matriks</small>
                                <span class="fw-bold text-dark" id="detailMetodeHead">TOPSIS</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 text-center">
                                <small class="text-success fw-extrabold d-block text-uppercase mb-1">Alternatif Terbaik</small>
                                <span class="fw-extrabold text-success" id="detailBestHead">-</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Tabel Snapshot Hasil Perangkingan TOPSIS
                    </h6>

                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0" id="tableDetailSnapshot">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center text-nowrap" style="min-width: 100px;">Ranking</th>
                                    <th style="width: 15%;">Kode Alternatif</th>
                                    <th>Nama Jurusan Kuliah</th>
                                    <th class="text-center">Jarak D+</th>
                                    <th class="text-center">Jarak D-</th>
                                    <th class="text-center">Nilai Preferensi (V)</th>
                                    <th class="text-center" style="width: 22%;">Status Rekomendasi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDetailSnapshot">
                                <!-- Dynamic Rows JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light p-3 px-4">
                <button type="button" class="btn btn-outline-secondary fw-bold px-3" data-bs-dismiss="modal">
                    Tutup
                </button>
                <a href="#" id="btnModalExcel" class="btn btn-success fw-bold px-3">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                </a>
                <button type="button" class="btn btn-primary fw-bold px-3" id="btnModalPrint">
                    <i class="bi bi-printer-fill me-1"></i> Print Snapshot
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form Hapus Riwayat -->
<form id="formDeleteRiwayat" method="POST" action="" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteRiwayatId" value="">
</form>

<!-- DataTables JS & SweetAlert2 Trigger -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Inisialisasi DataTables
    if ($('#tableRiwayat').length > 0 && $('#tableRiwayat tbody tr').length > 0 && !$('#tableRiwayat tbody tr td[colspan]').length) {
        $('#tableRiwayat').DataTable({
            autoWidth: false,
            scrollX: true,
            language: {
                search: "Pencarian:",
                lengthMenu: "Tampilkan _MENU_ riwayat",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ riwayat",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 riwayat",
                infoFiltered: "(disaring dari _MAX_ total riwayat)",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            },
            order: [[0, 'asc']],
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: 8, width: "160px" }
            ]
        });
    }

    // 2. SweetAlert Notification Trigger
    <?php if (!empty($swal_success)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= addslashes($swal_success); ?>',
            timer: 3000,
            showConfirmButton: false
        });
    <?php endif; ?>

    <?php if (!empty($swal_error)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?= addslashes($swal_error); ?>',
            confirmButtonColor: '#dc2626'
        });
    <?php endif; ?>

    // 3. Modal Handler Lihat Detail
    $(document).on('click', '.btn-view-detail', function () {
        const id = $(this).data('id');
        $('#loaderDetail').removeClass('d-none');
        $('#contentDetail').addClass('d-none');
        $('#modalDetailRiwayat').modal('show');

        // Set Link Modal Actions
        $('#btnModalExcel').attr('href', '<?= BASE_URL; ?>index.php?page=riwayat&action=export_excel&id=' + id);
        $('#btnModalPrint').off('click').on('click', function() {
            printSnapshotModal(id);
        });

        // AJAX Request
        $.ajax({
            url: '<?= BASE_URL; ?>index.php?page=riwayat&action=get_detail&id=' + id,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    const h = res.header;
                    $('#detailKodeText').text('Kode: ' + h.kode_perhitungan);
                    $('#detailKodeHead').text(h.kode_perhitungan);
                    $('#detailTanggalHead').text(h.tanggal);
                    $('#detailMetodeHead').text(h.metode + ' (' + h.jumlah_alternatif + ' Alt / ' + h.jumlah_kriteria + ' Krit)');
                    $('#detailBestHead').text(h.alternatif_terbaik + ' (V=' + h.nilai_tertinggi + ')');

                    let tbodyHtml = '';
                    res.details.forEach(function (row) {
                        const r = parseInt(row.ranking);
                        let rankBadgeHtml = `<span class="rank-badge-other">#${r}</span>`;
                        if (r === 1) {
                            rankBadgeHtml = `<span class="rank-badge-1"><i class="bi bi-trophy-fill me-1"></i>#1</span>`;
                        } else if (r === 2) {
                            rankBadgeHtml = `<span class="rank-badge-2"><i class="bi bi-award-fill me-1"></i>#2</span>`;
                        } else if (r === 3) {
                            rankBadgeHtml = `<span class="rank-badge-3"><i class="bi bi-star-fill me-1"></i>#3</span>`;
                        }

                        tbodyHtml += `
                            <tr>
                                <td class="text-center text-nowrap">${rankBadgeHtml}</td>
                                <td class="fw-bold text-primary">${row.kode_alternatif}</td>
                                <td class="fw-semibold text-dark">${row.nama_jurusan}</td>
                                <td class="text-center text-muted font-monospace">${row.nilai_d_plus}</td>
                                <td class="text-center text-muted font-monospace">${row.nilai_d_minus}</td>
                                <td class="text-center fw-extrabold text-primary font-monospace fs-6">${row.nilai_preferensi}</td>
                                <td class="text-center">${row.status_badge}</td>
                            </tr>
                        `;
                    });
                    $('#tbodyDetailSnapshot').html(tbodyHtml);

                    $('#loaderDetail').addClass('d-none');
                    $('#contentDetail').removeClass('d-none');
                } else {
                    Swal.fire('Gagal', res.message || 'Gagal memuat detail data.', 'error');
                    $('#modalDetailRiwayat').modal('hide');
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                Swal.fire('Error', 'Gagal terhubung ke server untuk mengambil detail.', 'error');
                $('#modalDetailRiwayat').modal('hide');
            }
        });
    });

    // 4. Print Single Detail Handler
    $(document).on('click', '.btn-print-single', function () {
        const id = $(this).data('id');
        printSnapshotModal(id);
    });

    function printSnapshotModal(id) {
        // Open modal detail then trigger print
        $('.btn-view-detail[data-id="' + id + '"]').trigger('click');
        setTimeout(function() {
            window.print();
        }, 600);
    }

    // 5. SweetAlert Hapus Riwayat
    $(document).on('click', '.btn-delete-riwayat', function () {
        const id = $(this).data('id');
        const kode = $(this).data('kode');

        Swal.fire({
            title: 'Hapus Riwayat Perhitungan?',
            text: 'Apakah Anda yakin ingin menghapus riwayat perhitungan ' + kode + ' ini? Seluruh detail snapshot ranking juga akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#deleteRiwayatId').val(id);
                $('#formDeleteRiwayat').submit();
            }
        });
    });
});
</script>

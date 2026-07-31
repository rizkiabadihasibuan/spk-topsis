<?php
/**
 * ====================================================
 * HALAMAN DETAIL PERHITUNGAN TRANSPARANSI ALGORITMA TOPSIS (13 STEPS) (TAHAP 11.7)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

$swal_error = '';

// ----------------------------------------------------
// 1. QUERY MASTER DATA KRITERIA, ALTERNATIF, & PENILAIAN
// ----------------------------------------------------
$list_kriteria   = [];
$list_alternatif = [];
$raw_penilaian   = [];
$matrix_X        = [];
$weights_W       = [];
$types_attr      = [];

if ($db !== null) {
    try {
        $list_kriteria   = $db->query("SELECT * FROM tb_kriteria ORDER BY id_kriteria ASC")->fetchAll();
        $list_alternatif = $db->query("SELECT * FROM tb_alternatif ORDER BY id_alternatif ASC")->fetchAll();
        $raw_penilaian   = $db->query("SELECT id_alternatif, id_kriteria, nilai FROM tb_penilaian")->fetchAll();

        foreach ($raw_penilaian as $row) {
            $matrix_X[$row['id_alternatif']][$row['id_kriteria']] = (float)$row['nilai'];
        }

        foreach ($list_kriteria as $krit) {
            $weights_W[$krit['id_kriteria']]  = (float)$krit['bobot'];
            $types_attr[$krit['id_kriteria']] = strtolower(trim($krit['jenis']));
        }
    } catch (PDOException $e) {
        $swal_error = 'Gagal memuat data matriks: ' . $e->getMessage();
    }
}

$total_kriteria   = count($list_kriteria);
$total_alternatif = count($list_alternatif);

// ----------------------------------------------------
// 2. VALIDASI KELENGKAPAN DATA
// ----------------------------------------------------
$is_data_complete = true;
$invalid_weights  = false;
$invalid_types    = false;

if ($total_kriteria === 0 || $total_alternatif === 0) {
    $is_data_complete = false;
} else {
    foreach ($list_alternatif as $alt) {
        $id_alt = $alt['id_alternatif'];
        foreach ($list_kriteria as $krit) {
            $id_krit = $krit['id_kriteria'];
            if (!isset($matrix_X[$id_alt][$id_krit])) {
                $is_data_complete = false;
                break 2;
            }
        }
    }

    foreach ($list_kriteria as $krit) {
        $id_k = $krit['id_kriteria'];
        if (!isset($weights_W[$id_k]) || $weights_W[$id_k] <= 0) $invalid_weights = true;
        if (empty($types_attr[$id_k])) $invalid_types = true;
    }
}

// ----------------------------------------------------
// 3. KALKULASI SELURUH 13 STEPS TRANSPARANSI TOPSIS
// ----------------------------------------------------
$sq_matrix_X     = []; // Step 2: x_ij^2
$sum_squares     = []; // Step 3: sum(x_ij^2)
$divider_sqrt    = []; // Step 4: sqrt(sum(x_ij^2))
$matrix_R        = []; // Step 5: r_ij
$matrix_Y        = []; // Step 7: y_ij
$ideal_pos_A     = []; // Step 8: A+
$ideal_neg_A     = []; // Step 9: A-
$distance_D_plus = []; // Step 10: D+
$distance_D_minus= []; // Step 11: D-
$pref_V          = []; // Step 12: V_i
$ranking_list    = []; // Step 13: Ranking

if ($is_data_complete && !$invalid_weights && !$invalid_types) {
    // Step 2 & 3 & 4: Kuadrat, Total Kuadrat, & Akar Pembagi
    foreach ($list_kriteria as $krit) {
        $id_krit = $krit['id_kriteria'];
        $sum_sq  = 0.0;
        foreach ($list_alternatif as $alt) {
            $id_alt = $alt['id_alternatif'];
            $x_ij   = $matrix_X[$id_alt][$id_krit];
            $sq     = $x_ij * $x_ij;

            $sq_matrix_X[$id_alt][$id_krit] = $sq;
            $sum_sq += $sq;
        }
        $sum_squares[$id_krit]  = $sum_sq;
        $divider_sqrt[$id_krit] = sqrt($sum_sq);
    }

    // Step 5 & 7: Normalisasi R & Terbobot Y
    foreach ($list_alternatif as $alt) {
        $id_alt = $alt['id_alternatif'];
        foreach ($list_kriteria as $krit) {
            $id_krit = $krit['id_kriteria'];
            $x_ij    = $matrix_X[$id_alt][$id_krit];
            $divider = $divider_sqrt[$id_krit];
            $w_j     = $weights_W[$id_krit];

            $r_ij = ($divider > 0) ? ($x_ij / $divider) : 0;
            $y_ij = $r_ij * $w_j;

            $matrix_R[$id_alt][$id_krit] = $r_ij;
            $matrix_Y[$id_alt][$id_krit] = $y_ij;
        }
    }

    // Step 8 & 9: Solusi Ideal A+ dan A-
    foreach ($list_kriteria as $krit) {
        $id_krit = $krit['id_kriteria'];
        $type    = $types_attr[$id_krit];

        $col_y = [];
        foreach ($list_alternatif as $alt) {
            $col_y[] = $matrix_Y[$alt['id_alternatif']][$id_krit];
        }

        $max_y = max($col_y);
        $min_y = min($col_y);

        $ideal_pos_A[$id_krit] = ($type === 'benefit') ? $max_y : $min_y;
        $ideal_neg_A[$id_krit] = ($type === 'benefit') ? $min_y : $max_y;
    }

    // Step 10 & 11 & 12: D+, D-, V_i
    foreach ($list_alternatif as $alt) {
        $id_alt      = $alt['id_alternatif'];
        $sum_sq_p    = 0.0;
        $sum_sq_m    = 0.0;

        foreach ($list_kriteria as $krit) {
            $id_krit = $krit['id_kriteria'];
            $y_ij    = $matrix_Y[$id_alt][$id_krit];

            $dp = $y_ij - $ideal_pos_A[$id_krit];
            $dm = $y_ij - $ideal_neg_A[$id_krit];

            $sum_sq_p += ($dp * $dp);
            $sum_sq_m += ($dm * $dm);
        }

        $d_plus  = sqrt($sum_sq_p);
        $d_minus = sqrt($sum_sq_m);
        $denom   = $d_minus + $d_plus;
        $v_i     = ($denom > 0) ? ($d_minus / $denom) : 0;

        $distance_D_plus[$id_alt]  = $d_plus;
        $distance_D_minus[$id_alt] = $d_minus;
        $pref_V[$id_alt]          = $v_i;

        $ranking_list[] = [
            'id_alternatif'   => $id_alt,
            'kode_alternatif' => $alt['kode_alternatif'],
            'nama_jurusan'    => $alt['nama_jurusan'],
            'd_plus'          => $d_plus,
            'd_minus'         => $d_minus,
            'nilai_v'         => $v_i
        ];
    }

    // Step 13: Sorting Ranking
    usort($ranking_list, function($a, $b) {
        return ($b['nilai_v'] <=> $a['nilai_v']);
    });
}

// ----------------------------------------------------
// 4. HANDLER PROSES PERHITUNGAN TOPSIS & SIMPAN RIWAYAT SNAPSHOT
// ----------------------------------------------------
$swal_success = '';
if (isset($_POST['action']) && $_POST['action'] === 'proses_hitung_simpan_topsis') {
    if ($is_data_complete && !$invalid_weights && !$invalid_types && !empty($ranking_list)) {
        try {
            // Generate Kode Unik (TOPSIS-0001)
            $lastId = (int)$db->query("SELECT IFNULL(MAX(id), 0) FROM riwayat_perhitungan")->fetchColumn();
            $nextNum = $lastId + 1;
            $kodePerhitungan = 'TOPSIS-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $topAlt   = $ranking_list[0]['kode_alternatif'] . ' - ' . $ranking_list[0]['nama_jurusan'];
            $topScore = $ranking_list[0]['nilai_v'];

            $db->beginTransaction();

            $stmtInsH = $db->prepare("
                INSERT INTO riwayat_perhitungan 
                (kode_perhitungan, metode, tanggal_perhitungan, jumlah_alternatif, jumlah_kriteria, alternatif_terbaik, nilai_preferensi_terbaik) 
                VALUES (?, 'TOPSIS', NOW(), ?, ?, ?, ?)
            ");
            $stmtInsH->execute([$kodePerhitungan, $total_alternatif, $total_kriteria, $topAlt, $topScore]);
            $riwayatIdNew = $db->lastInsertId();

            $stmtInsD = $db->prepare("
                INSERT INTO riwayat_detail 
                (riwayat_id, alternatif_id, nilai_d_plus, nilai_d_minus, nilai_preferensi, ranking) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($ranking_list as $rankIdx => $rItem) {
                $stmtInsD->execute([
                    $riwayatIdNew,
                    $rItem['id_alternatif'],
                    $rItem['d_plus'],
                    $rItem['d_minus'],
                    $rItem['nilai_v'],
                    $rankIdx + 1
                ]);
            }

            $db->commit();
            $swal_success = "Proses Perhitungan TOPSIS ($kodePerhitungan) berhasil diproses dan disimpan ke Riwayat!";
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $swal_error = 'Gagal menyimpan riwayat perhitungan: ' . $e->getMessage();
        }
    } else {
        $swal_error = 'Data matriks belum lengkap, tidak dapat memproses perhitungan.';
    }
}
?>

<!-- Style Khusus Cetak/Print Halaman -->
<style>
@media print {
    #sidebar-wrapper, .navbar-custom, .btn, .breadcrumb, footer, .no-print {
        display: none !important;
    }
    #page-content-wrapper { margin: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #ccc !important; box-shadow: none !important; }
    .accordion-collapse { display: block !important; }
}
</style>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb" class="mb-3 no-print">
    <ol class="breadcrumb bg-white p-2 rounded-3 border shadow-sm small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>index.php?page=dashboard" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a></li>
        <li class="breadcrumb-item text-muted">Perhitungan TOPSIS</li>
        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Detail Perhitungan TOPSIS (13 Steps)</li>
    </ol>
</nav>

<!-- Page Header Card -->
<div class="card card-custom p-4 mb-4 shadow-sm">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 fw-bold">
                <i class="bi bi-shield-check me-1"></i> Transparansi Algoritma Matematis 100%
            </span>
            <h3 class="fw-extrabold text-dark mb-1">
                <i class="bi bi-calculator-fill me-2 text-primary"></i>Tahapan Perhitungan Matematis TOPSIS
            </h3>
            <p class="text-muted mb-0 small">
                Detail audit matematika 13 langkah pengolahan matriks dari data database secara transparan dan akurat.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <form method="POST" action="" class="d-inline">
                <input type="hidden" name="action" value="proses_hitung_simpan_topsis">
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm rounded-pill">
                    <i class="bi bi-cpu-fill me-1"></i> Proses Perhitungan TOPSIS
                </button>
            </form>
            <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-bold" id="btnExpandAll">
                <i class="bi bi-arrows-expand me-1"></i> Buka Semua Langkah
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-bold" id="btnCollapseAll">
                <i class="bi bi-arrows-collapse me-1"></i> Tutup Semua
            </button>
            <button type="button" class="btn btn-dark btn-sm px-3 fw-bold" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Cetak Laporan Detail
            </button>
        </div>
    </div>
</div>

<?php if (!$is_data_complete || $invalid_weights || $invalid_types): ?>
    <div class="card card-custom p-4 border-warning bg-warning-subtle shadow-sm rounded-3 mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-exclamation-triangle-fill text-warning display-6 me-3"></i>
            <div>
                <h5 class="fw-bold text-dark mb-2">Data Penilaian Belum Lengkap!</h5>
                <p class="text-dark small mb-0">
                    Transparansi 13 Langkah TOPSIS belum dapat ditampilkan. Silakan lengkapi data kriteria, alternatif, dan nilai di menu Penilaian!
                </p>
            </div>
        </div>
    </div>
<?php else: ?>

    <!-- ACCORDION CONTAINER 13 STEPS TRANSPARANSI -->
    <div class="accordion" id="accordionTopsis13">

        <!-- STEP 1: MATRIKS KEPUTUSAN X -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep1">
                <button class="accordion-button fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep1">
                    <span class="badge bg-primary me-2">STEP 1</span> Matriks Keputusan ($X$)
                </button>
            </h2>
            <div id="colStep1" class="accordion-collapse collapse show" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Mengumpulkan data skor evaluasi alternatif $A_i$ terhadap kriteria $C_j$.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $X = [x_{ij}]_{m \times n}$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Tabel `tb_penilaian` dari database.</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Menjadi input utama untuk perhitungan kuadrat di Step 2.</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 small">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Alternatif</th>
                                    <?php foreach ($list_kriteria as $k): ?>
                                        <th class="text-center"><?= sanitize($k['kode_kriteria']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach ($list_alternatif as $a): $id_a = $a['id_alternatif']; ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td class="font-monospace fw-bold"><?= sanitize($a['kode_alternatif']); ?></td>
                                        <td><?= sanitize($a['nama_jurusan']); ?></td>
                                        <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                            <td class="text-center font-monospace"><?= formatNumber($matrix_X[$id_a][$id_k], 2); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: PERHITUNGAN KUADRAT -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep2">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep2">
                    <span class="badge bg-primary me-2">STEP 2</span> Perhitungan Kuadrat ($x_{ij}^2$)
                </button>
            </h2>
            <div id="colStep2" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Mengkuadratkan setiap elemen nilai $x_{ij}$ pada Matriks Keputusan $X$.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $x_{ij}^2 = x_{ij} \times x_{ij}$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Matriks Keputusan $X$ (Step 1).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Dijumlahkan pada Step 3.</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 small">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Alternatif</th>
                                    <?php foreach ($list_kriteria as $k): ?>
                                        <th class="text-center"><?= sanitize($k['kode_kriteria']); ?>^2</th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach ($list_alternatif as $a): $id_a = $a['id_alternatif']; ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td class="font-monospace fw-bold"><?= sanitize($a['kode_alternatif']); ?></td>
                                        <td><?= sanitize($a['nama_jurusan']); ?></td>
                                        <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                            <td class="text-center font-monospace"><?= formatNumber($sq_matrix_X[$id_a][$id_k], 2); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: JUMLAH KUADRAT -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep3">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep3">
                    <span class="badge bg-primary me-2">STEP 3</span> Jumlah Kuadrat Setiap Kolom ($\sum x_{ij}^2$)
                </button>
            </h2>
            <div id="colStep3" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menjumlahkan seluruh hasil kuadrat pada setiap kolom kriteria.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $\sum_{i=1}^{m} x_{ij}^2$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Hasil Kuadrat $x_{ij}^2$ (Step 2).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Ditarik akar kuadratnya pada Step 4.</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode Kriteria</th>
                                <th>Nama Kriteria</th>
                                <th class="text-end">Total Jumlah Kuadrat ($\sum x^2$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                <tr>
                                    <td class="font-monospace fw-bold"><?= sanitize($k['kode_kriteria']); ?></td>
                                    <td><?= sanitize($k['nama_kriteria']); ?></td>
                                    <td class="text-end font-monospace fw-bold text-primary"><?= formatNumber($sum_squares[$id_k], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 4: AKAR JUMLAH KUADRAT -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep4">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep4">
                    <span class="badge bg-primary me-2">STEP 4</span> Akar Jumlah Kuadrat / Pembagi Vektor ($\sqrt{\sum x_{ij}^2}$)
                </button>
            </h2>
            <div id="colStep4" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Mendapatkan nilai pembagi standar vektor untuk setiap kriteria.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $\sqrt{\sum_{i=1}^{m} x_{ij}^2}$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Total Kuadrat (Step 3).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Menjadi penyebut/pembagi pada Normalisasi Matriks R (Step 5).</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode Kriteria</th>
                                <th>Nama Kriteria</th>
                                <th class="text-end">Nilai Akar Pembagi ($\sqrt{\sum x^2}$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                <tr>
                                    <td class="font-monospace fw-bold"><?= sanitize($k['kode_kriteria']); ?></td>
                                    <td><?= sanitize($k['nama_kriteria']); ?></td>
                                    <td class="text-end font-monospace fw-bold text-success fs-6"><?= formatNumber($divider_sqrt[$id_k], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 5: NORMALISASI MATRIKS R -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep5">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep5">
                    <span class="badge bg-primary me-2">STEP 5</span> Normalisasi Matriks ($R$)
                </button>
            </h2>
            <div id="colStep5" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menstandarisasi nilai elemen matriks $x_{ij}$ ke rentang skala $0 \le r_{ij} \le 1$.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $r_{ij} = \frac{x_{ij}}{\sqrt{\sum x_{ij}^2}}$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Matriks Keputusan $X$ (Step 1) dibagi Akar Pembagi (Step 4).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Dikalikan dengan Bobot $W_j$ pada Step 7.</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 small">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Alternatif</th>
                                    <?php foreach ($list_kriteria as $k): ?>
                                        <th class="text-center"><?= sanitize($k['kode_kriteria']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach ($list_alternatif as $a): $id_a = $a['id_alternatif']; ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td class="font-monospace fw-bold"><?= sanitize($a['kode_alternatif']); ?></td>
                                        <td><?= sanitize($a['nama_jurusan']); ?></td>
                                        <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                            <td class="text-center font-monospace text-primary fw-bold"><?= formatNumber($matrix_R[$id_a][$id_k], 4); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 6: BOBOT KRITERIA W -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep6">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep6">
                    <span class="badge bg-primary me-2">STEP 6</span> Bobot Kriteria ($W_j$)
                </button>
            </h2>
            <div id="colStep6" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menentukan tingkat kepentingan relatif dari setiap kriteria penilaian.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $W = [w_1, w_2, \dots, w_n]$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Tabel `tb_kriteria` dari database.</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Pengali untuk pembentukan Matriks Terbobot Y (Step 7).</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode Kriteria</th>
                                <th>Nama Kriteria</th>
                                <th>Jenis Atribut</th>
                                <th class="text-end">Bobot Kepentingan ($W_j$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                <tr>
                                    <td class="font-monospace fw-bold"><?= sanitize($k['kode_kriteria']); ?></td>
                                    <td><?= sanitize($k['nama_kriteria']); ?></td>
                                    <td><?= getBadgeJenis($types_attr[$id_k]); ?></td>
                                    <td class="text-end font-monospace fw-bold text-dark"><?= formatNumber($weights_W[$id_k], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 7: MATRIKS TERBOBOT Y -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep7">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep7">
                    <span class="badge bg-primary me-2">STEP 7</span> Matriks Normalisasi Terbobot ($Y$)
                </button>
            </h2>
            <div id="colStep7" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Mengalikan matriks normalisasi $R$ dengan bobot kepentingan $W_j$.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $y_{ij} = r_{ij} \times w_j$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Matriks Normalisasi R (Step 5) dikali Bobot W (Step 6).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Menentukan Solusi Ideal Positif $A^+$ & Negatif $A^-$ (Step 8 & 9).</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0 small">
                            <thead class="table-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Alternatif</th>
                                    <?php foreach ($list_kriteria as $k): ?>
                                        <th class="text-center"><?= sanitize($k['kode_kriteria']); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; foreach ($list_alternatif as $a): $id_a = $a['id_alternatif']; ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td class="font-monospace fw-bold"><?= sanitize($a['kode_alternatif']); ?></td>
                                        <td><?= sanitize($a['nama_jurusan']); ?></td>
                                        <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                            <td class="text-center font-monospace text-warning fw-bold"><?= formatNumber($matrix_Y[$id_a][$id_k], 4); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 8: SOLUSI IDEAL POSITIF A+ -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep8">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep8">
                    <span class="badge bg-success me-2">STEP 8</span> Solusi Ideal Positif ($A^+$)
                </button>
            </h2>
            <div id="colStep8" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menentukan nilai terbaik untuk setiap kriteria.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $A_j^+ = \max y_{ij}$ (jika Benefit) atau $\min y_{ij}$ (jika Cost).</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Matriks Terbobot Y (Step 7) & Jenis Atribut Kriteria.</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Pengurang untuk jarak solusi positif $D^+$ pada Step 10.</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark" style="background-color: #16a34a;">
                            <tr>
                                <th>Kode Kriteria</th>
                                <th>Nama Kriteria</th>
                                <th>Jenis Atribut</th>
                                <th class="text-end">Nilai Solusi Ideal Positif ($A^+$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                <tr>
                                    <td class="font-monospace fw-bold"><?= sanitize($k['kode_kriteria']); ?></td>
                                    <td><?= sanitize($k['nama_kriteria']); ?></td>
                                    <td><?= getBadgeJenis($types_attr[$id_k]); ?></td>
                                    <td class="text-end font-monospace fw-bold text-success fs-6"><?= formatNumber($ideal_pos_A[$id_k], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 9: SOLUSI IDEAL NEGATIF A- -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep9">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep9">
                    <span class="badge bg-danger me-2">STEP 9</span> Solusi Ideal Negatif ($A^-$)
                </button>
            </h2>
            <div id="colStep9" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menentukan nilai terburuk untuk setiap kriteria.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $A_j^- = \min y_{ij}$ (jika Benefit) atau $\max y_{ij}$ (jika Cost).</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Matriks Terbobot Y (Step 7) & Jenis Atribut Kriteria.</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Pengurang untuk jarak solusi negatif $D^-$ pada Step 11.</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark" style="background-color: #dc2626;">
                            <tr>
                                <th>Kode Kriteria</th>
                                <th>Nama Kriteria</th>
                                <th>Jenis Atribut</th>
                                <th class="text-end">Nilai Solusi Ideal Negatif ($A^-$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($list_kriteria as $k): $id_k = $k['id_kriteria']; ?>
                                <tr>
                                    <td class="font-monospace fw-bold"><?= sanitize($k['kode_kriteria']); ?></td>
                                    <td><?= sanitize($k['nama_kriteria']); ?></td>
                                    <td><?= getBadgeJenis($types_attr[$id_k]); ?></td>
                                    <td class="text-end font-monospace fw-bold text-danger fs-6"><?= formatNumber($ideal_neg_A[$id_k], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 10: PERHITUNGAN D+ -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep10">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep10">
                    <span class="badge bg-primary me-2">STEP 10</span> Perhitungan Jarak Solusi Positif ($D^+$)
                </button>
            </h2>
            <div id="colStep10" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menghitung jarak Euclidean setiap alternatif terhadap Solusi Ideal Positif.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $D_i^+ = \sqrt{\sum_{j=1}^{n} (y_{ij} - A_j^+)^2}$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Matriks Terbobot Y (Step 7) & Solusi Ideal Positif $A^+$ (Step 8).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Pembagi untuk Nilai Preferensi $V_i$ pada Step 12.</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Alternatif Jurusan</th>
                                <th class="text-end">Nilai Jarak Solusi Positif ($D^+$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($list_alternatif as $a): $id_a = $a['id_alternatif']; ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="font-monospace fw-bold"><?= sanitize($a['kode_alternatif']); ?></td>
                                    <td><?= sanitize($a['nama_jurusan']); ?></td>
                                    <td class="text-end font-monospace fw-bold text-primary"><?= formatNumber($distance_D_plus[$id_a], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 11: PERHITUNGAN D- -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep11">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep11">
                    <span class="badge bg-success me-2">STEP 11</span> Perhitungan Jarak Solusi Negatif ($D^-$)
                </button>
            </h2>
            <div id="colStep11" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menghitung jarak Euclidean setiap alternatif terhadap Solusi Ideal Negatif.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $D_i^- = \sqrt{\sum_{j=1}^{n} (y_{ij} - A_j^-)^2}$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Matriks Terbobot Y (Step 7) & Solusi Ideal Negatif $A^-$ (Step 9).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Pembilang dan pembagi Nilai Preferensi $V_i$ pada Step 12.</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Alternatif Jurusan</th>
                                <th class="text-end">Nilai Jarak Solusi Negatif ($D^-$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($list_alternatif as $a): $id_a = $a['id_alternatif']; ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="font-monospace fw-bold"><?= sanitize($a['kode_alternatif']); ?></td>
                                    <td><?= sanitize($a['nama_jurusan']); ?></td>
                                    <td class="text-end font-monospace fw-bold text-success"><?= formatNumber($distance_D_minus[$id_a], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 12: NILAI PREFERENSI V_i -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep12">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep12">
                    <span class="badge bg-warning text-dark me-2">STEP 12</span> Nilai Preferensi ($V_i$)
                </button>
            </h2>
            <div id="colStep12" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menghitung nilai kedekatan relatif setiap alternatif terhadap solusi ideal.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Rumus:</strong> $V_i = \frac{D_i^-}{D_i^- + D_i^+}$</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Jarak $D^+$ (Step 10) & Jarak $D^-$ (Step 11).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Acuan sorting untuk penetapan Ranking akhir pada Step 13.</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Alternatif Jurusan</th>
                                <th class="text-end">Nilai $D^+$</th>
                                <th class="text-end">Nilai $D^-$</th>
                                <th class="text-end">Nilai Preferensi ($V_i$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($list_alternatif as $a): $id_a = $a['id_alternatif']; ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="font-monospace fw-bold"><?= sanitize($a['kode_alternatif']); ?></td>
                                    <td><?= sanitize($a['nama_jurusan']); ?></td>
                                    <td class="text-end font-monospace text-primary"><?= formatNumber($distance_D_plus[$id_a], 4); ?></td>
                                    <td class="text-end font-monospace text-success"><?= formatNumber($distance_D_minus[$id_a], 4); ?></td>
                                    <td class="text-end font-monospace fw-bold text-dark fs-6"><?= formatNumber($pref_V[$id_a], 4); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 13: RANKING AKHIR -->
        <div class="accordion-item card card-custom border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
            <h2 class="accordion-header" id="headStep13">
                <button class="accordion-button collapsed fw-bold text-dark fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#colStep13">
                    <span class="badge bg-success me-2">STEP 13</span> Hasil Ranking Akhir Rekomendasi
                </button>
            </h2>
            <div id="colStep13" class="accordion-collapse collapse" data-bs-parent="#accordionTopsis13">
                <div class="accordion-body p-4">
                    <div class="alert alert-light border small mb-3">
                        <div><strong><i class="bi bi-bullseye me-1 text-primary"></i>Tujuan:</strong> Menentukan urutan rekomendasi jurusan dari preferensi terbesar hingga terendah.</div>
                        <div><strong><i class="bi bi-calculator me-1 text-primary"></i>Sorting:</strong> Descending order berdasarkan Nilai Preferensi ($V_i$).</div>
                        <div><strong><i class="bi bi-arrow-right-circle me-1 text-primary"></i>Asal Data:</strong> Nilai Preferensi $V_i$ (Step 12).</div>
                        <div><strong><i class="bi bi-arrow-down-circle me-1 text-primary"></i>Penggunaan:</strong> Rekomendasi keputusan jurusan akhir bagi siswa SMA.</div>
                    </div>

                    <table class="table table-bordered align-middle small mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 10%;">Ranking</th>
                                <th>Kode</th>
                                <th>Nama Alternatif Jurusan</th>
                                <th class="text-end">Nilai Preferensi ($V$)</th>
                                <th class="text-center">Status Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ranking_list as $rank => $item): $r = $rank + 1; ?>
                                <tr>
                                    <td class="text-center fw-bold fs-6">
                                        <?php if ($r === 1): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-trophy-fill me-1"></i>1</span>
                                        <?php elseif ($r === 2): ?>
                                            <span class="badge bg-secondary"><i class="bi bi-award-fill me-1"></i>2</span>
                                        <?php else: ?>
                                            <span><?= $r; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace fw-bold"><?= sanitize($item['kode_alternatif']); ?></td>
                                    <td class="fw-semibold text-dark"><?= sanitize($item['nama_jurusan']); ?></td>
                                    <td class="text-end font-monospace fw-bold text-primary fs-6"><?= formatNumber($item['nilai_v'], 4); ?></td>
                                    <td class="text-center">
                                        <?php if ($r === 1): ?>
                                            <span class="badge bg-success px-3 py-1">Rekomendasi Terbaik</span>
                                        <?php elseif ($r === 2): ?>
                                            <span class="badge bg-primary px-3 py-1">Alternatif Baik</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-1">Alternatif Dipertimbangkan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
<?php endif; ?>

<!-- SCRIPT TOGGLE EXPAND / COLLAPSE ALL ACCORDION -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const expandBtn   = document.getElementById('btnExpandAll');
    const collapseBtn = document.getElementById('btnCollapseAll');

    if (expandBtn) {
        expandBtn.addEventListener('click', function () {
            const collapses = document.querySelectorAll('#accordionTopsis13 .accordion-collapse');
            collapses.forEach(function (el) {
                const bsCollapse = new bootstrap.Collapse(el, { toggle: false });
                bsCollapse.show();
            });
        });
    }

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            const collapses = document.querySelectorAll('#accordionTopsis13 .accordion-collapse');
            collapses.forEach(function (el) {
                const bsCollapse = new bootstrap.Collapse(el, { toggle: false });
                bsCollapse.hide();
            });
        });
    }

    <?php if (!empty($swal_success)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= addslashes($swal_success); ?>',
            confirmButtonColor: '#2563eb'
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
});
</script>

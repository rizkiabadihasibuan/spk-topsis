<?php
/**
 * ====================================================
 * MODUL HASIL PERANGKAI (RANKING) TOPSIS (TAHAP 11.6)
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
        $swal_error = 'Gagal memuat data: ' . $e->getMessage();
    }
}

$total_kriteria   = count($list_kriteria);
$total_alternatif = count($list_alternatif);

// ----------------------------------------------------
// 2. VALIDASI KELENGKAPAN DATA PENILAIAN & BOBOT
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
        if (!isset($weights_W[$id_k]) || $weights_W[$id_k] <= 0) {
            $invalid_weights = true;
        }
        if (empty($types_attr[$id_k])) {
            $invalid_types = true;
        }
    }
}

// ----------------------------------------------------
// 3. COMPLETE TOPSIS ENGINE & RANKING CALCULATION
// ----------------------------------------------------
$ranking_results  = [];
$chart_labels     = [];
$chart_scores     = [];
$user_id          = $_SESSION['user_id'] ?? 1;

if ($is_data_complete && !$invalid_weights && !$invalid_types) {
    // A. Akar Pembagi & Matriks R
    $divider_sqrt = [];
    foreach ($list_kriteria as $krit) {
        $id_krit = $krit['id_kriteria'];
        $sum_sq  = 0.0;
        foreach ($list_alternatif as $alt) {
            $id_alt = $alt['id_alternatif'];
            $x_ij   = $matrix_X[$id_alt][$id_krit];
            $sum_sq += ($x_ij * $x_ij);
        }
        $divider_sqrt[$id_krit] = sqrt($sum_sq);
    }

    // B. Matriks Y
    $matrix_Y = [];
    foreach ($list_alternatif as $alt) {
        $id_alt = $alt['id_alternatif'];
        foreach ($list_kriteria as $krit) {
            $id_krit = $krit['id_kriteria'];
            $x_ij    = $matrix_X[$id_alt][$id_krit];
            $divider = $divider_sqrt[$id_krit];
            $w_j     = $weights_W[$id_krit];

            $r_ij = ($divider > 0) ? ($x_ij / $divider) : 0;
            $matrix_Y[$id_alt][$id_krit] = $r_ij * $w_j;
        }
    }

    // C. Solusi Ideal A+ dan A-
    $ideal_pos = [];
    $ideal_neg = [];
    foreach ($list_kriteria as $krit) {
        $id_krit = $krit['id_kriteria'];
        $type    = $types_attr[$id_krit];

        $col_y = [];
        foreach ($list_alternatif as $alt) {
            $col_y[] = $matrix_Y[$alt['id_alternatif']][$id_krit];
        }

        $max_y = max($col_y);
        $min_y = min($col_y);

        $ideal_pos[$id_krit] = ($type === 'benefit') ? $max_y : $min_y;
        $ideal_neg[$id_krit] = ($type === 'benefit') ? $min_y : $max_y;
    }

    // D. Jarak D+ & D- serta Nilai Preferensi V_i
    $calc_results = [];
    foreach ($list_alternatif as $alt) {
        $id_alt      = $alt['id_alternatif'];
        $sum_sq_plus = 0.0;
        $sum_sq_minus= 0.0;

        foreach ($list_kriteria as $krit) {
            $id_krit = $krit['id_kriteria'];
            $y_ij    = $matrix_Y[$id_alt][$id_krit];

            $diff_p = $y_ij - $ideal_pos[$id_krit];
            $diff_m = $y_ij - $ideal_neg[$id_krit];

            $sum_sq_plus  += ($diff_p * $diff_p);
            $sum_sq_minus += ($diff_m * $diff_m);
        }

        $d_plus  = sqrt($sum_sq_plus);
        $d_minus = sqrt($sum_sq_minus);
        $denom   = $d_minus + $d_plus;
        $val_V   = ($denom > 0) ? ($d_minus / $denom) : 0.0;

        $calc_results[] = [
            'id_alternatif'   => $id_alt,
            'kode_alternatif' => $alt['kode_alternatif'],
            'nama_jurusan'    => $alt['nama_jurusan'],
            'd_plus'          => $d_plus,
            'd_minus'         => $d_minus,
            'nilai_v'         => $val_V
        ];
    }

    // E. Soting Otomatis Berdasarkan Nilai V_i Terbesar -> Terkecil
    usort($calc_results, function($a, $b) {
        if ($b['nilai_v'] == $a['nilai_v']) return 0;
        return ($b['nilai_v'] > $a['nilai_v']) ? 1 : -1;
    });

    // F. Penetapan Ranking & Simpan Snapshot ke tb_hasil_topsis
    if ($db !== null) {
        try {
            $stmtSaveLog = $db->prepare("
                INSERT INTO tb_hasil_topsis (id_user, id_alternatif, jarak_d_plus, jarak_d_minus, nilai_v, ranking, tanggal_hitung)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    jarak_d_plus = VALUES(jarak_d_plus),
                    jarak_d_minus = VALUES(jarak_d_minus),
                    nilai_v = VALUES(nilai_v),
                    ranking = VALUES(ranking),
                    tanggal_hitung = NOW()
            ");

            foreach ($calc_results as $rank => $item) {
                $rank_number = $rank + 1;
                $item['ranking'] = $rank_number;
                $ranking_results[] = $item;

                // Chart arrays
                $chart_labels[] = $item['kode_alternatif'] . ' - ' . $item['nama_jurusan'];
                $chart_scores[] = round($item['nilai_v'], 4);

                // Save DB
                $stmtSaveLog->execute([
                    $user_id,
                    $item['id_alternatif'],
                    $item['d_plus'],
                    $item['d_minus'],
                    $item['nilai_v'],
                    $rank_number
                ]);
            }
        } catch (PDOException $e) {
            error_log("Save Hasil TOPSIS Error: " . $e->getMessage());
        }
    }
}
?>

<!-- Style Khusus Cetak/Print Halaman -->
<style>
@media print {
    #sidebar-wrapper, .navbar-custom, .btn, .breadcrumb, footer, .no-print {
        display: none !important;
    }
    #page-content-wrapper {
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
    }
}
</style>

<!-- Breadcrumb Navigasi -->
<nav aria-label="breadcrumb" class="mb-3 no-print">
    <ol class="breadcrumb bg-white p-2 rounded-3 border shadow-sm small">
        <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>index.php?page=dashboard" class="text-decoration-none"><i class="bi bi-house-door me-1"></i>Home</a></li>
        <li class="breadcrumb-item text-muted">Hasil Perangkai</li>
        <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">Ranking Rekomendasi Jurusan</li>
    </ol>
</nav>

<!-- Page Header Card -->
<div class="card card-custom p-4 mb-4 shadow-sm rounded-3">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 mb-2 fw-semibold">
                <i class="bi bi-trophy-fill me-1"></i> Hasil Akhir Perangkai TOPSIS
            </span>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-award-fill me-2 text-warning"></i>Ranking Rekomendasi Jurusan Kuliah
            </h4>
            <p class="text-muted mb-0 small">
                Hasil urutan preferensi ($V_i$) terbaik hingga terendah untuk membantu penentuan jurusan siswa SMA secara objektif.
            </p>
        </div>
        <div class="d-flex gap-2 no-print">
            <button type="button" class="btn btn-outline-dark shadow-sm fw-medium px-3 py-2" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Print Halaman
            </button>
            <a href="<?= BASE_URL; ?>index.php?page=topsis" class="btn btn-primary shadow-sm fw-medium px-3 py-2">
                <i class="bi bi-calculator me-1"></i> Detail Matriks TOPSIS
            </a>
        </div>
    </div>
</div>

<!-- ====================================================
     VALIDASI D+ DAN D- BELUM DIHITUNG
     ==================================================== -->
<?php if (!$is_data_complete || $invalid_weights || $invalid_types): ?>
    <div class="card card-custom p-4 border-warning bg-warning-subtle shadow-sm rounded-3 mb-4">
        <div class="d-flex align-items-start">
            <i class="bi bi-exclamation-triangle-fill text-warning display-6 me-3"></i>
            <div>
                <h5 class="fw-bold text-dark mb-2">Perhitungan $D^+$ dan $D^-$ Belum Tersedia!</h5>
                <p class="text-dark small mb-2">
                    Hasil ranking belum dapat ditampilkan karena data matriks penilaian atau bobot kriteria di database belum diisi secara lengkap.
                </p>
                <div class="mt-2">
                    <a href="<?= BASE_URL; ?>index.php?page=penilaian" class="btn btn-warning text-white fw-semibold px-3 py-2 shadow-sm">
                        <i class="bi bi-pencil-square me-1"></i> Lengkapi Penilaian Matriks
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>

    <!-- HIGHLIGHT 3 BESAR REKOMENDASI (TOP WINNERS) -->
    <?php if (count($ranking_results) >= 1): ?>
        <div class="row g-3 mb-4 no-print">
            <!-- Rank 1 Card (Emas / Gold) -->
            <div class="col-12 col-md-4">
                <div class="card card-custom p-4 text-white border-0 shadow-lg h-100 position-relative overflow-hidden" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 50%, #b45309 100%);">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-white text-dark fw-extrabold px-3 py-2 rounded-pill shadow-sm" style="letter-spacing: 0.5px;">
                            🥇 REKOMENDASI #1
                        </span>
                        <i class="bi bi-trophy-fill display-4 text-white opacity-40"></i>
                    </div>
                    <h4 class="fw-extrabold mb-1 text-white"><?= sanitize($ranking_results[0]['nama_jurusan']); ?></h4>
                    <small class="opacity-90 font-monospace d-block mb-3">Kode Jurusan: <?= sanitize($ranking_results[0]['kode_alternatif']); ?></small>
                    <div class="mt-auto pt-3 border-top border-white border-opacity-25 d-flex justify-content-between align-items-center">
                        <span class="small opacity-90 fw-medium">Nilai Preferensi ($V_1$):</span>
                        <span class="fw-extrabold font-monospace fs-4 text-white"><?= formatNumber($ranking_results[0]['nilai_v'], 4); ?></span>
                    </div>
                </div>
            </div>

            <!-- Rank 2 Card (Perak / Silver) -->
            <?php if (count($ranking_results) >= 2): ?>
                <div class="col-12 col-md-4">
                    <div class="card card-custom p-4 text-white border-0 shadow-lg h-100 position-relative overflow-hidden" style="background: linear-gradient(135deg, #475569 0%, #64748b 50%, #334155 100%);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-white text-dark fw-extrabold px-3 py-2 rounded-pill shadow-sm" style="letter-spacing: 0.5px;">
                                🥈 REKOMENDASI #2
                            </span>
                            <i class="bi bi-award-fill display-4 text-white opacity-40"></i>
                        </div>
                        <h4 class="fw-extrabold mb-1 text-white"><?= sanitize($ranking_results[1]['nama_jurusan']); ?></h4>
                        <small class="opacity-90 font-monospace d-block mb-3">Kode Jurusan: <?= sanitize($ranking_results[1]['kode_alternatif']); ?></small>
                        <div class="mt-auto pt-3 border-top border-white border-opacity-25 d-flex justify-content-between align-items-center">
                            <span class="small opacity-90 fw-medium">Nilai Preferensi ($V_2$):</span>
                            <span class="fw-extrabold font-monospace fs-4 text-white"><?= formatNumber($ranking_results[1]['nilai_v'], 4); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rank 3 Card (Perunggu / Bronze) -->
            <?php if (count($ranking_results) >= 3): ?>
                <div class="col-12 col-md-4">
                    <div class="card card-custom p-4 text-white border-0 shadow-lg h-100 position-relative overflow-hidden" style="background: linear-gradient(135deg, #9a3412 0%, #c2410c 50%, #7c2d12 100%);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-white text-dark fw-extrabold px-3 py-2 rounded-pill shadow-sm" style="letter-spacing: 0.5px;">
                                🥉 REKOMENDASI #3
                            </span>
                            <i class="bi bi-star-fill display-4 text-white opacity-40"></i>
                        </div>
                        <h4 class="fw-extrabold mb-1 text-white"><?= sanitize($ranking_results[2]['nama_jurusan']); ?></h4>
                        <small class="opacity-90 font-monospace d-block mb-3">Kode Jurusan: <?= sanitize($ranking_results[2]['kode_alternatif']); ?></small>
                        <div class="mt-auto pt-3 border-top border-white border-opacity-25 d-flex justify-content-between align-items-center">
                            <span class="small opacity-90 fw-medium">Nilai Preferensi ($V_3$):</span>
                            <span class="fw-extrabold font-monospace fs-4 text-white"><?= formatNumber($ranking_results[2]['nilai_v'], 4); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TABEL HASIL PERANGKAI UTAMA (DATATABLES) -->
    <div class="card card-custom p-4 shadow-sm rounded-3 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-table me-2 text-primary"></i>Tabel Hasil Perangkai Rekomendasi Jurusan
            </h6>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                Rumus: $V_i = \frac{D_i^-}{D_i^- + D_i^+}$
            </span>
        </div>

        <div class="table-responsive">
            <table id="tableRanking" class="table table-hover table-striped align-middle border w-100" style="font-size: 0.9rem;">
                <thead class="table-dark" style="background-color: #1e293b;">
                    <tr>
                        <th class="text-center" style="width: 8%;">Ranking</th>
                        <th style="width: 12%;">Kode</th>
                        <th style="width: 30%;">Nama Jurusan Kuliah</th>
                        <th class="text-end" style="width: 13%;">Jarak $D^+$</th>
                        <th class="text-end" style="width: 13%;">Jarak $D^-$</th>
                        <th class="text-end" style="width: 14%;">Nilai Preferensi ($V$)</th>
                        <th class="text-center" style="width: 10%;">Status Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking_results as $row): $rank = $row['ranking']; ?>
                        <tr>
                            <td class="text-center">
                                <?php if ($rank === 1): ?>
                                    <span class="rank-badge-1"><i class="bi bi-trophy-fill me-1"></i>#1</span>
                                <?php elseif ($rank === 2): ?>
                                    <span class="rank-badge-2"><i class="bi bi-award-fill me-1"></i>#2</span>
                                <?php elseif ($rank === 3): ?>
                                    <span class="rank-badge-3"><i class="bi bi-star-fill me-1"></i>#3</span>
                                <?php else: ?>
                                    <span class="rank-badge-other">#<?= $rank; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                    <?= sanitize($row['kode_alternatif']); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark"><?= sanitize($row['nama_jurusan']); ?></td>
                            <td class="text-end font-monospace text-primary fw-semibold"><?= formatNumber($row['d_plus'], 4); ?></td>
                            <td class="text-end font-monospace text-success fw-semibold"><?= formatNumber($row['d_minus'], 4); ?></td>
                            <td class="text-end font-monospace fw-extrabold fs-6 text-dark"><?= formatNumber($row['nilai_v'], 4); ?></td>
                            <td class="text-center">
                                <?php if ($rank === 1): ?>
                                    <span class="badge bg-success shadow-sm px-3 py-2 rounded-pill"><i class="bi bi-trophy-fill me-1"></i>Rekomendasi Utama</span>
                                <?php elseif ($rank <= 3): ?>
                                    <span class="badge bg-primary shadow-sm px-3 py-2 rounded-pill"><i class="bi bi-star-fill me-1"></i>Prioritas Tinggi</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary shadow-sm px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i>Opsi Pertimbangan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ACCORDION PROSES PERHITUNGAN RUMUS V_i PER ALTERNATIF -->
    <div class="card card-custom p-4 shadow-sm rounded-3 mb-4 no-print">
        <h6 class="fw-bold text-dark mb-3">
            <i class="bi bi-calculator me-2 text-primary"></i>Rincian Langkah Kalkulasi Nilai Preferensi ($V_i$) Per Alternatif
        </h6>

        <div class="accordion" id="accordionFormulaV">
            <?php foreach ($ranking_results as $index => $row): ?>
                <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-none">
                    <h2 class="accordion-header" id="headRank<?= $row['id_alternatif']; ?>">
                        <button class="accordion-button <?= ($index > 0) ? 'collapsed' : ''; ?> fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#colRank<?= $row['id_alternatif']; ?>" aria-expanded="<?= ($index === 0) ? 'true' : 'false'; ?>">
                            <span class="badge bg-dark font-monospace me-2">Rank <?= $row['ranking']; ?></span>
                            <span class="text-dark">[<?= sanitize($row['kode_alternatif']); ?>] <?= sanitize($row['nama_jurusan']); ?></span>
                            <span class="ms-auto me-3 font-monospace fw-bold text-primary">V = <?= formatNumber($row['nilai_v'], 4); ?></span>
                        </button>
                    </h2>
                    <div id="colRank<?= $row['id_alternatif']; ?>" class="accordion-collapse collapse <?= ($index === 0) ? 'show' : ''; ?>" data-bs-parent="#accordionFormulaV">
                        <div class="accordion-body bg-light p-3 small">
                            <div class="p-3 bg-white border rounded-3">
                                <div><strong>Nilai Jarak Solusi Ideal Positif ($D^+$):</strong> <span class="font-monospace text-primary"><?= formatNumber($row['d_plus'], 4); ?></span></div>
                                <div><strong>Nilai Jarak Solusi Ideal Negatif ($D^-$):</strong> <span class="font-monospace text-success"><?= formatNumber($row['d_minus'], 4); ?></span></div>
                                <div class="mt-2 pt-2 border-top">
                                    <strong>Langkah Perhitungan Rumus:</strong><br>
                                    <code>V_i = D- / (D- + D+)</code><br>
                                    <code>V_i = <?= formatNumber($row['d_minus'], 4); ?> / (<?= formatNumber($row['d_minus'], 4); ?> + <?= formatNumber($row['d_plus'], 4); ?>)</code><br>
                                    <code>V_i = <?= formatNumber($row['d_minus'], 4); ?> / <?= formatNumber($row['d_minus'] + $row['d_plus'], 4); ?></code><br>
                                    <strong class="text-primary fs-6">Hasil V_i = <?= formatNumber($row['nilai_v'], 4); ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- SEKSI GRAFIK CHART.JS (BAR CHART & DOUGHNUT CHART) -->
    <div class="row g-3 mb-4 no-print">
        <!-- BAR CHART SKOR PREFERENSI -->
        <div class="col-lg-7">
            <div class="card card-custom p-4 shadow-sm rounded-3 h-100">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Grafik Perbandingan Nilai Preferensi ($V_i$)
                </h6>
                <div style="position: relative; height: 280px;">
                    <canvas id="chartBarRanking"></canvas>
                </div>
            </div>
        </div>

        <!-- DOUGHNUT CHART DISTRIBUSI PREFERENSI -->
        <div class="col-lg-5">
            <div class="card card-custom p-4 shadow-sm rounded-3 h-100">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="bi bi-pie-chart-fill me-2 text-success"></i>Distribusi Persentase Rekomendasi
                </h6>
                <div style="position: relative; height: 280px;">
                    <canvas id="chartPieRanking"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT CHART.JS INSIALISASI GRAFIK -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // DataTables Ranking Setup
        $('#tableRanking').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
            },
            responsive: true,
            order: [[0, 'asc']]
        });

        const labels = <?= json_encode($chart_labels); ?>;
        const scores = <?= json_encode($chart_scores); ?>;

        // 1. Inisialisasi Bar Chart
        const ctxBar = document.getElementById('chartBarRanking');
        if (ctxBar) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Nilai Preferensi (V)',
                        data: scores,
                        backgroundColor: 'rgba(37, 99, 235, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1.5,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 1.0,
                            ticks: { precision: 2 }
                        }
                    }
                }
            });
        }

        // 2. Inisialisasi Doughnut Chart
        const ctxPie = document.getElementById('chartPieRanking');
        if (ctxPie) {
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: scores,
                        backgroundColor: [
                            '#16a34a', '#2563eb', '#f59e0b', '#dc2626', '#1e40af', '#64748b', '#8b5cf6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    });
    </script>

<?php endif; ?>

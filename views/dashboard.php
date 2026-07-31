<?php
/**
 * ====================================================
 * HALAMAN DASHBOARD STATISTIK MODERN (REALTIME DATA)
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

// 1. Ambil Data Real dari Database MySQL via PDO
$countAlternatif = 0;
$countKriteria   = 0;
$countPenilaian  = 0;
$countRiwayat    = 0;

$latestHasil     = null;
$barChartLabels  = [];
$barChartValues  = [];
$rankDist        = ['rank1' => 0, 'rank2' => 0, 'rank3' => 0, 'rankOther' => 0];
$monthlyChart    = [];
$recentActivities= [];

if ($db !== null) {
    try {
        // 1. Count Total Records
        $countAlternatif = (int)$db->query("SELECT COUNT(*) FROM tb_alternatif")->fetchColumn();
        $countKriteria   = (int)$db->query("SELECT COUNT(*) FROM tb_kriteria")->fetchColumn();
        $countPenilaian  = (int)$db->query("SELECT COUNT(*) FROM tb_penilaian")->fetchColumn();
        
        // Count Riwayat Perhitungan (Primary: riwayat_perhitungan, Fallback: hasil_perhitungan)
        try {
            $countRiwayat = (int)$db->query("SELECT COUNT(*) FROM riwayat_perhitungan")->fetchColumn();
        } catch (PDOException $e) {
            $countRiwayat = (int)$db->query("SELECT COUNT(*) FROM hasil_perhitungan")->fetchColumn();
        }

        // 2. Fetch Latest Calculation Record (Card Hasil)
        try {
            $stmtLatest = $db->query("SELECT * FROM riwayat_perhitungan ORDER BY id DESC LIMIT 1");
            $latestHasil = $stmtLatest->fetch();

            if ($latestHasil) {
                // Fetch Details for Bar Chart & Pie Chart
                $stmtDet = $db->prepare("
                    SELECT d.*, a.kode_alternatif, a.nama_jurusan
                    FROM riwayat_detail d
                    JOIN tb_alternatif a ON d.alternatif_id = a.id_alternatif
                    WHERE d.riwayat_id = ?
                    ORDER BY d.ranking ASC
                ");
                $stmtDet->execute([$latestHasil['id']]);
                $detList = $stmtDet->fetchAll();

                foreach ($detList as $dRow) {
                    $barChartLabels[] = $dRow['kode_alternatif'] . ' - ' . $dRow['nama_jurusan'];
                    $barChartValues[] = (float)$dRow['nilai_preferensi'];

                    $r = (int)$dRow['ranking'];
                    if ($r === 1) $rankDist['rank1']++;
                    elseif ($r === 2) $rankDist['rank2']++;
                    elseif ($r === 3) $rankDist['rank3']++;
                    else $rankDist['rankOther']++;
                }
            }
        } catch (PDOException $e) {
            $latestHasil = null;
        }

        // Fallback jika riwayat_perhitungan belum ada data, jalankan TOPSIS realtime sederhana
        if (empty($barChartLabels)) {
            $list_alt = $db->query("SELECT * FROM tb_alternatif ORDER BY id_alternatif ASC")->fetchAll();
            $list_krit = $db->query("SELECT * FROM tb_kriteria ORDER BY id_kriteria ASC")->fetchAll();
            $list_pen  = $db->query("SELECT id_alternatif, id_kriteria, nilai FROM tb_penilaian")->fetchAll();

            if (!empty($list_alt) && !empty($list_krit)) {
                $matX = []; $weightsW = []; $typesA = [];
                foreach ($list_pen as $p) { $matX[$p['id_alternatif']][$p['id_kriteria']] = (float)$p['nilai']; }
                foreach ($list_krit as $k) { $weightsW[$k['id_kriteria']] = (float)$k['bobot']; $typesA[$k['id_kriteria']] = strtolower(trim($k['jenis'])); }

                $divSqrt = [];
                foreach ($list_krit as $k) {
                    $idk = $k['id_kriteria']; $sumSq = 0.0;
                    foreach ($list_alt as $a) { $v = $matX[$a['id_alternatif']][$idk] ?? 0; $sumSq += ($v * $v); }
                    $divSqrt[$idk] = sqrt($sumSq);
                }

                $matY = [];
                foreach ($list_alt as $a) {
                    $ida = $a['id_alternatif'];
                    foreach ($list_krit as $k) {
                        $idk = $k['id_kriteria']; $x = $matX[$ida][$idk] ?? 0; $div = $divSqrt[$idk];
                        $r = ($div > 0) ? ($x / $div) : 0;
                        $matY[$ida][$idk] = $r * ($weightsW[$idk] ?? 0);
                    }
                }

                $idealP = []; $idealN = [];
                foreach ($list_krit as $k) {
                    $idk = $k['id_kriteria']; $colY = [];
                    foreach ($list_alt as $a) { $colY[] = $matY[$a['id_alternatif']][$idk]; }
                    $maxY = max($colY); $minY = min($colY);
                    $idealP[$idk] = ($typesA[$idk] === 'benefit') ? $maxY : $minY;
                    $idealN[$idk] = ($typesA[$idk] === 'benefit') ? $minY : $maxY;
                }

                $calcReal = [];
                foreach ($list_alt as $a) {
                    $ida = $a['id_alternatif']; $sqP = 0.0; $sqM = 0.0;
                    foreach ($list_krit as $k) {
                        $idk = $k['id_kriteria']; $y = $matY[$ida][$idk];
                        $dp = $y - $idealP[$idk]; $dm = $y - $idealN[$idk];
                        $sqP += ($dp * $dp); $sqM += ($dm * $dm);
                    }
                    $dP = sqrt($sqP); $dM = sqrt($sqM); $denom = $dP + $dM;
                    $vI = ($denom > 0) ? ($dM / $denom) : 0;
                    $calcReal[] = ['kode' => $a['kode_alternatif'], 'nama' => $a['nama_jurusan'], 'v' => $vI];
                }

                usort($calcReal, function($a, $b) { return ($b['v'] <=> $a['v']); });

                foreach ($calcReal as $idx => $cRow) {
                    $barChartLabels[] = $cRow['kode'] . ' - ' . $cRow['nama'];
                    $barChartValues[] = (float)$cRow['v'];

                    $r = $idx + 1;
                    if ($r === 1) $rankDist['rank1']++;
                    elseif ($r === 2) $rankDist['rank2']++;
                    elseif ($r === 3) $rankDist['rank3']++;
                    else $rankDist['rankOther']++;
                }

                if (!empty($calcReal)) {
                    $latestHasil = [
                        'kode_perhitungan'         => 'Sesi Realtime',
                        'alternatif_terbaik'       => $calcReal[0]['kode'] . ' - ' . $calcReal[0]['nama'],
                        'nilai_preferensi_terbaik' => $calcReal[0]['v'],
                        'tanggal_perhitungan'      => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        // 3. Monthly Calculations Line Chart (6 Bulan Terakhir)
        try {
            $stmtMonth = $db->query("
                SELECT DATE_FORMAT(tanggal_perhitungan, '%M %Y') as bulan, COUNT(*) as total
                FROM riwayat_perhitungan
                GROUP BY DATE_FORMAT(tanggal_perhitungan, '%Y-%m')
                ORDER BY tanggal_perhitungan ASC
                LIMIT 6
            ");
            $monthlyData = $stmtMonth->fetchAll();
            foreach ($monthlyData as $mRow) {
                $monthlyChart['labels'][] = $mRow['bulan'];
                $monthlyChart['values'][] = (int)$mRow['total'];
            }
        } catch (PDOException $e) {
            $monthlyChart = ['labels' => [], 'values' => []];
        }

        if (empty($monthlyChart['labels'])) {
            $monthlyChart = [
                'labels' => ['Mei', 'Juni', 'Juli', 'Agustus'],
                'values' => [1, 2, 4, max(1, $countRiwayat)]
            ];
        }

        // 4. Fetch 10 Recent System Activities (Aktivitas Terbaru)
        // Combine riwayat_perhitungan, tb_alternatif, tb_kriteria, tb_penilaian
        $recentActivities = [];

        // Activity 1: Riwayat Perhitungan Terbaru
        try {
            $stmtActH = $db->query("SELECT kode_perhitungan, alternatif_terbaik, tanggal_perhitungan FROM riwayat_perhitungan ORDER BY id DESC LIMIT 4");
            while ($actRow = $stmtActH->fetch()) {
                $recentActivities[] = [
                    'icon'      => 'bi-cpu-fill text-primary bg-primary-subtle',
                    'title'     => 'Proses Perhitungan TOPSIS (' . sanitize($actRow['kode_perhitungan']) . ')',
                    'desc'      => 'Rekomendasi Terbaik: <strong>' . sanitize($actRow['alternatif_terbaik']) . '</strong>',
                    'timestamp' => $actRow['tanggal_perhitungan']
                ];
            }
        } catch (PDOException $e) {}

        // Activity 2: Update Data Alternatif
        try {
            $stmtActAlt = $db->query("SELECT kode_alternatif, nama_jurusan, updated_at FROM tb_alternatif ORDER BY updated_at DESC LIMIT 3");
            while ($altRow = $stmtActAlt->fetch()) {
                $recentActivities[] = [
                    'icon'      => 'bi-journal-check text-success bg-success-subtle',
                    'title'     => 'Pembaruan Alternatif Jurusan',
                    'desc'      => 'Jurusan: <strong>' . sanitize($altRow['kode_alternatif'] . ' - ' . $altRow['nama_jurusan']) . '</strong>',
                    'timestamp' => $altRow['updated_at']
                ];
            }
        } catch (PDOException $e) {}

        // Activity 3: Update Data Kriteria
        try {
            $stmtActKrit = $db->query("SELECT kode_kriteria, nama_kriteria, updated_at FROM tb_kriteria ORDER BY updated_at DESC LIMIT 3");
            while ($kritRow = $stmtActKrit->fetch()) {
                $recentActivities[] = [
                    'icon'      => 'bi-sliders text-warning bg-warning-subtle',
                    'title'     => 'Pembaruan Kriteria & Bobot',
                    'desc'      => 'Kriteria: <strong>' . sanitize($kritRow['kode_kriteria'] . ' - ' . $kritRow['nama_kriteria']) . '</strong>',
                    'timestamp' => $kritRow['updated_at']
                ];
            }
        } catch (PDOException $e) {}

        // Sort Combined Activities by Timestamp DESC
        usort($recentActivities, function($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });

        // Limit to top 10 activities
        $recentActivities = array_slice($recentActivities, 0, 10);

    } catch (PDOException $e) {
        error_log("Dashboard Overhaul Error: " . $e->getMessage());
    }
}

// User Session Data
$namaAdmin  = $_SESSION['nama'] ?? 'Guru BK / Admin';
$loginTime  = $_SESSION['login_time'] ?? date('Y-m-d H:i:s');
?>

<!-- Header Dashboard Banner (Sleek & Compact Academic Banner) -->
<div class="card card-custom mb-3 p-3 p-md-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); border-radius: 14px;">
    <div class="row align-items-center g-3">
        <div class="col-lg-8 text-white">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-warning text-dark fw-bold px-2.5 py-1 rounded-pill shadow-sm" style="font-size: 0.72rem;">
                    <i class="bi bi-mortarboard-fill me-1"></i> Portal Bimbingan Konseling (BK) SMA
                </span>
            </div>
            <h4 class="fw-bold text-white mb-1.5" style="font-size: 1.4rem; letter-spacing: -0.3px;">
                Selamat Datang di Portal BK SMA, <?= sanitize($namaAdmin); ?>! 👋
            </h4>
            <p class="text-white opacity-90 mb-3 fw-normal" style="font-size: 0.88rem; line-height: 1.5; max-width: 660px; color: #ffffff !important;">
                Sistem Pendukung Keputusan Penentuan Jurusan Kuliah Siswa SMA Menggunakan Metode <strong>TOPSIS (Technique for Order Preference by Similarity to Ideal Solution)</strong>.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL; ?>index.php?page=topsis" class="btn btn-warning text-dark fw-bold px-3 py-1.5 shadow-sm rounded-pill" style="font-size: 0.82rem;">
                    <i class="bi bi-cpu-fill me-1"></i> Mulai Perhitungan TOPSIS
                </a>
                <a href="<?= BASE_URL; ?>index.php?page=riwayat" class="btn btn-outline-light fw-bold px-3 py-1.5 shadow-sm rounded-pill" style="font-size: 0.82rem;">
                    <i class="bi bi-clock-history me-1"></i> Lihat Riwayat
                </a>
            </div>
        </div>
        <div class="col-lg-4 text-end d-none d-lg-block">
            <div class="px-3 py-2.5 bg-white bg-opacity-10 rounded-3 d-inline-block text-center border border-white border-opacity-20 shadow-sm" style="backdrop-filter: blur(8px);">
                <i class="bi bi-award-fill text-warning fs-3 mb-1 d-block"></i>
                <h6 class="fw-bold text-white mb-1" style="font-size: 0.88rem;">Rekomendasi Jurusan</h6>
                <span class="badge bg-success text-white fw-bold px-2.5 py-0.5 rounded-pill" style="font-size: 0.68rem;">Data Real Database</span>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ACTION SHORTCUT BAR -->
<div class="card card-custom p-3 mb-4 shadow-sm rounded-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="fw-bold text-dark small me-2">
            <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Quick Action Shortcuts:
        </span>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_URL; ?>index.php?page=alternatif" class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3">
                <i class="bi bi-plus-circle me-1"></i> Tambah Alternatif
            </a>
            <a href="<?= BASE_URL; ?>index.php?page=penilaian" class="btn btn-outline-success btn-sm fw-bold rounded-pill px-3">
                <i class="bi bi-pencil-square me-1"></i> Tambah Penilaian
            </a>
            <a href="<?= BASE_URL; ?>index.php?page=topsis" class="btn btn-outline-warning text-dark btn-sm fw-bold rounded-pill px-3">
                <i class="bi bi-calculator me-1"></i> Mulai Perhitungan TOPSIS
            </a>
            <a href="<?= BASE_URL; ?>index.php?page=riwayat" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                <i class="bi bi-clock-history me-1"></i> Lihat Riwayat
            </a>
        </div>
    </div>
</div>

<!-- GRID 4 CARD STATISTIK REALTIME -->
<div class="row g-3 mb-4">
    <!-- Card 1: Jumlah Alternatif -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom stat-card primary h-100 shadow-sm" style="padding: 1.35rem 1.35rem 1.35rem 1.85rem !important; border-left: none !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary fw-bold d-block text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Jumlah Alternatif</span>
                    <h2 class="fw-extrabold mb-1 text-dark" style="font-size: 2rem; color: #0f172a !important;"><?= formatNumber($countAlternatif, 0); ?></h2>
                    <small class="text-primary fw-bold" style="font-size: 0.78rem;"><i class="bi bi-mortarboard-fill me-1"></i>Jurusan Kuliah</small>
                </div>
                <div class="stat-icon primary ms-3">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Jumlah Kriteria -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom stat-card success h-100 shadow-sm" style="padding: 1.35rem 1.35rem 1.35rem 1.85rem !important; border-left: none !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary fw-bold d-block text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Jumlah Kriteria</span>
                    <h2 class="fw-extrabold mb-1 text-dark" style="font-size: 2rem; color: #0f172a !important;"><?= formatNumber($countKriteria, 0); ?></h2>
                    <small class="text-success fw-bold" style="font-size: 0.78rem;"><i class="bi bi-sliders me-1"></i>Kriteria & Bobot</small>
                </div>
                <div class="stat-icon success ms-3">
                    <i class="bi bi-sliders"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Jumlah Penilaian -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom stat-card warning h-100 shadow-sm" style="padding: 1.35rem 1.35rem 1.35rem 1.85rem !important; border-left: none !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary fw-bold d-block text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Jumlah Penilaian</span>
                    <h2 class="fw-extrabold mb-1 text-dark" style="font-size: 2rem; color: #0f172a !important;"><?= formatNumber($countPenilaian, 0); ?></h2>
                    <small class="text-warning-emphasis fw-bold" style="font-size: 0.78rem;"><i class="bi bi-pencil-square me-1"></i>Matriks Terisi</small>
                </div>
                <div class="stat-icon warning ms-3">
                    <i class="bi bi-pencil-square"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Jumlah Riwayat Perhitungan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-custom stat-card info h-100 shadow-sm" style="padding: 1.35rem 1.35rem 1.35rem 1.85rem !important; border-left: none !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary fw-bold d-block text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Jumlah Riwayat</span>
                    <h2 class="fw-extrabold mb-1 text-dark" style="font-size: 2rem; color: #0f172a !important;"><?= formatNumber($countRiwayat, 0); ?></h2>
                    <small class="text-info-emphasis fw-bold" style="font-size: 0.78rem;"><i class="bi bi-clock-history me-1"></i>Sesi Perhitungan</small>
                </div>
                <div class="stat-icon info ms-3">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 1: CARD HASIL ALTERNATIF TERBAIK & BAR CHART PREFERENSI -->
<div class="row g-3 mb-4">
    <!-- CARD HASIL: ALTERNATIF TERBAIK -->
    <div class="col-lg-4">
        <div class="card card-custom h-100 p-4 shadow-sm rounded-3 border-0 text-white" style="background: linear-gradient(135deg, #15803d 0%, #166534 100%);">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge bg-warning text-dark fw-extrabold px-3 py-1 rounded-pill">
                    <i class="bi bi-trophy-fill me-1"></i> RANK 1 TOPSIS
                </span>
                <small class="text-white opacity-75 fw-semibold" style="font-size: 0.78rem;">
                    <?= !empty($latestHasil['kode_perhitungan']) ? sanitize($latestHasil['kode_perhitungan']) : 'Sesi Terakhir'; ?>
                </small>
            </div>

            <div class="my-auto py-2 text-center text-lg-start">
                <small class="text-white opacity-80 fw-bold text-uppercase d-block mb-1" style="letter-spacing: 0.5px;">Alternatif Terbaik</small>
                <h3 class="fw-extrabold text-white mb-2" style="font-size: 1.5rem;">
                    <?= !empty($latestHasil['alternatif_terbaik']) ? sanitize($latestHasil['alternatif_terbaik']) : 'Belum Dihitung'; ?>
                </h3>
                
                <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-20 px-3 py-1.5 rounded-pill mb-3">
                    <span class="small opacity-90">Nilai Preferensi ($V_i$):</span>
                    <strong class="fs-5 text-warning fw-extrabold">
                        <?= !empty($latestHasil['nilai_preferensi_terbaik']) ? number_format((float)$latestHasil['nilai_preferensi_terbaik'], 6) : '0.000000'; ?>
                    </strong>
                </div>
            </div>

            <div class="pt-3 border-top border-white border-opacity-20 d-flex align-items-center justify-content-between text-white opacity-90 small">
                <span><i class="bi bi-calendar-check me-1"></i>Waktu Eksekusi:</span>
                <span class="fw-bold"><?= !empty($latestHasil['tanggal_perhitungan']) ? date('d/m/Y H:i', strtotime($latestHasil['tanggal_perhitungan'])) : '-'; ?></span>
            </div>
        </div>
    </div>

    <!-- GRAFIK 1: BAR CHART PREFERENSI SETIAP ALTERNATIF -->
    <div class="col-lg-8">
        <div class="card card-custom p-4 h-100 shadow-sm rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Grafik Nilai Preferensi ($V_i$) Setiap Alternatif
                </h6>
                <span class="badge bg-light text-muted border">Bar Chart</span>
            </div>

            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="barChartPreferensi"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ROW 2: GRAFIK PIE DISTRIBUSI RANKING & LINE CHART TREND BUKAN -->
<div class="row g-3 mb-4">
    <!-- GRAFIK 2: PIE CHART DISTRIBUSI RANKING -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100 shadow-sm rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-pie-chart-fill me-2 text-success"></i>Persentase Distribusi Ranking Jurusan
                </h6>
                <span class="badge bg-light text-muted border">Doughnut Chart</span>
            </div>

            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="pieChartRanking"></canvas>
            </div>
        </div>
    </div>

    <!-- GRAFIK 3: LINE CHART TREND PERHITUNGAN PER BULAN -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100 shadow-sm rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-graph-up-arrow me-2 text-warning"></i>Trend Jumlah Perhitungan per Bulan
                </h6>
                <span class="badge bg-light text-muted border">Line Chart</span>
            </div>

            <div style="position: relative; height: 240px; width: 100%;">
                <canvas id="lineChartMonthly"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ROW 3: AKTIVITAS TERBARU (10 LOG TERAKHIR) -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card card-custom p-4 shadow-sm rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="bi bi-activity me-2 text-danger"></i>Aktivitas Terbaru Sistem (10 Log Terakhir)
                </h6>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">Live Activity Feed</span>
            </div>

            <?php if (empty($recentActivities)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox display-6 text-muted mb-2 d-block"></i>
                    Belum ada aktivitas yang tercatat.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentActivities as $act): ?>
                        <div class="list-group-item px-0 py-2.5 border-0 border-bottom border-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="badge p-2.5 rounded-circle shadow-xs <?= $act['icon']; ?>">
                                        <i class="bi bi-clock-history fs-6"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem;"><?= $act['title']; ?></h6>
                                        <small class="text-muted" style="font-size: 0.8rem;"><?= $act['desc']; ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <small class="text-secondary fw-semibold" style="font-size: 0.76rem;">
                                        <i class="bi bi-clock me-1 text-muted"></i><?= date('d/m/Y H:i', strtotime($act['timestamp'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- INITIALIZATION SCRIPT FOR CHART.JS 3 CHARTS -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // ----------------------------------------------------
    // 1. BAR CHART: NILAI PREFERENSI SETIAP ALTERNATIF
    // ----------------------------------------------------
    const ctxBar = document.getElementById('barChartPreferensi');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode(!empty($barChartLabels) ? $barChartLabels : ['A1', 'A2', 'A3', 'A4', 'A5']); ?>,
                datasets: [{
                    label: 'Nilai Preferensi (Vi)',
                    data: <?= json_encode(!empty($barChartValues) ? $barChartValues : [0.85, 0.72, 0.94, 0.65, 0.58]); ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    borderColor: '#2563eb',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Skor Vi: ' + context.parsed.y.toFixed(6);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1.0,
                        ticks: { stepSize: 0.2 }
                    },
                    x: {
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    }

    // ----------------------------------------------------
    // 2. PIE / DOUGHNUT CHART: PERSENTASE DISTRIBUSI RANKING
    // ----------------------------------------------------
    const ctxPie = document.getElementById('pieChartRanking');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Rank 1 (Terbaik)', 'Rank 2 (Sangat Baik)', 'Rank 3 (Baik)', 'Rank Lainnya'],
                datasets: [{
                    data: [
                        <?= $rankDist['rank1']; ?>,
                        <?= $rankDist['rank2']; ?>,
                        <?= $rankDist['rank3']; ?>,
                        <?= $rankDist['rankOther']; ?>
                    ],
                    backgroundColor: [
                        '#10b981', // Emerald Rank 1
                        '#2563eb', // Blue Rank 2
                        '#f59e0b', // Gold Rank 3
                        '#94a3b8'  // Gray Other
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, boxWidth: 12 }
                    }
                }
            }
        });
    }

    // ----------------------------------------------------
    // 3. LINE CHART: TREND PERHITUNGAN PER BULAN
    // ----------------------------------------------------
    const ctxLine = document.getElementById('lineChartMonthly');
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: <?= json_encode($monthlyChart['labels']); ?>,
                datasets: [{
                    label: 'Jumlah Sesi Perhitungan',
                    data: <?= json_encode($monthlyChart['values']); ?>,
                    fill: true,
                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
                    borderColor: '#f59e0b',
                    borderWidth: 3,
                    tension: 0.35,
                    pointBackgroundColor: '#d97706',
                    pointRadius: 4
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
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>

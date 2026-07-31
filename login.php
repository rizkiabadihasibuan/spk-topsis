<?php
/**
 * ====================================================
 * HALAMAN LOGIN & AUTENTIKASI PENGGUNA
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

require_once __DIR__ . '/config/global.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

// Jika pengguna sudah login, langsung alihkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$error_message = '';
$success_message = '';

// Cek apakah ada notifikasi logout
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success_message = 'Anda telah berhasil keluar dari sistem.';
}

// Otomatisasi Seeder Default Admin jika database terhubung dan tabel users kosong
if ($db !== null) {
    try {
        $check = $db->query("SELECT COUNT(*) as total FROM tb_users")->fetch();
        if ($check && (int)$check['total'] === 0) {
            $default_pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
            $default_pass_siswa = password_hash('siswa123', PASSWORD_DEFAULT);
            
            $stmtInsert = $db->prepare("INSERT INTO tb_users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute(['admin', $default_pass_admin, 'Guru BK / Administrator', 'admin']);
            $stmtInsert->execute(['siswa1', $default_pass_siswa, 'Budi Santoso', 'siswa']);
        }
    } catch (PDOException $e) {
        // Abaikan jika tabel belum di-import
    }
}

// Pemrosesan Form Submit Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 1. Validasi Input Kosong
    if (empty($username)) {
        $error_message = 'Username tidak boleh kosong!';
    } elseif (empty($password)) {
        $error_message = 'Password tidak boleh kosong!';
    } else {
        if ($db === null) {
            $error_message = 'Koneksi database gagal! Mohon pastikan MySQL XAMPP aktif.';
        } else {
            try {
                // 2. Query Database dengan PDO Prepared Statement (Aman dari SQL Injection)
                $stmt = $db->prepare("SELECT * FROM tb_users WHERE username = :username LIMIT 1");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch();

                if (!$user) {
                    // Username tidak ditemukan
                    $error_message = 'Username yang Anda masukkan tidak terdaftar!';
                } else {
                    // 3. Verifikasi Hash Password menggunakan password_verify()
                    if (password_verify($password, $user['password'])) {
                        // Login Berhasil! Simpan Session Lengkap
                        $_SESSION['user_id']    = $user['id_user'];
                        $_SESSION['nama']       = $user['nama_lengkap'];
                        $_SESSION['username']   = $user['username'];
                        $_SESSION['role']       = $user['role'];
                        $_SESSION['login_time'] = date('Y-m-d H:i:s');

                        // Redirect ke Dashboard Utama
                        header("Location: " . BASE_URL . "index.php?page=dashboard");
                        exit;
                    } else {
                        // Password salah
                        $error_message = 'Password yang Anda masukkan salah!';
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Terjadi kesalahan sistem database: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengguna - <?= APP_NAME; ?></title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.15) 0%, transparent 40%);
            pointer-events: none;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.4);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
        }
        .login-header {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            padding: 2.5rem 2rem 1.25rem 2rem;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .brand-icon-box {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 16px rgba(245, 158, 11, 0.35);
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.15);
        }
        .input-group-text {
            background-color: #f8fafc;
            border-right: none;
            color: #64748b;
        }
        .input-group .form-control {
            border-left: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Header Card Brand & Title -->
    <div class="login-header">
        <div class="brand-icon-box">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <span class="badge bg-warning text-dark fw-bold px-3 py-1 mb-2 rounded-pill" style="font-size: 0.75rem;">
            PORTAL BIMBINGAN KONSELING (BK) SMA
        </span>
        <h4 class="fw-bold text-dark mb-1" style="letter-spacing: -0.3px;"><?= APP_NAME; ?></h4>
        <p class="text-muted small mb-2">
            Penentuan Jurusan Kuliah Siswa SMA Menggunakan Metode <strong><?= APP_METHOD; ?></strong>
        </p>
    </div>

    <!-- Form Login Body -->
    <div class="card-body p-4 pt-2">
        <form action="" method="POST" autocomplete="off">
            <!-- Field Username -->
            <div class="mb-3">
                <label for="username" class="form-label fw-medium text-dark small">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" id="username" class="form-control py-2" placeholder="Masukkan username" value="<?= sanitize($_POST['username'] ?? ''); ?>">
                </div>
            </div>

            <!-- Field Password -->
            <div class="mb-4">
                <label for="password" class="form-label fw-medium text-dark small">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" id="password" class="form-control py-2" placeholder="Masukkan password">
                </div>
            </div>

            <!-- Button Submit Login -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Login Masuk
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SweetAlert2 JS & Validation Trigger -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
<?php if (!empty($error_message)): ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal Login',
        text: '<?= addslashes($error_message); ?>',
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Coba Lagi'
    });
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil Logout',
        text: '<?= addslashes($success_message); ?>',
        confirmButtonColor: '#2563eb',
        timer: 3000,
        timerProgressBar: true
    });
<?php endif; ?>
</script>

</body>
</html>

# 🎓 Sistem Pendukung Keputusan (SPK) Penentuan Jurusan Kuliah Siswa SMA
### *Penentuan Jurusan Kuliah Siswa SMA Menggunakan Metode TOPSIS (Technique for Order of Preference by Similarity to Ideal Solution)*

![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-InnoDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

---

## 📌 1. Tentang Proyek

**SPK TOPSIS Penentuan Jurusan Kuliah Siswa SMA** adalah aplikasi berbasis web yang dibangun untuk membantu siswa SMA dan pihak sekolah (Guru Bimbingan Konseling / Administrator) dalam menentukan rekomendasi pilihan **jurusan kuliah / program studi di Perguruan Tinggi** berdasarkan nilai akademis, potensi akademik, minat psikotes, estimasi biaya kuliah, dan daya serap prospek kerja.

Sistem ini menerapkan algoritma **TOPSIS**, sebuah metode pengambilan keputusan kriteria majemuk (*Multiple Criteria Decision Making / MCDM*) yang memilih alternatif terbaik berdasarkan jarak terdekat dari **Solusi Ideal Positif ($A^+$)** dan jarak terjauh dari **Solusi Ideal Negatif ($A^-$)**.

---

## 🌟 2. Fitur Utama

- 🔐 **Multi-Role Access Control**:
  - **Administrator / Guru BK**: Akses penuh mengelola data kriteria, bobot, sub-kriteria, alternatif jurusan, input matriks penilaian, perhitungan TOPSIS, riwayat, dan impor/ekspor data Excel.
  - **Siswa**: Akses melihat hasil rekomendasi jurusan pribadi dan grafik peringkat.
- 🎯 **Manajemen Kriteria Dinamis**: Pengaturan kode, nama kriteria, bobot (validasi otomatis total 1.00 / 100%), dan atribut jenis (**Benefit** atau **Cost**).
- 🏷️ **Sub-Kriteria & Bobot**: Penilaian kualitatif berjenjang per kriteria.
- 🎓 **Manajemen Alternatif Jurusan**: Pengelolaan nama jurusan, fakultas, deskripsi program studi, dan prospek karir lulusan.
- 📝 **Matriks Penilaian Dinamis**: Input & update nilai matriks keputusan ($X_{ij}$) interaktif.
- 🧮 **Engine Perhitungan TOPSIS Transparan**: Menampilkan 6 tahap matematis lengkap secara visual (Matriks Keputusan $X$, Matriks Ternormalisasi $R$, Matriks Terbobot $Y$, Solusi Ideal $A^+/A^-$, Jarak $D^+/D^-$, dan Nilai Preferensi $V_i$).
- 📈 **Dashboard Visual Analytics**: Grafik interaktif menggunakan **ApexCharts.js** untuk distribusi bobot kriteria dan statistik rekomendasi teratas.
- 📂 **Import & Export Excel**: Dukungan pengunggahan batch data kriteria, alternatif, dan penilaian via Excel serta fitur unduh template.
- 📜 **Riwayat Perhitungan**: Rekam jejak (*snapshot*) kalkulasi per session/user untuk perbandingan dari waktu ke waktu.
- 🖨️ **Cetak Laporan**: Fitur pratinjau dan cetak laporan hasil peringkat rekomendasi jurusan.

---

## 🛠️ 3. Teknologi & Spesifikasi

- **Backend**: PHP 8.x Native (Arsitektur Modular)
- **Database**: MySQL / MariaDB dengan Engine InnoDB (*Foreign Keys & Cascading*)
- **Frontend**: HTML5, Vanilla CSS, Bootstrap 5.3
- **Visualisasi Data**: ApexCharts.js
- **Iconography**: FontAwesome 6 & Bootstrap Icons
- **Pengolah Excel**: PhpSpreadsheet / Custom Multi-sheet Excel Helper

---

## 📂 4. Struktur Direktori Proyek

```text
spk-topsis/
├── assets/
│   ├── css/
│   │   └── style.css            # Custom Academic Theme Stylesheet & Utilities
│   └── js/
│       └── main.js             # Client-side validation & UI interactions
├── config/
│   ├── database.php            # Koneksi PDO Database & Helper Query
│   ├── global.php              # Konstanta aplikasi & Session Helper
│   ├── helper.php              # Utility function (Alerts, Formatting, Sanitize)
│   └── excel_helper.php        # Helper Parser Import/Export Excel
├── includes/
│   ├── header.php              # HTML Head, Fonts, & CSS Asset Inclusions
│   ├── navbar.php              # Header Navbar Navigation & User Profile Dropdown
│   ├── sidebar.php             # Sidebar Menu Navigation
│   └── footer.php              # HTML Footer & JS Library Inclusions
├── views/
│   ├── 404.php                 # Halaman Not Found
│   ├── alternatif.php          # Modul CRUD Alternatif Jurusan
│   ├── blank.php               # Template dasar halaman
│   ├── dashboard.php           # Modul Dashboard Analytics & Charts
│   ├── import_excel.php        # Modul Import & Export Data Excel
│   ├── kriteria.php            # Modul CRUD Kriteria & Sub-kriteria
│   ├── penilaian.php           # Modul Input Matriks Penilaian
│   ├── ranking.php             # Modul Hasil Akhir, Peringkat & Cetak Laporan
│   ├── riwayat.php             # Modul Log Riwayat Perhitungan TOPSIS
│   └── topsis.php              # Engine Perhitungan TOPSIS (Step 1 - Step 6)
├── database.sql                # Skrip SQL DDL & Seed Data Awal
├── index.php                   # Controller Utama (Router/Dispatcher)
├── login.php                   # Halaman Login Multi-Role
├── logout.php                  # Handler Logout Session
└── README.md                   # Dokumentasi Proyek
```

---

## ⚙️ 5. Panduan Instalasi & Konfigurasi

### **Prasyarat Sistem**:
1. Web Server (XAMPP / WAMP / Laragon / Nginx + PHP)
2. PHP versi 7.4 atau **8.x**
3. Database MySQL / MariaDB
4. Web Browser Modern (Chrome, Edge, Firefox, Opera)

### **Langkah-Langkah Instalasi**:

1. **Unduh / Salin Proyek**:
   Letakkan folder proyek di dalam direktori `htdocs` web server Anda:
   ```bash
   C:\xampp\htdocs\spk-topsis
   ```

2. **Impor Database**:
   - Buka `phpMyAdmin` di browser: `http://localhost/phpmyadmin`
   - Buat database baru dengan nama `db_spk_topsis`.
   - Pilih database `db_spk_topsis`, lalu pilih tab **Import**.
   - Pilih file `database.sql` yang berada di dalam folder proyek, lalu klik **Go / Kirim**.

3. **Konfigurasi Database** *(Opsional)*:
   Jika username/password MySQL Anda berbeda dari default XAMPP, sesuaikan pada file `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'db_spk_topsis');
   ```

4. **Jalankan Aplikasi**:
   Buka browser dan akses alamat berikut:
   ```text
   http://localhost/spk-topsis
   ```

---

## 🔑 6. Akun Login Akses Sistem

| Role | Username | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator / Guru BK** | `admin` | `admin123` | Full Access: Kriteria, Alternatif, Penilaian, TOPSIS, Import/Export Excel. |
| **Siswa** | `siswa1` | `siswa123` | Read-only Access: Melihat Hasil Rekomendasi Jurusan & Peringkat. |

---

## 📐 7. Skema Data & Algoritma Perhitungan TOPSIS

### **Skema Penggabungan Data Real & Rating Kualitatif**:
Aplikasi SPK TOPSIS ini secara ilmiah mendukung penggabungan 2 jenis data secara simultan:
- 📊 **Data Real / Kuantitatif Asli (0-100)**: Digunakan untuk nilai akademis asli (seperti *C1: Nilai Rapor Akademik* dan *C2: Skor TPA*).
- ⭐ **Data Rating Konversi (1-5)**: Digunakan untuk kriteria kualitatif atau berjenjang:
  - **C3: Akreditasi Jurusan & Kampus**: `5` = Unggul / A, `4` = Baik Sekali / B, `3` = Baik / C, `2` = Cukup, `1` = Belum.
  - **C4: Minat & Psikotes**: `5` = Sangat Berminat s.d `1` = Tidak Berminat.
  - **C5: Estimasi Biaya UKT (Cost)**: `1` = < Rp 2Jt s.d `5` = > Rp 12Jt.
  - **C6: Prospek Kerja**: `5` = Sangat Luas s.d `1` = Sangat Rendah.

Seluruh perbedaan satuan dan skala angka tersebut otomatis disetarakan secara homogen pada **Tahap Normalisasi Vektor ($R$)** TOPSIS.

---

### **6 Tahapan Utama Algoritma TOPSIS**:

1. **Matriks Keputusan ($X$)**:
   Pembentukan matriks ukuran $m \times n$ (alternatif $\times$ kriteria).
2. **Matriks Ternormalisasi ($R$)**:
   $$r_{ij} = \frac{x_{ij}}{\sqrt{\sum_{i=1}^{m} x_{ij}^2}}$$
3. **Matriks Ternormalisasi Terbobot ($Y$)**:
   $$y_{ij} = w_j \cdot r_{ij}$$
4. **Solusi Ideal Positif ($A^+$) dan Negatif ($A^-$)**:
   - Jika Benefit: $y_j^+ = \max(y_{ij})$, $y_j^- = \min(y_{ij})$
   - Jika Cost: $y_j^+ = \min(y_{ij})$, $y_j^- = \max(y_{ij})$
5. **Jarak Solusi Ideal ($D_i^+$ dan $D_i^-$)**:
   $$D_i^+ = \sqrt{\sum_{j=1}^{n} (y_{ij} - y_j^+)^2}, \quad D_i^- = \sqrt{\sum_{j=1}^{n} (y_{ij} - y_j^-)^2}$$
6. **Nilai Preferensi ($V_i$) & Peringkat**:
   $$V_i = \frac{D_i^-}{D_i^+ + D_i^-}$$
   *Nilai $V_i$ mendekati 1 menunjukkan alternatif jurusan yang paling direkomendasikan.*

---

## 📄 8. Lisensi

Proyek ini dikembangkan di bawah lisensi **MIT License**. Bebas digunakan, dimodifikasi, dan dikembangkan untuk keperluan akademik, tugas akhir, maupun penelitian.

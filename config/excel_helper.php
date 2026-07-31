<?php
/**
 * ====================================================
 * HELPER IMPORT & PARSING EXCEL / CSV
 * Project: SPK TOPSIS Penentuan Jurusan SMA
 * ====================================================
 */

/**
 * Fungsi Mendownload Template Excel / CSV Sesuai Jenis Import
 * @param string $type ('alternatif', 'kriteria', 'penilaian')
 */
function downloadTemplateExcel($type) {
    ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Template_' . ucfirst($type) . '.csv');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM untuk Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($type === 'alternatif') {
        fputcsv($output, ['kode_alternatif', 'nama_alternatif', 'deskripsi']);
        fputcsv($output, ['A1', 'Teknik Informatika', 'Program studi bidang komputasi']);
        fputcsv($output, ['A2', 'Sistem Informasi', 'Program studi manajemen TI']);
        fputcsv($output, ['A3', 'Manajemen', 'Program studi bidang bisnis']);
    } elseif ($type === 'kriteria') {
        fputcsv($output, ['kode_kriteria', 'nama_kriteria', 'bobot', 'jenis', 'keterangan']);
        fputcsv($output, ['C1', 'Nilai Matematika', '0.25', 'benefit', 'Nilai rapor semester']);
        fputcsv($output, ['C2', 'Minat & Bakat', '0.25', 'benefit', 'Hasil tes minat']);
        fputcsv($output, ['C3', 'Biaya Kuliah', '0.20', 'cost', 'Estimasi SPP per semester']);
        fputcsv($output, ['C4', 'Prospek Kerja', '0.30', 'benefit', 'Tingkat serapan lulusan']);
    } elseif ($type === 'penilaian') {
        fputcsv($output, ['kode_alternatif', 'kode_kriteria', 'nilai']);
        fputcsv($output, ['A1', 'C1', '85']);
        fputcsv($output, ['A1', 'C2', '90']);
        fputcsv($output, ['A1', 'C3', '75']);
        fputcsv($output, ['A2', 'C1', '80']);
    }

    fclose($output);
    exit;
}

/**
 * Fungsi Membaca Baris File CSV / Excel Sederhana
 * @param string $filePath
 * @return array Baris data (array of arrays)
 */
function readCsvOrExcelData($filePath) {
    $rows = [];
    if (!file_exists($filePath)) return $rows;

    $handle = fopen($filePath, 'r');
    if ($handle !== false) {
        // Abaikan BOM jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($data = fgetcsv($handle, 2048, ",")) !== false) {
            // Jika fgetcsv gagal memisahkan koma (misal pemisah titik koma ';'), coba explode ';'
            if (count($data) == 1 && strpos($data[0], ';') !== false) {
                $data = explode(';', $data[0]);
            }
            // Bersihkan whitespace & quotes pada setiap cell
            $clean_row = array_map(function($cell) {
                return trim(trim($cell), '"\'');
            }, $data);

            if (!empty(array_filter($clean_row))) {
                $rows[] = $clean_row;
            }
        }
        fclose($handle);
    }
    return $rows;
}

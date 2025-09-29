<?php
session_start();
require_once '../config/koneksi.php';

// Pengecekan sesi: Pastikan user sudah login dan role-nya 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$error_message = '';
$success_message = '';
$redirect_to = 'data_mahasiswa.php'; // Halaman tujuan setelah operasi selesai

if (isset($_GET['id'])) {
    $id_user = cleanInput($koneksi, $_GET['id']);

    // 1. Cek apakah ID valid dan merupakan mahasiswa
    $check_query = "SELECT nama, role FROM users WHERE id_user = '$id_user' AND role = 'mahasiswa'";
    $check_result = mysqli_query($koneksi, $check_query);

    if (mysqli_num_rows($check_result) == 0) {
        // Jika ID tidak ditemukan atau bukan mahasiswa
        $_SESSION['error_message'] = 'Gagal! Data mahasiswa tidak ditemukan.';
    } else {
        $mahasiswa_data = mysqli_fetch_assoc($check_result);
        $nama_mahasiswa = htmlspecialchars($mahasiswa_data['nama']);

        // 2. Query DELETE data mahasiswa
        // Gunakan transaksi untuk menghapus data terkait di tabel lain jika ada (optional, tapi disarankan)
        mysqli_begin_transaction($koneksi);

        try {
            // Hapus data kas masuk mahasiswa (jika ada)
            $delete_kas_masuk = "DELETE FROM kas_masuk WHERE id_user = '$id_user'";
            mysqli_query($koneksi, $delete_kas_masuk);

            // Hapus data pengguna dari tabel users
            $delete_user = "DELETE FROM users WHERE id_user = '$id_user' AND role = 'mahasiswa'";
            $delete_result = mysqli_query($koneksi, $delete_user);

            if ($delete_result) {
                mysqli_commit($koneksi);
                $_SESSION['success_message'] = 'Berhasil! Data mahasiswa **' . $nama_mahasiswa . '** berhasil dihapus, termasuk riwayat pembayarannya.';
            } else {
                mysqli_rollback($koneksi);
                throw new Exception('Gagal menghapus data dari database.');
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error: Gagal menghapus data. ' . $e->getMessage() . ' ' . mysqli_error($koneksi);
        }
    }
} else {
    $_SESSION['error_message'] = 'Gagal! ID mahasiswa tidak ditemukan.';
}

mysqli_close($koneksi);

// Redirect ke halaman data mahasiswa
header("Location: $redirect_to");
exit;
?>
<?php
session_start();
require_once '../config/koneksi.php';

// Pengecekan sesi: Pastikan user sudah login dan role-nya 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$nama_admin = $_SESSION['nama'];
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Fungsi format Rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

$id_user = isset($_GET['id_user']) ? cleanInput($koneksi, $_GET['id_user']) : null;
$nim = isset($_GET['nim']) ? cleanInput($koneksi, $_GET['nim']) : '';

// 1. Ambil data mahasiswa
$query_mahasiswa = "SELECT id_user, nim, nama FROM users WHERE id_user = '$id_user' AND role = 'mahasiswa' LIMIT 1";
$result_mahasiswa = mysqli_query($koneksi, $query_mahasiswa);
$mahasiswa_data = mysqli_fetch_assoc($result_mahasiswa);

if (!$mahasiswa_data) {
    $error_message = 'Data mahasiswa tidak ditemukan atau ID tidak valid.';
    $id_user = null;
} else {
    $nama_mahasiswa = $mahasiswa_data['nama'];

    // 2. LOGIKA HAPUS KAS MASUK
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_kas_masuk'])) {
        $id_kas_masuk = cleanInput($koneksi, $_POST['id_kas_masuk']);
        
        // Ambil data sebelum menghapus untuk pesan sukses
        $query_check = "SELECT jumlah, tanggal FROM kas_masuk WHERE id_kas_masuk = '$id_kas_masuk' AND id_user = '$id_user'";
        $result_check = mysqli_query($koneksi, $query_check);
        $data_check = mysqli_fetch_assoc($result_check);
        
        if ($data_check) {
            $delete_query = "DELETE FROM kas_masuk WHERE id_kas_masuk = '$id_kas_masuk' AND id_user = '$id_user'";
            $delete_result = mysqli_query($koneksi, $delete_query);

            if ($delete_result) {
                $_SESSION['success_message'] = 'Berhasil menghapus transaksi Kas Masuk sebesar **' . formatRupiah($data_check['jumlah']) . '** pada tanggal ' . date('d M Y', strtotime($data_check['tanggal'])) . ' untuk ' . htmlspecialchars($nama_mahasiswa) . '.';
                // Redirect untuk refresh dan menghindari resubmission
                header('Location: kelola_kas_mahasiswa.php?id_user=' . $id_user . '&nim=' . $mahasiswa_data['nim']);
                exit;
            } else {
                $error_message = 'Error: Gagal menghapus data transaksi. ' . mysqli_error($koneksi);
            }
        } else {
            $error_message = 'Error: Transaksi tidak ditemukan atau bukan milik mahasiswa ini.';
        }
    }
}


// 3. Ambil semua riwayat kas masuk untuk user ini
$query_riwayat = "SELECT id_kas_masuk, tanggal, jumlah, keterangan 
                  FROM kas_masuk 
                  WHERE id_user = '$id_user' 
                  ORDER BY tanggal DESC, id_kas_masuk DESC";
$result_riwayat = $id_user ? mysqli_query($koneksi, $query_riwayat) : false;

// 4. Hitung total kas yang sudah dibayarkan oleh user ini
$query_total = "SELECT SUM(jumlah) AS total_bayar FROM kas_masuk WHERE id_user = '$id_user'";
$result_total = $id_user ? mysqli_query($koneksi, $query_total) : false;
$data_total = $result_total ? mysqli_fetch_assoc($result_total) : ['total_bayar' => 0];
$total_bayar = $data_total['total_bayar'] ?? 0;


// Tutup koneksi database
mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Kelola Kas Mahasiswa</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <nav class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i data-feather="dollar-sign" class="text-indigo-600 h-6 w-6"></i>
                        <span class="ml-2 text-xl font-bold text-indigo-600">Ukas Campus</span>
                    </div>
                </div>
                <div class="hidden md:ml-6 md:flex md:items-center md:space-x-8">
                    <a href="dashboard.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="data_mahasiswa.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Kelola Mahasiswa
                    </a>
                    <a href="input_kas_masuk.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Input Kas Masuk
                    </a>
                    <a href="laporan_kas_keluar.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Kas Keluar
                    </a>
                    <a href="laporan_status_iuran.php" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Lapor. Status Iuran
                    </a>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-700 text-sm font-medium mr-4 hidden sm:block">Hai, <?php echo htmlspecialchars($nama_admin); ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i data-feather="log-out" class="w-4 h-4 inline mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Kelola Transaksi Kas Mahasiswa</h1>
            <a href="laporan_status_iuran.php" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                <i data-feather="arrow-left" class="w-4 h-4 inline mr-1"></i> Kembali ke Status Iuran
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md relative mb-4" role="alert">
                <strong class="font-bold">Sukses!</strong>
                <span class="block sm:inline"> <?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md relative mb-4" role="alert">
                <strong class="font-bold">Gagal!</strong>
                <span class="block sm:inline"> <?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($id_user && $mahasiswa_data): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-l-4 border-indigo-500 card-hover">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Data Mahasiswa: <?php echo htmlspecialchars($nama_mahasiswa); ?> (NIM: <?php echo htmlspecialchars($mahasiswa_data['nim']); ?>)</h2>
                <div class="flex justify-between items-center mt-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Kas Masuk Seluruhnya</p>
                        <p class="text-3xl font-extrabold text-indigo-600 mt-1">**<?php echo formatRupiah($total_bayar); ?>**</p>
                    </div>
                    <div>
                        <a href="input_kas_masuk.php?id_user=<?php echo $id_user; ?>" class="bg-green-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-600 transition shadow-md">
                            <i data-feather="plus-circle" class="w-4 h-4 inline mr-1"></i> Tambah Transaksi Baru
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Riwayat Transaksi Kas Masuk Individual</h2>
                    <p class="text-sm text-gray-500">Gunakan fitur **Hapus** untuk membatalkan atau mengoreksi kesalahan input jumlah. Transaksi yang dihapus akan mengurangi total kas masuk.</p>
                </div>
                
                <div class="overflow-x-auto scrollbar-hide">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Transaksi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            if ($result_riwayat && mysqli_num_rows($result_riwayat) > 0) {
                                while($data = mysqli_fetch_assoc($result_riwayat)) {
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($data['id_kas_masuk']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($data['keterangan']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right text-green-600"><?php echo formatRupiah($data['jumlah']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form method="POST" action="kelola_kas_mahasiswa.php?id_user=<?php echo $id_user; ?>&nim=<?php echo htmlspecialchars($mahasiswa_data['nim']); ?>" class="inline">
                                        <input type="hidden" name="id_kas_masuk" value="<?php echo $data['id_kas_masuk']; ?>">
                                        <input type="hidden" name="hapus_kas_masuk" value="1">
                                        <button type="submit" onclick="return confirm('Yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan. Menghapus transaksi akan mengurangi total iuran yang sudah dibayarkan.')" class="text-red-600 hover:text-red-900">
                                            <i data-feather="trash-2" class="w-4 h-4 inline mr-1"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                            ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Mahasiswa ini belum memiliki riwayat pembayaran kas yang tercatat.
                                </td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p class="text-center text-base text-gray-400">
                &copy; 2025 Ukas Campus. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // Initialize Feather Icons
        feather.replace();
    </script>
</body>
</html>
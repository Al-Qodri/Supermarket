<?php
session_start();
require_once '../config/koneksi.php'; // Memastikan koneksi ke database tersedia

// Pengecekan sesi: Pastikan user sudah login dan role-nya 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php'); // Redirect jika tidak terautentikasi atau bukan admin
    exit;
}

$nama_admin = $_SESSION['nama'];
$success_message = '';
$error_message = '';
$today_date = date('Y-m-d'); // Tanggal hari ini untuk default input

// Ambil pesan dari sesi jika ada (dari proses hapus sebelumnya)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// Fungsi format Rupiah (diambil dari dashboard.php)
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// ====================================================================
// LOGIKA UNTUK INPUT KAS KELUAR BARU
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['catat_keluar'])) {
    // 1. Ambil dan bersihkan input
    $tanggal = cleanInput($koneksi, $_POST['tanggal']);
    $jumlah = cleanInput($koneksi, $_POST['jumlah']);
    $keterangan = cleanInput($koneksi, $_POST['keterangan']);
    // id_user diisi dengan id_user admin yang mencatat
    $id_user_admin = $_SESSION['id_user']; 

    // 2. Validasi
    if (empty($tanggal) || empty($jumlah) || !is_numeric($jumlah) || $jumlah <= 0) {
        $error_message = 'Gagal! Semua field wajib diisi, dan Jumlah harus berupa angka positif.';
    } else {
        // 3. Query INSERT data kas keluar
        $insert_query = "INSERT INTO kas_keluar (id_user_admin, tanggal, jumlah, keterangan) 
                         VALUES ('$id_user_admin', '$tanggal', '$jumlah', '$keterangan')";
        
        $insert_result = mysqli_query($koneksi, $insert_query);

        if ($insert_result) {
            $formatted_jumlah = formatRupiah($jumlah);
            $success_message = 'Berhasil! Kas Keluar sebesar **' . $formatted_jumlah . '** berhasil dicatat.';
            
            // Opsional: Kosongkan field form setelah sukses kecuali tanggal
            unset($_POST['jumlah']); 
            unset($_POST['keterangan']); 
        } else {
            $error_message = 'Error: Gagal menyimpan data ke database. ' . mysqli_error($koneksi);
        }
    }
}


// ====================================================================
// LOGIKA UNTUK HAPUS KAS KELUAR
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus_keluar'])) {
    $id_kas_keluar_to_delete = cleanInput($koneksi, $_POST['id_kas_keluar']);
    
    // Ambil data sebelum menghapus untuk pesan sukses
    $query_check = "SELECT jumlah, keterangan FROM kas_keluar WHERE id_kas_keluar = '$id_kas_keluar_to_delete'";
    $result_check = mysqli_query($koneksi, $query_check);
    $data_check = mysqli_fetch_assoc($result_check);
    
    if ($data_check) {
        $delete_query = "DELETE FROM kas_keluar WHERE id_kas_keluar = '$id_kas_keluar_to_delete'";
        $delete_result = mysqli_query($koneksi, $delete_query);

        if ($delete_result) {
            $_SESSION['success_message'] = 'Berhasil menghapus transaksi Kas Keluar sebesar **' . formatRupiah($data_check['jumlah']) . '** dengan keterangan: ' . htmlspecialchars($data_check['keterangan']) . '.';
        } else {
            $_SESSION['error_message'] = 'Error: Gagal menghapus data transaksi. ' . mysqli_error($koneksi);
        }
    } else {
        $_SESSION['error_message'] = 'Error: Transaksi kas keluar tidak ditemukan.';
    }

    // Redirect untuk refresh halaman dan menghindari resubmission
    header('Location: laporan_kas_keluar.php');
    exit;
}


// ====================================================================
// LOGIKA UNTUK LAPORAN (REPORT) KAS KELUAR
// ====================================================================

// Query untuk mengambil semua data kas keluar, digabungkan dengan nama admin yang mencatat
$query_kas_keluar = "SELECT 
                        kk.id_kas_keluar, 
                        kk.tanggal, 
                        kk.jumlah, 
                        kk.keterangan, 
                        u.nama AS nama_admin_pencatat
                     FROM kas_keluar kk
                     JOIN users u ON kk.id_user_admin = u.id_user
                     ORDER BY kk.tanggal DESC, kk.id_kas_keluar DESC";
$result_kas_keluar = mysqli_query($koneksi, $query_kas_keluar);

// Hitung total kas keluar untuk ringkasan
$query_total = "SELECT SUM(jumlah) AS total_keluar FROM kas_keluar";
$result_total = mysqli_query($koneksi, $query_total);
$total_keluar = mysqli_fetch_assoc($result_total)['total_keluar'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Laporan & Catat Kas Keluar</title>
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
                    <a href="laporan_kas_keluar.php" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Kas Keluar
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
            <h1 class="text-3xl font-bold text-gray-800">Catat & Lapor Kas Keluar</h1>
            <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                <i data-feather="arrow-left" class="w-4 h-4 inline mr-1"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-l-4 border-red-500 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Pengeluaran Kas Sepanjang Waktu</p>
                    <p class="text-3xl font-extrabold text-red-600 mt-1">**<?php echo formatRupiah($total_keluar); ?>**</p>
                </div>
                <div class="p-4 rounded-full bg-red-100 text-red-600 flex-shrink-0">
                    <i data-feather="bar-chart-2" class="w-8 h-8"></i>
                </div>
            </div>
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

        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i data-feather="minus-circle" class="w-5 h-5 inline mr-2 text-red-500"></i> Catat Pengeluaran Baru
            </h2>
            <form method="POST" action="laporan_kas_keluar.php">
                <input type="hidden" name="catat_keluar" value="1">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Keluar</label>
                        <input type="date" id="tanggal" name="tanggal" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               value="<?php echo isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : $today_date; ?>">
                    </div>

                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rupiah)</label>
                        <input type="number" id="jumlah" name="jumlah" required min="1000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               placeholder="Contoh: 150000" 
                               value="<?php echo isset($_POST['jumlah']) ? htmlspecialchars($_POST['jumlah']) : ''; ?>">
                    </div>
                    
                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <input type="text" id="keterangan" name="keterangan" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               placeholder="Contoh: Beli alat tulis"
                               value="<?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?>">
                    </div>
                </div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-base font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition mt-6">
                    <i data-feather="upload" class="w-5 h-5 inline mr-2"></i> Simpan Pengeluaran
                </button>
            </form>
        </div>


        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Riwayat Semua Kas Keluar</h2>
                <p class="text-sm text-gray-500">Total: <?php echo mysqli_num_rows($result_kas_keluar); ?> Transaksi</p>
                <p class="mt-2 text-xs text-red-500">Gunakan tombol Hapus untuk membatalkan atau mengoreksi transaksi kas keluar yang salah.</p>
            </div>
            
            <div class="overflow-x-auto scrollbar-hide">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pencatat</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($result_kas_keluar) > 0) {
                            while($data = mysqli_fetch_assoc($result_kas_keluar)) {
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($data['keterangan']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right text-red-600"><?php echo formatRupiah($data['jumlah']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($data['nama_admin_pencatat']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" action="laporan_kas_keluar.php" class="inline">
                                    <input type="hidden" name="id_kas_keluar" value="<?php echo $data['id_kas_keluar']; ?>">
                                    <input type="hidden" name="hapus_keluar" value="1">
                                    <button type="submit" onclick="return confirm('Yakin ingin menghapus transaksi kas keluar ini? Tindakan ini tidak dapat dibatalkan.')" class="text-red-600 hover:text-red-900">
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
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Belum ada data pengeluaran kas yang tercatat.
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <?php 
            // Tutup koneksi database
            mysqli_close($koneksi);
            ?>

        </div>
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
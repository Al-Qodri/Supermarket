<?php
session_start();
require_once '../config/koneksi.php';

// Pengecekan sesi: Pastikan user sudah login dan role-nya 'mahasiswa'
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'mahasiswa') {
    header('Location: ../index.php'); // Redirect jika tidak terautentikasi atau bukan mahasiswa
    exit;
}

$id_user = $_SESSION['id_user'];
$nama_mahasiswa = $_SESSION['nama'];

// Fungsi format Rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// 1. Ambil semua riwayat kas masuk untuk user ini
$query_riwayat = "SELECT id_kas_masuk, tanggal, jumlah, keterangan 
                  FROM kas_masuk 
                  WHERE id_user = '$id_user' 
                  ORDER BY tanggal DESC, id_kas_masuk DESC";
$result_riwayat = mysqli_query($koneksi, $query_riwayat);

// 2. Hitung total kas yang sudah dibayarkan oleh user ini
$query_total = "SELECT SUM(jumlah) AS total_bayar FROM kas_masuk WHERE id_user = '$id_user'";
$result_total = mysqli_query($koneksi, $query_total);
$data_total = mysqli_fetch_assoc($result_total);
$total_bayar = $data_total['total_bayar'] ?? 0;

// Tutup koneksi database
mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Riwayat Pembayaran</title>
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
                    <a href="cek_pembayaran.php" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Riwayat Pembayaran
                    </a>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-700 text-sm font-medium mr-4 hidden sm:block">Hai, <?php echo htmlspecialchars($nama_mahasiswa); ?></span>
                    <a href="../logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <i data-feather="log-out" class="w-4 h-4 inline mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Riwayat Pembayaran Kas Anda</h1>
            <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                <i data-feather="arrow-left" class="w-4 h-4 inline mr-1"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-l-4 border-indigo-500 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Kas Masuk yang sudah Anda Bayarkan</p>
                    <p class="text-3xl font-extrabold text-indigo-600 mt-1">**<?php echo formatRupiah($total_bayar); ?>**</p>
                </div>
                <div class="p-4 rounded-full bg-indigo-100 text-indigo-600 flex-shrink-0">
                    <i data-feather="check-circle" class="w-8 h-8"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Semua Transaksi Kas Masuk Anda</h2>
                <p class="text-sm text-gray-500">Total: <?php echo mysqli_num_rows($result_riwayat); ?> Transaksi</p>
            </div>
            
            <div class="overflow-x-auto scrollbar-hide">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($result_riwayat) > 0) {
                            while($data = mysqli_fetch_assoc($result_riwayat)) {
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo date('d M Y', strtotime($data['tanggal'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($data['keterangan']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right text-green-600"><?php echo formatRupiah($data['jumlah']); ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                Belum ada riwayat pembayaran kas yang tercatat.
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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
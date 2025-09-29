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

// Ambil total kas yang sudah dibayarkan oleh user ini
$query_pembayaran = "SELECT SUM(jumlah) AS total_bayar FROM kas_masuk WHERE id_user = '$id_user'";
$result_pembayaran = mysqli_query($koneksi, $query_pembayaran);
$data_pembayaran = mysqli_fetch_assoc($result_pembayaran);
$total_bayar = $data_pembayaran['total_bayar'] ?? 0;

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// ====================================================================
// START: DATA UNTUK RIWAYAT PEMBAYARAN TERBARU
// ====================================================================

// Query untuk mengambil 5 data kas masuk terakhir oleh mahasiswa ini
$query_riwayat = "SELECT tanggal, jumlah, keterangan FROM kas_masuk WHERE id_user = '$id_user' ORDER BY tanggal DESC, id_kas_masuk DESC LIMIT 5";
$result_riwayat = mysqli_query($koneksi, $query_riwayat);
$latest_payments = mysqli_fetch_all($result_riwayat, MYSQLI_ASSOC);

// ====================================================================
// END: DATA UNTUK RIWAYAT PEMBAYARAN TERBARU
// ====================================================================

// Tutup koneksi database
mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Dashboard Mahasiswa</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
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
<body class="bg-gray-50 font-sans antialiased" id="vanta-bg">
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
                    <a href="dashboard.php" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="cek_pembayaran.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
        <div class="gradient-bg rounded-xl p-6 text-white mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold mb-2">Halo, <?php echo htmlspecialchars($nama_mahasiswa); ?>!</h1>
                    <p class="opacity-90">NIM: <?php echo $_SESSION['nim']; ?> | Kelola iuran kas Anda dengan mudah.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="cek_pembayaran.php" class="bg-white text-indigo-600 px-6 py-2 rounded-lg font-medium hover:bg-opacity-90 transition">
                        <i data-feather="search" class="w-4 h-4 inline mr-1"></i> Cek Riwayat Pembayaran
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 md:col-span-2 border-l-4 border-indigo-600 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Iuran Kas yang sudah Anda bayarkan:</p>
                        <p class="text-4xl font-extrabold text-indigo-600 mt-1">**<?php echo formatRupiah($total_bayar); ?>**</p>
                    </div>
                    <div class="p-4 rounded-full bg-indigo-100 text-indigo-600 flex-shrink-0">
                        <i data-feather="award" class="w-8 h-8"></i>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-4">*Informasi tunggakan harus dihitung berdasarkan total iuran wajib yang ditetapkan Admin.</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i data-feather="clock" class="w-6 h-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Iuran Wajib / Bulan</p>
                        <p class="text-2xl font-semibold text-gray-900">Rp 4.000 (P)</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8 card-hover">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Riwayat 5 Pembayaran Terakhir Anda</h2>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Total: <?php echo count($latest_payments); ?> Transaksi Terakhir</span>
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
                        if (count($latest_payments) > 0) {
                            foreach ($latest_payments as $payment) {
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo date('d M Y', strtotime($payment['tanggal'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate"><?php echo htmlspecialchars($payment['keterangan']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right text-green-600"><?php echo formatRupiah($payment['jumlah']); ?></td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                Belum ada riwayat pembayaran kas yang tercatat untuk Anda.
                            </td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 text-center">
                <a href="cek_pembayaran.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Lihat Semua Riwayat Pembayaran</a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Aksi Cepat</h2>
            </div>
            <div class="divide-y divide-gray-200">
                <a href="cek_pembayaran.php" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition block">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-green-100 text-green-600">
                            <i data-feather="list" class="w-4 h-4"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Lihat Riwayat Pembayaran Saya</p>
                            <p class="text-sm text-gray-500">Cek kapan saja Anda menyetor kas.</p>
                        </div>
                    </div>
                    <i data-feather="chevron-right" class="w-5 h-5 text-gray-400"></i>
                </a>
                <a href="../logout.php" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition block">
                    <div class="flex items-center">
                        <div class="p-2 rounded-full bg-red-100 text-red-600">
                            <i data-feather="power" class="w-4 h-4"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Logout</p>
                            <p class="text-sm text-gray-500">Keluar dari sistem.</p>
                        </div>
                    </div>
                    <i data-feather="chevron-right" class="w-5 h-5 text-gray-400"></i>
                </a>
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
        
        // Initialize Vanta.js background
        VANTA.NET({
            el: "#vanta-bg",
            mouseControls: true,
            touchControls: true,
            gyroControls: false,
            minHeight: 200.00,
            minWidth: 200.00,
            scale: 1.00,
            scaleMobile: 1.00,
            color: 0x667eea,
            backgroundColor: 0xf7fafc,
            points: 12.00,
            maxDistance: 20.00,
            spacing: 15.00
        });
    </script>
</body>
</html>

<?php
session_start();
require_once '../config/koneksi.php'; // Memastikan koneksi ke database tersedia

// Pengecekan sesi: Pastikan user sudah login dan role-nya 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php'); // Redirect jika tidak terautentikasi atau bukan admin
    exit;
}

$nama_admin = $_SESSION['nama'];

// Fungsi format Rupiah (diambil dari dashboard.php)
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// ====================================================================
// LOGIKA REAL-TIME STATUS PEMBAYARAN IURAN WAJIB
// ====================================================================

$required_fee = 4000; // ASUMSI Iuran Wajib per Mahasiswa per Bulan (sesuai dashboard.php)
$current_year = date('Y');
$current_month = date('m');
$payment_status_label = "Bulan " . date('F Y'); // Contoh: September 2025

// 1. Query untuk mengambil total pembayaran bulan ini untuk SEMUA mahasiswa
$query_monthly_payments = "
    SELECT 
        u.id_user, 
        u.nim, 
        u.nama, 
        COALESCE(SUM(km.jumlah), 0) AS total_bayar_bulan_ini
    FROM users u
    LEFT JOIN kas_masuk km ON u.id_user = km.id_user 
        AND YEAR(km.tanggal) = '$current_year' 
        AND MONTH(km.tanggal) = '$current_month'
    WHERE u.role = 'mahasiswa'
    GROUP BY u.id_user, u.nim, u.nama
    ORDER BY u.nama ASC
";
$result_payments = mysqli_query($koneksi, $query_monthly_payments);
$students_status = mysqli_fetch_all($result_payments, MYSQLI_ASSOC);

// 2. Tentukan status 'Lunas' atau 'Menunggak'
$paid_count = 0;
$unpaid_count = 0;

foreach ($students_status as &$student) {
    if ($student['total_bayar_bulan_ini'] >= $required_fee) {
        $student['status'] = 'Lunas';
        $paid_count++;
    } else {
        $student['status'] = 'Menunggak';
        $unpaid_count++;
    }
}

// 3. Hitung total mahasiswa
$total_students = count($students_status);

// Tutup koneksi database
mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Laporan Status Iuran</title>
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
            <h1 class="text-3xl font-bold text-gray-800">Laporan Status Iuran Mahasiswa</h1>
            <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                <i data-feather="arrow-left" class="w-4 h-4 inline mr-1"></i> Kembali ke Dashboard
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-indigo-500 card-hover">
                <p class="text-sm font-medium text-gray-500">Total Mahasiswa</p>
                <p class="text-3xl font-extrabold text-indigo-600 mt-1"><?php echo $total_students; ?> Orang</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 card-hover">
                <p class="text-sm font-medium text-gray-500">Mahasiswa **Lunas** (<?php echo $payment_status_label; ?>)</p>
                <p class="text-3xl font-extrabold text-green-600 mt-1"><?php echo $paid_count; ?> Orang</p>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500 card-hover">
                <p class="text-sm font-medium text-gray-500">Mahasiswa **Menunggak** (<?php echo $payment_status_label; ?>)</p>
                <p class="text-3xl font-extrabold text-red-600 mt-1"><?php echo $unpaid_count; ?> Orang</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Daftar Status Pembayaran Iuran Wajib</h2>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Periode: **<?php echo $payment_status_label; ?>** (Wajib: <?php echo formatRupiah($required_fee); ?>)</span>
            </div>
            
            <div class="overflow-x-auto scrollbar-hide">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIM</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Iuran</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Dibayar Bulan Ini</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $no = 1;
                        if ($total_students > 0) {
                            // Sort by status to show Menunggak first
                            usort($students_status, function($a, $b) {
                                if ($a['status'] === $b['status']) { return 0; }
                                return ($a['status'] === 'Menunggak') ? -1 : 1;
                            });

                            foreach($students_status as $student) {
                                $status = $student['status'];
                                $bg_class = ($status == 'Lunas') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $no++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($student['nim']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($student['nama']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $bg_class; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-right text-gray-700">
                                <?php echo formatRupiah($student['total_bayar_bulan_ini']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="kelola_kas_mahasiswa.php?id_user=<?php echo $student['id_user']; ?>&nim=<?php echo htmlspecialchars($student['nim']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                    <i data-feather="edit" class="w-4 h-4 inline mr-1"></i> Kelola Transaksi
                                </a>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Belum ada data mahasiswa yang terdaftar.
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
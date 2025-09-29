<?php
session_start();
require_once '../config/koneksi.php';

// Pengecekan sesi: Pastikan user sudah login dan role-nya 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php'); // Redirect jika tidak terautentikasi atau bukan admin
    exit;
}

$nama_admin = $_SESSION['nama'];

// 1. Ambil Total Kas Masuk
$query_masuk = "SELECT SUM(jumlah) AS total_masuk FROM kas_masuk";
$result_masuk = mysqli_query($koneksi, $query_masuk);
$data_masuk = mysqli_fetch_assoc($result_masuk);
$total_masuk = $data_masuk['total_masuk'] ?? 0;

// 2. Ambil Total Kas Keluar
$query_keluar = "SELECT SUM(jumlah) AS total_keluar FROM kas_keluar";
$result_keluar = mysqli_query($koneksi, $query_keluar);
$data_keluar = mysqli_fetch_assoc($result_keluar);
$total_keluar = $data_keluar['total_keluar'] ?? 0;

// 3. Hitung Saldo Akhir
$saldo_akhir = $total_masuk - $total_keluar;

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// ====================================================================
// START: DATA CHART REAL-TIME
// ====================================================================

// --- DATA FOR MONTHLY OVERVIEW CHART (Last 6 Months) ---
$months = [];
$kas_masuk_data = [];
$kas_keluar_data = [];

// Get the last 6 months' labels and data
for ($i = 5; $i >= 0; $i--) {
    $month_timestamp = strtotime("-$i month");
    $month_label = date('M Y', $month_timestamp); // e.g., 'Sep 2025'
    $months[] = $month_label;

    $year = date('Y', $month_timestamp);
    $month = date('m', $month_timestamp);

    // Query Kas Masuk
    $query_monthly_masuk = "SELECT SUM(jumlah) AS total FROM kas_masuk WHERE YEAR(tanggal) = '$year' AND MONTH(tanggal) = '$month'";
    $result_monthly_masuk = mysqli_query($koneksi, $query_monthly_masuk);
    $total_masuk_month = mysqli_fetch_assoc($result_monthly_masuk)['total'] ?? 0;
    $kas_masuk_data[] = (int)$total_masuk_month;

    // Query Kas Keluar
    $query_monthly_keluar = "SELECT SUM(jumlah) AS total FROM kas_keluar WHERE YEAR(tanggal) = '$year' AND MONTH(tanggal) = '$month'";
    $result_monthly_keluar = mysqli_query($koneksi, $query_monthly_keluar);
    $total_keluar_month = mysqli_fetch_assoc($result_monthly_keluar)['total'] ?? 0;
    $kas_keluar_data[] = (int)$total_keluar_month;
}

$months_json = json_encode($months);
$kas_masuk_data_json = json_encode($kas_masuk_data);
$kas_keluar_data_json = json_encode($kas_keluar_data);

// --- DATA FOR PAYMENT STATUS CHART & TABLE (Current Month) ---
$required_fee = 4000; // ASUMSI Iuran Wajib per Mahasiswa per Bulan

$current_year = date('Y');
$current_month = date('m');

// 1. Get total payment this month for all students
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

$paid_count = 0;
$unpaid_count = 0;
$latest_statuses = []; 

foreach ($students_status as &$student) {
    if ($student['total_bayar_bulan_ini'] >= $required_fee) {
        $student['status'] = 'Lunas';
        $paid_count++;
    } else {
        $student['status'] = 'Menunggak';
        $unpaid_count++;
    }
}

// Sort by status to show Menunggak first for the table
usort($students_status, function($a, $b) {
    if ($a['status'] === $b['status']) {
        return 0; // maintain order for same status
    }
    // 'Menunggak' comes before 'Lunas'
    return ($a['status'] === 'Menunggak') ? -1 : 1;
});
// Take a few for the dashboard table (e.g., first 5 or less)
$latest_statuses = array_slice($students_status, 0, 5);

$payment_data_json = json_encode([$paid_count, $unpaid_count]);

// Total number of students for the chart title/label
$total_students = count($students_status);
// Label for the payment status section
$payment_status_label = "Bulan " . date('M Y'); 

// ====================================================================
// END: DATA CHART REAL-TIME
// ====================================================================

// Close the connection before the HTML output
mysqli_close($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="data_mahasiswa.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Kelola Mahasiswa
                    </a>
                    <a href="input_kas_masuk.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Input Kas Masuk
                    </a>
                    <a href="laporan_kas_keluar.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Kas Keluar
                    </a>
                    <a href="laporan_status_iuran.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
        <div class="gradient-bg rounded-xl p-6 text-white mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold mb-2">Selamat Datang, Admin <?php echo htmlspecialchars($nama_admin); ?>!</h1>
                    <p class="opacity-90">Kelola dana kas kelas Anda secara efisien dan transparan.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="input_kas_masuk.php" class="bg-white text-indigo-600 px-6 py-2 rounded-lg font-medium hover:bg-opacity-90 transition">
                        <i data-feather="plus" class="w-4 h-4 inline mr-1"></i> Input Kas Masuk
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                        <i data-feather="dollar-sign" class="w-6 h-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Saldo Kas Saat Ini</p>
                        <p class="text-2xl font-semibold text-gray-900">**<?php echo formatRupiah($saldo_akhir); ?>**</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i data-feather="trending-up" class="w-6 h-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Kas Masuk</p>
                        <p class="text-2xl font-semibold text-gray-900">**<?php echo formatRupiah($total_masuk); ?>**</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i data-feather="trending-down" class="w-6 h-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Kas Keluar</p>
                        <p class="text-2xl font-semibold text-gray-900">**<?php echo formatRupiah($total_keluar); ?>**</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Monthly Overview (6 Bulan Terakhir)</h2>
                    </div>
                <canvas id="monthlyChart" height="250"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Status Pembayaran Iuran Wajib</h2>
                    <span class="text-sm font-medium text-indigo-600"><?php echo $payment_status_label; ?></span>
                </div>
                <canvas id="paymentChart" height="250"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Menu Administrasi</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 divide-y divide-gray-200 sm:divide-y-0 sm:divide-x">
                <a href="input_kas_masuk.php" class="p-6 hover:bg-gray-50 transition block">
                    <i data-feather="upload" class="w-6 h-6 text-indigo-600 mb-2"></i>
                    <p class="text-base font-medium text-gray-900">Input Kas Masuk (Iuran)</p>
                    <p class="text-sm text-gray-500">Catat setiap pemasukan iuran kas.</p>
                </a>
                <a href="laporan_kas_keluar.php" class="p-6 hover:bg-gray-50 transition block">
                    <i data-feather="download" class="w-6 h-6 text-red-600 mb-2"></i>
                    <p class="text-base font-medium text-gray-900">Catat & Lapor Kas Keluar</p>
                    <p class="text-sm text-gray-500">Kelola pengeluaran dan laporan kas.</p>
                </a>
                <a href="data_mahasiswa.php" class="p-6 hover:bg-gray-50 transition block">
                    <i data-feather="users" class="w-6 h-6 text-green-600 mb-2"></i>
                    <p class="text-base font-medium text-gray-900">Kelola Data Mahasiswa</p>
                    <p class="text-sm text-gray-500">Lihat dan atur data anggota kelas.</p>
                </a>
                <a href="laporan_status_iuran.php" class="p-6 hover:bg-gray-50 transition block">
                    <i data-feather="file-text" class="w-6 h-6 text-purple-600 mb-2"></i>
                    <p class="text-base font-medium text-gray-900">Laporan Status Iuran</p>
                    <p class="text-sm text-gray-500">Cek status bayar/belum bayar real-time.</p>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Status Pembayaran Iuran Wajib (<?php echo $payment_status_label; ?>)</h2>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Iuran Wajib: <?php echo formatRupiah($required_fee); ?></span>
            </div>
            <div class="overflow-x-auto scrollbar-hide">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIM</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Iuran</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Dibayar Bulan Ini</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($latest_statuses) > 0): ?>
                            <?php foreach ($latest_statuses as $student): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['nama']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($student['nim']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $status = $student['status'];
                                    $bg_class = ($status == 'Lunas') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $bg_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-gray-700">
                                    <?php echo formatRupiah($student['total_bayar_bulan_ini']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Tidak ada data mahasiswa atau data pembayaran untuk bulan ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 text-center">
                <a href="laporan_status_iuran.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Lihat Seluruh Status Pembayaran (Total: <?php echo $total_students; ?> Mahasiswa)</a>
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
        
        // Initialize Vanta.js background (Runs on a lighter color for the admin page)
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

        // Initialize charts with REAL-TIME data from PHP
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- DATA DARI PHP ---
            const monthlyLabels = <?php echo $months_json; ?>;
            const monthlyMasuk = <?php echo $kas_masuk_data_json; ?>;
            const monthlyKeluar = <?php echo $kas_keluar_data_json; ?>;
            const paymentStatusData = <?php echo $payment_data_json; ?>; // [Paid, Unpaid]

            // Monthly Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: monthlyMasuk,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Pengeluaran',
                            data: monthlyKeluar,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.3,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        // Format number as Rupiah
                                        label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, ticks) {
                                    // Format number on the y-axis
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });

            // Payment Status Chart (Doughnut)
            const paymentCtx = document.getElementById('paymentChart').getContext('2d');
            const paymentChart = new Chart(paymentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Lunas', 'Menunggak'],
                    datasets: [{
                        label: 'Jumlah Mahasiswa',
                        data: paymentStatusData, // [Paid Count, Unpaid Count]
                        backgroundColor: ['#10b981', '#f87171'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const count = context.parsed;
                                    const total = paymentStatusData.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + count + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>

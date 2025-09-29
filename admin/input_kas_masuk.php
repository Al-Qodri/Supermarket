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

// Ambil ID user dari GET (dari halaman kelola) atau POST (dari submisi form sebelumnya)
$id_user_preselected = isset($_GET['id_user']) ? cleanInput($koneksi, $_GET['id_user']) : (isset($_POST['id_user']) ? cleanInput($koneksi, $_POST['id_user']) : '');

// Query untuk mengambil semua data mahasiswa (role='mahasiswa') untuk dropdown
$query_mahasiswa = "SELECT id_user, nim, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
$result_mahasiswa = mysqli_query($koneksi, $query_mahasiswa);
$mahasiswa_list = mysqli_fetch_all($result_mahasiswa, MYSQLI_ASSOC);

// Proses form input kas masuk
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil dan bersihkan input
    $id_user = cleanInput($koneksi, $_POST['id_user']);
    $tanggal = cleanInput($koneksi, $_POST['tanggal']);
    $jumlah = cleanInput($koneksi, $_POST['jumlah']);
    $keterangan = cleanInput($koneksi, $_POST['keterangan']);

    // 2. Validasi
    if (empty($id_user) || empty($tanggal) || empty($jumlah) || !is_numeric($jumlah) || $jumlah <= 0) {
        $error_message = 'Gagal! Semua field wajib diisi dengan benar, dan Jumlah harus lebih dari nol.';
    } else {
        // Ambil nama mahasiswa untuk pesan sukses
        $stmt_nama = mysqli_prepare($koneksi, "SELECT nama FROM users WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_nama, "i", $id_user);
        mysqli_stmt_execute($stmt_nama);
        $result_nama = mysqli_stmt_get_result($stmt_nama);
        $mahasiswa_data = mysqli_fetch_assoc($result_nama);
        $nama_mahasiswa = $mahasiswa_data['nama'] ?? 'Mahasiswa Tidak Dikenal';

        // 3. Query INSERT data kas masuk
        // id_kas_masuk biasanya AUTO_INCREMENT
        $insert_query = "INSERT INTO kas_masuk (id_user, tanggal, jumlah, keterangan) 
                         VALUES (?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($koneksi, $insert_query);
        mysqli_stmt_bind_param($stmt, "isds", $id_user, $tanggal, $jumlah, $keterangan);
        
        $insert_result = mysqli_stmt_execute($stmt);

        if ($insert_result) {
            $formatted_jumlah = "Rp " . number_format($jumlah, 0, ',', '.');
            $success_message = 'Berhasil! Kas Masuk sebesar **' . $formatted_jumlah . '** dari **' . htmlspecialchars($nama_mahasiswa) . '** berhasil dicatat.';
            
            // Opsional: Kosongkan field form setelah sukses kecuali tanggal
            // Tetapkan kembali id_user_preselected untuk menjaga pilihan mahasiswa
            $id_user_preselected = $id_user; 
            unset($_POST['id_user']);
            unset($_POST['jumlah']); 
            $_POST['keterangan'] = 'Iuran Wajib'; 
        } else {
            $error_message = 'Error: Gagal menyimpan data ke database. ' . mysqli_error($koneksi);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Input Kas Masuk</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    <a href="input_kas_masuk.php" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Input Kas Masuk
                    </a>
                    <a href="laporan_kas_keluar.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Input Kas Masuk (Pemasukan)</h1>
            <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                <i data-feather="arrow-left" class="w-4 h-4 inline mr-1"></i> Kembali ke Dashboard
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

        <div class="bg-white rounded-xl shadow-lg p-8">
            <form method="POST" action="input_kas_masuk.php">
                <div class="space-y-6">
                    
                    <div>
                        <label for="id_user" class="block text-sm font-medium text-gray-700 mb-1">Mahasiswa Penyetor Kas</label>
                        <select id="id_user" name="id_user" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition">
                            <option value="">-- Pilih Mahasiswa --</option>
                            <?php foreach ($mahasiswa_list as $mahasiswa): ?>
                                <option value="<?php echo $mahasiswa['id_user']; ?>"
                                    <?php 
                                    // Gunakan id_user_preselected untuk logika pemilihan
                                    echo ($id_user_preselected == $mahasiswa['id_user']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mahasiswa['nama']) . ' (' . htmlspecialchars($mahasiswa['nim']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($mahasiswa_list)): ?>
                            <p class="mt-2 text-sm text-red-500">Tidak ada data mahasiswa. Silakan <a href="tambah_mahasiswa.php" class="font-medium underline">tambah mahasiswa</a> terlebih dahulu.</p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Setor</label>
                        <input type="date" id="tanggal" name="tanggal" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               value="<?php echo isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : $today_date; ?>">
                    </div>

                    <div>
                        <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Kas (Rupiah)</label>
                        <input type="number" id="jumlah" name="jumlah" required min="1000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               placeholder="Contoh: 50000" 
                               value="<?php echo isset($_POST['jumlah']) ? htmlspecialchars($_POST['jumlah']) : ''; ?>">
                    </div>
                    
                    <div>
                        <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="3"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               placeholder="Contoh: Iuran Wajib Bulan November"><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : 'Iuran Wajib'; ?></textarea>
                    </div>


                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-base font-medium text-white gradient-bg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition mt-8">
                        <i data-feather="save" class="w-5 h-5 inline mr-2"></i> Catat Kas Masuk
                    </button>
                </div>
            </form>
        </div>
        <?php 
        // Tutup koneksi database
        mysqli_close($koneksi); 
        ?>
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
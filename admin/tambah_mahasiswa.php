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

// Proses form tambah mahasiswa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil dan bersihkan input
    $nim = cleanInput($koneksi, $_POST['nim']);
    $nama = cleanInput($koneksi, $_POST['nama']);
    // Catatan: Fungsi hashPassword di koneksi.php saat ini mengembalikan plain text
    $password_plain = cleanInput($koneksi, $_POST['password']);
    $password_hashed = hashPassword($password_plain); // Gunakan fungsi yang ada di koneksi.php
    $role = 'mahasiswa';

    // 2. Cek apakah NIM sudah ada di database
    $check_query = "SELECT nim FROM users WHERE nim = '$nim' LIMIT 1";
    $check_result = mysqli_query($koneksi, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $error_message = 'Gagal! NIM **' . htmlspecialchars($nim) . '** sudah terdaftar.';
    } else {
        // 3. Query INSERT data mahasiswa baru
        // Kolom id_user biasanya AUTO_INCREMENT, jadi tidak perlu di-insert
        $insert_query = "INSERT INTO users (nim, nama, password, role) 
                         VALUES ('$nim', '$nama', '$password_hashed', '$role')";
        
        $insert_result = mysqli_query($koneksi, $insert_query);

        if ($insert_result) {
            $success_message = 'Berhasil! Data mahasiswa **' . htmlspecialchars($nama) . '** dengan NIM **' . htmlspecialchars($nim) . '** berhasil ditambahkan.';
            
            // Opsional: Kosongkan field form setelah sukses
            $_POST = array(); 
        } else {
            $error_message = 'Error: Gagal menyimpan data ke database. ' . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Tambah Mahasiswa</title>
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
                    <a href="data_mahasiswa.php" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Kelola Mahasiswa
                    </a>
                    <a href="input_kas_masuk.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
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
            <h1 class="text-3xl font-bold text-gray-800">Tambah Mahasiswa Baru</h1>
            <a href="data_mahasiswa.php" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                <i data-feather="arrow-left" class="w-4 h-4 inline mr-1"></i> Kembali ke Data Mahasiswa
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
            <form method="POST" action="tambah_mahasiswa.php">
                <div class="space-y-6">
                    
                    <div>
                        <label for="nim" class="block text-sm font-medium text-gray-700 mb-1">NIM (Nomor Induk Mahasiswa)</label>
                        <input type="text" id="nim" name="nim" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               placeholder="Contoh: 12345678" 
                               value="<?php echo isset($_POST['nim']) ? htmlspecialchars($_POST['nim']) : ''; ?>">
                    </div>

                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               placeholder="Masukkan nama lengkap mahasiswa" 
                               value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Awal (Default)</label>
                        <input type="password" id="password" name="password" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                               placeholder="Masukkan password awal untuk login mahasiswa">
                        <p class="mt-2 text-xs text-gray-500">Password akan disimpan sebagai plain text sesuai konfigurasi `koneksi.php`.</p>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-base font-medium text-white gradient-bg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition mt-8">
                        <i data-feather="save" class="w-5 h-5 inline mr-2"></i> Simpan Data Mahasiswa
                    </button>
                </div>
            </form>
        </div>
        <?php mysqli_close($koneksi); ?>
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
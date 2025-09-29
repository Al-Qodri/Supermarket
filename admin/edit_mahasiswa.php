<?php
session_start();
require_once '../config/koneksi.php';

// Pengecekan sesi: Pastikan user sudah login dan role-nya 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$nama_admin = $_SESSION['nama'];
$success_message = '';
$error_message = '';
$data_mahasiswa = null;
$id_user = isset($_GET['id']) ? cleanInput($koneksi, $_GET['id']) : null;

// ====================================================================
// A. LOGIKA UNTUK MEMPERBARUI DATA (POST Request)
// ====================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_user'])) {
    // 1. Ambil dan bersihkan input
    $id_user_update = cleanInput($koneksi, $_POST['id_user']);
    $nim = cleanInput($koneksi, $_POST['nim']);
    $nama = cleanInput($koneksi, $_POST['nama']);
    $password_plain = cleanInput($koneksi, $_POST['password']);
    
    $update_fields = array();

    // 2. Cek apakah NIM sudah ada di database (kecuali untuk user yang sedang diedit)
    $check_query = "SELECT nim FROM users WHERE nim = '$nim' AND id_user != '$id_user_update' LIMIT 1";
    $check_result = mysqli_query($koneksi, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $error_message = 'Gagal! NIM **' . htmlspecialchars($nim) . '** sudah terdaftar pada mahasiswa lain.';
    } else {
        // Tambahkan field yang akan diupdate
        $update_fields[] = "nim = '$nim'";
        $update_fields[] = "nama = '$nama'";

        // Cek jika password diisi, maka update password
        if (!empty($password_plain)) {
            $password_hashed = hashPassword($password_plain); // Menggunakan fungsi hashPassword (plain text)
            $update_fields[] = "password = '$password_hashed'";
        }

        // 3. Query UPDATE data mahasiswa
        $update_query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id_user = '$id_user_update' AND role = 'mahasiswa'";
        
        $update_result = mysqli_query($koneksi, $update_query);

        if ($update_result) {
            // Update berhasil, refresh data admin jika dia mengedit dirinya sendiri (walaupun ini role mahasiswa)
            if ($_SESSION['id_user'] == $id_user_update) {
                 $_SESSION['nama'] = $nama;
            }
            
            $success_message = 'Berhasil! Data mahasiswa **' . htmlspecialchars($nama) . '** dengan NIM **' . htmlspecialchars($nim) . '** berhasil diperbarui.';
            // Set ID untuk mode GET agar form menampilkan data yang baru diupdate
            $id_user = $id_user_update; 
        } else {
            $error_message = 'Error: Gagal menyimpan data ke database. ' . mysqli_error($koneksi);
        }
    }
} 
// ====================================================================
// B. LOGIKA UNTUK MENGAMBIL DATA LAMA (GET Request atau setelah POST GAGAL/SUKSES)
// ====================================================================

if ($id_user) {
    $fetch_query = "SELECT id_user, nim, nama, password FROM users WHERE id_user = '$id_user' AND role = 'mahasiswa' LIMIT 1";
    $fetch_result = mysqli_query($koneksi, $fetch_query);

    if (mysqli_num_rows($fetch_result) > 0) {
        $data_mahasiswa = mysqli_fetch_assoc($fetch_result);
        
        // Gunakan data dari POST jika ada error agar input user tidak hilang
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $error_message) {
            $data_mahasiswa['nim'] = $_POST['nim'];
            $data_mahasiswa['nama'] = $_POST['nama'];
        }
    } else {
        $error_message = 'Data mahasiswa tidak ditemukan atau ID tidak valid.';
        $id_user = null;
    }
} else {
    $error_message = 'Parameter ID mahasiswa tidak ditemukan.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Edit Mahasiswa</title>
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
            <h1 class="text-3xl font-bold text-gray-800">Edit Data Mahasiswa</h1>
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

        <?php if ($data_mahasiswa): ?>
            <div class="bg-white rounded-xl shadow-lg p-8">
                <form method="POST" action="edit_mahasiswa.php?id=<?php echo htmlspecialchars($id_user); ?>">
                    <input type="hidden" name="id_user" value="<?php echo htmlspecialchars($data_mahasiswa['id_user']); ?>">
                    <div class="space-y-6">
                        
                        <div>
                            <label for="nim" class="block text-sm font-medium text-gray-700 mb-1">NIM (Nomor Induk Mahasiswa)</label>
                            <input type="text" id="nim" name="nim" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                                placeholder="Contoh: 12345678" 
                                value="<?php echo htmlspecialchars($data_mahasiswa['nim']); ?>">
                        </div>

                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                                placeholder="Masukkan nama lengkap mahasiswa" 
                                value="<?php echo htmlspecialchars($data_mahasiswa['nama']); ?>">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru (Kosongkan jika tidak ingin diubah)</label>
                            <input type="password" id="password" name="password" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" 
                                placeholder="Masukkan password baru">
                            <p class="mt-2 text-xs text-gray-500">Saat ini, password akan disimpan sebagai plain text sesuai konfigurasi `koneksi.php`.</p>
                        </div>

                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-base font-medium text-white gradient-bg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition mt-8">
                            <i data-feather="save" class="w-5 h-5 inline mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
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
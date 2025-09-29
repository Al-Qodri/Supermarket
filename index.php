<?php
session_start();
// PERUBAHAN UTAMA: Bypass Login untuk akses langsung ke Dashboard Admin
// ======================================================================================

// Cek jika user BELUM login, maka buat sesi Admin default dan redirect
if (!isset($_SESSION['role'])) {
    // Tetapkan sesi sebagai Admin Default
    // Nilai 999 dan BYPASS adalah nilai dummy/statis untuk akses tanpa autentikasi
    $_SESSION['id_user'] = 999;
    $_SESSION['nim'] = 'BYPASS';
    $_SESSION['nama'] = 'Admin Tanpa Login';
    $_SESSION['role'] = 'admin';
    
    // Langsung redirect ke dashboard Admin
    header('Location: admin/dashboard.php'); 
    exit;
}

// Jika sesi sudah ada, arahkan sesuai role yang ada di sesi.
if ($_SESSION['role'] == 'admin') { 
    header('Location: admin/dashboard.php'); 
    exit;
} elseif ($_SESSION['role'] == 'mahasiswa') { 
    header('Location: mahasiswa/dashboard.php'); 
    exit;
}

// Bagian di bawah ini (require koneksi dan proses POST) tidak lagi dieksekusi 
// karena bypass di atas akan selalu terjadi jika sesi belum ada.
// require_once 'config/koneksi.php'; 
// $error_message = '';
// if ($_SERVER['REQUEST_METHOD'] == 'POST') { ... }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukas Campus | Login (DISABLED)</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
    <style>
        .gradient-text {
            /* Membuat teks berwarna gradien */
            background-image: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        /* Style untuk Vanta.js background */
        #vanta-bg {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Menyembunyikan scrollbar di beberapa browser */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="font-sans antialiased" id="vanta-bg">

    <div class="w-full max-w-md">
        <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-2xl p-8 sm:p-10 border border-indigo-100/50">
            <div class="text-center mb-8">
                <i data-feather="dollar-sign" class="w-10 h-10 mx-auto mb-2 gradient-text"></i>
                <h1 class="text-3xl font-extrabold gradient-text">Ukas Campus</h1>
                <p class="text-gray-500 mt-2 text-sm">Masuk (Akses Langsung Admin Aktif).</p>
            </div>

            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-md relative mb-4" role="alert">
                <strong class="font-bold">Info!</strong>
                <span class="block sm:inline"> Akses login dibypass, sistem otomatis masuk sebagai Admin.</span>
            </div>

            <form>
                <div class="space-y-6">
                    <div>
                        <label for="username_or_nim" class="block text-sm font-medium text-gray-700 mb-1">NIM / Username Admin</label>
                        <input type="text" id="username_or_nim" name="username_or_nim" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" placeholder="Otomatis Masuk">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" disabled class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition" placeholder="Otomatis Masuk">
                    </div>

                    <button type="submit" disabled class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gray-400">
                        <i data-feather="log-in" class="w-4 h-4 mr-2"></i> Akses Dibypass
                    </button>
                </div>
            </form>

            <p class="mt-8 text-center text-xs text-gray-400">
                &copy; 2025 CUkas Campus. Sistem Informasi Kas Mahasiswa.
            </p>
        </div>
    </div>

    <script>
        // Inisialisasi Feather Icons
        feather.replace();
        
        // Inisialisasi Vanta.js background (latar belakang dinamis)
        VANTA.NET({
            el: "#vanta-bg",
            mouseControls: true,
            touchControls: true,
            gyroControls: false,
            minHeight: 200.00,
            minWidth: 200.00,
            scale: 1.00,
            scaleMobile: 1.00,
            color: 0x667eea, // Warna utama gradien yang digunakan
            backgroundColor: 0xf3f4f6, // Sedikit lebih gelap dari bg-gray-50
            points: 12.00,
            maxDistance: 25.00,
            spacing: 20.00
        });
    </script>
</body>
</html>
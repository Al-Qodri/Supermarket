<?php
// 1. **PERBAIKAN UTAMA:** Wajib memulai sesi terlebih dahulu
// agar sesi yang aktif dapat dikenali dan dihancurkan.
session_start();

// 2. Hapus semua variabel sesi yang terdaftar
$_SESSION = array();

// 3. Hancurkan sesi (Menghapus data sesi dari server)
session_destroy();

// 4. Hapus cookie sesi (Opsional, tapi disarankan untuk keamanan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Redirect ke halaman utama (login page)
// Pastikan index.php adalah halaman login Anda
header('Location: index.php');
exit;
?>
<?php
// Ukas/config/koneksi.php

// Konfigurasi Database - HARAP SESUAIKAN JIKA SERVER ANDA BERBEDA
$host = "sql304.infinityfree.com";
$user = "if0_40044230";   // default XAMPP
$pass = "Yy66EqM2wcNKc";       // default kosong
$db   = "if0_40044230_db_kasmahasiswa"; // Ganti dengan nama database Anda

// Membuat koneksi menggunakan MySQLi Prosedural
$koneksi = mysqli_connect($host, $user, $pass, $db); //

// Cek koneksi dan hentikan jika gagal
if (mysqli_connect_errno()) {
    // Memberikan pesan error yang lebih jelas (opsional)
    die("Koneksi Database GAGAL! Pastikan XAMPP/MySQL running dan konfigurasi koneksi.php benar. Error: " . mysqli_connect_error()); //
}


// --- FUNGSI-FUNGSI PENDUKUNG ---

// Fungsi untuk meng-hash password (biarkan, tapi Anda tidak perlu menggunakannya)
function hashPassword($password) {
    // KEMBALIKAN KE PLAIN TEXT (KEAMANAN SANGAT RENDAH)
    return $password; 
    // Kode asli: return password_hash($password, PASSWORD_DEFAULT);
}

// Fungsi untuk memverifikasi password
function verifyPassword($password, $hash) {
    // PERUBAHAN KRUSIAL: Verifikasi password diubah menjadi perbandingan string sederhana (plain text)
    return $password === $hash;
    // Kode asli: return password_verify($password, $hash);
}

// Fungsi untuk membersihkan input (mencegah SQL Injection dan XSS)
function cleanInput($koneksi, $data) {
    $data = trim($data); //
    $data = stripslashes($data); //
    $data = htmlspecialchars($data); //
    $data = mysqli_real_escape_string($koneksi, $data); //
    return $data; //
} 

?>

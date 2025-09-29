# Ukas Campus: Sistem Informasi Kas Mahasiswa

Aplikasi web sederhana berbasis PHP dan MySQLi Prosedural untuk mengelola dana kas kelas/kampus. Sistem ini menyediakan dua antarmuka: **Admin** untuk pengelolaan penuh kas dan data mahasiswa, serta **Mahasiswa** untuk melacak riwayat pembayaran iuran pribadi.

## Fitur Utama

### 1. Modul Administrasi (`/admin`)
Modul ini dapat diakses oleh Admin untuk mengelola seluruh aspek keuangan dan data anggota kelas.

* **Dashboard Interaktif**: Melihat saldo kas saat ini, total kas masuk, dan total kas keluar. Terdapat grafik *Monthly Overview* (6 bulan terakhir) dan *Status Pembayaran Iuran Wajib* (bulan berjalan).
* **Kelola Data Mahasiswa**: Menambah, mengedit, dan menghapus data mahasiswa (NIM, Nama, Password).
* **Input Kas Masuk**: Mencatat setoran iuran atau pemasukan kas dari mahasiswa tertentu.
* **Catat & Lapor Kas Keluar**: Mencatat pengeluaran kas baru, melihat riwayat pengeluaran, dan menghapus transaksi yang salah.
* **Laporan Status Iuran**: Menyajikan laporan status pembayaran iuran wajib (asumsi **Rp 4.000** per bulan) secara *real-time*, mengelompokkan mahasiswa menjadi **Lunas** atau **Menunggak**.
* **Kelola Transaksi Individu**: Halaman detail untuk melihat dan menghapus transaksi kas masuk spesifik dari satu mahasiswa.

### 2. Modul Mahasiswa (`/mahasiswa`)
Modul ini dapat diakses oleh Mahasiswa untuk melacak informasi pribadi mereka.

* **Dashboard**: Melihat total seluruh iuran yang sudah dibayarkan dan riwayat 5 transaksi terakhir.
* **Riwayat Pembayaran**: Melihat semua daftar transaksi kas masuk yang telah dicatat atas namanya.

## Persyaratan Sistem

* Web Server (Apache, Nginx, dll.)
* PHP (Disarankan versi 7.x atau 8.x)
* MySQL / MariaDB
* `php-mysqli` extension

## Instalasi dan Konfigurasi

1.  **Impor Database:** Buat database baru di MySQL (misalnya `if0_40044230_db_kasmahasiswa`). Anda perlu memiliki skema database yang sesuai (tabel `users`, `kas_masuk`, `kas_keluar`, dll.). Karena skema tidak disediakan, diasumsikan skema sudah dibuat.

2.  **Konfigurasi Koneksi Database:**
    Buka file `kas/config/koneksi.php` dan sesuaikan variabel koneksi sesuai dengan konfigurasi database Anda:

    ```php
    $host = "sql304.infinityfree.com"; // Ganti dengan host Anda
    $user = "if0_40044230";           // Ganti dengan username DB Anda
    $pass = "Yy66EqM2wcNKc";           // Ganti dengan password DB Anda
    $db   = "if0_40044230_db_kasmahasiswa"; // Ganti dengan nama database Anda
    
    $koneksi = mysqli_connect($host, $user, $pass, $db);
    // ...
    ```

3.  **Akses Aplikasi:**
    * Unggah seluruh folder `kas` ke direktori root web server Anda.
    * Akses aplikasi melalui browser: `http://localhost/kas/` (sesuaikan URL Anda).

## Catatan Keamanan Penting

**PERHATIAN!** Aplikasi ini memiliki kerentanan keamanan yang serius dan tidak disarankan untuk lingkungan produksi:

1.  **Bypass Login Default:** Halaman `kas/index.php` secara otomatis mengarahkan pengunjung ke Dashboard Admin dengan membuat sesi admin default (`Admin Tanpa Login`, ID `999`) jika belum ada sesi yang aktif. Ini menonaktifkan fitur login.
2.  **Penyimpanan Password Plain Text:** Dalam `kas/config/koneksi.php`, fungsi `hashPassword` dan `verifyPassword` diubah untuk menyimpan dan memverifikasi kata sandi sebagai **plain text** (teks biasa), yang merupakan praktik yang sangat tidak aman.

---

## Struktur File

Berikut adalah struktur utama direktori proyek:
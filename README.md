# LMS SMAM

Aplikasi **Learning Management System (LMS)** berbasis web untuk sekolah (**LMS SMAM**) yang dibangun menggunakan **PHP native** dan **MySQL (PDO)**.

## Fitur Utama

- **Multi-Role Authentication** (`Admin`, `Guru`, `Siswa`) dengan login menggunakan Email atau NIS.
- **Dashboard per Role** untuk ringkasan aktivitas dan navigasi cepat.
- **Manajemen Pengguna** (Admin): tambah, edit, dan hapus akun pengguna.
- **Materi Pembelajaran**: unggah & kelola materi (PDF/PPT atau tautan) berdasarkan kelas dan mata pelajaran, lengkap dengan fitur unduh.
- **Kuis Online**: pembuatan soal, publikasi/unpublish kuis, pengerjaan kuis oleh siswa, dan halaman review hasil.
- **Pengumuman**: publikasi dan pengelolaan pengumuman sekolah.
- **Jadwal Pelajaran**: manajemen jadwal kelas dan mata pelajaran.
- **Feedback**: pengiriman umpan balik oleh siswa dan daftar masukan untuk admin/guru.
- **Pengaturan Profil**: pembaruan profil dan kata sandi akun untuk siswa dan guru.

## Teknologi

- **Backend:** PHP 8+ (PDO)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Font Awesome
- **Deployment:** Vercel (`vercel-php`) / XAMPP (Apache + MySQL)

## Menjalankan Secara Lokal (XAMPP)

1. Clone repository ini ke dalam folder `htdocs` XAMPP:
   ```bash
   git clone https://github.com/Iceutube/lms-smam.git
   ```
2. Nyalakan **Apache** dan **MySQL** di XAMPP Control Panel.
3. Buat database MySQL bernama `db_lms`, lalu import file `database/db_lms.sql`.
4. Konfigurasi koneksi berada di `includes/db.php` (secara default mengarah ke `localhost`, user `root`, password kosong, database `db_lms`).
5. Buka `http://localhost/lms-smam` di browser.

## Akun Demo (dari `database/db_lms.sql`)

| Role | Email / NIS | Password |
| --- | --- | --- |
| Admin | `admin@smam.sch.id` | `admin123` |
| Guru | `guru@smam.sch.id` | `guru123` |
| Siswa | `siswa@smam.sch.id` / `2025001` | `siswa123` |

## Konfigurasi Environment Variables (Vercel / Produksi)

Atur environment variables berikut di Vercel Project Settings agar aplikasi terhubung ke database MySQL cloud (seperti TiDB Cloud, Aiven, atau Railway):

| Variable | Deskripsi | Default |
| --- | --- | --- |
| `DB_HOST` | Host server MySQL | `localhost` |
| `DB_PORT` | Port MySQL | `3306` |
| `DB_NAME` | Nama database | `db_lms` |
| `DB_USER` | Username database | `root` |
| `DB_PASS` | Password database | *(kosong)* |
| `DB_SSL` | Aktifkan SSL (`true` / `false`) | `false` |

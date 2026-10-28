# Sistem Informasi Sensus Penduduk Desa Bongan

Aplikasi PHP Native dan MySQL untuk mengelola data penduduk, keluarga, laporan peristiwa kependudukan, verifikasi, dan laporan bulanan.

Lambang Desa Bongan ditampilkan menggunakan aset PNG transparan di `assets/images/logo-bongan.png`.

## Persyaratan

- XAMPP dengan Apache, MySQL/MariaDB, dan PHP 8.0 atau lebih baru.
- Ekstensi PHP `pdo_mysql` dan `mbstring` aktif.
- Browser modern dan koneksi internet untuk memuat Bootstrap, Bootstrap Icons, serta Chart.js dari CDN.

## Instalasi di XAMPP

1. Salin folder proyek ke `C:\xampp\htdocs\SISTEM SENSUS BONGAN`.
2. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin`, pilih menu **Import**, lalu impor `database/schema.sql`. Setelah itu impor `database/demo_data.sql` untuk menambahkan akun dan data fiktif siap pakai. Skrip skema membuat database `sensus_bongan` beserta tabel-tabelnya.
4. Jika konfigurasi MySQL berbeda dari bawaan XAMPP, sesuaikan host, nama database, username, dan password pada `config/database.php` atau atur environment variable `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS`.
5. Buka `http://localhost/SISTEM%20SENSUS%20BONGAN/`. Pada halaman login tersedia akun dummy admin, kepala desa, dan penduduk; klik **Isi** di samping akun untuk mengisi formulir secara otomatis.
6. Data fiktif berisi tiga penduduk, dua keluarga, dan contoh laporan. Untuk menguji pendaftaran, gunakan kode `DEMO000000000001` dan `KKDEMO0000000001`.

### Akun dummy (lokal saja)

| Peran | Username | Password |
|---|---|---|
| Admin | `demo.admin` | `DemoAdmin123!` |
| Kepala Desa | `demo.kepala` | `DemoKepala123!` |
| Penduduk | `demo.penduduk` | `DemoPenduduk123!` |

**Jangan gunakan akun maupun password dummy di server publik atau lingkungan produksi.** Formulir login menyediakan tombol **Isi** untuk memasukkan kredensial dummy dengan satu klik. Akun admin lain yang sudah ada tidak dihapus.

> Buat cadangan database secara berkala melalui phpMyAdmin. Jangan membuka `database/schema.sql` melalui browser; jalankan sebagai skrip impor di phpMyAdmin.

### Memperbarui database yang sudah ada

Jika database dibuat sebelum fitur pendaftaran mandiri ditambahkan, impor satu kali `database/migrations/001_add_user_approval_status.sql` melalui phpMyAdmin. Akun yang sudah ada otomatis tetap disetujui; jangan menjalankan migrasi yang sama lebih dari sekali.

Jika database telah dibuat sebelum format kode ilustrasi ditambahkan, impor satu kali `database/migrations/002_require_illustrative_identity_codes.sql`. Migrasi ini menolak nilai identitas yang bukan kode contoh. Ekspor/cadangkan data terlebih dahulu; setiap data penduduk atau keluarga yang telah ada perlu diubah ke kode ilustrasi sebelum constraint dapat diterapkan.

### Jika halaman menampilkan 404

Pastikan folder aplikasi berada di bawah **document root Apache yang aktif**, bukan hanya di bawah folder instalasi XAMPP. Pada instalasi lokal proyek ini, XAMPP berada di `C:\xampp\htdocs` dan document root Apache berada di `C:\xampp\htdocs\htdocs`. Folder aplikasi telah dihubungkan ke document root agar alamat `http://localhost/SISTEM%20SENSUS%20BONGAN/` dapat diakses. Instalasi XAMPP standar biasanya memakai document root `C:\xampp\htdocs`.

## Hak akses

- **Admin:** mengelola data penduduk dan keluarga, membuat akun, mengirim/memeriksa laporan peristiwa, serta menyusun laporan bulanan.
- **Kepala desa:** melihat dashboard, data penduduk, keluarga, dan peristiwa; menyetujui laporan bulanan.
- **Penduduk:** mendaftar memakai kode penduduk dan keluarga contoh yang cocok; setelah disetujui admin, dapat melihat data keluarga sendiri, memperbarui nama, data pribadi, dan informasi kontak/profil (kode penduduk serta relasi keluarga dikelola admin), mengirim laporan peristiwa, dan memeriksa status laporannya.

## Catatan alur data

- Sistem ini menggunakan nama, alamat, pekerjaan, serta kode identitas fiktif; jangan masukkan data pribadi asli.
- Kode penduduk memakai `DEMO` diikuti 12 angka; kode keluarga memakai `KKDEMO` diikuti 10 angka. Keduanya diberi constraint unik dan validasi format di aplikasi serta database.
- Berkas `database/demo_data.sql` menambahkan tiga penduduk contoh (**Andi Contoh**, **Siti Contoh**, dan **Budi Contoh**), dua keluarga, akun untuk tiap-tiap peran, dan contoh laporan. Kode `DEMO000000000001` beserta `KKDEMO0000000001` dapat dipakai untuk menguji pendaftaran.
- Kelahiran, kematian, mutasi datang, dan mutasi pindah disimpan pada tabel `population_events` dan dibedakan menurut jenis peristiwanya, sehingga proses verifikasi serta rekap dapat memakai alur yang sama.
- Laporan kelahiran, kematian, dan mutasi berstatus menunggu sampai diverifikasi admin.
- Verifikasi laporan kematian/pindah menonaktifkan penduduk terkait; verifikasi penduduk datang mengaktifkan kembali data penduduk yang ditautkan.
- Laporan kelahiran atau penduduk datang yang belum memiliki data penduduk tetap tercatat dalam rekap peristiwa. Admin perlu menambahkan data penduduk lengkap melalui menu **Data Penduduk** agar masuk ke jumlah penduduk aktif.
- Laporan bulanan menyimpan snapshot jumlah penduduk aktif saat laporan disusun dan peristiwa terverifikasi pada bulan yang dipilih. Persetujuan kepala desa mengunci snapshot tersebut.
- Tombol unduh menghasilkan CSV yang dapat dibuka di spreadsheet. Tombol cetak menggunakan dialog cetak browser.

## Pengujian Black Box

| Skenario | Hasil yang diharapkan |
|---|---|
| Masuk dengan kredensial benar/salah | Masuk ke dashboard sesuai peran / pesan kesalahan tanpa membuka sesi |
| Daftar penduduk dengan kode contoh dan kode yang tidak cocok | Permohonan dibuat tanpa akses login sampai disetujui / permohonan ditolak |
| Login sebelum dan sesudah persetujuan admin | Akses ditolak dengan status menunggu / masuk ke dashboard penduduk |
| Akses URL halaman tanpa login atau memakai peran lain | Dialihkan ke login / mendapat respons 403 |
| Tambah penduduk dengan kode ilustrasi dan kode duplikat | Data tersimpan / duplikat ditolak oleh constraint unik |
| Isi form penduduk tanpa isian wajib atau format kode noncontoh | Form ditolak dengan pesan validasi |
| Ubah dan hapus penduduk | Perubahan tersimpan / konfirmasi ditampilkan sebelum penghapusan |
| Penduduk memperbarui profilnya | Data pribadi dan kontak tersimpan, nama akun ikut diperbarui, sedangkan kode penduduk dan relasi keluarga tidak berubah |
| Cari penduduk dengan nama atau kode ilustrasi | Hasil yang sesuai ditampilkan |
| Kirim laporan kelahiran, kematian, atau mutasi | Laporan tersimpan dengan status menunggu verifikasi |
| Verifikasi atau tolak peristiwa | Status laporan berubah; kematian/pindah memperbarui status penduduk tertaut |
| Susun laporan bulanan dan setujui sebagai kepala desa | Snapshot tersimpan dan berubah menjadi disetujui |
| Unduh atau cetak laporan | CSV diunduh / dialog cetak browser dibuka |

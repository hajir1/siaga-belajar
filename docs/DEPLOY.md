# Panduan Deploy

Siaga Belajar disajikan dari **satu domain**: Laravel melayani `/api/*`, dan hasil build React (`backend/public/index.html` + `backend/public/assets/`) melayani semua halaman lain. Hasil build sudah ada di repositori, jadi server **tidak perlu Node.js**.

Kebutuhan server: PHP 8.3 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`; MySQL 8 / MariaDB 10.6+; Apache dengan `mod_rewrite` (shared hosting umumnya sudah) atau Nginx.

## A. Shared hosting (cPanel / hPanel) dengan akses SSH/Terminal

1. **Buat database** MySQL beserta user-nya di panel hosting. Catat nama database, user, dan sandi.
2. **Pilih versi PHP 8.3+** di panel (menu "Select PHP Version" / "PHP Configuration").
3. **Unggah kode** ke folder di luar `public_html`, misalnya `~/siaga-belajar`:
   ```bash
   cd ~
   git clone https://github.com/<akun>/siaga-belajar.git
   cd siaga-belajar/backend
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```
4. **Isi `.env`**: `APP_URL` dan `CORS_ALLOWED_ORIGINS` = `https://domain-anda`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Pastikan `APP_ENV=production` dan `APP_DEBUG=false`.
5. **Migrasi dan data demo**:
   ```bash
   php artisan migrate --force --seed
   php artisan config:cache && php artisan route:cache
   ```
6. **Arahkan domain ke folder `public`.** Pilih salah satu:
   - Paling rapi: di panel, ubah *Document Root* domain/subdomain ke `~/siaga-belajar/backend/public`.
   - Jika document root tidak bisa diubah (harus `public_html`): ganti `public_html` dengan symlink.
     ```bash
     mv ~/public_html ~/public_html_lama
     ln -s ~/siaga-belajar/backend/public ~/public_html
     ```
7. **Izin folder**: `chmod -R 775 storage bootstrap/cache`.
8. Aktifkan **SSL** (AutoSSL / Let's Encrypt) di panel.

## B. Shared hosting tanpa SSH (File Manager + phpMyAdmin)

Contoh di bawah memakai lokasi `public_html/cakramasta` untuk subdomain `cakramasta.terraetloka.id`.

1. **Siapkan paket siap unggah** (berisi `vendor/` produksi, folder `storage/` lengkap, `.env` produksi, dan `.htaccess` di root). Buat sendiri di komputer:
   ```bash
   cd backend
   composer install --no-dev --optimize-autoloader
   rm -f bootstrap/cache/*.php      # jangan bawa cache dari komputer lokal
   ```
   lalu zip seluruh isi folder `backend` **termasuk berkas tersembunyi** (`.env`, `.htaccess`) dan folder kosong di `storage/`.
2. **Unggah dan ekstrak** zip ke `public_html/cakramasta` lewat File Manager.
3. **Pastikan folder ini ada** (buat lewat File Manager jika belum) dan izinnya **755** (atau 775 jika 755 masih gagal):
   ```
   storage/framework/cache/data
   storage/framework/sessions
   storage/framework/views
   storage/logs
   bootstrap/cache
   ```
   Jika folder `storage/framework/views` tidak ada, Laravel menampilkan galat **"Please provide a valid cache path."**
4. **Hapus** `bootstrap/cache/packages.php` dan `bootstrap/cache/services.php` jika ada (berkas ini dibuat ulang otomatis). Berkas dari komputer lokal bisa merujuk paket pengembangan yang tidak terpasang di server.
5. **Isi `.env`** (klik kanan → Edit): `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://cakramasta.terraetloka.id`, `CORS_ALLOWED_ORIGINS` sama dengan APP_URL, `DB_HOST=localhost`, dan `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` dari menu MySQL Databases. `APP_KEY` wajib terisi (`php artisan key:generate --show` di komputer sendiri).
6. **Database**: buat database + user di panel, lalu buka phpMyAdmin → pilih database → **Import** → berkas `siaga_belajar.sql` (berisi tabel + data demo). Alternatif: jalankan `php artisan migrate --seed` ke MySQL lokal, ekspor dengan phpMyAdmin/`mysqldump`, lalu impor.
7. **Document root**:
   - Paling rapi: ubah document root subdomain ke `public_html/cakramasta/public`.
   - Jika tidak bisa diubah: biarkan ke `public_html/cakramasta`. Berkas `.htaccess` di root proyek meneruskan semua permintaan ke `public/`, sehingga `.env`, `vendor`, dan `storage` tidak bisa dibuka dari web.
8. Pilih **PHP 8.3** atau lebih baru untuk subdomain itu (menu Select PHP Version / MultiPHP Manager), dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`.
9. Buka `https://cakramasta.terraetloka.id/up` (harus 200), lalu jalankan daftar cek di bawah.

## C. VPS dengan Nginx

```nginx
server {
    listen 80;
    server_name domain-anda;
    root /var/www/siaga-belajar/backend/public;
    index index.html index.php;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;

    # API dan health check ke Laravel
    location ~ ^/(api|up)(/|$) {
        try_files $uri /index.php?$query_string;
    }

    # Rute React: file nyata atau index.html (memuat ulang /guru/soal tidak 404)
    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

## Agar rute React tidak 404 saat dimuat ulang

Sudah ditangani di `backend/public/.htaccess` (Apache):

```apache
# Rute React (bukan /api, bukan /up, bukan file/folder nyata) langsung ke index.html
RewriteCond %{REQUEST_URI} !^/(api|up)(/|$)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.html [L]
```

Sebagai cadangan, `routes/web.php` juga mengembalikan `index.html` untuk rute non-API, sehingga tetap aman jika aturan rewrite di atas tidak aktif.

## Memperbarui aplikasi

```bash
cd ~/siaga-belajar && git pull
cd backend && composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
```

Jika frontend diubah, jalankan `cd frontend && npm run build` di komputer sendiri lalu commit isi `backend/public/assets` dan `backend/public/index.html`.

Mengulang data demo (menghapus semua data): `php artisan migrate:fresh --seed --force`.

## Galat yang sering muncul

| Galat | Penyebab | Perbaikan |
|---|---|---|
| `Please provide a valid cache path.` | Folder `storage/framework/views` (dan teman-temannya) tidak ada atau tidak bisa ditulis | Langkah B.3 |
| Halaman galat Symfony/Laravel dengan jejak kode | `APP_DEBUG=true` atau `.env` tidak terbaca | Langkah B.5, lalu hapus `bootstrap/cache/config.php` jika ada |
| `No application encryption key has been specified.` | `APP_KEY` kosong | Langkah B.5 |
| `SQLSTATE[HY000] [1045] Access denied` / `[2002]` | Data database di `.env` salah | Cek nama database/user (biasanya berawalan nama akun hosting, misalnya `terraetl1_...`), `DB_HOST=localhost` |
| `Class "...Pail..." not found` atau `Collision` | `bootstrap/cache/packages.php` dari lokal | Langkah B.4 |
| 500 tanpa pesan | Izin folder | Lihat `storage/logs/laravel.log`, set izin 755/775 |
| Halaman React 404 saat dimuat ulang | `.htaccess` di `public/` tidak ikut terunggah (berkas tersembunyi) | Tampilkan berkas tersembunyi di File Manager, unggah ulang `public/.htaccess` |

## Daftar cek setelah deploy (di URL publik)

1. `https://domain-anda/up` menampilkan halaman status 200.
2. `https://domain-anda/api/tidak-ada` membalas JSON `{"message":"Data tidak ditemukan."}` tanpa jejak error (berarti `APP_DEBUG=false`).
3. Buka `https://domain-anda/guru/soal` langsung (tanpa login) → diarahkan ke halaman masuk, bukan 404.
4. Masuk sebagai guru → dashboard menampilkan peringatan untuk Andi, Citra, Eka.
5. Muat ulang halaman dashboard → tetap tampil (tidak 404).
6. Masuk sebagai siswa → kerjakan 3 soal → keluar. Masuk sebagai guru → jumlah jawaban Andi bertambah.
7. Masuk sebagai orang tua → laporan Andi tampil dengan saran per topik.
8. Login dengan sandi salah 6 kali → muncul pesan "Terlalu banyak percobaan login".

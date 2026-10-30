# Siaga Belajar

Platform **deteksi dini siswa yang mulai tertinggal**. Guru membuat mapel, topik, dan soal. Siswa mengerjakan kuis adaptif. Sistem menghitung nilai per topik dan memberi **peringatan dini** ke guru. Orang tua melihat laporan anaknya dengan saran pendampingan.

Karya lomba UINIC 8.0 Web Development, tema *Engineering Interconnected Web Solutions to Empower Digital Ecosystems*, subtema Pendidikan.

- Sasaran: siswa SMP di sekolah dengan koneksi terbatas atau tidak stabil.
- Peran: guru, siswa, orang tua.
- Stack: Laravel 13 (REST API JSON) + MySQL, React 19 + Vite + React Router, Laravel Sanctum (token Bearer).

> Semua data siswa dan riwayat di versi demo adalah **data demo** (dibuat oleh seeder), bukan data siswa sungguhan.

## Daftar isi

1. [Fitur](#fitur)
2. [Akun demo](#akun-demo)
3. [Menjalankan di lokal](#menjalankan-di-lokal)
4. [Struktur kode](#struktur-kode)
5. [Cara kerja inti](#cara-kerja-inti)
6. [Dokumentasi API](#dokumentasi-api)
7. [Keamanan](#keamanan)
8. [Pengujian](#pengujian)
9. [Batasan produk](#batasan-produk)
10. [Rencana pengembangan](#rencana-pengembangan)

Dokumen lain: [Arsitektur, ERD, dan daftar endpoint](docs/ARSITEKTUR.md) · [Panduan deploy](docs/DEPLOY.md) · [Bahan proposal dan skrip demo](docs/PROPOSAL.md)

## Fitur

Sudah tersedia:

| Fitur | Keterangan |
|---|---|
| Login per peran | Guru, siswa, dan orang tua masuk dengan akun yang dibuat sekolah (tanpa pendaftaran bebas). |
| Kuis adaptif (mode online) | Soal dipilih dekat dengan kemampuan siswa (rating Elo), topik lemah diprioritaskan, soal yang salah muncul lagi setelah beberapa soal, soal yang sama tidak muncul berturut-turut. |
| Penilaian di server | Kunci jawaban tidak pernah dikirim ke perangkat siswa sebelum soal dijawab. Server menilai dan memperbarui rating. |
| Dashboard guru | Nilai per topik per siswa, tren per pertemuan, dan daftar peringatan dini. |
| Peringatan dini | Nilai topik di bawah 55, atau nilai turun 3 pertemuan berturut-turut. |
| Laporan orang tua | Hanya data anaknya sendiri, ringkasan bahasa sederhana, dan saran pendampingan per topik. |
| Bank soal | Guru menambah, mengubah, dan menghapus soal pilihan ganda (draf atau terbit). |
| Mapel dan topik dinamis | Semua mapel dan topik berasal dari database. Guru bisa menambah mapel dan topik sendiri. |
| Pengaturan mapel | Mode Ketat, mode pengerjaan (online/paket), proteksi seleksi teks, batas waktu per soal. |
| Mode Ketat | Mengurangi penyalinan dan melacak kebocoran: seleksi teks, klik kanan, salin, dan potong ditahan di area soal; watermark nama + ID siswa; pindah tab dan blur dicatat (jumlah + durasi) dan tampil di dashboard guru sebagai "perlu dicek"; batas waktu per soal; urutan pilihan diacak. Guru bisa mematikan proteksi seleksi teks agar pembaca layar tidak terhalang. |
| Mode Paket (koneksi tidak stabil) | Siswa melihat estimasi ukuran lalu mengunduh paket soal satu mapel **tanpa kunci jawaban**. Soal dikerjakan walau sinyal hilang, jawaban antre di perangkat (localStorage), lalu dikirim dan dinilai server saat online dengan percobaan ulang otomatis. Status: "Tersimpan di perangkat", "Menunggu sinyal (N jawaban)", "Terkirim". Benar/salah tampil setelah sinkron. |
| PWA | Manifest + service worker (vite-plugin-pwa): setelah dimuat sekali, aplikasi tetap terbuka tanpa sinyal. |
| Peta kelemahan kelas | Heatmap siswa x topik dan kalimat ringkas, misalnya "20% kelas lemah di Suhu dan Kalor (1 dari 5 siswa)." |
| Laporan lewat WhatsApp | Tombol membuka WhatsApp (tautan wa.me) berisi ringkasan laporan dan saran untuk topik yang perlu didampingi. |
| Remedial otomatis | Topik yang kena peringatan dini muncul di beranda siswa sebagai "Perlu diulang". Latihan ulang hanya berisi topik itu dan dimulai dari soal sedikit lebih mudah. |
| Ekspor/impor CSV | Guru mengunduh nilai kelas dan bank soal (CSV, terbaca di Excel), serta mengimpor soal dari CSV dengan validasi per baris (semua-atau-tidak-sama-sekali). |
| Kalibrasi kualitas soal | Dari data jawaban: soal terlalu mudah/sulit, tingkat yang tidak sesuai data (rating bergeser), dan pengecoh yang tidak pernah dipilih. Hanya tanda untuk ditinjau guru, tidak mengubah soal otomatis. |
| UI mobile-first | Dirancang untuk layar 360px, mode gelap, tombol minimal 44px, fokus keyboard terlihat, bahasa Indonesia sederhana. |

Pengaturan demo: Mode Ketat aktif di IPA (ada contoh catatan pindah tab untuk Dimas), Matematika sengaja mati agar bisa dinyalakan saat demo. Semua mapel memakai mode online; ubah ke Mode Paket di menu Mapel.

## Akun demo

Kata sandi semua akun: **`siaga123`**

| Peran | Email | Keterangan |
|---|---|---|
| Guru | `guru@siaga.test` | Bu Rina Hartati, wali Kelas 8A (data demo) |
| Siswa | `andi@siaga.test` | Pecahan turun 3 pertemuan berturut-turut (peringatan) |
| Siswa | `bela@siaga.test` | Nilai baik di semua topik |
| Siswa | `citra@siaga.test` | Aljabar dan Gerak dan Gaya di bawah 55 (peringatan) |
| Siswa | `dimas@siaga.test` | Rata-rata |
| Siswa | `eka@siaga.test` | Suhu dan Kalor turun 3 pertemuan berturut-turut (peringatan) |
| Orang tua | `ortu@siaga.test` | Ibu Sari, orang tua Andi |

Bank soal berisi 100 soal SMP: Matematika (Bilangan Bulat, Pecahan, Aljabar, Perbandingan, Bangun Datar) dan IPA (Klasifikasi Makhluk Hidup, Zat dan Wujudnya, Suhu dan Kalor, Gerak dan Gaya, Sistem Pencernaan), masing-masing 10 soal per topik. Riwayat demo berisi 6 pertemuan mingguan per siswa per mapel.

## Menjalankan di lokal

Kebutuhan: PHP 8.3+, Composer 2, Node.js 20+, MySQL 8 / MariaDB 10.6+ (atau SQLite untuk coba cepat).

```bash
# 1. Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Atur DB_* di .env (atau DB_CONNECTION=sqlite lalu: touch database/database.sqlite)
# Untuk lokal, ubah juga: APP_ENV=local, APP_DEBUG=true, APP_URL=http://localhost:8000
php artisan migrate --seed
php artisan serve            # http://localhost:8000

# 2. Frontend (terminal lain)
cd frontend
npm install
npm run dev                  # http://localhost:5173, /api diteruskan ke :8000
```

Membangun frontend untuk produksi: `cd frontend && npm run build`. Hasilnya masuk ke `backend/public/` (`index.html` + `assets/`), sehingga satu domain menyajikan SPA dan API. Hasil build ikut disimpan di repositori agar hosting tanpa Node.js tetap bisa dipakai.

Mengulang data demo dari awal: `php artisan migrate:fresh --seed`.

## Struktur kode

```
siaga-belajar/
├── backend/                         Laravel (API saja)
│   ├── app/
│   │   ├── Http/Controllers/Api/    Controller tipis: Auth, Mapel, Soal, Kuis, DashboardGuru, LaporanOrtu
│   │   ├── Http/Middleware/         PastikanPeran (middleware 'peran:guru' dst.)
│   │   ├── Http/Requests/           Form Request untuk validasi semua input
│   │   ├── Http/Resources/          API Resource (SoalKuisResource tanpa kolom "benar")
│   │   ├── Models/                  Eloquent model dengan $fillable
│   │   ├── Policies/                Otorisasi: Mapel, Soal, SesiKuis, User (laporan)
│   │   └── Services/                Logika bisnis
│   │       ├── EloService.php           rumus rating Elo
│   │       ├── PemilihanSoalService.php soal berikutnya (adaptif)
│   │       ├── PenilaianService.php     menilai jawaban + idempotensi id_unik_klien
│   │       ├── NilaiTopikService.php    nilai per topik dan per pertemuan
│   │       ├── PeringatanDiniService.php aturan peringatan dini
│   │       ├── DashboardGuruService.php  matriks siswa x topik
│   │       ├── LaporanOrtuService.php    ringkasan dan saran untuk orang tua
│   │       ├── SoalService.php           simpan soal + pilihan
│   │       ├── PaketService.php          Mode Paket: paket tanpa kunci + sinkron
│   │       ├── BerkasCsvService.php      ekspor/impor CSV
│   │       └── KualitasSoalService.php   kalibrasi kualitas soal
│   ├── database/migrations/         Skema dengan PK, FK, indeks
│   ├── database/seeders/            Akun demo, bank 100 soal, riwayat demo
│   ├── routes/api.php               Semua endpoint REST
│   └── tests/Feature/               Tes Pest (45 tes)
├── frontend/                        React + Vite
│   └── src/
│       ├── lib/                     api.js (fetch + token), auth.jsx (context), useApi.js,
│       │                            antrean.js (paket + antrean jawaban + sinkron), useKetat.js (pindah tab, timer)
│       ├── components/              Layout, RuteTerlindungi (route guard), AreaSoal (Mode Ketat), StatusSinkron, Ui
│       └── pages/                   Login, siswa/, guru/, ortu/
└── docs/                            Arsitektur, deploy, bahan proposal
```

## Cara kerja inti

**Rating Elo.** Setiap siswa punya rating per mapel (awal 1000). Setiap soal punya rating awal dari tingkatnya: mudah 900, sedang 1050, sulit 1200. Peluang benar = 1 / (1 + 10^((rating_soal − rating_siswa)/400)). Setelah menjawab, rating siswa berubah `32 × (hasil − peluang)` dan rating soal berubah ke arah sebaliknya dengan faktor 16. Kode: `backend/app/Services/EloService.php`.

**Pemilihan soal berikutnya** (`PemilihanSoalService`), berurutan:
1. Jika ada soal yang dijawab salah di sesi ini dan sudah lewat 3 soal lain, soal itu diulang (maksimal satu kali ulang per soal).
2. Pilih topik: 70% topik dengan nilai terendah, 30% topik acak (agar topik lain tetap terlatih). Topik yang belum pernah dikerjakan dianggap bernilai 50.
3. Dalam topik itu, ambil 3 soal terbit dengan rating paling dekat ke rating siswa, lalu pilih satu secara acak.
4. Soal terakhir tidak pernah dipilih lagi berturut-turut, dan 8 soal terakhir di sesi dihindari selama masih ada soal lain.

**Nilai topik** = persentase benar dari 10 jawaban terakhir di topik itu. **Nilai pertemuan** = persentase benar di satu sesi kuis untuk topik itu.

**Peringatan dini** (`PeringatanDiniService`):
- `di_bawah_batas`: nilai topik di bawah 55, dengan minimal 3 jawaban.
- `turun_beruntun`: nilai pertemuan turun 3 kali berturut-turut (4 pertemuan terakhir menurun terus).

**Anti jawaban ganda.** Setiap jawaban membawa `id_unik_klien` (UUID dari perangkat). Kombinasi `(siswa_id, id_unik_klien)` unik di database, sehingga jawaban yang terkirim ulang (misalnya saat sinyal putus) tidak dicatat dua kali dan rating tidak berubah dua kali.

## Dokumentasi API

Basis URL: `https://domain-anda/api`. Semua permintaan dan respons berformat JSON. Sertakan header `Accept: application/json`. Endpoint selain `/login` butuh header `Authorization: Bearer <token>`.

### Ringkasan endpoint

| Metode | Path | Peran | Fungsi |
|---|---|---|---|
| POST | `/login` | publik (maks. 5x/menit) | Masuk, mendapat token |
| GET | `/me` | semua | Data pengguna yang sedang masuk |
| POST | `/logout` | semua | Mencabut token |
| GET | `/mapel` | guru, siswa | Daftar mapel (guru: buatannya; siswa: dari gurunya) + topik |
| GET | `/mapel/{mapel}` | guru, siswa | Detail mapel + topik |
| POST | `/guru/mapel` | guru | Membuat mapel |
| PATCH | `/guru/mapel/{mapel}` | guru pemilik | Mengubah pengaturan mapel |
| POST | `/guru/mapel/{mapel}/topik` | guru pemilik | Menambah topik |
| GET | `/guru/mapel/{mapel}/soal` | guru pemilik | Daftar soal (filter `topik_id`, `status`, `page`) |
| GET | `/guru/mapel/{mapel}/dashboard` | guru pemilik | Nilai per topik per siswa + peringatan dini |
| POST | `/guru/soal` | guru pemilik mapel | Membuat soal pilihan ganda |
| GET | `/guru/soal/{soal}` | guru pemilik | Detail soal lengkap dengan kunci |
| PUT | `/guru/soal/{soal}` | guru pemilik | Mengubah soal |
| DELETE | `/guru/soal/{soal}` | guru pemilik | Menghapus soal |
| POST | `/kuis/sesi` | siswa | Memulai sesi kuis untuk satu mapel |
| GET | `/kuis/sesi/{sesi}/soal-berikutnya` | siswa pemilik sesi | Soal berikutnya (tanpa kunci) |
| POST | `/kuis/sesi/{sesi}/jawaban` | siswa pemilik sesi (maks. 60x/menit) | Mengirim jawaban, server menilai |
| POST | `/kuis/sesi/{sesi}/selesai` | siswa pemilik sesi | Menutup sesi, ringkasan skor |
| POST | `/kuis/sesi/{sesi}/kejadian` | siswa pemilik sesi | Mencatat pindah tab/blur (hanya jika Mode Ketat aktif) |
| GET | `/kuis/rekomendasi` | siswa | Topik remedial (yang kena peringatan dini) |
| GET | `/kuis/mapel/{mapel}/paket/info` | siswa | Jumlah soal dan perkiraan ukuran paket |
| GET | `/kuis/mapel/{mapel}/paket` | siswa | Paket soal tanpa kunci jawaban |
| POST | `/kuis/sinkron` | siswa (maks. 60x/menit) | Mengirim satu sesi Mode Paket (jawaban + kejadian), aman diulang |
| GET | `/guru/mapel/{mapel}/ekspor/nilai` | guru pemilik | CSV nilai per topik per siswa |
| GET | `/guru/mapel/{mapel}/ekspor/soal` | guru pemilik | CSV bank soal (format sama dengan impor) |
| POST | `/guru/mapel/{mapel}/impor/soal` | guru pemilik | Impor soal dari CSV (multipart, field `berkas`, maks. 1 MB / 300 soal) |
| GET | `/guru/contoh-format-soal` | guru | Contoh CSV untuk impor |
| GET | `/guru/mapel/{mapel}/kualitas-soal` | guru pemilik | Analisis kualitas soal |
| GET | `/ortu/anak` | orang tua | Daftar anak |
| GET | `/laporan/siswa/{siswa}` | orang tua dari siswa, guru yang mengajar | Laporan per mapel per topik + saran |

### Format galat

| Status | Arti | Contoh body |
|---|---|---|
| 401 | Belum masuk / token tidak berlaku | `{"message": "Silakan masuk terlebih dahulu."}` |
| 403 | Peran atau kepemilikan tidak sesuai | `{"message": "Anda tidak punya akses ke data ini."}` |
| 404 | Data tidak ada | `{"message": "Data tidak ditemukan."}` |
| 422 | Validasi gagal | `{"message": "...", "errors": {"pilihan": ["Harus ada tepat 1 pilihan yang benar."]}}` |
| 429 | Terlalu banyak permintaan | `{"message": "Terlalu banyak percobaan login. Coba lagi sebentar lagi."}` |

### Contoh request dan response

**POST `/login`**

```json
// request
{ "email": "andi@siaga.test", "password": "siaga123" }
// 200
{ "token": "3|Qk9x...", "user": { "id": 2, "nama": "Andi Pratama", "email": "andi@siaga.test", "peran": "siswa" } }
// 422
{ "message": "Email atau kata sandi salah.", "errors": { "email": ["Email atau kata sandi salah."] } }
```

**GET `/mapel`**

```json
{ "data": [ {
  "id": 2, "nama": "IPA", "mode_ketat": false, "mode_pengerjaan": "online",
  "proteksi_seleksi_teks": true, "batas_waktu_per_soal": null, "jumlah_soal_terbit": 50,
  "topik": [ { "id": 6, "nama": "Klasifikasi Makhluk Hidup" }, { "id": 7, "nama": "Zat dan Wujudnya" } ]
} ] }
```

**POST `/kuis/sesi`**

```json
// request
{ "mapel_id": 2 }
// 201
{ "data": { "id": 61, "mapel_id": 2, "mulai": "2026-10-29T04:50:04+00:00", "selesai": null } }
```

**GET `/kuis/sesi/{sesi}/soal-berikutnya`** (perhatikan: tidak ada kolom `benar` maupun rating soal)

```json
{ "data": {
  "id": 78,
  "teks": "Kalor yang dibutuhkan untuk memanaskan 2 kg air dari 20°C menjadi 30°C (c air = 4.200 J/kg°C) adalah ...",
  "topik": "Suhu dan Kalor",
  "pilihan": [ { "id": 309, "teks": "8.400 J" }, { "id": 310, "teks": "42.000 J" },
               { "id": 311, "teks": "168.000 J" }, { "id": 312, "teks": "84.000 J" } ]
} }
// Jika belum ada soal terbit:
{ "data": null, "message": "Belum ada soal terbit di mapel ini." }
```

**POST `/kuis/sesi/{sesi}/jawaban`**

```json
// request (dijawab_pada opsional, ISO 8601; dipakai saat sinkron jawaban yang tersimpan di perangkat)
{ "soal_id": 78, "pilihan_id": 309, "id_unik_klien": "7d3f0c7e-2b1a-4c55-9a1e-0f2b8c9d1e23" }
// 201 (jawaban baru)
{ "data": { "id_unik_klien": "7d3f0c7e-...", "benar": false, "pilihan_benar_id": 312, "rating_baru": 1233, "duplikat": false } }
// 200 (id_unik_klien sama dikirim ulang: tidak dicatat dua kali)
{ "data": { "id_unik_klien": "7d3f0c7e-...", "benar": false, "pilihan_benar_id": 312, "rating_baru": 1233, "duplikat": true } }
```

Saat Mode Ketat memakai batas waktu dan waktu habis, kirim `{"soal_id": 78, "pilihan_id": null, "waktu_habis": true, "id_unik_klien": "..."}`; jawaban dicatat salah. Ditolak (422) jika mapel tidak memakai batas waktu.

**POST `/kuis/sesi`** juga menerima `topik_id` (opsional) untuk sesi remedial: `{"mapel_id": 1, "topik_id": 2}` → respons memuat `"topik_fokus": {"id": 2, "nama": "Pecahan"}`.

**POST `/kuis/sesi/{sesi}/kejadian`**

```json
// request
{ "jenis": "pindah_tab", "durasi_detik": 48 }
// 201 jika Mode Ketat aktif, 200 {"data":{"dicatat":false}} jika tidak
{ "data": { "dicatat": true } }
```

**GET `/kuis/mapel/{mapel}/paket/info`** → `{ "data": { "jumlah_soal": 50, "perkiraan_ukuran_byte": 13619 } }`

**GET `/kuis/mapel/{mapel}/paket`** (tanpa `benar`, urutan soal dan pilihan diacak)

```json
{ "data": {
  "mapel": { "id": 2, "nama": "IPA", "mode_ketat": true, "proteksi_seleksi_teks": true, "batas_waktu_per_soal": null },
  "dibuat_pada": "2026-10-29T09:10:00+00:00",
  "soal": [ { "id": 61, "teks": "...", "topik": "Klasifikasi Makhluk Hidup", "pilihan": [ { "id": 241, "teks": "..." } ] } ]
} }
```

**POST `/kuis/sinkron`**

```json
// request (maks. 100 jawaban dan 100 kejadian per permintaan)
{ "mapel_id": 2, "id_sesi_klien": "1b6f...-uuid", "mulai": "2026-10-29T09:12:00+07:00", "selesai": "2026-10-29T09:20:00+07:00",
  "jawaban": [ { "soal_id": 61, "pilihan_id": 242, "id_unik_klien": "c0a1...-uuid", "dijawab_pada": "2026-10-29T09:13:05+07:00" } ],
  "kejadian": [ { "id_unik_klien": "9f2e...-uuid", "jenis": "pindah_tab", "durasi_detik": 20, "waktu": "2026-10-29T09:15:00+07:00" } ] }
// 200 (status per jawaban: tersimpan | duplikat | ditolak, misalnya soal sudah ditarik guru)
{ "data": { "sesi_id": 63, "rating_baru": 1228,
  "hasil": [ { "id_unik_klien": "c0a1...", "status": "tersimpan", "benar": true, "pilihan_benar_id": 242 } ] } }
```

**GET `/kuis/rekomendasi`**

```json
{ "data": [ { "mapel_id": 1, "mapel": "Matematika", "topik_id": 2, "topik": "Pecahan", "nilai": 50,
  "alasan": [ "Nilai Pecahan 50, di bawah 55.", "Nilai Pecahan turun 3 pertemuan berturut-turut." ] } ] }
```

**POST `/guru/mapel/{mapel}/impor/soal`** (multipart, `berkas` = CSV)

```text
topik;teks;tingkat;status;benar;salah1;salah2;salah3;salah4;salah5
Pecahan;Hasil dari 1/2 + 1/4 adalah ...;mudah;draf;3/4;2/6;1/6;2/4;;
```

```json
// 201
{ "data": { "jumlah": 1, "topik_baru": [] }, "message": "1 soal berhasil diimpor." }
// 422 (tidak ada yang disimpan)
{ "message": "...", "errors": { "berkas": [ "Baris 3: Teks soal wajib diisi. Pilihan jawaban minimal 2 item." ] } }
```

**GET `/guru/mapel/{mapel}/kualitas-soal`**

```json
{ "data": [ { "id": 12, "teks": "...", "topik": "Pecahan", "tingkat": "mudah", "status": "terbit",
  "rating_awal": 900, "rating_sekarang": 840, "jumlah_jawaban": 14, "persen_benar": 93,
  "pengecoh": [ { "id": 45, "teks": "3/8", "dipilih": 0 } ],
  "catatan": [ "Terlalu mudah: hampir semua siswa menjawab benar." ] } ] }
```

Dashboard guru juga memuat `ringkasan_kelas` (per topik: `rata_rata`, `jumlah_lemah`, `persen_lemah`) dan `kalimat_kelas` (kalimat ringkas peta kelemahan).

**POST `/kuis/sesi/{sesi}/selesai`**

```json
{ "data": { "sesi": { "id": 61, "mapel_id": 2, "mulai": "...", "selesai": "..." }, "jumlah_soal": 12, "jumlah_benar": 9 } }
```

**POST `/guru/soal`** (PUT `/guru/soal/{soal}` memakai body yang sama)

Aturan: `teks` 5-1000 karakter; `tingkat` mudah/sedang/sulit; `status` draf/terbit; `pilihan` 2-6 item, teks tidak kosong, maks. 500 karakter, tidak boleh sama; **tepat 1** pilihan benar; `topik_id` harus milik `mapel_id`.

```json
// request
{ "mapel_id": 1, "topik_id": 1, "teks": "Hasil dari 9 - 12 adalah ...", "tingkat": "mudah", "status": "terbit",
  "pilihan": [ { "teks": "-3", "benar": true }, { "teks": "3", "benar": false }, { "teks": "21", "benar": false } ] }
// 201
{ "data": { "id": 101, "mapel_id": 1, "topik_id": 1, "topik": "Bilangan Bulat", "teks": "Hasil dari 9 - 12 adalah ...",
  "tingkat": "mudah", "rating_elo": 900, "status": "terbit",
  "pilihan": [ { "id": 401, "teks": "-3", "benar": true }, { "id": 402, "teks": "3", "benar": false }, { "id": 403, "teks": "21", "benar": false } ] } }
// 422
{ "message": "Harus ada tepat 1 pilihan yang benar.", "errors": { "pilihan": ["Harus ada tepat 1 pilihan yang benar."] } }
```

**PATCH `/guru/mapel/{mapel}`** (semua field opsional)

```json
// request
{ "mode_ketat": true, "mode_pengerjaan": "paket", "proteksi_seleksi_teks": false, "batas_waktu_per_soal": 60 }
// 200
{ "data": { "id": 1, "nama": "Matematika", "mode_ketat": true, "mode_pengerjaan": "paket", "proteksi_seleksi_teks": false, "batas_waktu_per_soal": 60, "topik": [ ... ] } }
```

**POST `/guru/mapel/{mapel}/topik`**

```json
// request
{ "nama": "Statistika" }
// 201
{ "data": { "id": 11, "nama": "Statistika" } }
```

**GET `/guru/mapel/{mapel}/dashboard`**

```json
{ "data": {
  "mapel": { "id": 1, "nama": "Matematika" },
  "topik": [ { "id": 1, "nama": "Bilangan Bulat" }, { "id": 2, "nama": "Pecahan" } ],
  "siswa": [ {
    "id": 2, "nama": "Andi Pratama", "rating": 1180,
    "topik": [ { "topik_id": 2, "nilai": 50, "jumlah_jawaban": 24, "tren": [100, 100, 75, 50, 25],
                 "peringatan": [ { "jenis": "di_bawah_batas", "pesan": "Nilai Pecahan 50, di bawah 55." },
                                 { "jenis": "turun_beruntun", "pesan": "Nilai Pecahan turun 3 pertemuan berturut-turut." } ] } ],
    "peringatan": [ { "jenis": "di_bawah_batas", "pesan": "...", "topik_id": 2 } ],
    "kejadian_pindah_tab": { "jumlah": 0, "durasi_detik": 0 }
  } ],
  "jumlah_siswa_berperingatan": 3
} }
```

**GET `/ortu/anak`**

```json
{ "data": [ { "id": 2, "nama": "Andi Pratama", "email": "andi@siaga.test", "peran": "siswa" } ] }
```

**GET `/laporan/siswa/{siswa}`**

```json
{ "data": {
  "anak": { "id": 2, "nama": "Andi Pratama" },
  "soal_7_hari": 20,
  "mapel": [ {
    "mapel_id": 1, "mapel": "Matematika",
    "ringkasan": "Di Matematika, Andi masih terus berlatih. Perlu didampingi di Pecahan.",
    "topik": [ { "topik_id": 2, "topik": "Pecahan", "nilai": 50, "jumlah_jawaban": 24, "status": "perlu_bantuan", "peringatan": true,
                 "saran": "Andi masih kesulitan di Pecahan. Dampingi saat latihan, ajak membaca ulang catatan, dan sampaikan ke guru agar dibantu." } ]
  } ]
} }
```

## Keamanan

- **Autentikasi**: Laravel Sanctum mode token Bearer, token berlaku 30 hari (`SANCTUM_EXPIRATION`), dicabut saat logout. Mode token dipilih karena nantinya jawaban yang tersimpan di perangkat bisa disinkron berjam-jam kemudian tanpa masalah sesi atau CSRF kedaluwarsa, dan API mudah diuji juri lewat Postman/curl.
- **Otorisasi selalu di server**: middleware `peran:...` per grup rute, lalu Policy untuk kepemilikan (guru hanya mapel/soal miliknya, siswa hanya sesinya sendiri, orang tua hanya anaknya). Route guard di React hanya untuk kenyamanan navigasi.
- **Kunci jawaban tidak bocor**: model `PilihanSoal` menyembunyikan kolom `benar` secara bawaan, dan `SoalKuisResource` hanya mengirim `id` dan `teks` pilihan. Ada tes otomatis yang memastikan respons soal tidak memuat `"benar"`.
- **Validasi** di semua input lewat Form Request. Query memakai Eloquent/query builder dengan binding (tanpa SQL mentah gabungan string).
- **Rate limit**: login 5x/menit per email+IP, jawaban 60x/menit per siswa, API umum 120x/menit.
- **Mass assignment**: semua model memakai `$fillable`.
- **CORS dibatasi** ke asal di `CORS_ALLOWED_ORIGINS` (produksi: satu domain).
- **Pesan galat** singkat berbahasa Indonesia tanpa detail internal; `APP_DEBUG=false` di produksi.
- **Rahasia** di `.env` (tidak masuk repositori); contoh ada di `backend/.env.example`.
- Header keamanan dasar (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`) di `backend/public/.htaccess`.
- XSS: React meng-escape semua teks secara bawaan; aplikasi tidak memakai `dangerouslySetInnerHTML`.
- CSV: sel yang diawali `=`, `+`, `@` (dan `-` yang bukan angka) diberi awalan petik saat ekspor untuk mencegah formula injection di Excel. Impor dibatasi 1 MB / 300 soal dan divalidasi dengan aturan yang sama dengan form soal.
- Mode Paket: paket tidak memuat kunci; antrean jawaban disimpan per akun di perangkat, sehingga HP yang dipakai bergantian tidak mengirim jawaban atas nama akun lain. Waktu dari perangkat yang di masa depan diganti waktu server.

## Pengujian

```bash
cd backend
php artisan test
```

45 tes fitur (Pest) lulus, antara lain:
- siswa dan orang tua ditolak (403) di endpoint guru; guru ditolak mengerjakan kuis; tanpa token ditolak (401);
- login salah ditolak dan dibatasi 5x/menit;
- respons soal berikutnya tidak memuat `benar` maupun `rating_elo`;
- jawaban dinilai di server dan rating berubah; `id_unik_klien` ganda tidak tercatat dua kali;
- soal yang sama tidak muncul berturut-turut; soal salah muncul lagi setelah 3 soal; soal dipilih dekat rating siswa;
- form soal kosong, kurang dari 2 pilihan, 0 atau 2 jawaban benar, teks pilihan kosong, teks terlalu panjang, topik dari mapel lain: semua ditolak (422);
- soal draf tidak muncul di kuis;
- peringatan dini muncul dari riwayat demo; orang tua tidak bisa membuka laporan anak lain;
- pindah tab hanya dicatat saat Mode Ketat aktif dan muncul di dashboard; jawaban "waktu habis" hanya diterima jika mapel memakai batas waktu;
- paket tidak memuat `benar`; sinkron ulang tidak membuat sesi, jawaban, atau kejadian ganda; jawaban untuk soal yang sudah ditarik ditandai "ditolak";
- peta kelemahan kelas, rekomendasi remedial, sesi remedial hanya berisi satu topik;
- ekspor lalu impor ulang bank soal menghasilkan isi yang sama; impor dengan satu baris salah tidak menyimpan apa pun; sel berbahaya diamankan;
- kualitas soal hanya untuk guru.

Alur di browser (360px) juga diuji dengan Playwright: Mode Ketat (watermark, seleksi teks mati, pindah tab tercatat, waktu habis), Mode Paket (unduh, muat ulang halaman tanpa sinyal, 3 jawaban "Menunggu sinyal", sinyal kembali → "Terkirim" dan benar/salah tampil), impor CSV, kualitas soal, remedial, dan tombol WhatsApp.

## Batasan produk

Kami menulis batasan ini secara terbuka:

- **Web tidak bisa mencegah screenshot atau foto layar.** Mode Ketat hanya *mengurangi penyalinan dan melacak kebocoran* (watermark nama siswa, pencatatan pindah tab), bukan mencegahnya. Siswa yang paham teknis tetap bisa mematikan JavaScript atau membuka DevTools.
- Batas waktu per soal dihitung di perangkat siswa; server mencatat jawaban "waktu habis" yang dikirim perangkat, tetapi tidak mengukur sendiri lamanya siswa melihat soal.
- Mode Paket menyimpan soal (tanpa kunci) dan jawaban di localStorage browser. Menghapus data browser sebelum sinkron akan menghapus jawaban yang belum terkirim. Dalam Mode Paket, soal yang salah tidak langsung diulang karena penilaian baru terjadi saat sinkron.
- **Sistem tidak bisa mencegah siswa mencari jawaban dari luar** (bertanya teman, mesin pencari). Karena itu kuis diposisikan sebagai **latihan formatif** untuk menemukan siswa yang perlu dibantu, bukan ujian penentu nilai.
- Catatan pindah tab ditampilkan sebagai **"perlu dicek"**, bukan vonis curang. Pindah tab bisa terjadi karena notifikasi atau alasan wajar lain.
- **Mendukung koneksi tidak stabil, bukan tanpa internet sama sekali.** Login pertama, pengunduhan paket soal, dan sinkronisasi jawaban tetap butuh koneksi.
- Peringatan dini adalah **sinyal awal** berdasarkan aturan sederhana (di bawah 55 atau turun 3 pertemuan). Guru tetap perlu menilai konteks siswa.
- Kualitas rating soal bergantung pada jumlah jawaban; soal baru memakai rating awal dari tingkat kesulitan yang dipilih guru.
- Data di versi demo adalah data demo.

## Rencana pengembangan

Semua prioritas P0, P1, dan P2 di rancangan awal sudah dikerjakan. Ide berikutnya (belum dikerjakan):
- Impor akun siswa dan kelas lewat CSV (saat ini akun dibuat lewat seeder).
- Remedial yang juga menyarankan materi bacaan singkat per topik, ditulis guru.
- Antrean Mode Paket di IndexedDB untuk paket yang sangat besar (saat ini localStorage, cukup untuk ratusan soal).
- Notifikasi ke guru saat peringatan dini baru muncul.
- Ekspor laporan orang tua ke PDF.

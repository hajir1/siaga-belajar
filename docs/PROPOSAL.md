# Bahan Proposal: Siaga Belajar

Bagian proposal yang diminta: **Arsitektur Sistem**, **Fitur Unggulan**, **UI/UX Preview**. Diagram arsitektur, ERD, dan daftar endpoint ada di [ARSITEKTUR.md](ARSITEKTUR.md).

## Ringkasan

Siaga Belajar membantu guru SMP menemukan siswa yang mulai tertinggal **sebelum** terlambat. Siswa berlatih dengan kuis adaptif yang menyesuaikan kemampuan. Dari riwayat jawaban, sistem menghitung nilai per topik dan memberi peringatan dini ketika nilai turun tiga pertemuan berturut-turut atau berada di bawah 55. Orang tua menerima laporan dengan bahasa sederhana dan saran pendampingan per topik. Aplikasi dirancang ringan untuk sekolah dengan koneksi terbatas atau tidak stabil.

## Fitur unggulan (sudah berjalan)

1. **Peringatan dini per topik.** Dua aturan yang mudah dipahami guru: nilai di bawah 55, atau turun 3 pertemuan berturut-turut. Guru langsung melihat siapa yang perlu dibantu dan di topik apa.
2. **Peta kelemahan kelas.** Heatmap siswa x topik dan kalimat ringkas seperti "20% kelas lemah di Suhu dan Kalor (1 dari 5 siswa)", sehingga guru tahu topik mana yang perlu dibahas ulang di kelas.
3. **Remedial otomatis.** Topik yang kena peringatan muncul di beranda siswa sebagai "Perlu diulang". Latihan ulang hanya berisi topik itu dan dimulai dari soal yang sedikit lebih mudah. Deteksi dini langsung berujung tindak lanjut.
4. **Kuis adaptif berbasis rating Elo.** Soal dipilih dekat kemampuan siswa, topik lemah diprioritaskan, soal yang salah muncul lagi setelah beberapa soal, dan soal yang sama tidak muncul berturut-turut.
5. **Mode Paket untuk koneksi tidak stabil.** Unduh paket soal (tanpa kunci, dengan estimasi ukuran, sekitar 13 KB untuk 50 soal), kerjakan saat sinyal hilang, jawaban dikirim otomatis saat sinyal kembali. Status selalu jelas: "Tersimpan di perangkat", "Menunggu sinyal (N jawaban)", "Terkirim". PWA membuat aplikasi tetap terbuka tanpa sinyal setelah dimuat sekali.
6. **Mode Ketat yang jujur.** Mengurangi penyalinan (seleksi teks dan salin ditahan, urutan pilihan diacak, batas waktu per soal) dan melacak kebocoran (watermark nama + ID siswa, catatan pindah tab sebagai "perlu dicek", bukan vonis). Proteksi seleksi teks bisa dimatikan demi pembaca layar.
7. **Penilaian di server, kunci tidak bocor.** Kolom jawaban benar tidak pernah dikirim ke perangkat siswa sebelum dijawab, baik di mode online maupun di paket.
8. **Laporan orang tua yang bisa ditindaklanjuti**, lengkap dengan tombol "Kirim laporan lewat WhatsApp".
9. **Alat bantu guru**: impor/ekspor soal dan nilai lewat CSV (terbaca di Excel), serta kalibrasi kualitas soal yang menandai soal terlalu mudah/sulit, tingkat yang tidak sesuai data, dan pengecoh yang tidak pernah dipilih.
10. **Arsitektur interoperabel.** REST API JSON terdokumentasi (bisa dipakai aplikasi sekolah atau LMS lain) dan basis data relasional MySQL; 45 tes otomatis.
11. **Ringan dan aksesibel.** Mobile-first 360px, mode gelap, tombol minimal 44px, fokus keyboard terlihat, label untuk pembaca layar, warna nilai selalu disertai angka. Bundel aplikasi sekitar 100 KB (gzip).

## Rencana pengembangan

- Impor akun siswa dan kelas lewat CSV (saat ini akun dibuat lewat seeder).
- Materi bacaan singkat per topik yang ditulis guru, ditampilkan bersama latihan remedial.
- Penyimpanan Mode Paket di IndexedDB untuk paket yang sangat besar.
- Notifikasi ke guru saat peringatan dini baru muncul.
- Ekspor laporan orang tua ke PDF.

## Batasan produk (teks untuk proposal)

> Siaga Belajar adalah alat **latihan formatif** untuk deteksi dini, bukan alat ujian penentu nilai. Kami menuliskan batasannya secara terbuka:
>
> 1. Aplikasi web tidak dapat mencegah screenshot atau foto layar. Mode Ketat bertujuan **mengurangi penyalinan dan melacak kebocoran**, bukan mencegahnya sepenuhnya. Batas waktu per soal dihitung di perangkat siswa.
> 2. Sistem tidak dapat mencegah siswa mencari jawaban dari luar aplikasi. Karena itu hasil kuis dipakai untuk menemukan siswa yang perlu dibantu, bukan untuk menghukum.
> 3. Catatan pindah tab ditampilkan sebagai "perlu dicek", bukan bukti kecurangan.
> 4. Siaga Belajar **mendukung koneksi tidak stabil**, tetapi tetap membutuhkan koneksi untuk login pertama, pengunduhan paket soal, dan sinkronisasi jawaban. Jawaban Mode Paket tersimpan di browser perangkat; jika data browser dihapus sebelum sinkron, jawaban yang belum terkirim ikut hilang.
> 5. Peringatan dini memakai aturan sederhana dan transparan. Guru tetap menjadi pengambil keputusan dan perlu mempertimbangkan konteks siswa.
> 6. Data siswa pada versi demo adalah data demo.

## UI/UX Preview

Tangkapan layar 360px sudah tersedia di folder `screenshots/` (hasil uji otomatis): dashboard guru dengan peringatan dini dan peta kelemahan, kuis Mode Ketat (watermark + hitung mundur), Mode Paket tanpa sinyal ("Menunggu sinyal (3 jawaban)") dan hasil setelah terkirim, beranda siswa dengan "Perlu diulang", form soal dengan pesan validasi, impor CSV, kualitas soal, laporan orang tua (terang dan gelap). Ambil ulang dari URL publik setelah deploy jika perlu.

Prinsip desain yang bisa ditulis:
- Satu tindakan utama per layar, tombol besar (minimal 44px) untuk jari.
- Warna nilai: hijau ≥ 75, kuning 55-74, merah < 55, **selalu disertai angka** sehingga tidak bergantung pada warna.
- Bahasa sederhana dan tidak menghakimi ("Belum tepat. Soal ini akan muncul lagi nanti.").
- Navigasi keyboard: tautan "Langsung ke konten", fokus terlihat jelas, fokus pindah ke soal baru.
- Tabel lebar di dashboard bergulir di dalam kartunya sendiri, halaman tidak bergeser ke samping.

## Skrip demo 3 menit

Siapkan 2 jendela browser: jendela A untuk guru, jendela B (mode samaran / HP) untuk siswa lalu orang tua. Kata sandi semua akun `siaga123`. Jalankan `php artisan migrate:fresh --seed` sebelum demo agar data bersih.

| Waktu | Siapa | Langkah | Yang ditunjukkan |
|---|---|---|---|
| 0:00-0:15 | - | Buka URL publik. | Masalah: guru sering baru tahu siswa tertinggal saat ujian. Siaga Belajar memberi tahu lebih awal dan langsung memberi tindak lanjut. |
| 0:15-0:55 | Guru (A) | **Bank Soal** → **+ Tambah soal** → klik Simpan saat kosong (pesan validasi) → isi soal Matematika topik Pecahan, 4 pilihan, 1 benar, **Terbit**, simpan. Menu **Mapel** → Matematika: nyalakan **Mode Ketat**, batas waktu 30 detik, simpan. | Validasi klien + server, mapel/topik dari database, pengaturan per mapel. |
| 0:55-1:45 | Siswa Andi (B) | Beranda menampilkan **Perlu diulang: Pecahan**. Klik **Mulai latihan** Matematika: tampak watermark nama + ID dan hitung mundur. Jawab 1 soal, lalu pindah ke tab lain beberapa detik dan kembali (muncul "sudah 1 kali"). Jawab 1-2 soal lagi, satu sengaja salah. | Mode Ketat: mengurangi penyalinan dan melacak kebocoran. Benar/salah dinilai server. Remedial otomatis. |
| 1:45-2:30 | Guru (A) | Muat ulang **Dashboard** → Matematika. | Peringatan dini Andi (Pecahan turun 3x + di bawah 55) dan Citra (Aljabar). **Peta kelemahan kelas** dengan kalimat ringkas. **Perlu dicek**: Andi 1 kali pindah tab (bukan vonis curang). Tunjukkan tombol Unduh CSV. |
| 2:30-2:50 | Orang tua (B) | Keluar, masuk `ortu@siaga.test`. | Hanya data Andi, ringkasan sederhana, saran per topik, tombol **Kirim laporan lewat WhatsApp**. |
| 2:50-3:00 | - | Tutup dengan Mode Paket: sebut bahwa guru bisa mengubah mapel ke Mode Paket untuk sekolah bersinyal lemah. | Batasan jujur: latihan formatif, mendukung koneksi tidak stabil, tidak mencegah screenshot. |

Jika waktu tersisa atau untuk video cadangan: ubah IPA ke **Mode Paket**, siswa mengunduh paket (tampak ukuran sekitar 13 KB), matikan Wi-Fi/data di HP, kerjakan 3 soal (status "Menunggu sinyal (3 jawaban)"), nyalakan lagi (status "Terkirim", benar/salah tampil).

## Daftar uji manual (P0)

**P0.1 Data**
- `php artisan migrate:fresh --seed` berhasil; tabel `soal` berisi 100 baris; `users` berisi 1 guru, 5 siswa, 1 orang tua.

**P0.2 Auth dan peran**
- Login sandi salah → "Email atau kata sandi salah."; 6 kali berturut-turut → 429.
- Login siswa, panggil `GET /api/guru/mapel/1/dashboard` dengan tokennya → 403.
- Login siswa, buka `/guru` di browser → diarahkan ke `/siswa`.
- Tanpa token, `GET /api/me` → 401. Setelah logout, token lama → 401.

**P0.3 Kuis adaptif**
- Respons `GET /api/kuis/sesi/{id}/soal-berikutnya` tidak memuat `benar` maupun `rating_elo`.
- Jawab salah satu soal, jawab 3 soal lain → soal yang salah muncul lagi.
- Soal yang sama tidak muncul dua kali berturut-turut.
- Kirim jawaban yang sama dengan `id_unik_klien` sama dua kali → kedua kali `duplikat: true`, riwayat hanya 1.
- Siswa B memanggil sesi milik siswa A → 403.

**P0.4 Mapel/topik dinamis**
- Tambah topik "Statistika" di menu Mapel → langsung muncul di pilihan topik form soal. Tambah nama yang sama → ditolak.

**P0.5 Dashboard guru**
- Matematika: Andi punya peringatan Pecahan (turun 3x + di bawah 55), Citra Aljabar (di bawah 55). IPA: Eka Suhu dan Kalor (turun 3x), Citra Gerak dan Gaya.

**P0.6 Laporan orang tua**
- Orang tua hanya melihat Andi. `GET /api/laporan/siswa/{id Bela}` dengan token orang tua → 403.

**P0.7 Form soal dan pengaturan mapel**
- Kirim form kosong → ditolak (klien dan server). 1 pilihan saja, 0 atau 2 jawaban benar, teks pilihan kosong, teks soal > 1000 karakter → ditolak.
- Soal berstatus draf tidak muncul di kuis siswa.
- Ubah mode pengerjaan ke "paket" → tersimpan; nilai di luar online/paket lewat API → 422.

## Daftar uji manual (P1)

**Mode Ketat**
- Mapel dengan Mode Ketat: di area soal teks tidak bisa diseleksi, klik kanan/salin tidak jalan, ada watermark nama + ID siswa.
- Matikan "Proteksi seleksi teks": teks soal bisa diseleksi lagi, watermark tetap ada.
- Pindah tab beberapa detik lalu kembali → dashboard guru "Perlu dicek" bertambah.
- Mapel tanpa Mode Ketat: pindah tab tidak tercatat.
- Isi batas waktu 15 detik: hitung mundur tampil; jika dibiarkan, muncul "Waktu habis" dan tercatat salah.
- Urutan pilihan berubah saat soal yang sama muncul lagi.

**Mode Paket**
- Ubah mapel ke Mode Paket → siswa diarahkan ke halaman paket dan melihat jumlah soal + perkiraan ukuran sebelum mengunduh.
- Respons `GET /api/kuis/mapel/{id}/paket` tidak memuat `benar`.
- Setelah unduh, matikan sinyal, muat ulang halaman → aplikasi tetap terbuka (PWA), ada pita "Tidak ada sinyal".
- Kerjakan 3 soal → status "Tersimpan di perangkat. Menunggu sinyal (3 jawaban)".
- Nyalakan sinyal → status "Terkirim", hasil benar/salah tampil. Di database hanya ada 1 sesi dan 3 jawaban walau sinkron terkirim ulang.

**Peta kelemahan kelas dan WhatsApp**
- Dashboard menampilkan kalimat "X% kelas lemah di ..." dan batang per topik; sel tabel berwarna sesuai nilai.
- Laporan orang tua: tombol WhatsApp membuka wa.me dengan ringkasan laporan.

## Daftar uji manual (P2)

- Beranda Andi menampilkan "Perlu diulang: Pecahan"; latihan ulang hanya berisi soal Pecahan.
- `POST /api/kuis/sesi` dengan `topik_id` dari mapel lain → 422.
- Bank Soal → Unduh contoh format → isi → Impor → soal muncul; topik baru dibuat otomatis.
- Impor berkas dengan satu baris teks kosong → ditolak dengan nomor baris, tidak ada soal yang tersimpan.
- Ekspor bank soal lalu buka di Excel: huruf seperti ° dan ² terbaca benar; teks yang diawali "=" tidak dijalankan sebagai rumus.
- Menu Kualitas Soal menampilkan catatan (terlalu mudah, tingkat tidak sesuai, pengecoh tidak dipilih). Siswa memanggil endpoint kualitas → 403.

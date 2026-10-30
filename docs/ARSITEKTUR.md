# Arsitektur Sistem Siaga Belajar

## Diagram arsitektur

```mermaid
flowchart LR
    subgraph Perangkat["Perangkat pengguna (HP / laptop, browser)"]
        SPA["React SPA (Vite)<br/>React Router + route guard<br/>Context auth, fetch API"]
        LS[("localStorage<br/>token, paket soal tanpa kunci,<br/>antrean jawaban per akun")]
        SW["Service worker (PWA)<br/>cache aplikasi"]
        SPA --- SW
        SPA --- LS
    end

    subgraph Server["Hosting: satu domain"]
        HT["Apache .htaccess<br/>rute React → index.html<br/>/api/* → index.php"]
        subgraph Laravel["Laravel 13 (API saja)"]
            MW["Middleware<br/>auth:sanctum · peran:guru/siswa/ortu · throttle"]
            FR["Form Request<br/>(validasi)"]
            CT["Controller tipis"]
            PL["Policy<br/>(kepemilikan data)"]
            SV["Service<br/>Elo · PemilihanSoal · Penilaian<br/>NilaiTopik · PeringatanDini<br/>DashboardGuru · LaporanOrtu<br/>Paket · BerkasCsv · KualitasSoal"]
            RS["API Resource<br/>(SoalKuisResource tanpa kunci)"]
            EL["Eloquent Model"]
        end
        DB[("MySQL")]
    end

    SPA -- "HTTPS JSON<br/>Authorization: Bearer" --> HT
    HT --> MW --> FR --> CT
    CT --> PL
    CT --> SV --> EL --> DB
    CT --> RS -- JSON --> SPA
```

Alur satu jawaban kuis:

```mermaid
sequenceDiagram
    participant S as Siswa (React)
    participant A as API Laravel
    participant P as PemilihanSoalService
    participant N as PenilaianService
    participant D as MySQL
    S->>A: GET /kuis/sesi/{id}/soal-berikutnya
    A->>P: pilih soal (ulang soal salah, topik lemah, rating dekat)
    P->>D: riwayat sesi, nilai topik, rating siswa
    A-->>S: soal + pilihan (TANPA kolom benar)
    S->>A: POST /jawaban {soal_id, pilihan_id, id_unik_klien}
    A->>N: nilai di server
    N->>D: cek id_unik_klien (anti ganda)
    N->>D: transaksi: simpan riwayat, update rating siswa & soal (Elo)
    A-->>S: {benar, pilihan_benar_id, rating_baru}
```

Alur Mode Paket (koneksi tidak stabil):

```mermaid
sequenceDiagram
    participant S as Siswa (React + localStorage)
    participant A as API Laravel
    S->>A: GET /kuis/mapel/{id}/paket/info (ukuran)
    S->>A: GET /kuis/mapel/{id}/paket (soal TANPA kunci)
    Note over S: Sinyal hilang. Siswa mengerjakan,<br/>jawaban + kejadian masuk antrean
    Note over S: Status: "Menunggu sinyal (N jawaban)"
    S->>A: POST /kuis/sinkron (saat online, coba ulang otomatis)
    A-->>S: hasil per jawaban: tersimpan/duplikat/ditolak + benar/salah
    Note over S: Status: "Terkirim"
```

## ERD

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string password
        enum role "guru|siswa|ortu"
    }
    kelas {
        bigint id PK
        string nama
        bigint guru_id FK
    }
    kelas_siswa {
        bigint kelas_id PK,FK
        bigint siswa_id PK,FK
    }
    ortu_siswa {
        bigint ortu_id PK,FK
        bigint siswa_id PK,FK
    }
    mapel {
        bigint id PK
        string nama
        bigint pembuat_id FK
        bool mode_ketat
        enum mode_pengerjaan "online|paket"
        bool proteksi_seleksi_teks
        smallint batas_waktu_per_soal "detik, nullable"
    }
    topik {
        bigint id PK
        bigint mapel_id FK
        string nama "unik per mapel"
    }
    soal {
        bigint id PK
        bigint mapel_id FK
        bigint topik_id FK
        text teks
        enum tingkat "mudah|sedang|sulit"
        smallint rating_elo
        enum status "draf|terbit"
        bigint pembuat_id FK
    }
    pilihan_soal {
        bigint id PK
        bigint soal_id FK
        string teks
        bool benar "tidak pernah dikirim ke siswa"
    }
    sesi_kuis {
        bigint id PK
        bigint siswa_id FK
        bigint mapel_id FK
        uuid id_unik_klien "Mode Paket, nullable"
        bigint topik_fokus_id FK "remedial, nullable"
        timestamp mulai
        timestamp selesai
    }
    riwayat_jawaban {
        bigint id PK
        bigint sesi_id FK
        bigint siswa_id FK
        bigint soal_id FK
        bigint pilihan_id FK "nullable"
        bool benar
        bool waktu_habis
        smallint rating_sebelum
        uuid id_unik_klien "unik per siswa"
        timestamp dijawab_pada
        timestamp disinkron_pada
    }
    rating_siswa {
        bigint siswa_id PK,FK
        bigint mapel_id PK,FK
        smallint rating
    }
    kejadian_kuis {
        bigint id PK
        bigint sesi_id FK
        uuid id_unik_klien "nullable"
        enum jenis "pindah_tab|blur"
        int durasi_detik
        timestamp waktu
    }

    users ||--o{ kelas : "mengampu (guru)"
    kelas ||--o{ kelas_siswa : ""
    users ||--o{ kelas_siswa : "terdaftar (siswa)"
    users ||--o{ ortu_siswa : "orang tua"
    users ||--o{ ortu_siswa : "anak"
    users ||--o{ mapel : "membuat"
    mapel ||--o{ topik : ""
    mapel ||--o{ soal : ""
    topik ||--o{ soal : ""
    soal ||--|{ pilihan_soal : ""
    users ||--o{ sesi_kuis : "mengerjakan"
    mapel ||--o{ sesi_kuis : ""
    sesi_kuis ||--o{ riwayat_jawaban : ""
    soal ||--o{ riwayat_jawaban : ""
    pilihan_soal |o--o{ riwayat_jawaban : "dipilih"
    users ||--o{ rating_siswa : ""
    mapel ||--o{ rating_siswa : ""
    sesi_kuis ||--o{ kejadian_kuis : ""
    topik |o--o{ sesi_kuis : "fokus remedial"
```

Indeks penting: `soal(mapel_id, status, rating_elo)` untuk pemilihan soal, `riwayat_jawaban(siswa_id, id_unik_klien)` UNIQUE untuk anti jawaban ganda, `riwayat_jawaban(sesi_id, id)` untuk riwayat sesi, `topik(mapel_id, nama)` UNIQUE.

Kolom tambahan dari rancangan awal: `sesi_kuis.id_unik_klien` dan `kejadian_kuis.id_unik_klien` (UNIQUE per siswa/sesi, agar sinkron ulang Mode Paket tidak membuat data ganda), `sesi_kuis.topik_fokus_id` (sesi remedial), `riwayat_jawaban.waktu_habis` (batas waktu Mode Ketat).

Catatan skema: kolom `pilihan_id` di `riwayat_jawaban` ditambahkan dari rancangan awal agar jawaban yang dipilih siswa tercatat (berguna untuk analisis pengecoh di tahap kalibrasi soal).

## Daftar endpoint

| Metode | Path | Peran |
|---|---|---|
| POST | `/api/login` | publik |
| GET | `/api/me` | semua |
| POST | `/api/logout` | semua |
| GET | `/api/mapel` | guru, siswa |
| GET | `/api/mapel/{mapel}` | guru, siswa |
| POST | `/api/guru/mapel` | guru |
| PATCH | `/api/guru/mapel/{mapel}` | guru pemilik |
| POST | `/api/guru/mapel/{mapel}/topik` | guru pemilik |
| GET | `/api/guru/mapel/{mapel}/soal` | guru pemilik |
| GET | `/api/guru/mapel/{mapel}/dashboard` | guru pemilik |
| POST | `/api/guru/soal` | guru pemilik mapel |
| GET / PUT / DELETE | `/api/guru/soal/{soal}` | guru pemilik |
| POST | `/api/kuis/sesi` | siswa |
| GET | `/api/kuis/sesi/{sesi}/soal-berikutnya` | siswa pemilik sesi |
| POST | `/api/kuis/sesi/{sesi}/jawaban` | siswa pemilik sesi |
| POST | `/api/kuis/sesi/{sesi}/selesai` | siswa pemilik sesi |
| POST | `/api/kuis/sesi/{sesi}/kejadian` | siswa pemilik sesi |
| GET | `/api/kuis/rekomendasi` | siswa |
| GET | `/api/kuis/mapel/{mapel}/paket/info` | siswa |
| GET | `/api/kuis/mapel/{mapel}/paket` | siswa |
| POST | `/api/kuis/sinkron` | siswa |
| GET | `/api/guru/mapel/{mapel}/ekspor/nilai` | guru pemilik |
| GET | `/api/guru/mapel/{mapel}/ekspor/soal` | guru pemilik |
| POST | `/api/guru/mapel/{mapel}/impor/soal` | guru pemilik |
| GET | `/api/guru/contoh-format-soal` | guru |
| GET | `/api/guru/mapel/{mapel}/kualitas-soal` | guru pemilik |
| GET | `/api/ortu/anak` | orang tua |
| GET | `/api/laporan/siswa/{siswa}` | orang tua dari siswa, guru pengajar |

Contoh request/response lengkap ada di [README](../README.md#dokumentasi-api).

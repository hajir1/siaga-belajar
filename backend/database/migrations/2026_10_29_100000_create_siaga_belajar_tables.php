<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('kelas_siswa', function (Blueprint $table) {
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['kelas_id', 'siswa_id']);
            $table->index('siswa_id');
        });

        Schema::create('ortu_siswa', function (Blueprint $table) {
            $table->foreignId('ortu_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['ortu_id', 'siswa_id']);
            $table->index('siswa_id');
        });

        Schema::create('mapel', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->foreignId('pembuat_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('mode_ketat')->default(false);
            $table->enum('mode_pengerjaan', ['online', 'paket'])->default('online');
            $table->boolean('proteksi_seleksi_teks')->default(true);
            $table->unsignedSmallInteger('batas_waktu_per_soal')->nullable()->comment('detik');
            $table->timestamps();
        });

        Schema::create('topik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapel_id')->constrained('mapel')->cascadeOnDelete();
            $table->string('nama', 100);
            $table->timestamps();
            $table->unique(['mapel_id', 'nama']);
        });

        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mapel_id')->constrained('mapel')->cascadeOnDelete();
            $table->foreignId('topik_id')->constrained('topik')->cascadeOnDelete();
            $table->text('teks');
            $table->enum('tingkat', ['mudah', 'sedang', 'sulit']);
            $table->unsignedSmallInteger('rating_elo');
            $table->enum('status', ['draf', 'terbit'])->default('draf');
            $table->foreignId('pembuat_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['mapel_id', 'status', 'rating_elo']);
        });

        Schema::create('pilihan_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soal')->cascadeOnDelete();
            $table->string('teks', 500);
            $table->boolean('benar')->default(false);
        });

        Schema::create('sesi_kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapel')->cascadeOnDelete();
            $table->timestamp('mulai');
            $table->timestamp('selesai')->nullable();
            $table->index(['siswa_id', 'mapel_id', 'mulai']);
        });

        Schema::create('riwayat_jawaban', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_id')->constrained('sesi_kuis')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->cascadeOnDelete();
            $table->foreignId('pilihan_id')->nullable()->constrained('pilihan_soal')->nullOnDelete();
            $table->boolean('benar');
            $table->unsignedSmallInteger('rating_sebelum');
            $table->uuid('id_unik_klien');
            $table->timestamp('dijawab_pada');
            $table->timestamp('disinkron_pada');
            $table->unique(['siswa_id', 'id_unik_klien']);
            $table->index(['sesi_id', 'id']);
            $table->index(['siswa_id', 'soal_id']);
        });

        Schema::create('rating_siswa', function (Blueprint $table) {
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapel')->cascadeOnDelete();
            $table->unsignedSmallInteger('rating')->default(1000);
            $table->timestamps();
            $table->primary(['siswa_id', 'mapel_id']);
        });

        Schema::create('kejadian_kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_id')->constrained('sesi_kuis')->cascadeOnDelete();
            $table->enum('jenis', ['pindah_tab', 'blur']);
            $table->unsignedInteger('durasi_detik')->default(0);
            $table->timestamp('waktu');
            $table->index('sesi_id');
        });
    }

    public function down(): void
    {
        foreach (['kejadian_kuis', 'rating_siswa', 'riwayat_jawaban', 'sesi_kuis', 'pilihan_soal', 'soal', 'topik', 'mapel', 'ortu_siswa', 'kelas_siswa', 'kelas'] as $tabel) {
            Schema::dropIfExists($tabel);
        }
    }
};

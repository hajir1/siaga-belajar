<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan untuk P1/P2:
 * - sesi_kuis.id_unik_klien: sesi yang dibuat di perangkat (Mode Paket) agar sinkron ulang tidak membuat sesi ganda.
 * - sesi_kuis.topik_fokus_id: sesi remedial yang hanya berisi satu topik.
 * - riwayat_jawaban.waktu_habis: jawaban kosong karena batas waktu per soal habis.
 * - kejadian_kuis.id_unik_klien: kejadian pindah tab dari Mode Paket tidak tercatat dua kali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_kuis', function (Blueprint $table) {
            $table->uuid('id_unik_klien')->nullable()->after('mapel_id');
            $table->foreignId('topik_fokus_id')->nullable()->after('id_unik_klien')->constrained('topik')->nullOnDelete();
            $table->unique(['siswa_id', 'id_unik_klien']);
        });

        Schema::table('riwayat_jawaban', function (Blueprint $table) {
            $table->boolean('waktu_habis')->default(false)->after('benar');
        });

        Schema::table('kejadian_kuis', function (Blueprint $table) {
            $table->uuid('id_unik_klien')->nullable()->after('sesi_id');
            $table->unique(['sesi_id', 'id_unik_klien']);
        });
    }

    public function down(): void
    {
        Schema::table('kejadian_kuis', function (Blueprint $table) {
            $table->dropUnique(['sesi_id', 'id_unik_klien']);
            $table->dropColumn('id_unik_klien');
        });
        Schema::table('riwayat_jawaban', function (Blueprint $table) {
            $table->dropColumn('waktu_habis');
        });
        Schema::table('sesi_kuis', function (Blueprint $table) {
            $table->dropUnique(['siswa_id', 'id_unik_klien']);
            $table->dropConstrainedForeignId('topik_fokus_id');
            $table->dropColumn('id_unik_klien');
        });
    }
};

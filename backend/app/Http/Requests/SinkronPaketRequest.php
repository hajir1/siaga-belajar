<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Satu sesi Mode Paket yang dikerjakan di perangkat, dikirim saat ada sinyal. */
class SinkronPaketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mapel_id' => ['required', 'integer', 'exists:mapel,id'],
            'id_sesi_klien' => ['required', 'uuid'],
            'mulai' => ['required', 'date'],
            'selesai' => ['nullable', 'date'],
            'jawaban' => ['present', 'array', 'max:100'],
            'jawaban.*.soal_id' => ['required', 'integer'],
            'jawaban.*.pilihan_id' => ['nullable', 'integer'],
            'jawaban.*.waktu_habis' => ['sometimes', 'boolean'],
            'jawaban.*.id_unik_klien' => ['required', 'uuid', 'distinct'],
            'jawaban.*.dijawab_pada' => ['required', 'date'],
            'kejadian' => ['sometimes', 'array', 'max:100'],
            'kejadian.*.id_unik_klien' => ['required', 'uuid', 'distinct'],
            'kejadian.*.jenis' => ['required', Rule::in(['pindah_tab', 'blur'])],
            'kejadian.*.durasi_detik' => ['required', 'integer', 'min:0', 'max:86400'],
            'kejadian.*.waktu' => ['required', 'date'],
        ];
    }
}

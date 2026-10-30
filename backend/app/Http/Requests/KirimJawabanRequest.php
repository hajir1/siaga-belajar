<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KirimJawabanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'soal_id' => ['required', 'integer'],
            'pilihan_id' => ['required_unless:waktu_habis,true', 'nullable', 'integer'],
            'waktu_habis' => ['sometimes', 'boolean'],
            'id_unik_klien' => ['required', 'uuid'],
            'dijawab_pada' => ['nullable', 'date'],
        ];
    }
}

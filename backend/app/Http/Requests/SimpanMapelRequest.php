<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai untuk membuat (POST) dan mengubah pengaturan (PATCH) mapel. */
class SimpanMapelRequest extends FormRequest
{
    public function rules(): array
    {
        $wajib = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'nama' => [$wajib, 'string', 'min:2', 'max:100'],
            'mode_ketat' => ['sometimes', 'boolean'],
            'mode_pengerjaan' => ['sometimes', Rule::in(['online', 'paket'])],
            'proteksi_seleksi_teks' => ['sometimes', 'boolean'],
            'batas_waktu_per_soal' => ['sometimes', 'nullable', 'integer', 'min:10', 'max:600'],
        ];
    }
}

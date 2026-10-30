<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MulaiSesiRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mapel_id' => ['required', 'integer', 'exists:mapel,id'],
            // Sesi remedial: hanya satu topik. Topik harus milik mapel yang sama.
            'topik_id' => ['nullable', 'integer', Rule::exists('topik', 'id')->where('mapel_id', (int) $this->input('mapel_id'))],
        ];
    }
}

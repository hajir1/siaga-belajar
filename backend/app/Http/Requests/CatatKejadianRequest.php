<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatatKejadianRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(['pindah_tab', 'blur'])],
            'durasi_detik' => ['required', 'integer', 'min:0', 'max:86400'],
        ];
    }
}

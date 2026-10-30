<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanTopikRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nama' => [
                'required', 'string', 'min:2', 'max:100',
                Rule::unique('topik', 'nama')->where('mapel_id', $this->route('mapel')->id),
            ],
        ];
    }

    public function messages(): array
    {
        return ['nama.unique' => 'Topik dengan nama ini sudah ada.'];
    }
}

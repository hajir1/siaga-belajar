<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi soal pilihan ganda: 2-6 pilihan, tepat 1 benar, teks tidak kosong dan dibatasi panjangnya.
 * Topik harus milik mapel yang dipilih. Kepemilikan mapel dicek lewat Policy di controller.
 */
class SimpanSoalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Rapikan spasi agar teks yang hanya berisi spasi dianggap kosong.
        $pilihan = collect($this->input('pilihan', []))->map(fn ($p) => is_array($p) ? [
            'teks' => is_string($p['teks'] ?? null) ? trim($p['teks']) : ($p['teks'] ?? null),
            'benar' => $p['benar'] ?? false,
        ] : $p)->all();

        $this->merge([
            'teks' => is_string($this->input('teks')) ? trim($this->input('teks')) : $this->input('teks'),
            'pilihan' => $pilihan,
        ]);
    }

    public function rules(): array
    {
        return [
            'mapel_id' => ['required', 'integer', 'exists:mapel,id'],
            'topik_id' => [
                'required', 'integer',
                Rule::exists('topik', 'id')->where('mapel_id', (int) $this->input('mapel_id')),
            ],
            'teks' => ['required', 'string', 'min:5', 'max:1000'],
            'tingkat' => ['required', Rule::in(['mudah', 'sedang', 'sulit'])],
            'status' => ['required', Rule::in(['draf', 'terbit'])],
            'pilihan' => ['required', 'array', 'min:2', 'max:6'],
            'pilihan.*' => ['required', 'array:teks,benar'],
            'pilihan.*.teks' => ['required', 'string', 'max:500', 'distinct'],
            'pilihan.*.benar' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $pilihan = $this->input('pilihan');
                if (! is_array($pilihan)) {
                    return;
                }
                $jumlahBenar = collect($pilihan)->filter(fn ($p) => filter_var($p['benar'] ?? false, FILTER_VALIDATE_BOOLEAN))->count();
                if ($jumlahBenar !== 1) {
                    $validator->errors()->add('pilihan', 'Harus ada tepat 1 pilihan yang benar.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'topik_id.exists' => 'Topik tidak ditemukan di mapel ini.',
            'pilihan.min' => 'Minimal 2 pilihan jawaban.',
            'pilihan.max' => 'Maksimal 6 pilihan jawaban.',
            'pilihan.*.teks.required' => 'Teks pilihan tidak boleh kosong.',
            'pilihan.*.teks.distinct' => 'Teks pilihan tidak boleh sama.',
        ];
    }
}

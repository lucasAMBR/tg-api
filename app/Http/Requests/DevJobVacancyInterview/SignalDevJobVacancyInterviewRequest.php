<?php

namespace App\Http\Requests\DevJobVacancyInterview;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SignalDevJobVacancyInterviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'uuid',
                'exists:dev_job_vacancy_interviews,id'
            ],
            // Tipo do sinal WebRTC repassado para o outro participante
            'type' => [
                'required',
                'string',
                'in:offer,answer,candidate'
            ],
            // SDP (offer/answer) ou ICE candidate, montado pelo lado que envia
            'payload' => [
                'required'
            ]
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id')
        ]);
    }
}

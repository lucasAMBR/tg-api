<?php

namespace App\Http\Requests\Profiles\CompanyProfiles;

use App\Enums\OperationalSegmentEnum;
use App\Rules\Cellphone;
use App\Rules\Cnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyProfileRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cnpj' => ['sometimes', new Cnpj],
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'bio' => ['sometimes', 'string'],
            'phone' => ['sometimes', new Cellphone],
            'founding_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'operational_segment' => ['sometimes', Rule::enum(OperationalSegmentEnum::class)]
        ];
    }
}

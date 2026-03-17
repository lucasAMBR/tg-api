<?php

namespace App\Http\Requests\Profiles\CompanyProfiles;

use App\Enums\OperationalSegmentEnum;
use App\Rules\Cellphone;
use App\Rules\Cnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyProfileRequest extends FormRequest
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
            'cnpj' => ['required', 'unique:company_profiles,cnpj', new Cnpj],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'bio' => ['required', 'string'],
            'phone' => ['required', new Cellphone],
            'founding_date' => ['required', 'date', 'date_format:Y-m-d'],
            'operational_segment' => ['required', Rule::enum(OperationalSegmentEnum::class)]
        ];
    }
}

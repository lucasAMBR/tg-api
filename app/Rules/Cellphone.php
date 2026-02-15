<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cellphone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $regex = '/^\+55[1-9]{2}9[0-9]{8}$/';

        if (!preg_match($regex, $value)) {
            $fail('O campo :attribute deve estar no formato +5535999999999.');
        }
    }
}

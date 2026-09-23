<?php

namespace App\Rules;

use App\Support\NopHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ValidNop — validasi NOP resmi 18 digit.
 *
 * Dipakai agar pesan kesalahan menjelaskan (mis. input 14 digit diberi tahu
 * bahwa itu format ringkas lama), bukan sekadar "format invalid".
 */
class ValidNop implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! NopHelper::isValid(is_string($value) ? $value : null)) {
            $fail(NopHelper::validationMessage(is_string($value) ? $value : null));
        }
    }
}

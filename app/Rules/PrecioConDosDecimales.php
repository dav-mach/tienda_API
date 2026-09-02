<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PrecioConDosDecimales implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail('El campo :attribute debe ser un número.');
            return;
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', (string) $value)) {
            $fail('El campo :attribute no puede tener más de 2 decimales.');
        }
    }
}

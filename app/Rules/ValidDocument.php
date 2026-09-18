<?php

namespace App\Rules;

use App\Support\Documents;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidDocument implements ValidationRule
{
    /**
     * Optional expected type: 'pf' or 'pj'.
     */
    public function __construct(protected ?string $type = null) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Documents::isValid($value, $this->type)) {
            $fail(match ($this->type) {
                'pj' => 'O CNPJ informado é inválido.',
                'pf' => 'O CPF informado é inválido.',
                default => 'O CPF/CNPJ informado é inválido.',
            });
        }
    }
}
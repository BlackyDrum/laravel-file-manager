<?php

namespace App\Rules;

use App\Models\Files;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class ValidateFileName implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $filename = $value->getClientOriginalName();

        if (strlen($filename) > 64) {
            $fail('The filename cannot not be greater than 64 characters');
        }

        $file = Files::query()->where('name', '=', $filename)
            ->where('owner_id', '=', Auth::id())->first();

        if (!empty($file)) {
            $fail('You already have a file with the name ' . $filename);
        }

    }
}

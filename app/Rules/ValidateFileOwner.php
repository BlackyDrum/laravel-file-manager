<?php

namespace App\Rules;

use App\Models\Files;
use App\Models\SharedFiles;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class ValidateFileOwner implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $file = Files::query()->where('identifier', '=', $value)->first();

        try {
            SharedFiles::query()->where('user_id', '=', Auth::id())
                ->where('file_id', '=', $file->id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            if ($file->owner_id != Auth::id()) {
                $fail('You cannot access this file');
            }
        }

    }
}

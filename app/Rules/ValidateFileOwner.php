<?php

namespace App\Rules;

use App\Models\Files;
use App\Models\SharedFiles;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ValidateFileOwner implements ValidationRule
{
    private $privilege;

    function __construct($privilege)
    {
        $this->privilege = $privilege;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $file = Files::query()->where('identifier', '=', $value)->first();

        if ($file->owner_id == Auth::id()) {
            // always let the owner of the file through
        }
        else {
            $shared = SharedFiles::query()
                ->where('user_id', '=', Auth::id())
                ->where('file_id', '=', $file->id)
                ->join('shared_file_has_privilege', 'shared_file_has_privilege.shared_file_id', '=', 'shared_files.id')
                ->join('shared_files_privileges', 'shared_files_privileges.id', '=', 'shared_file_has_privilege.privilege_id')
                ->select('shared_files_privileges.privilege')
                ->get();

            $hasPrivilege = false;
            foreach ($shared as $p) {
                if ($p['privilege'] == $this->privilege) {
                    $hasPrivilege = true;
                    break;
                }
            }

            if (!$hasPrivilege) {
                $fail('You do not have the required privileges');
            }
        }

    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedFileHasPrivilege extends Model
{
    use HasFactory;

    protected $table = 'shared_file_has_privilege';

    protected $fillable = [
        'shared_file_id',
        'privilege_id'
    ];
}

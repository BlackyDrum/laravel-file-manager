<?php

namespace Database\Seeders;

use App\Models\SharedFilesPrivileges;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppData extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $privileges = ['download', 'rename', 'delete'];

        foreach ($privileges as $privilege) {
            SharedFilesPrivileges::query()->create([
                'privilege' => $privilege
            ]);
        }
    }
}

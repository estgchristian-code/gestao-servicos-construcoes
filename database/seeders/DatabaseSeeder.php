<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Only fixed/system data is seeded. Companies and users are created
     * on demand (first access) via the `tenant:create` artisan command.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
    }
}

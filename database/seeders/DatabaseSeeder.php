<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Order matters: departments own users, users log cases, cases carry
     * remarks, and notifications are derived from everything above.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            UserSeeder::class,
            CaseSeeder::class,
            CaseCommentSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}

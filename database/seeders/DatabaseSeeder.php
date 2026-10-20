<?php

namespace Database\Seeders;

use Database\Seeders\LearningSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LearningSeeder::class,
            Grade9CurriculumSeeder::class,
            Grade10CurriculumSeeder::class,
            Grade10BiologyNoteSeeder::class,
            Grade11CurriculumSeeder::class,
            Grade12CurriculumSeeder::class,
            LatestUpdateSeeder::class,
        ]);
    }
}

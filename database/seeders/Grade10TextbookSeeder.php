<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class Grade10TextbookSeeder extends Seeder
{
    /**
     * Run the database seeds for Grade 10 Textbooks.
     */
    public function run(): void
    {
        $this->command->info('Running Grade 10 Textbook Seeder...');
        Artisan::call('textbooks:attach-grade10', [], $this->command->getOutput());
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update database records containing space in folder name 'textbooks/grade 10' or 'textbooks/Grade 10' -> 'textbooks/grade10'
        DB::table('textbooks')
            ->where(function ($query) {
                $query->where('file_url', 'like', '%textbooks/grade 10%')
                      ->orWhere('file_url', 'like', '%textbooks/Grade 10%')
                      ->orWhere('file_url', 'like', '%textbooks/grade_10%');
            })
            ->get()
            ->each(function ($textbook) {
                $newUrl = preg_replace('/textbooks\/grade[\s_]+10/i', 'textbooks/grade10', $textbook->file_url);
                if ($newUrl !== $textbook->file_url) {
                    DB::table('textbooks')->where('id', $textbook->id)->update(['file_url' => $newUrl]);
                }
            });

        // Safely migrate physical directory files if legacy folder exists
        $legacyFolders = [
            storage_path('app/public/textbooks/grade 10'),
            storage_path('app/public/textbooks/Grade 10'),
        ];
        $targetFolder = storage_path('app/public/textbooks/grade10');

        if (!is_dir($targetFolder)) {
            @mkdir($targetFolder, 0755, true);
        }

        foreach ($legacyFolders as $legacyFolder) {
            if (is_dir($legacyFolder) && $legacyFolder !== $targetFolder && !is_link($legacyFolder)) {
                $files = array_diff(scandir($legacyFolder), ['.', '..']);
                foreach ($files as $file) {
                    $src = $legacyFolder . '/' . $file;
                    $dst = $targetFolder . '/' . $file;
                    if (is_file($src) && !file_exists($dst)) {
                        @rename($src, $dst);
                    }
                }
                $remaining = array_diff(scandir($legacyFolder), ['.', '..']);
                if (empty($remaining)) {
                    @rmdir($legacyFolder);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('textbooks')
            ->where('file_url', 'like', '%textbooks/grade10%')
            ->get()
            ->each(function ($textbook) {
                $oldUrl = str_replace('textbooks/grade10', 'textbooks/grade 10', $textbook->file_url);
                DB::table('textbooks')->where('id', $textbook->id)->update(['file_url' => $oldUrl]);
            });

        $legacyFolder = storage_path('app/public/textbooks/grade 10');
        $newFolder = storage_path('app/public/textbooks/grade10');

        if (is_dir($newFolder) && !is_dir($legacyFolder)) {
            @rename($newFolder, $legacyFolder);
        }
    }
};

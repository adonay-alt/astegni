<?php

namespace Database\Seeders;

use App\Models\LatestUpdate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LatestUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Clears all old updates and seeds 3 brand new book-related updates.
     */
    public function run(): void
    {
        // 1. Ensure public storage updates directory exists
        Storage::disk('public')->makeDirectory('updates');

        // 2. Remove all existing latest updates
        LatestUpdate::query()->delete();

        // 3. Clear public cache for latest updates
        Cache::forget('cms_latest_updates_list_en');
        Cache::forget('cms_latest_updates_list_am');

        // 4. Book image paths
        $imagePath1 = 'updates/curriculum_textbooks_guide.jpg';
        $imagePath2 = 'updates/chapter_summary_ai_notes.jpg';
        $imagePath3 = 'updates/national_exam_workbooks.jpg';

        // Ensure fallback images exist if high-res pictures are not yet uploaded
        $this->ensureImageExists($imagePath1, 'Curriculum Textbooks & Guides', '#1e3a8a');
        $this->ensureImageExists($imagePath2, 'Chapter Summary Booklets & Notes', '#065f46');
        $this->ensureImageExists($imagePath3, 'National Exam Prep Workbooks', '#7c2d12');

        // 5. Seed 3 new book-related updates
        $updates = [
            [
                'title' => 'New Curriculum Textbooks & Digital Study Guides Released',
                'title_am' => 'አዳዲስ የስርዓተ ትምህርት የመማሪያ መጽሐፍት እና የዲጂታል ጥናት መመሪያዎች ወጡ',
                'description' => 'Explore our newly updated library of Ethiopian curriculum textbooks for Grades 9 through 12. Read, stream, and study chapter-by-chapter with interactive digital notes, formula sheets, and downloadable study guides.',
                'description_am' => 'ከ9ኛ እስከ 12ኛ ክፍል ያሉ የተሟሉ አዳዲስ የኢትዮጵያ ስርዓተ ትምህርት የመማሪያ መጽሐፍትን በዲጂታል ቤተ-መጽሐፍታችን ያግኙ። በይነተገናኝ ማስታወሻዎች፣ የቀመር ሰንጠረዦች እና አጋዥ የጥናት መመሪያዎች ጋር በምዕራፍ ተከፋፍለው ያንብቡ እና ያጥኑ።',
                'image_path' => $imagePath1,
                'is_active' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Interactive Chapter Summary Booklets & AI Study Notes Available',
                'title_am' => 'በይነተገናኝ የምዕራፍ ማጠቃለያ መጽሐፍት እና የ AI የጥናት ማስታወሻዎች ቀረቡ',
                'description' => 'Master complex subjects faster with our comprehensive chapter summary booklets. Each booklet provides visual concept maps, key term glossaries, and AI-curated revision notes to boost exam performance.',
                'description_am' => 'ውስብስብ ትምህርቶችን በቀላሉ ለመረዳት የተዘጋጁ አጠቃላይ የምዕራፍ ማጠቃለያ መጽሐፍትን ይጠቀሙ። እያንዳንዱ መጽሐፍ የእይታ ጽንሰ-ሀሳብ ካርታዎችን፣ የቁልፍ ቃላት ፍቺዎችን እና በ AI የተዘጋጁ የማሻሻያ ማስታወሻዎችን ያካተተ ነው።',
                'image_path' => $imagePath2,
                'is_active' => true,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'National Exam Preparation Workbooks with Solved Past Papers',
                'title_am' => 'የብሔራዊ ፈተና የዝግጅት ልምምድ መጽሐፍት እና ያለፉ ፈተናዎች መፍትሄዎች',
                'description' => 'Prepare confidently for national matric and university entrance exams with our latest practice workbooks. Features 5+ years of verified past exam questions with step-by-step detailed explanations and answer keys.',
                'description_am' => 'ከ5+ ዓመታት በላይ ያለፉ የፈተና ጥያቄዎችን ደረጃ በደረጃ ዝርዝር ማብራሪያዎች እና መልሶች በያዙ አዳዲስ የዝግጅት ልምምድ መጽሐፍት ለብሔራዊ የማትሪክ እና ዩኒቨርሲቲ መግቢያ ፈተናዎች በልበ ሙሉነት ይዘጋጁ።',
                'image_path' => $imagePath3,
                'is_active' => true,
                'published_at' => now(),
            ],
        ];

        foreach ($updates as $data) {
            LatestUpdate::create($data);
        }
    }

    /**
     * Helper to ensure an image exists on disk, generating a fallback graphic if missing.
     */
    private function ensureImageExists(string $relativePath, string $text, string $hexBgColor): void
    {
        $fullPath = Storage::disk('public')->path($relativePath);

        if (file_exists($fullPath)) {
            return;
        }

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!function_exists('imagecreatetruecolor')) {
            return;
        }

        $width = 1200;
        $height = 630;
        $image = imagecreatetruecolor($width, $height);

        $hex = ltrim($hexBgColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $bgColor = imagecolorallocate($image, $r, $g, $b);
        $textColor = imagecolorallocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $bgColor);

        $font = 5;
        $textX = (int) (($width - (strlen($text) * imagefontwidth($font))) / 2);
        $textY = (int) (($height - imagefontheight($font)) / 2);
        imagestring($image, $font, $textX, $textY, $text, $textColor);

        imagejpeg($image, $fullPath, 90);
        imagedestroy($image);
    }
}

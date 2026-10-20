<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Grade9CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds for Grade 9 curriculum.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Find existing Grade 9 record dynamically (Do not hardcode ID)
            $grade = Grade::where('name', 'Grade 9')->firstOrFail();

            // 2. Define Grade 9 Curriculum Data (12 subjects, 93 chapters)
            $curriculum = [
                'Biology' => [
                    'Introduction to Biology',
                    'Characteristics and classification of organisms',
                    'Cells',
                    'Reproduction',
                    'Human health, nutrition and diseases',
                    'Ecology',
                ],
                'Geography' => [
                    'Geological History and Topography of Ethiopia',
                    'Climate of Ethiopia',
                    'Natural Resource Base of Ethiopia',
                    'Population and Demographic Characteristics of Ethiopia',
                    'Major Economic and Cultural Activities in Ethiopia',
                    'Human – Natural Environment Interactions in Ethiopia',
                    'Contemporary Geographic Issues and Public Concerns in Ethiopia',
                    'Geography Inquiry Skills and Techniques',
                ],
                'Mathematics' => [
                    'Further on Sets',
                    'The Number System',
                    'Solving Equations',
                    'Solving Inequalities',
                    'Introduction to Trigonometry',
                    'Regular Polygons',
                    'Congruency and Similarity',
                    'Vectors in Two Dimensions',
                    'Statistics and Probability',
                ],
                'History' => [
                    'The Discipline of History and Human Evolution',
                    'Ancient World Civilizations up to c. 500 AD',
                    'Peoples and States in Ethiopia and the Horn to the end of 13th C.',
                    'The Middle Ages and Early Modern World, C. 500 to 1750s',
                    'Peoples and States of Africa to 1500',
                    'Africa and the Outside World 1500-1880s',
                    'States, Principalities, Population Movements & Interactions in Ethiopia 13th to Mid-16th C.',
                    'Political, Social and Economic Processes in Ethiopia Mid-16th to Mid-19th C.',
                    'The Age of Revolutions 1750s to 1815',
                ],
                'Health and Physical Education' => [
                    'Sport and Society',
                    'Health and Physical Fitness',
                    'Athletics',
                    'Football',
                    'Volleyball',
                    'Basketball',
                    'Handball',
                    'Self-Defense and Sport Ethics',
                ],
                'Physics' => [
                    'Physics and Human Society',
                    'Physical Quantities',
                    'Motion in a Straight Line',
                    'Force, Work, Energy and Power',
                    'Simple Machines',
                    'Mechanical Oscillation and Sound Wave',
                    'Temperature and Thermometry',
                ],
                'Information Technology' => [
                    'Organization of Files',
                    'Computer Network',
                    'Application Software',
                    'Image Processing and Multimedia',
                    'Information and Computer Security',
                    'Fundamentals of Programming',
                ],
                'English for Ethiopia' => [
                    'Living in Urban Areas',
                    'Study Skills',
                    'Traffic Accident',
                    'National Parks',
                    'Horticulture',
                    'Poverty in Ethiopia',
                    'Community Services',
                    'Communicable Diseases',
                    'Fairness and Equity',
                    'The Internet',
                ],
                'Chemistry' => [
                    'Chemistry and Its Importance',
                    'Measurements and Scientific Methods',
                    'Structure of the Atom',
                    'Periodic Classification of Elements',
                    'Chemical Bonding',
                ],
                'Economics' => [
                    'Introducing Economics',
                    'The Basic Economic Problems and Economic Systems',
                    'Economic Resources and Markets',
                    'Introduction to Demand and Supply',
                    'Introduction to Production and Cost',
                    'Introduction to Money',
                    'Introduction to Macroeconomics',
                    'Basic Entrepreneurship',
                ],
                'Citizenship Education' => [
                    'Ethical Values',
                    'The Culture of Using Digital Technology',
                    'Constitutionalism',
                    'Understanding Indigenous Knowledge',
                    'Multiculturalism in Ethiopia',
                    'National Unity Through Diversity',
                    'Problem Solving Skills',
                    'Ethiopia\'s Foreign Relations in East Africa',
                ],
                'Amharic' => [
                    'ቋንቋ',
                    'ግብር',
                    'ማዕድን ማውጣት',
                    'ልቦለድ',
                    'ቃላዊ ስነፅሑፍ',
                    'ግጥም',
                    'ኮቪድ -19 (ኮሮና)',
                    'ተውኔት',
                    'ማህበራዊ መገናኛ ብዙኃን እና ተግባቦት',
                ],
            ];

            // 3. Seed/Reconcile Grade 9 subjects and chapters
            foreach ($curriculum as $subjectTitle => $chapters) {
                $subject = Subject::firstOrCreate(
                    [
                        'grade_id' => $grade->id,
                        'title'    => trim($subjectTitle),
                    ],
                    [
                        'description' => "Grade 9 {$subjectTitle} curriculum.",
                    ]
                );

                foreach ($chapters as $index => $chapterTitle) {
                    $chapterNumber = $index + 1;
                    $cleanTitle = trim($chapterTitle);

                    Chapter::updateOrCreate(
                        [
                            'grade_id'       => $grade->id,
                            'subject_id'     => $subject->id,
                            'chapter_number' => $chapterNumber,
                        ],
                        [
                            'title'       => $cleanTitle,
                            'description' => "Chapter {$chapterNumber}: {$cleanTitle}",
                        ]
                    );
                }
            }
        });

        // Clear query cache so API requests return refreshed data immediately
        Cache::flush();
    }
}

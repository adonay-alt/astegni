<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Grade10CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds for Grade 10 curriculum.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Find existing Grade 10 record (Do not hardcode ID or recreate Grade)
            $grade = Grade::where('name', 'Grade 10')->firstOrFail();

            // 2. Fetch existing subjects for Grade 10
            $existingSubjectIds = Subject::where('grade_id', $grade->id)->pluck('id')->toArray();

            // 3. Remove old chapters and subjects for Grade 10
            if (!empty($existingSubjectIds)) {
                Chapter::whereIn('subject_id', $existingSubjectIds)
                    ->orWhere('grade_id', $grade->id)
                    ->delete();

                Subject::whereIn('id', $existingSubjectIds)->delete();
            } else {
                Chapter::where('grade_id', $grade->id)->delete();
            }

            // 4. Define Grade 10 Curriculum Data
            $curriculum = [
                'Physics' => [
                    'Vector Quantities',
                    'Uniformly Accelerated Motion',
                    'Elasticity and Static Equilibrium of Rigid Body',
                    'Static and Current Electricity',
                    'Magnetism',
                    'Electromagnetic Waves and Geometrical Optics',
                ],
                'Mathematics' => [
                    'Relations and Functions',
                    'Polynomial Functions',
                    'Exponential and Logarithmic Functions',
                    'Trigonometric Functions',
                    'Circles',
                    'Solid Figures',
                    'Coordinate Geometry',
                ],
                'Information Technology' => [
                    'Organization of Files',
                    'Computer Network',
                    'Application Software',
                    'Image Processing and Multimedia',
                    'Information and Computer Security',
                    'Fundamentals of Programming',
                ],
                'Health and Physical Education' => [
                    'Sport and Society',
                    'Health and Physical Fitness',
                    'Athletics',
                    'Football',
                    'Volleyball',
                    'Basketball',
                    'Handball',
                    'Self-Defence and Sport Ethics',
                ],
                'Geography' => [
                    'Landforms of Africa',
                    'Climate of Africa',
                    'Natural Resource Base of Africa',
                    'Population of Africa',
                    'Major Economic and Cultural Activities of Africa',
                    'Human – Natural Environment Interactions',
                    'Geographic Issues and Public Concerns in Africa',
                    'Geospatial Information and Data Processing',
                ],
                'አማርኛ ቋንቋ' => [
                    'ቋንቋና ማህበረሰብ',
                    'ባህላዊ ጋብቻ',
                    'ሴቶች እና እድገት',
                    'በልዩነት ውስጥ ያለ አንድነት',
                    'የቋንቋ ለዛ',
                    ' የታላላቆች ሚና',
                    'ረጅም ልብወለድ',
                    'ማህበራዊ መገናኛ ብዙኃን',
                    'ሥራ ፈጠራ',
                ],
                'Biology' => [
                    'Sub-fields of Biology',
                    'Plants',
                    'Human Biology',
                    'Ecology',
                    'Health and Disease',
                    'Ecology and Environment',
                ],
                'History' => [
                    'Development of Capitalism and Nationalism 1815–1914',
                    'Africa & the Colonial Experience (1880s–1960s)',
                    'Social, Economic & Political Developments in Ethiopia mid 19th c to 1941',
                    'Society and Politics in the Age of World Wars 1914–1945',
                    'Global and Regional Developments Since 1945',
                    'Ethiopia: Internal Developments and External Influence: from 1941 to 1991',
                    'Africa Since 1960',
                    'Post-1991 Developments in Ethiopia',
                    'Indigenous Knowledge and Heritages of Ethiopia',
                ],
                'Economics' => [
                    'Theory of Consumer Behaviour',
                    'Theories of Demand and Supply',
                    'Theories of Production and Cost',
                    'Market Structure',
                    'Banking and Finance',
                    'Economic Growth',
                    'The Ethiopian Economy',
                    'Business Startups and Innovation',
                ],
                'English for Ethiopia' => [
                    'Population Growth',
                    'Travel Behaviors',
                    'Punctuality',
                    'Tourist Attractions',
                    'Honey Processing',
                    'Migration',
                    'Branding Ethiopia and National Identity',
                    'The Healing Power of Plants',
                    'Multilingualism',
                    'Digital Vs Satellite Television',
                ],
                'Citizenship Education' => [
                    'Democracy and Democratization',
                    'Citizens in the Digital Technology Age',
                    'Understanding Good Governance',
                    'Peace and Indigenous Conflict Resolution Mechanisms',
                    'Federalism in Ethiopia',
                    'Human Rights',
                    'Patriotism',
                    'Globalization and Global Issues',
                ],
                'Chemistry' => [
                    'Chemical Reactions and Stoichiometry',
                    'Solutions',
                    'Important Inorganic Compounds',
                    'Energy Changes and Electrochemistry',
                    'Metals and Nonmetals',
                    'Hydrocarbons and Their Natural Sources',
                ],
            ];

            // 5. Seed subjects and chapters
            foreach ($curriculum as $subjectTitle => $chapters) {
                $subject = Subject::firstOrCreate(
                    [
                        'grade_id' => $grade->id,
                        'title'    => trim($subjectTitle),
                    ],
                    [
                        'description' => "Grade 10 {$subjectTitle} curriculum.",
                    ]
                );

                foreach ($chapters as $index => $chapterTitle) {
                    $chapterNumber = $index + 1;
                    $cleanTitle = trim($chapterTitle);

                    Chapter::firstOrCreate(
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

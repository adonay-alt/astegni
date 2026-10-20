<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Grade12CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds for Grade 12 curriculum by stream.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Find existing Grade 12 record dynamically (Do not hardcode ID)
            $grade = Grade::where('name', 'Grade 12')->firstOrFail();

            // 2. Find or ensure Grade 12 Streams exist dynamically
            $naturalStream = Stream::firstOrCreate([
                'grade_id' => $grade->id,
                'name'     => 'Natural Science',
            ]);

            $socialStream = Stream::firstOrCreate([
                'grade_id' => $grade->id,
                'name'     => 'Social Science',
            ]);

            // 3. Define Grade 12 Curriculum Data by Stream (10 subjects, 79 chapters)
            $curriculum = [
                // NATURAL SCIENCE
                [
                    'title'     => 'Physics',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Application of physics in other ﬁelds',
                        'Two-dimensional motion',
                        'Fluid Mechanics',
                        'Electromagnetism',
                        'Basics of electronics',
                    ],
                ],
                [
                    'title'     => 'Chemistry',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Acid-Base Equilibrium',
                        'Electrochemistry',
                        'Industrial Chemistry',
                        'Polymers',
                        'Introduction To Environmental Chemistry',
                    ],
                ],
                [
                    'title'     => 'Agriculture',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Vegetable Crops Production and Management',
                        'Fruit Crops Production and Management',
                        'Root and Tuber Crops Production and Management',
                        'Coffee, Tea and Spices Production and Management',
                        'Introduction to Plant Biotechnology',
                        'Beef Cattle Production and Management',
                        'Sheep and Goat Production and Management',
                        'Camel Production and Management',
                        'Poultry Production and Management',
                        'Fishery Production and Management',
                        'Apiculture',
                        'Nursery and Plantation Technology',
                        'Basics of Agro-Forestry Systems and Practices',
                        'Soil and Water Conservation',
                        'Gender and Human Nutrition',
                        'Safe Food Production and Postharvest Handling',
                        'Application of Information and Communication Technologies (ICT) in Agriculture',
                    ],
                ],
                [
                    'title'     => 'Biology',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Application of Biology',
                        'Microorganisms',
                        'Energy transformation',
                        'Evolution',
                        'Human Body System',
                        'Climate Change',
                    ],
                ],
                [
                    'title'     => 'Information Technology',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Information Systems and Their Applications',
                        'Emerging Technologies',
                        'Database Management System',
                        'Web Authoring',
                        'Maintenance and Troubleshooting',
                        'Fundamentals of Programming',
                    ],
                ],

                // BOTH STREAMS (stream_id = null)
                [
                    'title'     => 'Mathematics',
                    'stream_id' => null,
                    'chapters'  => [
                        'Sequences and Series',
                        'Introductions to Calculus',
                        'Statistics',
                        'Introduction to Linear Programming',
                        'Mathematical Applications in Business',
                    ],
                ],
                [
                    'title'     => 'English',
                    'stream_id' => null,
                    'chapters'  => [
                        'Sustainable Development',
                        'Time Management',
                        'Evidence on Traffic Accident',
                        'Natural Resource Management',
                        'Mechanized Agriculture',
                        'Green Economies',
                        'National Pride',
                        'Telemedicine',
                        'Conflict Management',
                        'Robotics',
                    ],
                ],

                // SOCIAL SCIENCE
                [
                    'title'     => 'Economics',
                    'stream_id' => $socialStream->id,
                    'chapters'  => [
                        'The Fundamental Concepts of Macroeconomics',
                        'Aggregate Demand and Aggregate Supply Analysis',
                        'Market Failure and Consumer Protection',
                        'Macroeconomic Policy Instruments',
                        'Tax Theory and Practice',
                        'Poverty and Inequality',
                        'Macroeconomic Reforms in Ethiopia',
                        'Economy, Environment and Climate Change',
                    ],
                ],
                [
                    'title'     => 'History',
                    'stream_id' => $socialStream->id,
                    'chapters'  => [
                        'Development of Capitalism and Nationalism from 1815 to 1914',
                        'Africa and the Colonial Experience (1880s – 1960s)',
                        'Social, Economic and Political Developments in Ethiopia, Mid. 19th C. to 1941',
                        'Society and Politics in the Age of World Wars, 1914 – 1945',
                        'Global and Regional Developments Since 1945',
                        'Ethiopia: Internal Developments and External Influences from 1941 to 1991',
                        'Africa since the 1960s',
                        'Post 1991 Developments in Ethiopia',
                        'Indigenous Knowledge Systems and Heritages of Ethiopia',
                    ],
                ],
                [
                    'title'     => 'Geography',
                    'stream_id' => $socialStream->id,
                    'chapters'  => [
                        'Major Geological Processes Associated with Plate Tectonics',
                        'Climate Change',
                        'Management of Conflict Over Resources',
                        'Population Policies, Programs and the Environment',
                        'Challenges of Economic Development',
                        'Solutions to Environmental and Sustainability Problems',
                        'Contemporary Global Geographic Issues and Public Concerns',
                        'Geographical Enquiry and Map Making',
                    ],
                ],
            ];

            // 4. Seed Grade 12 subjects and chapters idempotently
            foreach ($curriculum as $subjectData) {
                $subjectTitle = trim($subjectData['title']);
                $streamId = $subjectData['stream_id'];

                $subject = Subject::firstOrCreate(
                    [
                        'grade_id'  => $grade->id,
                        'title'     => $subjectTitle,
                        'stream_id' => $streamId,
                    ],
                    [
                        'description' => "Grade 12 {$subjectTitle} curriculum.",
                    ]
                );

                foreach ($subjectData['chapters'] as $index => $chapterTitle) {
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

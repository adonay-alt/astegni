<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Grade11CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds for Grade 11 curriculum by stream.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Find existing Grade 11 record dynamically (Do not hardcode ID)
            $grade = Grade::where('name', 'Grade 11')->firstOrFail();

            // 2. Find or ensure Grade 11 Streams exist dynamically
            $naturalStream = Stream::firstOrCreate([
                'grade_id' => $grade->id,
                'name'     => 'Natural Science',
            ]);

            $socialStream = Stream::firstOrCreate([
                'grade_id' => $grade->id,
                'name'     => 'Social Science',
            ]);

            // 3. Fetch existing subjects for Grade 11
            $existingSubjectIds = Subject::where('grade_id', $grade->id)->pluck('id')->toArray();

            // 4. Remove old chapters and subjects for Grade 11 safely
            if (!empty($existingSubjectIds)) {
                Chapter::whereIn('subject_id', $existingSubjectIds)
                    ->orWhere('grade_id', $grade->id)
                    ->delete();

                Subject::whereIn('id', $existingSubjectIds)
                    ->orWhere('grade_id', $grade->id)
                    ->delete();
            } else {
                Chapter::where('grade_id', $grade->id)->delete();
            }

            // 5. Define Grade 11 Curriculum Data by Stream (10 subjects, 86 chapters)
            $curriculum = [
                // NATURAL SCIENCE
                [
                    'title'     => 'Chemistry',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Atomic Structure and Periodic Properties of the Elements',
                        'Chemical Bonding',
                        'Physical States of Matter',
                        'Chemical Kinetics',
                        'Chemical Equilibrium',
                        'Some Important Oxygen-Containing Organic Compounds',
                    ],
                ],
                [
                    'title'     => 'Physics',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Physics and Human Society',
                        'Vectors',
                        'Motion in One and Two Dimensions',
                        'Dynamics',
                        'Heat Conduction and Calorimetry',
                        'Electrostatics and Electric Circuit',
                        'Nuclear Physics',
                    ],
                ],
                [
                    'title'     => 'Biology',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Biology and Technology',
                        'Animals',
                        'Enzymes',
                        'Genetics',
                        'The Human Body Systems',
                        'Population and Natural Resources',
                    ],
                ],
                [
                    'title'     => 'Information Technology',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Information Systems and Its Applications',
                        'Emerging Technologies',
                        'Database Management',
                        'Web Development',
                        'Hardware Troubleshooting and Preventive Maintenance',
                        'Fundamentals of Programming',
                    ],
                ],
                [
                    'title'     => 'Agriculture',
                    'stream_id' => $naturalStream->id,
                    'chapters'  => [
                        'Introduction to Crop Production',
                        'Field Crops Production and Management',
                        'Industrial Crops Production and Management',
                        'Introduction to Farm Animals',
                        'Animal Feeds and Feeding Practices',
                        'Animal Genetics and Breeding Practices',
                        'Farm Animals Housing',
                        'Basic Animal Health and Disease Control',
                        'Dairy Cattle Production and Management',
                        'Introduction to Natural Resources',
                        'Management of Natural Resources',
                        'Concepts of Biodiversity',
                        'Climate Change Adaptation and Mitigation',
                        'Mechanized Farming',
                        'Introduction to Human Nutrition',
                        'Diversified Food Production and Consumption',
                    ],
                ],

                // BOTH STREAMS (stream_id = null)
                [
                    'title'     => 'Mathematics',
                    'stream_id' => null,
                    'chapters'  => [
                        'Relations and Functions',
                        'Rational Expressions and Rational Functions',
                        'Matrices',
                        'Determinants and Their Properties',
                        'Vectors',
                        'Transformation of the Plane',
                        'Statistics',
                        'Probability',
                    ],
                ],
                [
                    'title'     => 'English',
                    'stream_id' => null,
                    'chapters'  => [
                        'Environmental Hazards',
                        'Civilization',
                        'Causes of Road Traffic Accidents',
                        'People and Natural Resources',
                        'Irrigation',
                        'Global Warming',
                        'Patriotism',
                        'Efficiency of Health Services',
                        'Indigenous Conflict Resolution',
                        'Artificial Intelligence',
                    ],
                ],

                // SOCIAL SCIENCE
                [
                    'title'     => 'Geography',
                    'stream_id' => $socialStream->id,
                    'chapters'  => [
                        'Formation of the Continents',
                        'Climate Classification and Climate Regions of Our World',
                        'Natural Resources and Conflicts Over Resources',
                        'Global Population Dynamics and Challenges',
                        'Geography and Economic Development',
                        'Major Global Environmental Changes',
                        'Geographic Issues and Public Concerns',
                        'Geo-spatial Information and Data Processing',
                    ],
                ],
                [
                    'title'     => 'Economics',
                    'stream_id' => $socialStream->id,
                    'chapters'  => [
                        'Theory of Consumer Behavior and Demand',
                        'Market Structure and the Decision of Firms',
                        'National Income Accounting',
                        'Consumption, Saving and Investment',
                        'Trade and Finance',
                        'Economic Development',
                        'Main Sectors, Sectorial Policies and Strategies of Ethiopia',
                        'Economic Planning and Econometric Models in Ethiopia',
                        'Public Finance',
                        'Globalization and Economic Integration',
                    ],
                ],
                [
                    'title'     => 'History',
                    'stream_id' => $socialStream->id,
                    'chapters'  => [
                        'History, Historiography, and Human Evolution',
                        'Major Spots of Ancient World Civilizations up to c. 500 AD',
                        'Peoples, States and Historical Processes in Ethiopia and the Horn to the End of the 13th Century',
                        'The Middle Ages and Early Modern World, c. 500 AD–1789',
                        'Peoples and States of Africa to 1500',
                        'Africa and the Outside World: 1500–1880s',
                        'States, Principalities, Population Movements and Interactions in Ethiopia (13th to Mid-16th C.)',
                        'Political, Social and Economic Processes in Ethiopia, Mid-16th to Mid-19th Century',
                        'The Age of Revolutions, 1789 to 1815',
                    ],
                ],
            ];

            // 6. Seed Grade 11 subjects and chapters
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
                        'description' => "Grade 11 {$subjectTitle} curriculum.",
                    ]
                );

                foreach ($subjectData['chapters'] as $index => $chapterTitle) {
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

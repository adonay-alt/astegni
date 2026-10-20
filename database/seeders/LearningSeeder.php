<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Resource;
use App\Models\Subject;
use App\Models\Textbook;
use App\Models\Video;
use Illuminate\Database\Seeder;

class LearningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gradesConfig = [
            'Grade 8'  => 0,
            'Grade 9'  => 1,
            'Grade 10' => 1,
            'Grade 11' => 1,
            'Grade 12' => 2,
        ];

        foreach ($gradesConfig as $gName => $gType) {
            Grade::updateOrCreate(
                ['name' => $gName],
                ['type' => $gType]
            );
        }

        $gradeNames = [];

        // Seed Grade 11 and Grade 12 streams
        $streamGrades = ['Grade 11', 'Grade 12'];
        $streamNames = ['Natural Science', 'Social Science'];

        foreach ($streamGrades as $sGradeName) {
            $sGrade = Grade::where('name', $sGradeName)->first();
            foreach ($streamNames as $sName) {
                \App\Models\Stream::firstOrCreate([
                    'grade_id' => $sGrade->id,
                    'name' => $sName,
                ]);
            }
        }

        $subjectsData = [
            'Mathematics' => [
                ['title' => 'Number Systems', 'number' => 1],
                ['title' => 'Algebra & Functions', 'number' => 2],
            ],
            'Biology' => [
                ['title' => 'Cell Biology & Function', 'number' => 1],
                ['title' => 'Genetics & Inheritance', 'number' => 2],
            ],
        ];

        foreach ($gradeNames as $gradeName) {
            $grade = Grade::firstOrCreate(['name' => $gradeName]);

            foreach ($subjectsData as $subjectTitle => $chapters) {
                $subject = Subject::firstOrCreate(
                    [
                        'grade_id' => $grade->id,
                        'title' => $subjectTitle,
                    ],
                    [
                        'description' => "Comprehensive {$subjectTitle} curriculum for {$gradeName}.",
                    ]
                );

                foreach ($chapters as $chapterData) {
                    $chapter = Chapter::firstOrCreate(
                        [
                            'grade_id' => $grade->id,
                            'subject_id' => $subject->id,
                            'chapter_number' => $chapterData['number'],
                        ],
                        [
                            'title' => $chapterData['title'],
                            'description' => "Detailed study notes and exercises for {$chapterData['title']}.",
                        ]
                    );

                    // 1. Create Quiz for Chapter
                    $quiz = Quiz::firstOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'title' => "{$chapter->title} Practice Quiz",
                        ],
                        [
                            'grade_id' => $grade->id,
                            'subject_id' => $subject->id,
                            'description' => "Test your knowledge on {$chapter->title}.",
                        ]
                    );

                    // 2. Create 2 Quiz Questions for the Quiz
                    $questionsData = [
                        [
                            'question' => "What is the primary focus of {$chapter->title}?",
                            'option_a' => 'Fundamental principles and definitions',
                            'option_b' => 'Historical context only',
                            'option_c' => 'Advanced theoretical proofs',
                            'option_d' => 'Unrelated applications',
                            'correct_answer' => 'option_a',
                            'explanation' => 'Fundamental principles form the core foundation of this topic.',
                        ],
                        [
                            'question' => "Which of the following best applies to {$chapter->title}?",
                            'option_a' => 'Random guesses',
                            'option_b' => 'Systematic analysis and problem solving',
                            'option_c' => 'Ignoring basic laws',
                            'option_d' => 'None of the above',
                            'correct_answer' => 'option_b',
                            'explanation' => 'Systematic problem solving is key to mastering this chapter.',
                        ],
                    ];

                    foreach ($questionsData as $qData) {
                        QuizQuestion::firstOrCreate(
                            [
                                'quiz_id' => $quiz->id,
                                'question' => $qData['question'],
                            ],
                            [
                                'option_a' => $qData['option_a'],
                                'option_b' => $qData['option_b'],
                                'option_c' => $qData['option_c'],
                                'option_d' => $qData['option_d'],
                                'correct_answer' => $qData['correct_answer'],
                                'explanation' => $qData['explanation'],
                            ]
                        );
                    }

                    // 3. Create Note for Chapter
                    Note::firstOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'title' => "Core Notes: {$chapter->title}",
                        ],
                        [
                            'grade_id' => $grade->id,
                            'subject_id' => $subject->id,
                            'content' => "Summary of key concepts, formulas, and definitions for {$chapter->title} in {$subjectTitle} ({$gradeName}).",
                        ]
                    );

                    // 4. Create Textbook for Chapter
                    $slugGrade = strtolower(str_replace(' ', '-', $gradeName));
                    $slugSubject = strtolower($subjectTitle);
                    $slugChapter = $chapterData['number'];

                    Textbook::firstOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'title' => "{$gradeName} {$subjectTitle} Official Textbook - Chapter {$slugChapter}",
                        ],
                        [
                            'grade_id' => $grade->id,
                            'subject_id' => $subject->id,
                            'description' => "Official Ministry of Education textbook chapter pdf.",
                            'file_url' => "https://example.com/textbooks/{$slugGrade}-{$slugSubject}-chapter-{$slugChapter}.pdf",
                        ]
                    );

                    // 5. Create Resource for Chapter
                    $resourceTypes = ['video', 'pdf', 'link'];
                    $resourceType = $resourceTypes[($chapterData['number'] - 1) % count($resourceTypes)];

                    Resource::firstOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'title' => "Supplementary Resource for {$chapter->title}",
                        ],
                        [
                            'grade_id' => $grade->id,
                            'subject_id' => $subject->id,
                            'type' => $resourceType,
                            'url' => "https://example.com/resources/{$slugGrade}-{$slugSubject}-ch{$slugChapter}-guide",
                            'description' => "Additional learning resource to aid comprehension.",
                        ]
                    );

                    // 6. Create Video for Chapter
                    Video::firstOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'title' => "Video Lesson: {$chapter->title}",
                        ],
                        [
                            'grade_id' => $grade->id,
                            'subject_id' => $subject->id,
                            'description' => "Engaging video explanation covering {$chapter->title}.",
                            'video_url' => "https://example.com/videos/{$slugGrade}-{$slugSubject}-ch{$slugChapter}.mp4",
                            'thumbnail_url' => "https://example.com/thumbnails/{$slugGrade}-{$slugSubject}-ch{$slugChapter}.jpg",
                            'duration' => "15:30",
                        ]
                    );
                }
            }
        }
    }
}

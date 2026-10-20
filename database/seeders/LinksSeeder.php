<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LinksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Grade 10' => [
                'Chemistry' => [
                    1 => ['title' => 'Chemical Reactions and Stoichiometry', 'video_url' => 'https://www.youtube.com/watch?v=LFvKi1_VTWs'],
                    2 => ['title' => 'Solutions', 'video_url' => 'https://www.youtube.com/watch?v=PBBHpS44xVM'],
                    3 => ['title' => 'Important Inorganic Compounds', 'video_url' => 'https://www.youtube.com/watch?v=oru36IoAVKE'],
                    4 => ['title' => 'Energy Changes and Electrochemistry', 'video_url' => 'https://www.youtube.com/watch?v=q4nipDygLMU'],
                    5 => ['title' => 'Metals and Nonmetals', 'video_url' => 'https://www.youtube.com/watch?v=aFkKCPHy-Xc'],
                    6 => ['title' => 'Hydrocarbons and their Natural Sources', 'video_url' => 'https://www.youtube.com/watch?v=KE8gEo29uNk'],
                ],
                'Biology' => [
                    1 => ['title' => 'Sub-fields of Biology', 'video_url' => 'https://www.youtube.com/watch?v=as9lqStjnUY'],
                    2 => ['title' => 'Plants', 'video_url' => 'https://www.youtube.com/watch?v=9XLqghRJS-0'],
                    3 => ['title' => 'Biochemical Molecules', 'video_url' => 'https://www.youtube.com/watch?v=Kae0B9fhqC0'],
                    4 => ['title' => 'Cell Reproduction', 'video_url' => 'https://www.youtube.com/watch?v=jbrYaXoDKKY'],
                    5 => ['title' => 'Human Biology', 'video_url' => 'https://www.youtube.com/watch?v=3BbKdmB4tkg'],
                    6 => ['title' => 'Ecological Interactions', 'video_url' => 'https://www.youtube.com/watch?v=LnkaIAPp4Ng'],
                ],
                'Geography' => [
                    1 => ['title' => 'Landforms of Africa', 'video_url' => 'https://www.youtube.com/watch?v=ywqSVlkSk-0'],
                    2 => ['title' => 'Climate of Africa', 'video_url' => 'https://www.youtube.com/watch?v=E1zhOg55oY0'],
                    3 => ['title' => 'Natural Resource Base of Africa', 'video_url' => 'https://www.youtube.com/watch?v=4rH6XhC797k'],
                    4 => ['title' => 'Population of Africa', 'video_url' => 'https://www.youtube.com/watch?v=1l9gOJy8ZuY'],
                    5 => ['title' => 'Major Economic and Cultural Activities of Africa', 'video_url' => 'https://www.youtube.com/watch?v=6KEnpEO3Rlw'],
                    6 => ['title' => 'Human Natural Environment Interactions', 'video_url' => 'https://www.youtube.com/watch?v=vXQ9rel-_2k'],
                    7 => ['title' => 'Geographic Issues and Public Concerns in Africa', 'video_url' => 'https://www.youtube.com/watch?v=BTsDLYY78a8'],
                    8 => ['title' => 'Geospatial Information and Data Processing', 'video_url' => 'https://www.youtube.com/watch?v=ytju062vshc'],
                ],
                'Citizenship Education' => [
                    1 => ['title' => 'Democracy and Democratization', 'video_url' => 'https://www.youtube.com/watch?v=vgCMKqXHvn0'],
                    2 => ['title' => 'Citizens in the Digital Technology Age', 'video_url' => 'https://www.youtube.com/watch?v=I6KFqb2GkiE'],
                    3 => ['title' => 'Understanding Good Governance', 'video_url' => 'https://www.youtube.com/watch?v=cgagtQ4QZps'],
                    4 => ['title' => 'Peace and Indigenous Conflict Resolution Mechanisms', 'video_url' => 'https://www.youtube.com/watch?v=I5UogNa2WWc'],
                    5 => ['title' => 'Federalism in Ethiopia', 'video_url' => 'https://www.youtube.com/watch?v=J-qitAvXUMw'],
                    6 => ['title' => 'Human Rights', 'video_url' => 'https://www.youtube.com/watch?v=nDgIVseTkuE'],
                    7 => ['title' => 'Patriotism', 'video_url' => 'https://www.youtube.com/watch?v=is6rK6t4BSs'],
                    8 => ['title' => 'Globalization and Global Issues', 'video_url' => 'https://www.youtube.com/watch?v=iWzi7stqSAg'],
                ],
 
                'Economics' => [
                   1 => ['title' => 'Theory of Consumer Behaviour', 'video_url' => 'https://www.youtube.com/watch?v=jwXFMkh4__o'],
                   2 => ['title' => 'Theories of Demand and Supply', 'video_url' => 'https://www.youtube.com/watch?v=g9aDizJpd_s'],
                   3 => ['title' => 'Theories of Production and Cost', 'video_url' => 'https://www.youtube.com/watch?v=1FAeQPNk4Wo'],
                   4 => ['title' => 'Market Structure', 'video_url' => 'https://www.youtube.com/watch?v=Hj68yXYETAs'],
                   5 => ['title' => 'Banking and Finance', 'video_url' => 'https://www.youtube.com/watch?v=M2xAWtIE7vA'],
                   6 => ['title' => 'Economic Growth', 'video_url' => 'https://www.youtube.com/watch?v=4rtuUN9f-28'],
                   7 => ['title' => 'The Ethiopian Economy', 'video_url' => 'https://www.youtube.com/watch?v=cUSs8N8g-eY'],
                   8 => ['title' => 'Business Startups and Innovation', 'video_url' => 'https://www.youtube.com/watch?v=ZPv5jFtWiBE'],
                ],
 
               'English' => [
                   1  => ['title' => 'Population Growth', 'video_url' => 'https://www.youtube.com/watch?v=kLT9gRrriWw'],
                   2  => ['title' => 'Travel Behaviors', 'video_url' => 'https://www.youtube.com/watch?v=A9N7OMk5nXg'],
                   3  => ['title' => 'Punctuality', 'video_url' => 'https://www.youtube.com/watch?v=cSlPuxN_yws'],
                   4  => ['title' => 'Tourist Attractions', 'video_url' => 'https://www.youtube.com/watch?v=qrRQKW2-HRY'],
                   5  => ['title' => 'Honey Processing', 'video_url' => 'https://www.youtube.com/watch?v=I9GBgHwhbg4'],
                   6  => ['title' => 'Migration', 'video_url' => 'https://www.youtube.com/watch?v=ArF8a2LELsw'],
                   7  => ['title' => 'Branding Ethiopia and National Identity', 'video_url' => 'https://www.youtube.com/watch?v=agWYoEjCdJA'],
                   8  => ['title' => 'The Healing Power of Plants', 'video_url' => 'https://www.youtube.com/watch?v=n0SLWX31U7k'],
                   9  => ['title' => 'Multilingualism', 'video_url' => 'https://www.youtube.com/watch?v=MMmOLN5zBLY'],
                  10 => ['title' => 'Digital Vs Satellite Television', 'video_url' => 'https://www.youtube.com/watch?v=OpkatIqkLO8'],
                ],
            ],
        ];

        foreach ($data as $gradeName => $subjects) {
            $gradeId = DB::table('grades')->where('name', $gradeName)->value('id');

            if (!$gradeId) {
                $this->command->warn("No grade found matching '{$gradeName}' — skipping.");
                continue;
            }

            foreach ($subjects as $subjectName => $chapters) {
                $subjectId = DB::table('subjects')
                    ->where('grade_id', $gradeId)
                    ->where('title', $subjectName)
                    ->value('id');

                if (!$subjectId) {
                    $this->command->warn("No subject found for '{$subjectName}' in '{$gradeName}' — skipping.");
                    continue;
                }

                $chapterIds = DB::table('chapters')
                    ->where('subject_id', $subjectId)
                    ->pluck('id', 'chapter_number');

                foreach ($chapters as $chapterNumber => $video) {
                    $chapterId = $chapterIds[$chapterNumber] ?? null;

                    if (!$chapterId) {
                        $this->command->warn("No chapter {$chapterNumber} found for {$gradeName} {$subjectName} — skipping.");
                        continue;
                    }

                    DB::table('videos')->updateOrInsert(
                        [
                            // uniqueness check — prevents duplicate rows if you rerun the seeder
                            'grade_id'   => $gradeId,
                            'subject_id' => $subjectId,
                            'chapter_id' => $chapterId,
                        ],
                        [
                            'title'      => $video['title'],
                            'video_url'  => $video['video_url'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
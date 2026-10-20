<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade10GeographyQuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            config(['database.default' => 'sqlite']);
        }

        DB::transaction(function () {
            $grade = Grade::where('name', 'Grade 10')->first();
            if (!$grade) {
                $grade = Grade::create(['name' => 'Grade 10', 'type' => 1]);
            }

            $subject = Subject::where('grade_id', $grade->id)->where('title', 'Geography')->first();
            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Geography',
                    'description' => 'Grade 10 Geography Curriculum',
                ]);
            }

            $quizzesData = $this->getQuizData();

            foreach ($quizzesData as $chapterNumber => $data) {
                $chapter = Chapter::where('subject_id', $subject->id)
                    ->where('chapter_number', $chapterNumber)
                    ->first();

                if (!$chapter) {
                    $chapter = Chapter::create([
                        'grade_id' => $grade->id,
                        'subject_id' => $subject->id,
                        'chapter_number' => $chapterNumber,
                        'title' => $data['chapter_title'],
                        'description' => "Chapter {$chapterNumber}: {$data['chapter_title']}",
                    ]);
                }

                $quiz = Quiz::firstOrCreate(
                    [
                        'grade_id' => $grade->id,
                        'subject_id' => $subject->id,
                        'chapter_id' => $chapter->id,
                    ],
                    [
                        'title' => "Chapter {$chapterNumber} Quiz: {$chapter->title}",
                        'description' => "Assessment quiz for Grade 10 Geography Chapter {$chapterNumber}.",
                    ]
                );

                foreach ($data['questions'] as $q) {
                    QuizQuestion::updateOrCreate(
                        [
                            'quiz_id' => $quiz->id,
                            'question' => $q['question'],
                        ],
                        [
                            'option_a' => $q['option_a'],
                            'option_b' => $q['option_b'],
                            'option_c' => $q['option_c'],
                            'option_d' => $q['option_d'],
                            'correct_answer' => $q['correct_answer'],
                            'explanation' => $q['explanation'],
                        ]
                    );
                }
            }
        });
    }

    private function getQuizData(): array
    {
        return array (
  1 => 
  array (
    'chapter_title' => 'Landforms of Africa',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 1,
        'q_num' => 1,
        'question' => 'What deep gorge, measuring about 1,000 meters deep, cuts between Goha Tsiyon in North Shewa and Dejen in East Gojam?',
        'option_a' => 'Great Rift Valley',
        'option_b' => 'Omo Gorge',
        'option_c' => 'Abbay Gorge',
        'option_d' => 'Tekeze Gorge',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Internal tectonic processes and external erosion over millions of years formed the Abbay Gorge, which cuts down about 1,000 m deep between Goha Tsiyon and Dejen.',
      ),
      1 => 
      array (
        'chapter' => 1,
        'q_num' => 2,
        'question' => 'Which ocean is both the largest and deepest on Earth, covering one-third of the planet\'s surface?',
        'option_a' => 'Atlantic Ocean',
        'option_b' => 'Pacific Ocean',
        'option_c' => 'Indian Ocean',
        'option_d' => 'Southern Ocean',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The oceans cover 71% of the Earth\'s surface, and the Pacific Ocean is the largest and deepest, covering one-third of the Earth.',
      ),
      2 => 
      array (
        'chapter' => 1,
        'q_num' => 3,
        'question' => 'What physical coastal feature is defined as a narrow strip of land with water on both sides that connects two larger landmasses?',
        'option_a' => 'Peninsula',
        'option_b' => 'Island',
        'option_c' => 'Isthmus',
        'option_d' => 'Strait',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'An isthmus is a narrow strip of land with water on both sides connecting two larger pieces of land, such as the Isthmus of Panama.',
      ),
      3 => 
      array (
        'chapter' => 1,
        'q_num' => 4,
        'question' => 'Where is Mount Everest, the highest peak on Earth (8,849 meters above sea level), located?',
        'option_a' => 'Between India and Pakistan',
        'option_b' => 'Between Nepal and Tibet',
        'option_c' => 'Between China and Bhutan',
        'option_d' => 'Between Afghanistan and Nepal',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Mount Everest stands at 8,849 meters above sea level and is located between Nepal and Tibet in the Himalaya Mountain range.',
      ),
      4 => 
      array (
        'chapter' => 1,
        'q_num' => 5,
        'question' => 'What areal size and percentage of the Earth\'s total surface area does the continent of Africa cover?',
        'option_a' => '44 million km² and 10%',
        'option_b' => '30.37 million km² and 6%',
        'option_c' => '24.2 million km² and 12%',
        'option_d' => '17.8 million km² and 4%',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Africa\'s areal size is 30.37 million km², covering 6% of the Earth\'s total surface area and 20% of its land area.',
      ),
      5 => 
      array (
        'chapter' => 1,
        'q_num' => 6,
        'question' => 'Which mountain range forms a high divide between the Mediterranean Sea to the north and the Sahara Desert to the south in North Africa?',
        'option_a' => 'Drakensberg Mountains',
        'option_b' => 'Atlas Mountains',
        'option_c' => 'Ethiopian Highlands',
        'option_d' => 'Ruwenzori Mountains',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Atlas Mountains extend for over 2,000 kilometers across Morocco, Algeria, and Tunisia, forming a divide between the Mediterranean Sea and the Sahara Desert.',
      ),
      6 => 
      array (
        'chapter' => 1,
        'q_num' => 7,
        'question' => 'What is the vast, flat-surfaced desert pavement covered with pebbles in the Sahara Desert called?',
        'option_a' => 'Erg',
        'option_b' => 'Graben',
        'option_c' => 'Reg',
        'option_d' => 'Highveld',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'A desert surface of sand dunes is called an erg, whereas a desert pavement covered with pebbles on top of vast flat sheets of sand is called a reg.',
      ),
      7 => 
      array (
        'chapter' => 1,
        'q_num' => 8,
        'question' => 'The Sahel is a transitional semiarid grassland region that stretches about 5,000 kilometers along the southern edge of which desert?',
        'option_a' => 'Kalahari Desert',
        'option_b' => 'Namib Desert',
        'option_c' => 'Sahara Desert',
        'option_d' => 'Karoo Desert',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The Sahel is a 5,000-kilometer stretch of semiarid grassland located directly south of the Sahara Desert, acting as its shore or transition zone.',
      ),
      8 => 
      array (
        'chapter' => 1,
        'q_num' => 9,
        'question' => 'What adaptation mechanism helps trees and shrubs in the Savanna region survive the dry season?',
        'option_a' => 'Developing broad leaves year-round',
        'option_b' => 'Shedding their leaves during the dry season',
        'option_c' => 'Growing underwater roots',
        'option_d' => 'Producing dense, tall canopies',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Drought-resistant trees and shrubs in the Savanna shed their leaves during the dry season to reduce water loss from transpiration.',
      ),
      9 => 
      array (
        'chapter' => 1,
        'q_num' => 10,
        'question' => 'Which six countries comprise the Congo Basin tropical rainforest region in Central Africa?',
        'option_a' => 'Nigeria, Ghana, Ivory Coast, Liberia, Senegal, Mali',
        'option_b' => 'Cameroon, Central African Republic, Republic of the Congo, Democratic Republic of the Congo, Gabon, Equatorial Guinea',
        'option_c' => 'Kenya, Uganda, Tanzania, Rwanda, Burundi, Ethiopia',
        'option_d' => 'Angola, Zambia, Zimbabwe, Namibia, Botswana, Mozambique',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The six African countries that make up the Congo Basin tropical rainforest are Cameroon, CAR, Republic of the Congo, DRC, Gabon, and Equatorial Guinea.',
      ),
      10 => 
      array (
        'chapter' => 1,
        'q_num' => 11,
        'question' => 'What structural term describes the elongated block of the Earth\'s crust that slides downward between two faults to form a rift valley floor?',
        'option_a' => 'Escarpment',
        'option_b' => 'Plateau',
        'option_c' => 'Graben',
        'option_d' => 'Massif',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Normal faulting stretches and ruptures the land, creating a graben—an elongated block of the crust displaced downward between two faults.',
      ),
      11 => 
      array (
        'chapter' => 1,
        'q_num' => 12,
        'question' => 'What is the largest lake in Africa, which lies in a shallow depression between the eastern and western branches of the Rift Valley?',
        'option_a' => 'Lake Tanganyika',
        'option_b' => 'Lake Malawi',
        'option_c' => 'Lake Victoria',
        'option_d' => 'Lake Turkana',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Covering 68,800 square kilometers, Lake Victoria is Africa\'s largest lake and occupies a depression between the eastern and western rifts.',
      ),
      12 => 
      array (
        'chapter' => 1,
        'q_num' => 13,
        'question' => 'What feature splits the Ethiopian highlands into northwestern and southeastern halves?',
        'option_a' => 'The Abbay River Basin',
        'option_b' => 'The Main Ethiopian Rift (Great Rift Valley)',
        'option_c' => 'The Ogaden Plain',
        'option_d' => 'The Danakil Depression',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In central Ethiopia, the Main Ethiopian Rift (Great Rift Valley) splits the central highlands into northwestern and southeastern halves.',
      ),
      13 => 
      array (
        'chapter' => 1,
        'q_num' => 14,
        'question' => 'Which cold ocean current flows along the west coast of Namibia and contributed to the formation of the Namib Desert?',
        'option_a' => 'Agulhas Current',
        'option_b' => 'Benguela Current',
        'option_c' => 'Canary Current',
        'option_d' => 'Guinea Current',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Namib Desert along the west coast of Namibia was formed partly due to the cooling effect and moisture deprivation of the offshore Benguela Cold Current.',
      ),
      14 => 
      array (
        'chapter' => 1,
        'q_num' => 15,
        'question' => 'What is the inland plateau region of South Africa bounded by the Great Karoo, Kalahari Desert, and Bushveld called?',
        'option_a' => 'The Sahel',
        'option_b' => 'The Highveld',
        'option_c' => 'The Maghreb',
        'option_d' => 'The Horn',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Highveld is the South African inland plateau section bounded by the Great Karoo to the south, the Kalahari to the west, and the Bushveld to the north.',
      ),
    ),
  ),
  2 => 
  array (
    'chapter_title' => 'Climate of Africa',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 2,
        'q_num' => 1,
        'question' => 'How did ancient Greek scholars originally divide the Earth\'s climate zones in each hemisphere?',
        'option_a' => 'Tropical, Arid, and Polar',
        'option_b' => 'Maritime, Continental, and Alpine',
        'option_c' => 'Torrid, Temperate, and Frigid',
        'option_d' => 'Equatorial, Subtropical, and Tundra',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The ancient Greeks made the first attempt at climate classification by dividing each hemisphere into three broad zones: torrid, temperate, and frigid.',
      ),
      1 => 
      array (
        'chapter' => 2,
        'q_num' => 2,
        'question' => 'In Wladimir Köppen\'s climate classification scheme, which capital letter designates climate groups defined primarily by precipitation rather than temperature?',
        'option_a' => 'Group A',
        'option_b' => 'Group B',
        'option_c' => 'Group C',
        'option_d' => 'Group D',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Köppen\'s categories A, C, D, and E are defined using temperature criteria, whereas Group B uses precipitation as its primary criterion.',
      ),
      2 => 
      array (
        'chapter' => 2,
        'q_num' => 3,
        'question' => 'What global climate zone lies between 23.5° and 40° latitude, receiving intense summer radiation and containing most of the world\'s hot deserts?',
        'option_a' => 'Tropical Zone',
        'option_b' => 'Subtropics Zone',
        'option_c' => 'Temperate Zone',
        'option_d' => 'Cold Zone',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Subtropics Zone (23.5°–40°) receives vertical sun angles and thin cloud cover with low moisture, making it the zone where most global deserts are located.',
      ),
      3 => 
      array (
        'chapter' => 2,
        'q_num' => 4,
        'question' => 'What weather term describes the warm, dry, dusty northeasterly wind that blows out of the southern Sahara across West Africa during the dry season?',
        'option_a' => 'Sirocco',
        'option_b' => 'Monsoon',
        'option_c' => 'Harmattan',
        'option_d' => 'Trade Wind',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The harmattan is a dry, dusty, northeasterly wind blowing from the Sahara across West Africa during the period of low sun.',
      ),
      4 => 
      array (
        'chapter' => 2,
        'q_num' => 5,
        'question' => 'What causes the double peak (bimodal) rainfall pattern in regions with an equatorial climate?',
        'option_a' => 'Fluctuations in trade winds',
        'option_b' => 'The double passage of the Inter-Tropical Convergence Zone (ITCZ)',
        'option_c' => 'Cold ocean currents off the eastern coast',
        'option_d' => 'Seasonal monsoon wind reversals',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Equatorial regions experience two peak periods of rainfall matching the double passage of the sun and the Inter-Tropical Convergence Zone (ITCZ) overhead.',
      ),
      5 => 
      array (
        'chapter' => 2,
        'q_num' => 6,
        'question' => 'Where was the highest temperature ever recorded in Africa (and formerly the world) at 58°C?',
        'option_a' => 'Dallol, Ethiopia',
        'option_b' => 'Khartoum, Sudan',
        'option_c' => 'Azizia, Libya',
        'option_d' => 'Cairo, Egypt',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The highest temperature recorded in the Sahara region and the world was 58°C at Azizia, Libya.',
      ),
      6 => 
      array (
        'chapter' => 2,
        'q_num' => 7,
        'question' => 'Which region in Africa experiences a Mediterranean climate characterized by warm, dry summers and mild, wet winters?',
        'option_a' => 'The Congo Basin',
        'option_b' => 'The northernmost and southernmost fringes of the continent',
        'option_c' => 'The Horn of Africa',
        'option_d' => 'The East African Rift Valley',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Dry-summer subtropical or Mediterranean climates occur at the extreme northern and southern margins of Africa, receiving rain during their respective winter months.',
      ),
      7 => 
      array (
        'chapter' => 2,
        'q_num' => 8,
        'question' => 'What is the name of the wind system that blows cool, dry air from the Subtropical High-Pressure Belt towards Sub-Saharan Africa from November to April?',
        'option_a' => 'Southwest Monsoon Winds',
        'option_b' => 'Northeast Trade Winds',
        'option_c' => 'Westerly Winds',
        'option_d' => 'Polar Easterlies',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'From the north, the Subtropical High-Pressure Belt blows the dry, cool Northeast Trade Winds (Harmattan) over Sub-Saharan Africa from November to April.',
      ),
      8 => 
      array (
        'chapter' => 2,
        'q_num' => 9,
        'question' => 'How do cool ocean currents, such as the Canary and Benguela currents, affect the climate of adjacent coastal landmasses?',
        'option_a' => 'They increase annual rainfall and humidity.',
        'option_b' => 'They create stable, dry conditions and lower atmospheric moisture.',
        'option_c' => 'They cause heavy tropical convectional storms.',
        'option_d' => 'They warm the coastal land surface year-round.',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Cool ocean currents condense air moisture offshore into fog, depriving coastal winds of moisture and causing aridity on adjacent land areas.',
      ),
      9 => 
      array (
        'chapter' => 2,
        'q_num' => 10,
        'question' => 'What is the standard average environmental temperature decrease rate (lapse rate) per 1,000 meters increase in elevation?',
        'option_a' => '3.5°C',
        'option_b' => '6.4°C',
        'option_c' => '9.8°C',
        'option_d' => '12.0°C',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Air temperature decreases with altitude at an average lapse rate of 6.4°C for every 1,000 meters of elevation gained.',
      ),
      10 => 
      array (
        'chapter' => 2,
        'q_num' => 11,
        'question' => 'What percentage of Sub-Saharan Africa\'s labor force is employed in agriculture, making the sector highly vulnerable to climate conditions?',
        'option_a' => '30%',
        'option_b' => '50%',
        'option_c' => '70%',
        'option_d' => '90%',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'On average, agriculture accounts for about 70% of the labor force and over 25% of the GDP across the African continent.',
      ),
      11 => 
      array (
        'chapter' => 2,
        'q_num' => 12,
        'question' => 'What term describes natural internal atmospheric fluctuations versus long-term driver shifts caused by human activities?',
        'option_a' => 'Microclimate vs. Macroclimate',
        'option_b' => 'Climate Variability vs. Climate Change',
        'option_c' => 'Weather Patterns vs. Climate Regimes',
        'option_d' => 'Global Warming vs. Greenhouse Effect',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Climate variability refers to natural internal fluctuations, whereas climate change represents persistent shifts in average conditions over decades due to human (anthropogenic) or natural forcing.',
      ),
      12 => 
      array (
        'chapter' => 2,
        'q_num' => 13,
        'question' => 'In which month is the ITCZ positioned at its furthest northern limit (around 13°N) in North Africa?',
        'option_a' => 'January',
        'option_b' => 'April',
        'option_c' => 'June',
        'option_d' => 'October',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'During the northern hemisphere summer in June, the ITCZ shifts north to about 13 degrees latitude along the southern border of the Sahara.',
      ),
      13 => 
      array (
        'chapter' => 2,
        'q_num' => 14,
        'question' => 'What type of rainfall dominates high-altitude mountain slopes facing oncoming moist winds?',
        'option_a' => 'Convectional rainfall',
        'option_b' => 'Cyclonic rainfall',
        'option_c' => 'Orographic (Relief) rainfall',
        'option_d' => 'Frontal rainfall',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Mountainous regions force warm, moist winds to rise, producing relief or orographic rainfall on windward slopes.',
      ),
      14 => 
      array (
        'chapter' => 2,
        'q_num' => 15,
        'question' => 'What continent-wide development agenda, alongside the UN SDGs, is severely threatened by the impacts of climate change on African economies?',
        'option_a' => 'Agenda 2025',
        'option_b' => 'Africa\'s Agenda 2063',
        'option_c' => 'Lagos Plan of Action 2030',
        'option_d' => 'Vision 2050',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Climate change poses severe challenges to Africa\'s long-term economic plans, including the UN Millennium Development Goals/SDGs and Africa\'s Agenda 2063.',
      ),
    ),
  ),
  3 => 
  array (
    'chapter_title' => 'Natural Resource Base of Africa',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 3,
        'q_num' => 1,
        'question' => 'What is the fundamental difference between renewable and nonrenewable natural resources?',
        'option_a' => 'Renewable resources exist in fixed amounts; nonrenewable resources can be replenished rapidly.',
        'option_b' => 'Renewable resources are created by human technology; nonrenewable resources occur naturally.',
        'option_c' => 'Renewable resources can be replenished by natural processes as fast as they are used; nonrenewable resources exist in fixed amounts and can be depleted.',
        'option_d' => 'Renewable resources are non-metallic; nonrenewable resources are metallic.',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Renewable resources (like sunlight and wind) naturally replenish as fast as they are consumed, whereas nonrenewable resources (like fossil fuels) exist in fixed quantities.',
      ),
      1 => 
      array (
        'chapter' => 3,
        'q_num' => 2,
        'question' => 'What percentage of the Earth\'s total water supply is freshwater?',
        'option_a' => '0.5%',
        'option_b' => '2.5%',
        'option_c' => '10%',
        'option_d' => '29%',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'While water covers 71% of Earth\'s surface, oceans hold 96.5% as salt water, leaving only about 2.5% as freshwater.',
      ),
      2 => 
      array (
        'chapter' => 3,
        'q_num' => 3,
        'question' => 'What percentage of the Nile River\'s total water volume originates from headwaters in Ethiopia?',
        'option_a' => '30%',
        'option_b' => '50%',
        'option_c' => '70%',
        'option_d' => '86%',
        'raw_answer' => 'D',
        'correct_answer' => 'option_d',
        'explanation' => 'The right-wing tributaries originating in Ethiopia (Abbay, Baro, and Tekeze) contribute 86% of the Nile\'s total water volume.',
      ),
      3 => 
      array (
        'chapter' => 3,
        'q_num' => 4,
        'question' => 'Which African river ranks as the second-largest river by volume in the world, behind only the Amazon River?',
        'option_a' => 'Nile River',
        'option_b' => 'Congo River',
        'option_c' => 'Niger River',
        'option_d' => 'Zambezi River',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Congo River is Earth\'s second-largest river by water discharge volume and the second-longest river in Africa.',
      ),
      4 => 
      array (
        'chapter' => 3,
        'q_num' => 5,
        'question' => 'What major African river originates in the Futa Jalon highlands of Guinea and empties into the Atlantic Ocean through a massive delta in Nigeria?',
        'option_a' => 'Volta River',
        'option_b' => 'Niger River',
        'option_c' => 'Orange River',
        'option_d' => 'Limpopo River',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Niger River, western Africa\'s largest river, rises in the Futa Jalon highlands of Guinea and flows 4,200 km to its delta in southern Nigeria.',
      ),
      5 => 
      array (
        'chapter' => 3,
        'q_num' => 6,
        'question' => 'On which river border between Zambia and Zimbabwe is the famous Victoria Falls located?',
        'option_a' => 'Congo River',
        'option_b' => 'Zambezi River',
        'option_c' => 'Orange River',
        'option_d' => 'Kasai River',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Zambezi River features numerous waterfalls along its course, the most spectacular being Victoria Falls.',
      ),
      6 => 
      array (
        'chapter' => 3,
        'q_num' => 7,
        'question' => 'What is the most dominant soil group in Africa, covering approximately 22% of the continent\'s land surface?',
        'option_a' => 'Leptosols',
        'option_b' => 'Cambisols',
        'option_c' => 'Arenosols',
        'option_d' => 'Ferralsols',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Arenosols—sandy soils derived from quartz-rich parent material or dunes—are the most widespread soil group in Africa, covering 22% of its area.',
      ),
      7 => 
      array (
        'chapter' => 3,
        'q_num' => 8,
        'question' => 'Which thin, shallow soil type is typically found over hard bedrock or gravelly material in mountainous and desert regions, accounting for 17% of Africa\'s soil cover?',
        'option_a' => 'Calcisols',
        'option_b' => 'Leptosols',
        'option_c' => 'Nitisols',
        'option_d' => 'Vertisols',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Leptosols (from Greek *leptos*, meaning thin) are shallow soils resting over hard rock or gravel, common in mountain and desert terrain.',
      ),
      8 => 
      array (
        'chapter' => 3,
        'q_num' => 9,
        'question' => 'What strongly weathered, iron- and aluminum-rich red and yellow soils dominate high-rainfall zones in Central, Eastern, and Southern Africa?',
        'option_a' => 'Ferralsols',
        'option_b' => 'Arenosols',
        'option_c' => 'Solonchaks',
        'option_d' => 'Regosols',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Ferralsols are heavily leached soils rich in iron and aluminum oxides, giving them distinct red/yellow colors in humid tropical regions.',
      ),
      9 => 
      array (
        'chapter' => 3,
        'q_num' => 10,
        'question' => 'Which African country leads world production in chromium, manganese, and platinum group metals?',
        'option_a' => 'Democratic Republic of Congo',
        'option_b' => 'Guinea',
        'option_c' => 'South Africa',
        'option_d' => 'Ghana',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'South Africa ranks first globally in the mine production of chromium, manganese, and platinum.',
      ),
      10 => 
      array (
        'chapter' => 3,
        'q_num' => 11,
        'question' => 'The Democratic Republic of the Congo produces more than half of the world\'s supply of which strategic industrial metal?',
        'option_a' => 'Bauxite',
        'option_b' => 'Cobalt',
        'option_c' => 'Gold',
        'option_d' => 'Iron Ore',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The DRC produces over 50% of the world\'s cobalt (holding 51.4% of global reserves) and holds top rankings for industrial diamonds and tantalum.',
      ),
      11 => 
      array (
        'chapter' => 3,
        'q_num' => 12,
        'question' => 'What primary historical factor hindered post-independence African nations from effectively utilizing their mineral wealth for internal development?',
        'option_a' => 'Complete lack of mineral diversity',
        'option_b' => 'Colonial infrastructure designed only to transport raw ores to coastlines for foreign export',
        'option_c' => 'Lack of global demand for metals',
        'option_d' => 'Exhaustion of all reserves prior to 1950',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'European colonial powers built transport networks solely to extract raw ores to coastal ports for export, leaving mining poorly integrated with domestic processing industries.',
      ),
      12 => 
      array (
        'chapter' => 3,
        'q_num' => 13,
        'question' => 'What broad category of vegetation dominates regions with extremely low annual rainfall (below 200 mm) like the Sahara and Namib?',
        'option_a' => 'Tropical Rainforest',
        'option_b' => 'Tropical Shrubland',
        'option_c' => 'Tropical Desert (Xerophytes)',
        'option_d' => 'Subtropical Steppe',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Tropical desert vegetation receives less than 200 mm of annual rainfall, supporting sparse xerophytic plants adapted to extreme dryness.',
      ),
      13 => 
      array (
        'chapter' => 3,
        'q_num' => 14,
        'question' => 'What vegetation zone occurs at high elevations (above 3,000 meters) on tropical African mountains such as Mount Kenya and the Ethiopian Highlands?',
        'option_a' => 'Ericaceous and Afro-alpine shrublands/grasslands',
        'option_b' => 'Lowland evergreen canopy',
        'option_c' => 'Deciduous acacia woodland',
        'option_d' => 'Mangrove swamps',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Above the montane forest and Ericaceous belt on high African peaks (above 3,000 m), Afro-alpine shrublands and grasslands dominate.',
      ),
      14 => 
      array (
        'chapter' => 3,
        'q_num' => 15,
        'question' => 'What type of inland drainage basin features Lake Chad as its central shallow, evaporating body of water?',
        'option_a' => 'Exorheic basin',
        'option_b' => 'Endorheic (inland) basin',
        'option_c' => 'Estuary system',
        'option_d' => 'Rift valley trough',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Chad Basin constitutes the largest inland (endorheic) drainage area in Africa, where rivers flow into a central landlocked depression rather than an ocean. Here are 15 multiple-choice questions, correct answers, and detailed explanations for Chapters 4, 5, and 6 based on the Grade 10 Geography curriculum.',
      ),
    ),
  ),
  4 => 
  array (
    'chapter_title' => 'Population of Africa',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 4,
        'q_num' => 1,
        'question' => 'What is the currently estimated population size of the African continent, making it the second most populous continent in the world?',
        'option_a' => '800 million',
        'option_b' => '1.1 billion',
        'option_c' => '1.4 billion',
        'option_d' => '2.1 billion',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Africa\'s population passed 1.4 billion people, representing approximately 18% of the global population and placing it second behind Asia.',
      ),
      1 => 
      array (
        'chapter' => 4,
        'q_num' => 2,
        'question' => 'Which continent exhibits the highest crude birth rate (CBR) and annual population growth rate in the world?',
        'option_a' => 'Asia',
        'option_b' => 'South America',
        'option_c' => 'Africa',
        'option_d' => 'Europe',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Africa has the highest population growth rate globally (about 2.45% annually) and the highest crude birth rate, driven by high total fertility rates across Sub-Saharan countries.',
      ),
      2 => 
      array (
        'chapter' => 4,
        'q_num' => 3,
        'question' => 'What stage of the Demographic Transition Model (DTM) best characterizes most Sub-Saharan African countries today?',
        'option_a' => 'Stage 1 (High Stationary)',
        'option_b' => 'Stage 2 (Early Expanding)',
        'option_c' => 'Stage 3 (Late Expanding)',
        'option_d' => 'Stage 4 (Low Stationary)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Most Sub-Saharan African nations are in Stage 2 of the DTM, characterized by falling crude death rates due to modern medicine alongside persistently high crude birth rates, leading to rapid population expansion.',
      ),
      3 => 
      array (
        'chapter' => 4,
        'q_num' => 4,
        'question' => 'What population structure phenomenon is illustrated by the broad base of Africa\'s population pyramid?',
        'option_a' => 'An aging population',
        'option_b' => 'A low dependency ratio',
        'option_c' => 'A young population with high momentum for future growth',
        'option_d' => 'Equal proportion of elderly and youth',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Africa\'s broad-based population pyramid indicates that over 40% of the population is under 15 years old, creating strong demographic momentum for future growth.',
      ),
      4 => 
      array (
        'chapter' => 4,
        'q_num' => 5,
        'question' => 'Which region of Africa has the highest overall population density?',
        'option_a' => 'Sahara Desert',
        'option_b' => 'Central African Rain Forests',
        'option_c' => 'Nile River Valley and Delta',
        'option_d' => 'Kalahari Basin',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The Nile Valley and Delta in Egypt have some of the highest rural and urban population densities in the world due to the concentration of fertile soil and available water in an arid region.',
      ),
      5 => 
      array (
        'chapter' => 4,
        'q_num' => 6,
        'question' => 'What type of migration occurs when people move from rural areas to urban centers driven by factors like employment and access to services?',
        'option_a' => 'International migration',
        'option_b' => 'Internal rural-to-urban migration',
        'option_c' => 'Forced displacement',
        'option_d' => 'Transhumance',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Rural-to-urban migration is the dominant internal movement of people in Africa, driven by push factors in rural areas (lack of services, land scarcity) and pull factors in cities (job opportunities, education).',
      ),
      6 => 
      array (
        'chapter' => 4,
        'q_num' => 7,
        'question' => 'Which measure of population density divides the total population by the total area of arable (cultivable) land?',
        'option_a' => 'Crude (Arithmetic) Density',
        'option_b' => 'Agricultural Density',
        'option_c' => 'Physiological Density',
        'option_d' => 'Economic Density',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Physiological density calculates the number of people per unit area of arable land, providing a more accurate reflection of pressure on agricultural land than arithmetic density.',
      ),
      7 => 
      array (
        'chapter' => 4,
        'q_num' => 8,
        'question' => 'What term describes the loss of highly skilled, educated professionals (such as doctors and engineers) from African nations to developed nations?',
        'option_a' => 'Brain Drain',
        'option_b' => 'Return Migration',
        'option_c' => 'Step Migration',
        'option_d' => 'Counter-Urbanization',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => '"Brain drain" refers to the emigration of highly trained or intelligent people from developing nations (like many in Africa) to high-income countries in search of better pay and conditions.',
      ),
      8 => 
      array (
        'chapter' => 4,
        'q_num' => 9,
        'question' => 'What primary factor explains the sparse population distribution across the Saharan and Namib regions?',
        'option_a' => 'Extreme altitude',
        'option_b' => 'Arid climate and lack of freshwater',
        'option_c' => 'High risk of tropical diseases',
        'option_d' => 'Dense forest canopies',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Extreme aridity, lack of surface water, and severe heat make hyper-arid regions like the Sahara and Namib deserts naturally hostile to dense human settlement.',
      ),
      9 => 
      array (
        'chapter' => 4,
        'q_num' => 10,
        'question' => 'Which African country is the most populous on the continent, with over 210 million inhabitants?',
        'option_a' => 'Ethiopia',
        'option_b' => 'Egypt',
        'option_c' => 'Nigeria',
        'option_d' => 'South Africa',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Nigeria is the most populous country in Africa (and 6th in the world), followed by Ethiopia and Egypt.',
      ),
      10 => 
      array (
        'chapter' => 4,
        'q_num' => 11,
        'question' => 'What is the name of the demographic indicator that measures the number of deaths of infants under one year of age per 1,000 live births?',
        'option_a' => 'Child Mortality Rate',
        'option_b' => 'Infant Mortality Rate (IMR)',
        'option_c' => 'Maternal Mortality Ratio',
        'option_d' => 'Crude Death Rate',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Infant Mortality Rate (IMR) specifically counts the number of deaths of infants under one year old for every 1,000 live births in a given year.',
      ),
      11 => 
      array (
        'chapter' => 4,
        'q_num' => 12,
        'question' => 'What trend describes the process by which an increasing percentage of a country\'s total population comes to live in urban areas?',
        'option_a' => 'Population Momentum',
        'option_b' => 'Urbanization',
        'option_c' => 'Demographic Dividend',
        'option_d' => 'Suburbia',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Urbanization measures the growing proportion of people living in towns and cities relative to rural areas. Africa is currently experiencing the fastest urbanization rate in the world.',
      ),
      12 => 
      array (
        'chapter' => 4,
        'q_num' => 13,
        'question' => 'What formula represents the natural increase of a population?',
        'option_a' => 'Immigration - Emigration',
        'option_b' => 'Crude Birth Rate - Crude Death Rate',
        'option_c' => 'Total Population / Total Area',
        'option_d' => 'Dependency Ratio × 100',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Natural Increase Rate (NIR) is calculated by subtracting the Crude Death Rate (CDR) from the Crude Birth Rate (CBR), excluding migration.',
      ),
      13 => 
      array (
        'chapter' => 4,
        'q_num' => 14,
        'question' => 'What group makes up the "dependent population" in demographic dependency ratio calculations?',
        'option_a' => 'Individuals aged 15 to 64',
        'option_b' => 'Individuals under 15 and aged 65 and older',
        'option_c' => 'Employed adults in the formal sector',
        'option_d' => 'Female heads of households',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The age-dependency ratio compares the non-working age population (youth under 15 and elderly 65+) against the working-age population (ages 15–64).',
      ),
      14 => 
      array (
        'chapter' => 4,
        'q_num' => 15,
        'question' => 'What vector-borne disease historically limited high-density human settlement in low-lying, humid tropical areas of Sub-Saharan Africa?',
        'option_a' => 'Scurvy',
        'option_b' => 'Malaria',
        'option_c' => 'Influenza',
        'option_d' => 'Cholera',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Malaria (transmitted by *Anopheles* mosquitoes in warm, wet lowlands) historically influenced settlement choices, encouraging denser populations to settle in cooler, malaria-free highlands.',
      ),
    ),
  ),
  5 => 
  array (
    'chapter_title' => 'Major Economic and Cultural Activities of Africa',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 5,
        'q_num' => 1,
        'question' => 'What primary economic activity employs the largest proportion of the workforce across Sub-Saharan Africa?',
        'option_a' => 'Manufacturing industry',
        'option_b' => 'Agriculture',
        'option_c' => 'Financial services',
        'option_d' => 'Mining and extraction',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Agriculture remains the primary economic activity in Africa, employing roughly 50% to 70% of the labor force and forming the backbone of most rural economies.',
      ),
      1 => 
      array (
        'chapter' => 5,
        'q_num' => 2,
        'question' => 'Which agricultural system involves clearing forest land through burning, farming it for a few seasons until fertility declines, and then moving to a new plot?',
        'option_a' => 'Plantation Agriculture',
        'option_b' => 'Commercial Grain Farming',
        'option_c' => 'Shifting Cultivation (Slash-and-Burn)',
        'option_d' => 'Intensive Terracing',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Shifting cultivation (or bush fallowing/slash-and-burn) relies on clearing land using fire, cultivating until soil nutrients deplete, and moving on to allow the original plot to fallow naturally.',
      ),
      2 => 
      array (
        'chapter' => 5,
        'q_num' => 3,
        'question' => 'What type of farming focuses on large-scale, single-crop production (monoculture) primarily intended for export to foreign markets?',
        'option_a' => 'Subsistence Agriculture',
        'option_b' => 'Plantation Agriculture',
        'option_c' => 'Pastoralism',
        'option_d' => 'Mixed Farming',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Plantation agriculture uses large estates, high capital investment, and hired labor to cultivate commercial cash crops (such as rubber, palm oil, tea, and coffee) for export.',
      ),
      3 => 
      array (
        'chapter' => 5,
        'q_num' => 4,
        'question' => 'What term describes pastoralists who move their livestock seasonally between fixed summer and winter or highland and lowland pastures?',
        'option_a' => 'Nomadic pastoralism',
        'option_b' => 'Transhumance',
        'option_c' => 'Ranching',
        'option_d' => 'Sedentary farming',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Transhumance is the seasonal movement of livestock between defined geographic areas (e.g., highland pastures in wet seasons and lowland valleys in dry seasons).',
      ),
      4 => 
      array (
        'chapter' => 5,
        'q_num' => 5,
        'question' => 'What major challenge limits large-scale cattle rearing across vast humid areas of Central and West Africa?',
        'option_a' => 'Lack of rainfall',
        'option_b' => 'Infestation of the tsetse fly (causing trypanosomiasis)',
        'option_c' => 'Strict international import bans',
        'option_d' => 'Absence of native grass species',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The tsetse fly transmits trypanosomiasis (nagana in livestock), preventing successful cattle husbandry throughout much of the humid tropical forest and savanna zones.',
      ),
      5 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'Which economic sector includes activities related to raw material extraction and harvesting, such as farming, fishing, forestry, and mining?',
        'option_a' => 'Primary Sector',
        'option_b' => 'Secondary Sector',
        'option_c' => 'Tertiary Sector',
        'option_d' => 'Quaternary Sector',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The primary sector involves directly harvesting or extracting natural resources from the Earth (e.g., agriculture, mining, logging, fishing).',
      ),
      6 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'Which sector converts raw materials into finished or manufactured goods?',
        'option_a' => 'Primary Sector',
        'option_b' => 'Secondary Sector',
        'option_c' => 'Tertiary Sector',
        'option_d' => 'Quaternary Sector',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The secondary sector processes raw materials into manufactured products through industrial processing, construction, and manufacturing.',
      ),
      7 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'What economic organization was formed to promote regional economic integration among countries in Western Africa?',
        'option_a' => 'SADC',
        'option_b' => 'ECOWAS',
        'option_c' => 'COMESA',
        'option_d' => 'EAC',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'ECOWAS (Economic Community of West African States) was established to foster trade, economic cooperation, and regional integration among West African nations.',
      ),
      8 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'What major structural weakness characterizes the export trade profile of most African economies?',
        'option_a' => 'Heavy dependence on exporting high-tech manufactured electronics',
        'option_b' => 'Heavy dependence on exporting unprocessed primary commodities (minerals, agricultural goods)',
        'option_c' => 'Balanced trade composed strictly of services',
        'option_d' => 'Over-reliance on exporting heavy machinery',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Most African economies rely heavily on raw, unprocessed primary products (crude oil, raw minerals, coffee, cocoa) for export, making them vulnerable to global market price fluctuations.',
      ),
      9 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'What landmark trade agreement, signed by most African nations, aims to create a single continental market for goods and services?',
        'option_a' => 'AfCFTA (African Continental Free Trade Area)',
        'option_b' => 'GATT (General Agreement on Tariffs and Trade)',
        'option_c' => 'NAFTA',
        'option_d' => 'Mercosur',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The AfCFTA agreement aims to create the world\'s largest single market by removing tariffs and trade barriers across African nations to boost intra-African trade.',
      ),
      10 => 
      array (
        'chapter' => 5,
        'q_num' => 11,
        'question' => 'Which region in Africa is world-renowned for cocoa production, generating over 60% of the global cocoa supply?',
        'option_a' => 'East Africa (Kenya and Tanzania)',
        'option_b' => 'West Africa (Ivory Coast and Ghana)',
        'option_c' => 'North Africa (Egypt and Morocco)',
        'option_d' => 'Southern Africa (Zambia and Zimbabwe)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Côte d\'Ivoire (Ivory Coast) and Ghana in West Africa are the world\'s top two producers of cocoa beans, accounting for more than six out of every ten cocoa beans globally.',
      ),
      11 => 
      array (
        'chapter' => 5,
        'q_num' => 12,
        'question' => 'What primary source generates the vast majority of electricity in countries like Mozambique, Zambia, and Ethiopia?',
        'option_a' => 'Nuclear power',
        'option_b' => 'Coal-fired thermal power',
        'option_c' => 'Hydroelectric power (HEP)',
        'option_d' => 'Natural gas',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'High relief and abundant river networks allow countries like Ethiopia, Zambia, and Mozambique to rely predominantly on hydroelectric power (HEP) for electricity.',
      ),
      12 => 
      array (
        'chapter' => 5,
        'q_num' => 13,
        'question' => 'What type of economy combines elements of traditional subsistence systems, state market intervention, and private enterprise?',
        'option_a' => 'Pure Command Economy',
        'option_b' => 'Laissez-faire Capitalist Economy',
        'option_c' => 'Mixed Economy',
        'option_d' => 'Barter Economy',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Most African nations operate mixed economies where private businesses, state-owned enterprises, and traditional subsistence systems coexist.',
      ),
      13 => 
      array (
        'chapter' => 5,
        'q_num' => 14,
        'question' => 'What service-based sector has become a major contributor to GDP and foreign exchange in countries like Kenya, Tanzania, and Seychelles?',
        'option_a' => 'Heavy manufacturing',
        'option_b' => 'Tourism',
        'option_c' => 'Shipbuilding',
        'option_d' => 'Automotive assembly',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Eco-tourism, safari wildlife travel, and beach resorts make tourism a top earner of foreign currency and job creation in East Africa and island nations.',
      ),
      14 => 
      array (
        'chapter' => 5,
        'q_num' => 15,
        'question' => 'What term refers to economic activities that operate outside official registration, state regulation, and formal taxation?',
        'option_a' => 'Formal Sector',
        'option_b' => 'Informal Sector',
        'option_c' => 'Public Sector',
        'option_d' => 'Corporate Sector',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The informal sector (street vending, unregistered artisan shops, informal transport) employs a large proportion of urban workers in developing African cities.',
      ),
    ),
  ),
  6 => 
  array (
    'chapter_title' => 'Human – Natural Environment Interactions',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 6,
        'q_num' => 1,
        'question' => 'What term describes the degradation of land in arid, semi-arid, and dry sub-humid areas resulting from various factors, including climatic variations and human activities?',
        'option_a' => 'Deforestation',
        'option_b' => 'Desertification',
        'option_c' => 'Salinization',
        'option_d' => 'Soil compaction',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Desertification is defined as land degradation in drylands resulting from factors like climate change, overgrazing, deforestation, and unsustainable farming practices.',
      ),
      1 => 
      array (
        'chapter' => 6,
        'q_num' => 2,
        'question' => 'What massive ecological initiative led by the African Union aims to combat desertification across the Sahel region by planting a wall of trees and vegetation?',
        'option_a' => 'The Green Belt Movement',
        'option_b' => 'The Great Green Wall Initiative',
        'option_c' => 'The Sahara Re-afforestation Project',
        'option_d' => 'The African Clean Energy Plan',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Great Green Wall Initiative aims to plant an 8,000 km belt of trees, shrubs, and restored land across the entire width of the Sahel to stop the southward expansion of the Sahara.',
      ),
      2 => 
      array (
        'chapter' => 6,
        'q_num' => 3,
        'question' => 'What is the primary cause of soil salinization in arid agricultural regions like northern Sudan and Egypt?',
        'option_a' => 'Excessive use of chemical fertilizers',
        'option_b' => 'Poorly managed irrigation without adequate drainage in hot climates',
        'option_c' => 'High rainfall washing minerals downward',
        'option_d' => 'Acid rain deposition',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In dry climates, applying irrigation water without proper drainage causes water to evaporate quickly, leaving dissolved natural salts behind in topsoil layers.',
      ),
      3 => 
      array (
        'chapter' => 6,
        'q_num' => 4,
        'question' => 'What spatial tool uses satellites, sensors, and ground stations to acquire data about the Earth\'s surface from a distance without physical contact?',
        'option_a' => 'Geographic Information Systems (GIS)',
        'option_b' => 'Remote Sensing (RS)',
        'option_c' => 'Global Positioning System (GPS)',
        'option_d' => 'Plane Table Surveying',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Remote sensing is the science of gathering information about the Earth\'s surface using satellite imagery or aerial photography taken from aircraft or drones.',
      ),
      4 => 
      array (
        'chapter' => 6,
        'q_num' => 5,
        'question' => 'What is the system of satellites that allows users with handheld receivers to pinpoint their exact latitude, longitude, and elevation on Earth?',
        'option_a' => 'GIS',
        'option_b' => 'GPS',
        'option_c' => 'Contour Map',
        'option_d' => 'Altimeter',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Global Positioning System (GPS) relies on a constellation of space satellites to determine precise geographic coordinates (position and elevation) anywhere on Earth.',
      ),
      5 => 
      array (
        'chapter' => 6,
        'q_num' => 6,
        'question' => 'What technology consists of hardware and software designed to capture, store, manipulate, analyze, manage, and display geographically referenced data?',
        'option_a' => 'Remote Sensing',
        'option_b' => 'Geographic Information System (GIS)',
        'option_c' => 'Radiometric Dating',
        'option_d' => 'Photogrammetry',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'GIS is a computer-based spatial framework used to input, layer, analyze, and map diverse geographic data sets for decision-making.',
      ),
      6 => 
      array (
        'chapter' => 6,
        'q_num' => 7,
        'question' => 'On a topographic map, what do closely spaced contour lines indicate?',
        'option_a' => 'Flat plain or gently sloping terrain',
        'option_b' => 'A steep slope or cliff',
        'option_c' => 'A river valley floor',
        'option_d' => 'Depressions filled with water',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Contour lines connect points of equal elevation; when lines are drawn close together on a map, it shows that elevation changes rapidly over a short horizontal distance (steep slope).',
      ),
      7 => 
      array (
        'chapter' => 6,
        'q_num' => 8,
        'question' => 'If a map has a representative fraction (RF) scale of 1:50,000, what distance on the ground is represented by 1 cm on the map?',
        'option_a' => '50 meters',
        'option_b' => '500 meters (0.5 km)',
        'option_c' => '5 kilometers',
        'option_d' => '50 kilometers',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'At a scale of 1:50,000, 1 cm on the map equals 50,000 cm on the ground. Dividing 50,000 cm by 100 gives 500 meters (0.5 km).',
      ),
      8 => 
      array (
        'chapter' => 6,
        'q_num' => 9,
        'question' => 'What map component explains the meanings of symbols, colors, and line patterns used on the map layout?',
        'option_a' => 'Scale bar',
        'option_b' => 'Grid reference',
        'option_c' => 'Legend (or Key)',
        'option_d' => 'Compass Rose',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The map legend or key decodes all symbols, shading, and line designs used to represent natural and cultural features on a map.',
      ),
      9 => 
      array (
        'chapter' => 6,
        'q_num' => 10,
        'question' => 'What map projection is characterized by preserving true directions, making it useful for sea navigation, but severely distorting the size of landmasses at high latitudes?',
        'option_a' => 'Mercator Projection',
        'option_b' => 'Peters Equal-Area Projection',
        'option_c' => 'Conical Projection',
        'option_d' => 'Azimuthal Equidistant Projection',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The Mercator Projection maintains correct shapes and compass angles (rhumb lines), but inflates the area of landmasses located near the poles (such as Greenland and Antarctica).',
      ),
      10 => 
      array (
        'chapter' => 6,
        'q_num' => 11,
        'question' => 'What form of environmental degradation occurs when livestock density exceeds the carrying capacity of grazing pastures?',
        'option_a' => 'Deforestation',
        'option_b' => 'Overgrazing',
        'option_c' => 'Urban Sprawl',
        'option_d' => 'Eutrophication',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Overgrazing occurs when too many animals feed on grassland without sufficient rest periods for vegetation recovery, exposing topsoil to wind and water erosion.',
      ),
      11 => 
      array (
        'chapter' => 6,
        'q_num' => 12,
        'question' => 'What lines on a map or globe measure angular distance north or south of the Equator?',
        'option_a' => 'Lines of Longitude (Meridians)',
        'option_b' => 'Lines of Latitude (Parallels)',
        'option_c' => 'Contours',
        'option_d' => 'Isobars',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Parallels of latitude run horizontally east-west around the globe and measure position in degrees north or south from the Equator (0°).',
      ),
      12 => 
      array (
        'chapter' => 6,
        'q_num' => 13,
        'question' => 'What is the Greenwich Meridian (0° longitude) used for in international mapping and timekeeping?',
        'option_a' => 'Dividing the Northern and Southern Hemispheres',
        'option_b' => 'Serving as the universal starting point for measuring East/West longitude and Universal Time',
        'option_c' => 'Defining the tropical climate boundary',
        'option_d' => 'Indicating the magnetic North Pole',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Prime Meridian passing through Greenwich, England is assigned 0° longitude and serves as the baseline for global time zones and eastern/western coordinate measurements.',
      ),
      13 => 
      array (
        'chapter' => 6,
        'q_num' => 14,
        'question' => 'What GIS vector data model primitive is used to represent continuous linear features like rivers, roads, and power lines?',
        'option_a' => 'Point',
        'option_b' => 'Line (Polyline)',
        'option_c' => 'Polygon',
        'option_d' => 'Pixel (Grid Cell)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In vector GIS, linear real-world features with length but no areal width (e.g., roads, streams, pipelines) are stored as lines (polylines) connecting coordinate nodes.',
      ),
      14 => 
      array (
        'chapter' => 6,
        'q_num' => 15,
        'question' => 'What human activity is the single largest contributor to tropical deforestation in Sub-Saharan Africa?',
        'option_a' => 'Agricultural expansion (subsistence and commercial clearing)',
        'option_b' => 'Road construction',
        'option_c' => 'Hydroelectric dam creation',
        'option_d' => 'Industrial mining infrastructure',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The clearing of forest land for smallholder subsistence farming, cash-crop expansion, and fuelwood harvesting accounts for the majority of forest cover loss across tropical Africa. Here are 15 multiple-choice questions, correct answers, and detailed explanations for Chapters 7 and 8 based on the Grade 10 Geography curriculum.',
      ),
    ),
  ),
  7 => 
  array (
    'chapter_title' => 'Geographic Issues and Public Concerns in Africa',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 7,
        'q_num' => 1,
        'question' => 'What is the first step in conducting a formal geographic research inquiry or fieldwork project?',
        'option_a' => 'Collecting primary data in the field',
        'option_b' => 'Formulating a hypothesis or identifying the research problem',
        'option_c' => 'Analyzing statistical charts',
        'option_d' => 'Drawing the final map layout',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Every geographic inquiry begins by identifying a clear research question/problem or formulating a testable hypothesis before gathering field data.',
      ),
      1 => 
      array (
        'chapter' => 7,
        'q_num' => 2,
        'question' => 'Data collected directly by a researcher through field observations, interviews, and environmental measurements is classified as:',
        'option_a' => 'Secondary data',
        'option_b' => 'Primary data',
        'option_c' => 'Qualitative spatial proxy',
        'option_d' => 'Raster grid data',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Primary data consists of first-hand information gathered directly from original field sources using observations, measurements, surveys, or interviews.',
      ),
      2 => 
      array (
        'chapter' => 7,
        'q_num' => 3,
        'question' => 'What type of sampling method gives every individual or location in a study area an equal chance of being selected?',
        'option_a' => 'Systematic sampling',
        'option_b' => 'Stratified sampling',
        'option_c' => 'Simple random sampling',
        'option_d' => 'Purposeful sampling',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'In simple random sampling, every member or location within the sampling frame has an equal and independent probability of selection, reducing bias.',
      ),
      3 => 
      array (
        'chapter' => 7,
        'q_num' => 4,
        'question' => 'What field instrument is specifically used by geographers to measure slope angles and vertical gradients of landforms?',
        'option_a' => 'Barometer',
        'option_b' => 'Anemometer',
        'option_c' => 'Clinometer',
        'option_d' => 'Hygrometer',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'A clinometer (or inclinometer) is a handheld tool used in physical fieldwork to measure angles of slope, elevation, and vertical inclination.',
      ),
      4 => 
      array (
        'chapter' => 7,
        'q_num' => 5,
        'question' => 'What is the term for data obtained from existing published reports, government census documents, books, and academic journals?',
        'option_a' => 'Primary data',
        'option_b' => 'Secondary data',
        'option_c' => 'Raw sensor data',
        'option_d' => 'Empirical observational data',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Secondary data refers to information that has already been collected, processed, and published by other agencies or researchers.',
      ),
      5 => 
      array (
        'chapter' => 7,
        'q_num' => 6,
        'question' => 'Which type of map uses varying shades, patterns, or colors within defined administrative boundaries to represent statistical ratios or density data?',
        'option_a' => 'Isopleth map',
        'option_b' => 'Choropleth map',
        'option_c' => 'Dot distribution map',
        'option_d' => 'Topographic profile',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A choropleth map uses defined color gradients or shading patterns inside statistical areas (such as districts or states) to display data like population density.',
      ),
      6 => 
      array (
        'chapter' => 7,
        'q_num' => 7,
        'question' => 'On a topographic map, what feature is indicated by a series of concentric, closed contour lines with elevation increasing toward the center?',
        'option_a' => 'A depression or sinkhole',
        'option_b' => 'A river valley',
        'option_c' => 'A hill or mountain peak',
        'option_d' => 'A flat plateau edge',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Concentric closed contour lines where values steadily rise toward the inner circle represent a hilltop, knoll, or mountain peak.',
      ),
      7 => 
      array (
        'chapter' => 7,
        'q_num' => 8,
        'question' => 'How are steep V-shaped river valleys represented by contour lines on a topographic map?',
        'option_a' => 'Contour lines forming "V" shapes that point upstream (toward higher ground)',
        'option_b' => 'Contour lines forming "V" shapes that point downstream (toward lower ground)',
        'option_c' => 'Perfectly straight, widely spaced parallel lines',
        'option_d' => 'Circular closed lines with hachure marks',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'According to the "Rule of Vs," contour lines crossing a river valley bend into a "V" or "U" shape that points upstream toward higher elevation.',
      ),
      8 => 
      array (
        'chapter' => 7,
        'q_num' => 9,
        'question' => 'What is the vertical distance between two consecutive contour lines on a topographic map called?',
        'option_a' => 'Horizontal equivalent',
        'option_b' => 'Contour interval',
        'option_c' => 'Representative fraction',
        'option_d' => 'Gradient ratio',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The contour interval is the constant vertical distance in height represented between adjacent contour lines on a specific map.',
      ),
      9 => 
      array (
        'chapter' => 7,
        'q_num' => 10,
        'question' => 'What formula is used to calculate the average gradient (slope) between two points on a map?',
        'option_a' => 'Gradient = Horizontal Distance / Vertical Rise)',
        'option_b' => 'Gradient = Vertical Rise (Difference in Elevation) / Horizontal Distance)',
        'option_c' => 'Gradient = Vertical Rise × Horizontal Distance',
        'option_d' => 'Gradient = Contour Interval / Map Scale)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Gradient measures steepness and is calculated by dividing the vertical height difference (rise) by the horizontal ground distance (run).',
      ),
      10 => 
      array (
        'chapter' => 7,
        'q_num' => 11,
        'question' => 'Which cardinal direction corresponds to an azimuth bearing of 225° on a standard 360° magnetic compass?',
        'option_a' => 'North-East (NE)',
        'option_b' => 'South-East (SE)',
        'option_c' => 'South-West (SW)',
        'option_d' => 'North-West (NW)',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'On a compass dial: 0°/360° is North, 90° is East, 180° is South, 270° is West, placing 225° directly at South-West (SW).',
      ),
      11 => 
      array (
        'chapter' => 7,
        'q_num' => 12,
        'question' => 'What line drawn across a topographic map is used to construct a side-view cross-section showing the profile of the terrain?',
        'option_a' => 'Transect / Baseline',
        'option_b' => 'Isotherm',
        'option_c' => 'Isobar',
        'option_d' => 'Grid line',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'A transect line (or profile line cut) is drawn across a map between two points to plot elevation along that path and draw a terrain cross-section.',
      ),
      12 => 
      array (
        'chapter' => 7,
        'q_num' => 13,
        'question' => 'What fieldwork technique involves systematic, qualitative visual recording of land-use patterns, settlement structures, and environmental features while walking along a predetermined line?',
        'option_a' => 'Lab analysis',
        'option_b' => 'Transect walk',
        'option_c' => 'Telephonic survey',
        'option_d' => 'Remote spectral filtering',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A transect walk is a participatory field observation method where researchers walk along a designated path through a landscape to note environmental and land-use variations.',
      ),
      13 => 
      array (
        'chapter' => 7,
        'q_num' => 14,
        'question' => 'Which sampling strategy involves dividing a study area into distinct homogeneous sub-groups (e.g., highland, lowland, urban, rural) before taking random samples from each group?',
        'option_a' => 'Systematic sampling',
        'option_b' => 'Stratified random sampling',
        'option_c' => 'Opportunistic sampling',
        'option_d' => 'Cluster sampling',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Stratified random sampling splits a population or area into distinct strata (sub-categories) and samples randomly within each stratum to ensure fair representation.',
      ),
      14 => 
      array (
        'chapter' => 7,
        'q_num' => 15,
        'question' => 'What type of chart is best suited for showing continuous changes in a single climatic variable (such as monthly temperature) over time?',
        'option_a' => 'Pie chart',
        'option_b' => 'Line graph',
        'option_c' => 'Scatter plot',
        'option_d' => 'Rose diagram',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Line graphs effectively connect data points over continuous time intervals, making them ideal for displaying trend patterns like seasonal temperature variations.',
      ),
    ),
  ),
  8 => 
  array (
    'chapter_title' => 'Geospatial Information and Data Processing',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 8,
        'q_num' => 1,
        'question' => 'What major tectonic feature dominates the relief and structural topography of the Horn of Africa?',
        'option_a' => 'The Great Karoo Basin',
        'option_b' => 'The East African Rift System (Ethiopian Rift Valley)',
        'option_c' => 'The Atlas Fold Mountains',
        'option_d' => 'The Congo Depression',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The East African Rift System cuts diagonally across the Horn of Africa, splitting the highlands into northwestern and southeastern massifs.',
      ),
      1 => 
      array (
        'chapter' => 8,
        'q_num' => 2,
        'question' => 'Which mountain peak is the highest point in Ethiopia, reaching an elevation of 4,533 meters above sea level?',
        'option_a' => 'Mount Batu',
        'option_b' => 'Mount Ras Dashen',
        'option_c' => 'Mount Guna',
        'option_d' => 'Mount Kufa',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Located in the Simien Mountains range, Ras Dashen is the highest peak in Ethiopia at 4,533 meters above sea level.',
      ),
      2 => 
      array (
        'chapter' => 8,
        'q_num' => 3,
        'question' => 'What traditional agro-climatic zone in Ethiopia covers areas located above 3,200 meters in elevation with mean annual temperatures below 10°C?',
        'option_a' => 'Dega',
        'option_b' => 'Woina Dega',
        'option_c' => 'Wurch (Kur)',
        'option_d' => 'Kolla',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The Wurch (or Kur) zone represents high-alpine mountain zones above 3,200 m characterized by cold temperatures (<10°C) and frost.',
      ),
      3 => 
      array (
        'chapter' => 8,
        'q_num' => 4,
        'question' => 'Which region in Ethiopia is widely recognized as the lowest and hottest depression in Africa?',
        'option_a' => 'Ogaden Plain',
        'option_b' => 'Danakil (Afar) Depression',
        'option_c' => 'Borena Plain',
        'option_d' => 'Omo Valley',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Danakil (Afar) Depression includes points like Kobar Sink (~125 m below sea level) and experiences some of the highest year-round average temperatures.',
      ),
      4 => 
      array (
        'chapter' => 8,
        'q_num' => 5,
        'question' => 'What seasonal wind pattern brings the main rainy season (Kiremt) to the majority of Ethiopia between June and September?',
        'option_a' => 'Dry Northeast Trade Winds',
        'option_b' => 'Equatorial Westerlies and Indian Ocean Monsoons',
        'option_c' => 'Polar Easterlies',
        'option_d' => 'Harmattan Winds',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The main rainy season (Kiremt) in Ethiopia (June to September) is brought by moist Equatorial Westerlies and Indian Ocean monsoon air masses.',
      ),
      5 => 
      array (
        'chapter' => 8,
        'q_num' => 6,
        'question' => 'Which river basin in Ethiopia is an endorheic (inland) system that flows southward along the Rift Valley floor to empty into Lake Turkana?',
        'option_a' => 'Abbay River Basin',
        'option_b' => 'Omo-Gibe Basin',
        'option_c' => 'Awash Basin',
        'option_d' => 'Genale-Dawa Basin',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Omo-Gibe River flows southward along the Rift Valley floor and empties into Lake Turkana.',
      ),
      6 => 
      array (
        'chapter' => 8,
        'q_num' => 7,
        'question' => 'Which Ethiopian river basin flows entirely within the country, terminating in Lake Abbe near the Djibouti border?',
        'option_a' => 'Abbay River Basin',
        'option_b' => 'Baro-Akobo Basin',
        'option_c' => 'Awash River Basin',
        'option_d' => 'Tekeze Basin',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The Awash River flows entirely within Ethiopia along the Rift Valley floor, terminating in a chain of interconnected salty lakes including Lake Abbe.',
      ),
      7 => 
      array (
        'chapter' => 8,
        'q_num' => 8,
        'question' => 'What major hydroelectric project constructed on the Abbay River in Ethiopia is the largest hydroelectric power plant in Africa?',
        'option_a' => 'Gilgel Gibe III Dam',
        'option_b' => 'Grand Ethiopian Renaissance Dam (GERD)',
        'option_c' => 'Koka Dam',
        'option_d' => 'Tekeze Dam',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The GERD constructed on the Abbay (Blue Nile) River is the largest hydroelectric power station in Africa, boosting regional energy generation.',
      ),
      8 => 
      array (
        'chapter' => 8,
        'q_num' => 9,
        'question' => 'What traditional Ethiopian agro-climatic zone corresponds to cool temperate highlands situated between 2,300 meters and 3,200 meters elevation?',
        'option_a' => 'Kolla',
        'option_b' => 'Bereha',
        'option_c' => 'Dega',
        'option_d' => 'Woina Dega',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The Dega zone ranges from 2,300 to 3,200 m with cool temperatures (10°C–16°C), suitable for crops like barley, wheat, and pulses.',
      ),
      9 => 
      array (
        'chapter' => 8,
        'q_num' => 10,
        'question' => 'The Woina Dega agro-climatic zone in Ethiopia (1,500 m to 2,300 m elevation) is historically favored for human settlement because it features:',
        'option_a' => 'Moderate temperatures, fertile soils, and malaria-free conditions',
        'option_b' => 'Extreme heat and dense rainforest cover',
        'option_c' => 'Year-round freezing temperatures and glaciers',
        'option_d' => 'Hyper-arid sand dunes',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Woina Dega (sub-tropical/temperate) offers pleasant temperatures (16°C–20°C), reliable rainfall, fertile soils, and low disease risk.',
      ),
      10 => 
      array (
        'chapter' => 8,
        'q_num' => 11,
        'question' => 'What type of GIS raster data format stores spatial information as:',
        'option_a' => 'Discrete vector nodes and line segments',
        'option_b' => 'A continuous grid of square cells (pixels) with assigned attribute values',
        'option_c' => 'Textual spreadsheet tables without coordinates',
        'option_d' => 'Hand-drawn paper sketches',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Raster data models represent continuous geographic features using a regular grid array of equal-sized cells (pixels), such as satellite imagery.',
      ),
      11 => 
      array (
        'chapter' => 8,
        'q_num' => 12,
        'question' => 'Which component of a Geographic Information System (GIS) includes satellite receivers, computers, scanners, and digitizers?',
        'option_a' => 'Software',
        'option_b' => 'Hardware',
        'option_c' => 'People / Users',
        'option_d' => 'Data attributes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'GIS hardware comprises the physical equipment used to collect, store, process, and display spatial data (computers, GPS receivers, scanners).',
      ),
      12 => 
      array (
        'chapter' => 8,
        'q_num' => 13,
        'question' => 'Spatial data overlay analysis in GIS allows planners to:',
        'option_a' => 'Combine multiple geographic data layers (e.g., soil, slope, rainfall) to identify optimal land-use zones',
        'option_b' => 'Measure time using atomic clocks',
        'option_c' => 'Delete historical administrative boundaries',
        'option_d' => 'Increase map distortion near the Equator',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Overlay analysis superimposes multiple thematic map layers to analyze spatial relationships and solve complex planning problems.',
      ),
      13 => 
      array (
        'chapter' => 8,
        'q_num' => 14,
        'question' => 'What system uses radio signals broadcast from satellites to calculate exact 3D coordinates (latitude, longitude, altitude) anywhere on Earth?',
        'option_a' => 'GPS (Global Positioning System)',
        'option_b' => 'Barometer',
        'option_c' => 'Theodolite',
        'option_d' => 'Seismograph',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'GPS relies on satellite constellations to provide precise 3D positioning coordinates for navigation and spatial mapping.',
      ),
      14 => 
      array (
        'chapter' => 8,
        'q_num' => 15,
        'question' => 'Remote sensing satellites detect surface features on Earth primarily by measuring emitted or reflected:',
        'option_a' => 'Sound waves (sonar)',
        'option_b' => 'Electromagnetic radiation (light, infrared, microwave)',
        'option_c' => 'Atmospheric pressure waves',
        'option_d' => 'Magnetic gravitational pull',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Remote sensors capture reflected sun radiation or emitted thermal energy across electromagnetic bands to image Earth\'s surface.',
      ),
    ),
  ),
);
    }
}

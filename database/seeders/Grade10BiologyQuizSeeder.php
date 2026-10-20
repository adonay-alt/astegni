<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade10BiologyQuizSeeder extends Seeder
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

            $subject = Subject::where('grade_id', $grade->id)->where('title', 'Biology')->first();
            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Biology',
                    'description' => 'Grade 10 Biology Curriculum',
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
                        'description' => "Assessment quiz for Grade 10 Biology Chapter {$chapterNumber}.",
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
    'chapter_title' => 'Sub-fields of Biology',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 1,
        'q_num' => 1,
        'question' => 'Which branch of biology specifically deals with the study of insects?',
        'option_a' => 'Herpetology',
        'option_b' => 'Entomology',
        'option_c' => 'Ichthyology',
        'option_d' => 'Ornithology',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Entomology is the specialized branch of zoology that studies insects. Herpetology studies reptiles, ichthyology studies fish, and ornithology studies birds.',
      ),
      1 => 
      array (
        'chapter' => 1,
        'q_num' => 2,
        'question' => 'Which of the following is classified as a pure field of biology?',
        'option_a' => 'Biotechnology',
        'option_b' => 'Agriculture',
        'option_c' => 'Histology',
        'option_d' => 'Horticulture',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Histology (the study of tissue structure) is a pure field of biology as it focuses on understanding natural biological functions and structures. Biotechnology, agriculture, and horticulture are applied fields that use biological knowledge for practical purposes.',
      ),
      2 => 
      array (
        'chapter' => 1,
        'q_num' => 3,
        'question' => 'Who invented a method for manufacturing powerful lenses capable of magnifying up to 500 times, leading to the discovery of bacteria?',
        'option_a' => 'Robert Hooke',
        'option_b' => 'Antonie van Leeuwenhoek',
        'option_c' => 'Carl Linnaeus',
        'option_d' => 'Louis Pasteur',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Antonie van Leeuwenhoek improved microscope lenses to achieve up to 500x magnification, allowing him to discover bacteria and cell vacuoles.',
      ),
      3 => 
      array (
        'chapter' => 1,
        'q_num' => 4,
        'question' => 'The binomial nomenclature system used to name organisms (e.g., *Homo sapiens*) was devised by:',
        'option_a' => 'Aristotle',
        'option_b' => 'Charles Darwin',
        'option_c' => 'Carl Linnaeus',
        'option_d' => 'Gregor Mendel',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Carl Linnaeus created the hierarchical classification system and binomial nomenclature method (genus and species) used to identify living organisms.',
      ),
      4 => 
      array (
        'chapter' => 1,
        'q_num' => 5,
        'question' => 'Which scientist discovered that dried, crushed berries of the \'Endod\' plant (*Phytolacca dodecandra*) could act as an effective, low-cost molluscicide against freshwater snails transmitting Schistosomiasis?',
        'option_a' => 'Prof. Yalemtsehay Mekonnen',
        'option_b' => 'Prof. Gebissa Ejeta',
        'option_c' => 'Dr. Aklilu Lemma',
        'option_d' => 'Prof. Sebsebe Demissew',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Dr. Aklilu Lemma discovered that \'Endod\' suds kill the freshwater snails that host the Schistosomiasis (Bilharzia) parasite.',
      ),
      5 => 
      array (
        'chapter' => 1,
        'q_num' => 6,
        'question' => 'Prof. Gebissa Ejeta was awarded the World Food Prize in 2009 for his significant contribution to:',
        'option_a' => 'Developing high-yield, drought-tolerant, and Striga-resistant hybrid strains of sorghum',
        'option_b' => 'Identifying medicinal applications of *Moringa stenopetala*',
        'option_c' => 'Mapping the human genome using maize chromosomes',
        'option_d' => 'Identifying causal agents of tuberculosis and cholera',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Prof. Gebissa Ejeta developed commercial hybrid strains of sorghum that are resistant to drought and the parasitic Striga weed, dramatically increasing grain yields in Africa.',
      ),
      6 => 
      array (
        'chapter' => 1,
        'q_num' => 7,
        'question' => 'What branch of biology is concerned with the study of fossilized remains of ancient organisms?',
        'option_a' => 'Taxonomy',
        'option_b' => 'Paleontology',
        'option_c' => 'Embryology',
        'option_d' => 'Ecology',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Paleontology is the study of the origin, growth, and structure of past organisms through their fossils.',
      ),
      7 => 
      array (
        'chapter' => 1,
        'q_num' => 8,
        'question' => 'The use of biological evidence like DNA fingerprinting and blood typing in legal and criminal investigations is known as:',
        'option_a' => 'Biomedical Engineering',
        'option_b' => 'Bioinformatics',
        'option_c' => 'Forensic Science',
        'option_d' => 'Environmental Management',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Forensic science applies biological knowledge (such as DNA analysis and blood matching) to answer questions related to legal and criminal cases.',
      ),
      8 => 
      array (
        'chapter' => 1,
        'q_num' => 9,
        'question' => 'Who established that all species descended over time from common ancestors through the process of natural selection?',
        'option_a' => 'Gregor Mendel',
        'option_b' => 'Louis Pasteur',
        'option_c' => 'Charles Darwin',
        'option_d' => 'Robert Koch',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Charles Darwin proposed the theory of evolution via natural selection in his 1859 book, *On the Origin of Species*.',
      ),
      9 => 
      array (
        'chapter' => 1,
        'q_num' => 10,
        'question' => 'The father of modern bacteriology, credited with discovering the causal bacteria of tuberculosis, cholera, and anthrax, is:',
        'option_a' => 'Louis Pasteur',
        'option_b' => 'Robert Koch',
        'option_c' => 'Antonie van Leeuwenhoek',
        'option_d' => 'Galen',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'German physician Robert Koch is recognized as the father of modern bacteriology for identifying the specific bacteria responsible for tuberculosis, cholera, and anthrax.',
      ),
      10 => 
      array (
        'chapter' => 1,
        'q_num' => 11,
        'question' => 'Which field applies computer programs to acquire, store, analyze, and disseminate biological data like DNA and protein sequences?',
        'option_a' => 'Biotechnology',
        'option_b' => 'Genetic Engineering',
        'option_c' => 'Bioinformatics',
        'option_d' => 'Cytology',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Bioinformatics uses computational tools and software to organize and analyze large sets of biological sequence data.',
      ),
      11 => 
      array (
        'chapter' => 1,
        'q_num' => 12,
        'question' => 'In 1996, Ian Wilmut and Keith Campbell made history by successfully cloning which mammal using nuclear transfer?',
        'option_a' => 'A lab mouse',
        'option_b' => 'Dolly the Sheep',
        'option_c' => 'A rhesus monkey',
        'option_d' => 'A domestic cat',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Wilmut and Campbell successfully cloned Dolly the Sheep from an adult somatic cell using somatic cell nuclear transfer.',
      ),
      12 => 
      array (
        'chapter' => 1,
        'q_num' => 13,
        'question' => 'Which Ethiopian biologist\'s research focuses heavily on assessing the hazards of chemical pesticides and researching medicinal plants such as *Moringa stenopetala*?',
        'option_a' => 'Dr. Aklilu Lemma',
        'option_b' => 'Prof. Yalemtsehay Mekonnen',
        'option_c' => 'Prof. Legesse Negash',
        'option_d' => 'Dr. Berhane Asfaw',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Prof. Yalemtsehay Mekonnen has published extensive research on pesticide health hazards and the therapeutic properties of medicinal plants like *Moringa stenopetala*.',
      ),
      13 => 
      array (
        'chapter' => 1,
        'q_num' => 14,
        'question' => 'What field of biology focuses on the study of tissues?',
        'option_a' => 'Cytology',
        'option_b' => 'Anatomy',
        'option_c' => 'Histology',
        'option_d' => 'Morphology',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Histology is defined as the microscopic study of the detailed structure of biological tissues.',
      ),
      14 => 
      array (
        'chapter' => 1,
        'q_num' => 15,
        'question' => 'Penicillin, one of the most vital antibiotic discoveries, was discovered in 1928 by:',
        'option_a' => 'Alexander Fleming',
        'option_b' => 'Frederick Banting',
        'option_c' => 'Louis Pasteur',
        'option_d' => 'Robert Koch',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Sir Alexander Fleming discovered the antibiotic penicillin produced by *Penicillium* mold in 1928.',
      ),
    ),
  ),
  2 => 
  array (
    'chapter_title' => 'Plants',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 2,
        'q_num' => 1,
        'question' => 'Which of the following features is unique to angiosperms compared to gymnosperms?',
        'option_a' => 'Presence of vascular tissue (xylem and phloem)',
        'option_b' => 'Production of seeds',
        'option_c' => 'Enclosure of seeds within a fruit derived from a flower',
        'option_d' => 'Multicellular structure',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Angiosperms produce flowers and enclose their seeds inside fruits, whereas gymnosperms bear naked seeds on structures like cones.',
      ),
      1 => 
      array (
        'chapter' => 2,
        'q_num' => 2,
        'question' => 'In the internal structure of a leaf, which tissue layer contains the highest density of chloroplasts for maximum light absorption?',
        'option_a' => 'Upper epidermis',
        'option_b' => 'Spongy mesophyll',
        'option_c' => 'Palisade mesophyll',
        'option_d' => 'Lower epidermis',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The palisade layer consists of closely packed columnar cells situated near the upper leaf surface, containing the greatest concentration of chloroplasts per cell to capture sunlight.',
      ),
      2 => 
      array (
        'chapter' => 2,
        'q_num' => 3,
        'question' => 'In a typical flowering plant stem, water and dissolved minerals are conducted upwards through which tissue?',
        'option_a' => 'Phloem',
        'option_b' => 'Xylem',
        'option_c' => 'Endodermis',
        'option_d' => 'Cortex',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Xylem consists of dead, hollow vessels specialized for transporting water and dissolved inorganic minerals upward from roots to shoots.',
      ),
      3 => 
      array (
        'chapter' => 2,
        'q_num' => 4,
        'question' => 'A monocot root differs internally from a dicot root because a monocot root typically features:',
        'option_a' => 'A small or absent pith',
        'option_b' => 'A well-developed pith and numerous vascular bundles',
        'option_c' => 'Secondary growth driven by active cambium',
        'option_d' => 'Vascular bundles arranged in a distinct single ring of 2 to 6 bundles',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Monocot roots contain a large, well-developed central pith and many vascular bundles arranged in a ring, unlike dicot roots which have minimal or absent central pith and fewer vascular bundles.',
      ),
      4 => 
      array (
        'chapter' => 2,
        'q_num' => 5,
        'question' => 'What type of seed germination occurs when the hypocotyl elongates and pushes the cotyledons above the soil line?',
        'option_a' => 'Hypogeal germination',
        'option_b' => 'Epigeal germination',
        'option_c' => 'Phototropic germination',
        'option_d' => 'Dormant germination',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In epigeal germination, elongation of the hypocotyl carries the cotyledons out of the ground into the air, whereas in hypogeal germination, cotyledons remain under the soil.',
      ),
      5 => 
      array (
        'chapter' => 2,
        'q_num' => 6,
        'question' => 'During the light-dependent phase of photosynthesis, photolysis refers to:',
        'option_a' => 'The fixation of CO2 into glucose inside the stroma',
        'option_b' => 'The splitting of H2O molecules into H+ and O2 driven by solar energy',
        'option_c' => 'The absorption of green light by accessory pigments',
        'option_d' => 'The accumulation of starch in mesophyll cells',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Light energy absorbed by chlorophyll drives photolysis—the splitting of water (H2O) into hydrogen ions (H+) and oxygen (O2).',
      ),
      6 => 
      array (
        'chapter' => 2,
        'q_num' => 7,
        'question' => 'The dark reaction (light-independent reaction) of photosynthesis occurs in which region of the chloroplast?',
        'option_a' => 'Thylakoid membrane',
        'option_b' => 'Granum',
        'option_c' => 'Stroma',
        'option_d' => 'Cuticle',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Stroma is the fluid-filled matrix of the chloroplast where carbon fixation (the dark reaction) takes place to synthesize carbohydrates.',
      ),
      7 => 
      array (
        'chapter' => 2,
        'q_num' => 8,
        'question' => 'The transport of synthesized organic food (sucrose and amino acids) through the phloem from source to sink is called:',
        'option_a' => 'Transpiration',
        'option_b' => 'Translocation',
        'option_c' => 'Osmosis',
        'option_d' => 'Photolysis',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Translocation is the active movement of soluble organic compounds (like sucrose) through living phloem tissue from sites of production (sources) to sites of usage or storage (sinks).',
      ),
      8 => 
      array (
        'chapter' => 2,
        'q_num' => 9,
        'question' => 'Plant shoots curve toward a directional light source because the plant hormone auxin:',
        'option_a' => 'Is destroyed on the shaded side of the shoot',
        'option_b' => 'Concentrates on the shaded side, stimulating greater cell elongation there',
        'option_c' => 'Concentrates on the illuminated side, inhibiting cell growth',
        'option_d' => 'Moves downward into the root to prevent root curvature',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Unilateral light causes auxin to migrate to the shaded side of the shoot; this higher auxin concentration stimulates greater cell elongation on the dark side, causing the stem to bend toward light.',
      ),
      9 => 
      array (
        'chapter' => 2,
        'q_num' => 10,
        'question' => 'Plant roots grow downward into the soil in response to gravity. This directional growth response is termed:',
        'option_a' => 'Negative phototropism',
        'option_b' => 'Positive geotropism',
        'option_c' => 'Negative hydrotropism',
        'option_d' => 'Positive thigmotropism',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Growth toward the direction of gravitational pull is called positive geotropism (or gravitropism), which is characteristic of primary plant roots.',
      ),
      10 => 
      array (
        'chapter' => 2,
        'q_num' => 11,
        'question' => 'Which Ethiopian medicinal plant is traditionally used as an oral treatment against tapeworm infestations?',
        'option_a' => '*Ocimum lamifolium* (Damakesse)',
        'option_b' => '*Hagenia abyssinica* (Ye-kosso Zaf)',
        'option_c' => '*Ruta chalepensis* (Tena Adam)',
        'option_d' => '*Phytolacca dodecandra* (Endod)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => '*Hagenia abyssinica* (Kosso) female flowers/seeds are widely used in traditional Ethiopian medicine as a remedy for tapeworms.',
      ),
      11 => 
      array (
        'chapter' => 2,
        'q_num' => 12,
        'question' => 'Which plant structure consists of an anther and a filament?',
        'option_a' => 'Pistil',
        'option_b' => 'Stamen',
        'option_c' => 'Sepal',
        'option_d' => 'Petal',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The stamen is the male reproductive organ of a flower, consisting of a supporting stalk (filament) and a pollen-producing sack (anther).',
      ),
      12 => 
      array (
        'chapter' => 2,
        'q_num' => 13,
        'question' => 'A plant leaf appears green to our eyes because chlorophyll:',
        'option_a' => 'Absorbs green light efficiently',
        'option_b' => 'Absorbs red and blue light while reflecting green light',
        'option_c' => 'Reflects red and blue light wavelengths',
        'option_d' => 'Converts green light directly into ATP',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Chlorophyll pigments absorb wavelengths in the blue and red regions of the spectrum and reflect green light (500–600 nm), giving leaves their green appearance.',
      ),
      13 => 
      array (
        'chapter' => 2,
        'q_num' => 14,
        'question' => 'What apparatus is used in experiments to neutralize the effect of gravity on a growing plant seedling by rotating it continuously?',
        'option_a' => 'Potometer',
        'option_b' => 'Clinostat',
        'option_c' => 'Centrifuge',
        'option_d' => 'Spectrophotometer',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A clinostat slowly rotates a plant horizontally so that gravity acts equally on all sides, preventing unilateral gravitational bending.',
      ),
      14 => 
      array (
        'chapter' => 2,
        'q_num' => 15,
        'question' => 'Which distinguished Ethiopian botanist was awarded the Kew International Medal and elected a Foreign Member of the Royal Society for extensive research on Ethiopian flora and biodiversity?',
        'option_a' => 'Dr. Aklilu Lemma',
        'option_b' => 'Prof. Sebsebe Demissew',
        'option_c' => 'Prof. Gebissa Ejeta',
        'option_d' => 'Prof. Beyene Petros',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Prof. Sebsebe Demissew is a renowned plant systematist awarded the Kew International Medal (2016) and elected to the Royal Society (2018) for his work documenting Ethiopian plant diversity.',
      ),
    ),
  ),
  3 => 
  array (
    'chapter_title' => 'Biochemical Molecules',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 3,
        'q_num' => 1,
        'question' => 'Which of the following is an example of an inorganic biochemical compound found in the human body?',
        'option_a' => 'Glucose (C6H12O6)',
        'option_b' => 'Hydrochloric acid (HCl)',
        'option_c' => 'DNA',
        'option_d' => 'Triglyceride',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Inorganic compounds do not contain both carbon and hydrogen together; hydrochloric acid (HCl) and water (H2O) are classic inorganic biological molecules.',
      ),
      1 => 
      array (
        'chapter' => 3,
        'q_num' => 2,
        'question' => 'Water molecules exhibit polarity primarily because:',
        'option_a' => 'Oxygen and hydrogen share electrons equally',
        'option_b' => 'Oxygen is more electronegative than hydrogen, pulling shared electrons closer to itself',
        'option_c' => 'Hydrogen carries a partial negative charge while oxygen carries a partial positive charge',
        'option_d' => 'Water easily decomposes into ionic hydrogen and hydroxide in nature',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Oxygen has higher electronegativity than hydrogen, resulting in an unequal sharing of electrons that gives oxygen a partial negative (\\delta^-) charge and hydrogen a partial positive (\\delta+) charge.',
      ),
      2 => 
      array (
        'chapter' => 3,
        'q_num' => 3,
        'question' => 'The property of water that allows insects like raft spiders to walk across its surface is:',
        'option_a' => 'High latent heat of vaporization',
        'option_b' => 'High surface tension caused by cohesion between water molecules',
        'option_c' => 'Solvent action on non-polar lipids',
        'option_d' => 'Capillary action driven by low density',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'High cohesion among water molecules via hydrogen bonding produces high surface tension at the liquid surface, enabling light organisms to rest on top.',
      ),
      3 => 
      array (
        'chapter' => 3,
        'q_num' => 4,
        'question' => 'Why does ice float on top of liquid water?',
        'option_a' => 'Ice absorbs heat from liquid water, making it heavier',
        'option_b' => 'Below 4°C, water expands and becomes less dense in its solid ice form',
        'option_c' => 'Hydrogen bonds break completely when water freezes',
        'option_d' => 'Liquid water has higher latent heat than ice',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'As water cools below 4°C, hydrogen bonds lock molecules into a crystalline lattice that is less dense than liquid water, allowing ice to float.',
      ),
      4 => 
      array (
        'chapter' => 3,
        'q_num' => 5,
        'question' => 'The upward movement of water inside narrow plant vascular tubes against gravity due to adhesive and cohesive forces is called:',
        'option_a' => 'High heat capacity',
        'option_b' => 'Capillary action',
        'option_c' => 'Hydrolysis',
        'option_d' => 'Photolysis',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Capillary action results from adhesion (attraction between water and charged cell walls) combined with cohesion (attraction between water molecules), drawing water upward through narrow vessels.',
      ),
      5 => 
      array (
        'chapter' => 3,
        'q_num' => 6,
        'question' => 'Which statement best explains why water helps organisms maintain a stable internal body temperature despite environmental changes?',
        'option_a' => 'Water has low density when frozen',
        'option_b' => 'Water has a high specific heat capacity, requiring large energy transfers to change temperature',
        'option_c' => 'Water has a low boiling point relative to its molecular mass',
        'option_d' => 'Water molecules are completely non-polar',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Due to extensive hydrogen bonding, water has a high specific heat capacity, absorbing or losing large amounts of heat energy with minimal temperature fluctuations. Convert every mathematical formula, equation, fraction, square root, exponent, Greek-symbol expression, and mathematical notation in this file from LaTeX-style formatting into simple, human-readable mathematical notation suitable for Grade 10 students. </USER_REQUEST> <ADDITIONAL_METADATA> The current local time is: 2026-09-02T15:08:38+03:00. The user\'s current state is as follows: Active Document: c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10PhysicsQuizSeeder.php (LANGUAGE_PHP) Cursor is on line: 1308 Other open documents: - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10PhysicsQuizSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10HistoryQuizSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\tests\\Feature\\Grade10HistoryQuizSeederTest.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\API_ENDPOINTS.md (LANGUAGE_MARKDOWN) </ADDITIONAL_METADATA>',
      ),
      6 => 
      array (
        'chapter' => 3,
        'q_num' => 7,
        'question' => 'Water molecules stick to other polar surfaces or charged walls through a property known as:',
        'option_a' => 'Cohesion',
        'option_b' => 'Adhesion',
        'option_c' => 'Viscosity',
        'option_d' => 'Surface tension',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Adhesion is the force of attraction between water molecules and different polar or charged substances/surfaces.',
      ),
      7 => 
      array (
        'chapter' => 3,
        'q_num' => 8,
        'question' => 'Non-polar substances such as fats and oils are hydrophobic because:',
        'option_a' => 'They form strong hydrogen bonds with water',
        'option_b' => 'They lack charged or polar regions, preventing them from dissolving in polar water',
        'option_c' => 'They lower the boiling point of water',
        'option_d' => 'They contain high concentrations of sodium and calcium ions',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Water is a polar solvent and dissolves polar or ionic materials; non-polar molecules lack dipoles, making them insoluble (hydrophobic) in water.',
      ),
      8 => 
      array (
        'chapter' => 3,
        'q_num' => 9,
        'question' => 'What type of chemical bond forms between the partially positive hydrogen atom of one water molecule and the partially negative oxygen atom of another water molecule?',
        'option_a' => 'Covalent bond',
        'option_b' => 'Ionic bond',
        'option_c' => 'Hydrogen bond',
        'option_d' => 'Peptide bond',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Intermolecular attractions between partial positive hydrogen sites and partial negative oxygen sites of adjacent polar molecules are termed hydrogen bonds.',
      ),
      9 => 
      array (
        'chapter' => 3,
        'q_num' => 10,
        'question' => 'What percentage of the overall mass of a typical biological cell is made up of water?',
        'option_a' => '10% to 25%',
        'option_b' => '30% to 45%',
        'option_c' => '70% to 95%',
        'option_d' => '98% to 100%',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Water is the predominant intracellular solvent, making up approximately 70% to 95% of a cell\'s total mass.',
      ),
      10 => 
      array (
        'chapter' => 3,
        'q_num' => 11,
        'question' => 'Evaporative cooling (such as sweating in humans) effectively lowers body temperature due to water\'s:',
        'option_a' => 'Low freezing point',
        'option_b' => 'High latent heat of vaporization',
        'option_c' => 'High surface tension',
        'option_d' => 'Density change at 4°C',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Water has a high latent heat of vaporization, meaning considerable thermal energy is removed from the body surface when sweat evaporates from liquid to gas.',
      ),
      11 => 
      array (
        'chapter' => 3,
        'q_num' => 12,
        'question' => 'Which of the following is classified as an organic molecule?',
        'option_a' => 'Carbon dioxide (CO2)',
        'option_b' => 'Water (H2O)',
        'option_c' => 'Glucose (C6H12O6)',
        'option_d' => 'Calcium carbonate (CaCO3)',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Organic biological compounds contain both carbon and hydrogen bound together (e.g., carbohydrates like glucose).',
      ),
      12 => 
      array (
        'chapter' => 3,
        'q_num' => 13,
        'question' => 'At what temperature does pure liquid water reach its maximum density?',
        'option_a' => '0°C',
        'option_b' => '4°C',
        'option_c' => '37°C',
        'option_d' => '100°C',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Water reaches its highest density at 4°C; as it cools further toward 0°C, it expands and becomes less dense.',
      ),
      13 => 
      array (
        'chapter' => 3,
        'q_num' => 14,
        'question' => 'Molecules that readily dissolve in water are termed:',
        'option_a' => 'Hydrophobic',
        'option_b' => 'Hydrophilic',
        'option_c' => 'Non-polar',
        'option_d' => 'Organic polymers',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Hydrophilic ("water-loving") molecules are ionic or polar compounds that interact favorably with water dipoles and dissolve readily.',
      ),
      14 => 
      array (
        'chapter' => 3,
        'q_num' => 15,
        'question' => 'Standard boiling and freezing points of pure water at sea level are respectively:',
        'option_a' => '78°C and -114°C',
        'option_b' => '100°C and 0°C',
        'option_c' => '118°C and 16°C',
        'option_d' => '50°C and -10°C',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Under standard atmospheric pressure, pure water freezes into ice at 0°C and boils into steam at 100°C. Here are the questions, answers, and detailed explanations for items 4, 5, and 6 across all three chapters.',
      ),
    ),
  ),
  4 => 
  array (
    'chapter_title' => 'Cell Reproduction',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 4,
        'q_num' => 1,
        'question' => 'How does mitosis differ from meiosis in terms of daughter cell outcome?',
        'option_a' => 'Mitosis produces 4 genetically identical cells; meiosis produces 2 diploid cells',
        'option_b' => 'Mitosis produces 2 genetically identical diploid (2n) cells; meiosis produces 4 genetically unique haploid (n) cells',
        'option_c' => 'Mitosis reduces chromosome number by half; meiosis maintains chromosome number',
        'option_d' => 'Mitosis occurs only in sex organs; meiosis occurs in all somatic tissues',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Mitosis yields two identical diploid (2n) somatic cells, whereas meiosis yields four genetically diverse haploid (n) gametes.',
      ),
      1 => 
      array (
        'chapter' => 4,
        'q_num' => 2,
        'question' => 'During which phase of the cell cycle does DNA replication occur?',
        'option_a' => 'G1 phase',
        'option_b' => 'S phase of Interphase',
        'option_c' => 'Prophase',
        'option_d' => 'Telophase',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Synthesis (S) phase of Interphase is when nuclear DNA is completely replicated prior to cell division.',
      ),
      2 => 
      array (
        'chapter' => 4,
        'q_num' => 3,
        'question' => 'What is the correct sequential order of stages in mitosis?',
        'option_a' => 'Anaphase, Prophase, Metaphase, Telophase',
        'option_b' => 'Prophase, Metaphase, Anaphase, Telophase',
        'option_c' => 'Telophase, Anaphase, Metaphase, Prophase',
        'option_d' => 'Metaphase, Prophase, Telophase, Anaphase',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The stages of mitosis proceed in order: Prophase -> Metaphase -> Anaphase -> Telophase (PMAT).',
      ),
      3 => 
      array (
        'chapter' => 4,
        'q_num' => 4,
        'question' => 'During metaphase of mitosis, duplicated chromosomes line up along the:',
        'option_a' => 'Nuclear envelope',
        'option_b' => 'Equatorial (metaphase) plate in the cell center',
        'option_c' => 'Opposite poles of the cell',
        'option_d' => 'Nucleolus',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In metaphase, spindle fibers align chromosomes along the central equatorial plane of the cell.',
      ),
      4 => 
      array (
        'chapter' => 4,
        'q_num' => 5,
        'question' => 'What event occurs during anaphase of mitosis?',
        'option_a' => 'Chromosomes condense and become visible',
        'option_b' => 'Sister chromatids separate and are pulled toward opposite cell poles',
        'option_c' => 'Nuclear membrane reforms around chromosomes',
        'option_d' => 'Cytoplasm divides into two distinct cells',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'During anaphase, centromeres split and sister chromatids are pulled apart to opposite poles by shortening spindle fibers.',
      ),
      5 => 
      array (
        'chapter' => 4,
        'q_num' => 6,
        'question' => 'The division of the cytoplasm following nuclear division is called:',
        'option_a' => 'Karyokinesis',
        'option_b' => 'Cytokinesis',
        'option_c' => 'Interphase',
        'option_d' => 'Synapsis',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Cytokinesis is the physical cleavage or cell plate formation that splits cytoplasm into two distinct daughter cells.',
      ),
      6 => 
      array (
        'chapter' => 4,
        'q_num' => 7,
        'question' => 'Crossing over, which increases genetic variation in offspring, occurs during which stage of meiosis?',
        'option_a' => 'Prophase I',
        'option_b' => 'Metaphase II',
        'option_c' => 'Anaphase I',
        'option_d' => 'Telophase II',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Crossing over (exchange of genetic material between non-sister chromatids of homologous chromosomes) occurs during Prophase I.',
      ),
      7 => 
      array (
        'chapter' => 4,
        'q_num' => 8,
        'question' => 'Failure of homologous chromosomes or sister chromatids to separate properly during meiotic division is known as:',
        'option_a' => 'Recombination',
        'option_b' => 'Nondisjunction',
        'option_c' => 'Polyploidy',
        'option_d' => 'Transcription',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Nondisjunction leads to gametes containing abnormal numbers of chromosomes, causing genetic conditions like Down Syndrome.',
      ),
      8 => 
      array (
        'chapter' => 4,
        'q_num' => 9,
        'question' => 'Simple asexual cell division used by prokaryotic bacteria to produce two identical daughter cells is called:',
        'option_a' => 'Meiosis',
        'option_b' => 'Binary Fission',
        'option_c' => 'Budding',
        'option_d' => 'Sporulation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Bacterial prokaryotic cells divide rapidly via binary fission, replicating single circular DNA and pinching into two.',
      ),
      9 => 
      array (
        'chapter' => 4,
        'q_num' => 10,
        'question' => 'Programmed, regulated cell death essential for normal tissue development and removing damaged cells is termed:',
        'option_a' => 'Necrosis',
        'option_b' => 'Apoptosis',
        'option_c' => 'Lysis',
        'option_d' => 'Metastasis',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Apoptosis is a controlled physiological process of cell suicide that eliminates old, unneeded, or mutated cells.',
      ),
      10 => 
      array (
        'chapter' => 4,
        'q_num' => 11,
        'question' => 'Uncontrolled, rapid cell proliferation resulting from mutations in cell cycle regulating genes produces:',
        'option_a' => 'Normal tissue growth',
        'option_b' => 'Cancerous tumors',
        'option_c' => 'Stem cell differentiation',
        'option_d' => 'Polyploid gametes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Cancer results when checkpoint control mechanisms fail, leading to continuous, unchecked cell division and tumor formation.',
      ),
      11 => 
      array (
        'chapter' => 4,
        'q_num' => 12,
        'question' => 'What is the standard diploid (2n) chromosome number in normal human somatic cells?',
        'option_a' => '23 chromosomes',
        'option_b' => '46 chromosomes (23 pairs)',
        'option_c' => '92 chromosomes',
        'option_d' => '44 chromosomes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Human body cells contain 46 chromosomes organized into 23 homologous pairs (22 pairs of autosomes and 1 pair of sex chromosomes).',
      ),
      12 => 
      array (
        'chapter' => 4,
        'q_num' => 13,
        'question' => 'A visual display of an individual\'s complete set of chromosomes arranged in matched pairs by size and shape is a:',
        'option_a' => 'Pedigree chart',
        'option_b' => 'Karyotype',
        'option_c' => 'Punnett square',
        'option_d' => 'Genome map',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A karyotype allows geneticists to inspect chromosome number and detect structural chromosomal abnormalities.',
      ),
      13 => 
      array (
        'chapter' => 4,
        'q_num' => 14,
        'question' => 'Undifferentiated biological cells capable of renewing themselves and differentiating into specialized cell types are called:',
        'option_a' => 'Somatic cells',
        'option_b' => 'Stem cells',
        'option_c' => 'Gametes',
        'option_d' => 'Red blood cells',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Stem cells possess self-renewal ability and potency to differentiate into specialized organ tissues.',
      ),
      14 => 
      array (
        'chapter' => 4,
        'q_num' => 15,
        'question' => 'How many functional egg cells (ova) are produced from a single primary oocyte during human female meiosis?',
        'option_a' => '4 functional ova',
        'option_b' => '1 functional ovum and 3 polar bodies',
        'option_c' => '2 functional ova',
        'option_d' => '8 functional ova',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Oogenesis involves unequal cytoplasmic division, yielding 1 large functional egg cell and 3 small non-functional polar bodies.',
      ),
    ),
  ),
  5 => 
  array (
    'chapter_title' => 'Human Biology',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 5,
        'q_num' => 1,
        'question' => 'Which enzyme present in human saliva initiates chemical digestion of starches into maltose?',
        'option_a' => 'Pepsin',
        'option_b' => 'Salivary Amylase (Ptyalin)',
        'option_c' => 'Lipase',
        'option_d' => 'Trypsin',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Salivary amylase breaks down complex carbohydrates (starch) into simpler disaccharides (maltose) in the mouth.',
      ),
      1 => 
      array (
        'chapter' => 5,
        'q_num' => 2,
        'question' => 'In the human stomach, pepsinogen is activated into pepsin to digest proteins by the action of:',
        'option_a' => 'Sodium bicarbonate',
        'option_b' => 'Hydrochloric acid (HCl)',
        'option_c' => 'Bile salts',
        'option_d' => 'Insulin',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Hydrochloric acid (HCl) secreted by gastric parietal cells lowers stomach pH to ~1.5–2.0, converting inactive pepsinogen into active pepsin.',
      ),
      2 => 
      array (
        'chapter' => 5,
        'q_num' => 3,
        'question' => 'Microscopic finger-like projections lining the inner wall of the small intestine that increase surface area for nutrient absorption are:',
        'option_a' => 'Cilia',
        'option_b' => 'Villi and Microvilli',
        'option_c' => 'Flagella',
        'option_d' => 'Alveoli',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Villi and microvilli expand the small intestine absorption area hundreds of times to maximize nutrient uptake into blood capillaries.',
      ),
      3 => 
      array (
        'chapter' => 5,
        'q_num' => 4,
        'question' => 'Which component of human blood contains hemoglobin protein responsible for binding and transporting oxygen?',
        'option_a' => 'White Blood Cells (Leukocytes)',
        'option_b' => 'Red Blood Cells (Erythrocytes)',
        'option_c' => 'Blood Platelets (Thrombocytes)',
        'option_d' => 'Blood Plasma',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Red blood cells are packed with iron-containing hemoglobin molecules that reversibly bind oxygen in lungs and release it in body tissues.',
      ),
      4 => 
      array (
        'chapter' => 5,
        'q_num' => 5,
        'question' => 'Which chamber of the human heart possesses the thickest muscular wall to pump oxygenated blood throughout systemic circulation?',
        'option_a' => 'Right Atrium',
        'option_b' => 'Left Ventricle',
        'option_c' => 'Right Ventricle',
        'option_d' => 'Left Atrium',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The left ventricle pumps blood at high pressure through the aorta to the entire body, requiring a thick muscular myocardium.',
      ),
      5 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'Tiny air sac clusters in human lungs where gas exchange (O2 and CO2) occurs with surrounding capillaries are:',
        'option_a' => 'Bronchioles',
        'option_b' => 'Alveoli',
        'option_c' => 'Tracheal rings',
        'option_d' => 'Pleural membranes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Alveoli provide a thin, moist, expansive surface area where O2 diffuses into blood and CO2 diffuses out into lungs.',
      ),
      6 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'During human inhalation (breathing in), the dome-shaped diaphragm muscle:',
        'option_a' => 'Relaxes and moves upward',
        'option_b' => 'Contracts and moves downward',
        'option_c' => 'Remains static',
        'option_d' => 'Expands chest pressure',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Inhalation involves diaphragm contraction (flattening downward), increasing chest cavity volume and pulling air into lungs.',
      ),
      7 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'What is the functional microscopic filtering unit of the human kidney?',
        'option_a' => 'Nephron',
        'option_b' => 'Neuron',
        'option_c' => 'Ureter',
        'option_d' => 'Glomerulus capsule',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Each kidney contains over one million nephrons that filter blood, reabsorb essential nutrients, and produce urine.',
      ),
      8 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'What specialized gap junction converts an electrical nerve impulse into a chemical signal between two adjacent neurons?',
        'option_a' => 'Axon hillock',
        'option_b' => 'Synapse',
        'option_c' => 'Myelin sheath',
        'option_d' => 'Dendrite tip',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The synapse is the microscopic junction where neurotransmitter chemicals cross to transmit signals from one neuron to another.',
      ),
      9 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'Which component of the human nervous system acts as the central control coordinator for involuntary reflex actions?',
        'option_a' => 'Cerebrum',
        'option_b' => 'Spinal Cord',
        'option_c' => 'Hypothalamus',
        'option_d' => 'Pituitary Gland',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The spinal cord mediates rapid, automatic involuntary reflex arcs before conscious signals reach the brain.',
      ),
      10 => 
      array (
        'chapter' => 5,
        'q_num' => 11,
        'question' => 'Insulin, a pancreatic hormone secreted by beta cells, functions to:',
        'option_a' => 'Raise blood glucose concentration',
        'option_b' => 'Lower blood glucose concentration by promoting cellular uptake and glycogen storage',
        'option_c' => 'Stimulate breakdown of body fats',
        'option_d' => 'Increase heart rate',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Insulin lowers elevated blood glucose levels by signaling muscle, liver, and fat cells to absorb glucose and store it as glycogen.',
      ),
      11 => 
      array (
        'chapter' => 5,
        'q_num' => 12,
        'question' => 'Glucagon hormone secreted by pancreatic alpha cells acts oppositely to insulin by:',
        'option_a' => 'Converting glucose into starch',
        'option_b' => 'Stimulating liver glycogen breakdown into glucose, raising blood sugar levels',
        'option_c' => 'Inhibiting digestive enzymes',
        'option_d' => 'Promoting kidney water reabsorption',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'During low blood sugar (fasting), glucagon causes liver cells to convert stored glycogen back into glucose released into bloodstream.',
      ),
      12 => 
      array (
        'chapter' => 5,
        'q_num' => 13,
        'question' => 'Specialized white blood cells (B lymphocytes) defend the body against pathogens by producing:',
        'option_a' => 'Antigens',
        'option_b' => 'Specific Y-shaped Antibodies',
        'option_c' => 'Histamines only',
        'option_d' => 'Hemoglobin',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'B lymphocytes synthesize specific antibody proteins that bind to foreign antigens on pathogens, marking them for destruction.',
      ),
      13 => 
      array (
        'chapter' => 5,
        'q_num' => 14,
        'question' => 'Which blood vessels carry oxygenated blood AWAY from the heart to tissues under high pressure?',
        'option_a' => 'Veins',
        'option_b' => 'Arteries',
        'option_c' => 'Capillaries',
        'option_d' => 'Venules',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Arteries have thick elastic walls designed to transport blood away from the heart at high arterial pressure.',
      ),
      14 => 
      array (
        'chapter' => 5,
        'q_num' => 15,
        'question' => 'The maintenance of a constant, stable internal physiological environment within normal limits in the human body is:',
        'option_a' => 'Metabolism',
        'option_b' => 'Homeostasis',
        'option_c' => 'Thermoregulation',
        'option_d' => 'Osmoregulation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Homeostasis refers to physiological feedback mechanisms that keep internal variables (temperature, pH, blood sugar) constant.',
      ),
    ),
  ),
  6 => 
  array (
    'chapter_title' => 'Ecological Interaction',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 6,
        'q_num' => 1,
        'question' => 'In an ecological food chain, green plants that capture solar energy to produce organic food via photosynthesis occupy the first trophic level as:',
        'option_a' => 'Primary Consumers',
        'option_b' => 'Primary Producers (Autotrophs)',
        'option_c' => 'Decomposers',
        'option_d' => 'Secondary Consumers',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Primary producers (autotrophs) convert light energy into chemical energy, forming the foundation of all ecological food chains.',
      ),
      1 => 
      array (
        'chapter' => 6,
        'q_num' => 2,
        'question' => 'According to the 10% Energy Transfer Law in ecology, approximately how much energy is passed from one trophic level to the next higher level?',
        'option_a' => '100%',
        'option_b' => '50%',
        'option_c' => '10%',
        'option_d' => '90%',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'About 90% of energy is lost as heat, respiration, and waste at each stage; only ~10% is incorporated into biomass for the next level.',
      ),
      2 => 
      array (
        'chapter' => 6,
        'q_num' => 3,
        'question' => 'Herbivores that feed directly on green plants are classified at the second trophic level as:',
        'option_a' => 'Producers',
        'option_b' => 'Primary Consumers',
        'option_c' => 'Tertiary Consumers',
        'option_d' => 'Decomposers',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Primary consumers are plant-eating animals (herbivores) that occupy the second trophic level above producers.',
      ),
      3 => 
      array (
        'chapter' => 6,
        'q_num' => 4,
        'question' => 'Organisms like fungi and bacteria that break down dead organic material, returning essential mineral nutrients to soil, are:',
        'option_a' => 'Autotrophs',
        'option_b' => 'Decomposers (Saprotrophs)',
        'option_c' => 'Carnivores',
        'option_d' => 'Parasites',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Decomposers break down dead plant/animal matter, recycling carbon, nitrogen, and phosphorus back into abiotic nutrient pools.',
      ),
      4 => 
      array (
        'chapter' => 6,
        'q_num' => 5,
        'question' => 'A symbiotic relationship where BOTH interacting species benefit from each other is called:',
        'option_a' => 'Commensalism',
        'option_b' => 'Mutualism',
        'option_c' => 'Parasitism',
        'option_d' => 'Predation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Mutualism is a mutually beneficial interspecific interaction, such as nitrogen-fixing Rhizobium bacteria inside legume root nodules.',
      ),
      5 => 
      array (
        'chapter' => 6,
        'q_num' => 6,
        'question' => 'A symbiotic interaction where one organism benefits while the other organism is neither harmed nor helped is:',
        'option_a' => 'Mutualism',
        'option_b' => 'Commensalism',
        'option_c' => 'Parasitism',
        'option_d' => 'Competition',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Commensalism benefits one partner while leaving the host unaffected (e.g. orchids growing as epiphytes on tall tree branches).',
      ),
      6 => 
      array (
        'chapter' => 6,
        'q_num' => 7,
        'question' => 'A relationship where one organism (parasite) lives on or inside a host organism, deriving nutrients at the host\'s expense and harm, is:',
        'option_a' => 'Mutualism',
        'option_b' => 'Parasitism',
        'option_c' => 'Commensalism',
        'option_d' => 'Saprophytism',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Parasitism harms the host while benefiting the parasite (e.g. Plasmodium protozoa causing malaria inside human red blood cells).',
      ),
      7 => 
      array (
        'chapter' => 6,
        'q_num' => 8,
        'question' => 'In the carbon cycle, which natural biological process removes carbon dioxide (CO2) from the atmosphere?',
        'option_a' => 'Cellular Respiration',
        'option_b' => 'Photosynthesis',
        'option_c' => 'Combustion',
        'option_d' => 'Decomposition',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Photosynthesis by green plants and phytoplankton is the primary natural mechanism fixing atmospheric CO2 into organic carbon.',
      ),
      8 => 
      array (
        'chapter' => 6,
        'q_num' => 9,
        'question' => 'Nitrogen-fixing bacteria (such as Rhizobium and Azotobacter) convert inert atmospheric nitrogen gas (N2) into:',
        'option_a' => 'Oxygen gas',
        'option_b' => 'Usable nitrogen compounds (Ammonia / Nitrates)',
        'option_c' => 'Carbon dioxide',
        'option_d' => 'Glucose',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Nitrogen fixation converts unreactive atmospheric N2 into bioavailable ammonia (NH3) and nitrates (NO3-) that plants assimilate.',
      ),
      9 => 
      array (
        'chapter' => 6,
        'q_num' => 10,
        'question' => 'Ecological succession that begins on newly exposed, bare rocky surfaces completely devoid of pre-existing soil is:',
        'option_a' => 'Secondary Succession',
        'option_b' => 'Primary Succession',
        'option_c' => 'Climax Succession',
        'option_d' => 'Cyclic Succession',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Primary succession occurs on brand new substrates (cooled lava flows, retreated glaciers) where no soil previously existed.',
      ),
      10 => 
      array (
        'chapter' => 6,
        'q_num' => 11,
        'question' => 'Hardy pioneer species like lichens and mosses are crucial during primary succession because they:',
        'option_a' => 'Consume primary consumers',
        'option_b' => 'Weather bare rock into primitive soil over time',
        'option_c' => 'Block sunlight',
        'option_d' => 'Evaporate soil moisture',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Pioneer lichens secrete acids that physically and chemically break down rock into organic soil needed for plant roots.',
      ),
      11 => 
      array (
        'chapter' => 6,
        'q_num' => 12,
        'question' => 'The maximum population size of a species that a given ecosystem can support indefinitely based on available resources is its:',
        'option_a' => 'Biotic Potential',
        'option_b' => 'Carrying Capacity (K)',
        'option_c' => 'Trophic Index',
        'option_d' => 'Growth Factor',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Carrying capacity (K) represents the environmental upper limit for population size sustainable by available food, water, and space.',
      ),
      12 => 
      array (
        'chapter' => 6,
        'q_num' => 13,
        'question' => 'The progressive accumulation and increasing concentration of persistent non-biodegradable toxins (like DDT) at higher trophic levels is:',
        'option_a' => 'Eutrophication',
        'option_b' => 'Biomagnification (Bioaccumulation)',
        'option_c' => 'Succession',
        'option_d' => 'Nitrification',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Biomagnification causes top predators to accumulate high, toxic concentrations of persistent fat-soluble chemicals from their prey.',
      ),
      13 => 
      array (
        'chapter' => 6,
        'q_num' => 14,
        'question' => 'Excessive runoff of agricultural fertilizers (nitrates and phosphates) into lakes leading to dense algal blooms and oxygen depletion is:',
        'option_a' => 'Acid rain',
        'option_b' => 'Eutrophication',
        'option_c' => 'Desalination',
        'option_d' => 'Thermal pollution',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Eutrophication triggers explosive algal blooms; when algae die and decompose, bacteria consume dissolved oxygen, suffocating fish.',
      ),
      14 => 
      array (
        'chapter' => 6,
        'q_num' => 15,
        'question' => 'All the interconnected, overlapping food chains in a biological community form a complex:',
        'option_a' => 'Trophic Pyramid',
        'option_b' => 'Food Web',
        'option_c' => 'Biomass Chart',
        'option_d' => 'Energy Gradient',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A food web represents the network of interconnected feeding relationships across all species in an ecosystem.',
      ),
    ),
  ),
);
    }
}

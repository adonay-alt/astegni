<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade10ChemistryQuizSeeder extends Seeder
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

            $subject = Subject::where('grade_id', $grade->id)->where('title', 'Chemistry')->first();
            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Chemistry',
                    'description' => 'Grade 10 Chemistry Curriculum',
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
                        'description' => "Assessment quiz for Grade 10 Chemistry Chapter {$chapterNumber}.",
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
    'chapter_title' => 'Chemical Reactions and Stoichiometry',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 1,
        'q_num' => 1,
        'question' => 'Which of the following is considered a chemical change?',
        'option_a' => 'Powdering of sugar',
        'option_b' => 'Evaporation of liquids',
        'option_c' => 'Melting of ice',
        'option_d' => 'Turning of milk into curd',
        'raw_answer' => '**d) Turning of milk into curd**',
        'correct_answer' => 'option_d',
        'explanation' => 'A chemical change results in the formation of one or more new substances with a new chemical composition. Turning milk into curd involves forming a new substance. The other options (powdering sugar, evaporation, melting ice) are physical changes because no new chemical composition is created.',
      ),
      1 => 
      array (
        'chapter' => 1,
        'q_num' => 2,
        'question' => 'Which law must be obeyed when balancing a chemical equation?',
        'option_a' => 'Law of Definite Proportions',
        'option_b' => 'Law of Conservation of Mass',
        'option_c' => 'Law of Multiple Proportions',
        'option_d' => 'Avogadro\'s Law',
        'raw_answer' => '**b) Law of Conservation of Mass**',
        'correct_answer' => 'option_b',
        'explanation' => 'According to the Law of Conservation of Mass, atoms are neither created nor destroyed during a chemical reaction. Thus, the number of atoms of each element must remain the same on both sides of the chemical equation.',
      ),
      2 => 
      array (
        'chapter' => 1,
        'q_num' => 3,
        'question' => 'What is the correct balanced equation for the reaction of aluminum with oxygen to form aluminum oxide?',
        'option_a' => 'Al + O_2 -> Al_2O_3',
        'option_b' => '2Al + 3O_2 -> Al_2O_3',
        'option_c' => '4Al + 3O_2 -> 2Al_2O_3',
        'option_d' => '4Al + 2O_2 -> 2Al_2O_3',
        'raw_answer' => '**c) $4Al + 3O_2 \\rightarrow 2Al_2O_3$**',
        'correct_answer' => 'option_c',
        'explanation' => 'Balancing the equation gives 4 aluminum atoms and 6 oxygen atoms on both the reactant and product sides.',
      ),
      3 => 
      array (
        'chapter' => 1,
        'q_num' => 4,
        'question' => 'The general equation A + BC -> B + AC represents which type of chemical reaction?',
        'option_a' => 'Direct combination reaction',
        'option_b' => 'Decomposition reaction',
        'option_c' => 'Single displacement reaction',
        'option_d' => 'Double displacement reaction',
        'raw_answer' => '**c) Single displacement reaction**',
        'correct_answer' => 'option_c',
        'explanation' => 'A reaction in which one element displaces another element from its compound is classified as a single displacement reaction.',
      ),
      4 => 
      array (
        'chapter' => 1,
        'q_num' => 5,
        'question' => 'Which of the following is an example of a direct combination reaction?',
        'option_a' => '2HgO(s) -> 2Hg(l) + O_2(g)',
        'option_b' => 'Zn + 2HCl -> ZnCl_2 + H_2',
        'option_c' => '2Na + Cl_2 -> 2NaCl',
        'option_d' => 'AgNO_3 + NaCl -> AgCl + NaNO_3',
        'raw_answer' => '**c) $2Na + Cl_2 \\rightarrow 2NaCl$**',
        'correct_answer' => 'option_c',
        'explanation' => 'In a direct combination reaction, two pure substances react to form a single compound (A + B -> AB).',
      ),
      5 => 
      array (
        'chapter' => 1,
        'q_num' => 6,
        'question' => 'In terms of electron transfer, how are oxidation and reduction defined?',
        'option_a' => 'Oxidation is the gain of electrons; reduction is the loss of electrons.',
        'option_b' => 'Oxidation is the loss of electrons; reduction is the gain of electrons.',
        'option_c' => 'Both involve the gain of electrons.',
        'option_d' => 'Both involve the loss of electrons.',
        'raw_answer' => '**b) Oxidation is the loss of electrons; reduction is the gain of electrons.**',
        'correct_answer' => 'option_b',
        'explanation' => 'By definition, oxidation is the loss of one or more electrons by an atom, while reduction is the gain of one or more electrons.',
      ),
      6 => 
      array (
        'chapter' => 1,
        'q_num' => 7,
        'question' => 'What is the oxidation number of sulfur in H_2SO_4?',
        'option_a' => '+4',
        'option_b' => '+2',
        'option_c' => '+6',
        'option_d' => '-2',
        'raw_answer' => '**c) $+6$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The sum of oxidation numbers in a neutral compound is 0. Hydrogen is +1 (total +2) and oxygen is -2 (total -8). Therefore: (+2) + S + (-8) = 0 \\implies S = +6.',
      ),
      7 => 
      array (
        'chapter' => 1,
        'q_num' => 8,
        'question' => 'In a redox reaction, the substance that gains electrons and causes another substance to be oxidized is called the:',
        'option_a' => 'Oxidizing agent',
        'option_b' => 'Reducing agent',
        'option_c' => 'Catalyst',
        'option_d' => 'Precipitate',
        'raw_answer' => '**a) Oxidizing agent**',
        'correct_answer' => 'option_a',
        'explanation' => 'An oxidizing agent gains one or more electrons, undergoes reduction, and causes the oxidation of another substance.',
      ),
      8 => 
      array (
        'chapter' => 1,
        'q_num' => 9,
        'question' => 'What is the oxidation number of manganese (Mn) in the permanganate ion (MnO_4^-)?',
        'option_a' => '+2',
        'option_b' => '+4',
        'option_c' => '+6',
        'option_d' => '+7',
        'raw_answer' => '**d) $+7$**',
        'correct_answer' => 'option_d',
        'explanation' => 'The sum of oxidation numbers in a polyatomic ion equals its net charge. For MnO_4^-, x + 4(-2) = -1 \\implies x - 8 = -1 \\implies x = +7.',
      ),
      9 => 
      array (
        'chapter' => 1,
        'q_num' => 10,
        'question' => 'Which of the following is a non-redox reaction?',
        'option_a' => '2Fe + 3O_2 -> Fe_2O_3',
        'option_b' => 'KOH + HNO_3 -> KNO_3 + H_2O',
        'option_c' => 'Zn + CuSO_4 -> ZnSO_4 + Cu',
        'option_d' => 'Mg + 2HCl -> MgCl_2 + H_2',
        'raw_answer' => '**b) $KOH + HNO_3 \\rightarrow KNO_3 + H_2O$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Neutralization reactions and double displacement reactions do not involve changes in oxidation states or transfer of electrons, making them non-redox reactions.',
      ),
      10 => 
      array (
        'chapter' => 1,
        'q_num' => 11,
        'question' => 'What is Avogadro\'s number (NA), which represents the number of entities in one mole of a substance?',
        'option_a' => '3.01 * 10^{23',
        'option_b' => '6.022 * 10^{23',
        'option_c' => '1.66 * 10^{-24',
        'option_d' => '22.4 * 10^{23',
        'raw_answer' => '**b) $6.022 \\times 10^{23}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'One mole of any substance contains Avogadro\'s number (6.022 * 10^{23) of particles.',
      ),
      11 => 
      array (
        'chapter' => 1,
        'q_num' => 12,
        'question' => 'What is the empirical formula of a compound containing 79.9% carbon and 20.1% hydrogen by mass?',
        'option_a' => 'CH_2',
        'option_b' => 'CH_3',
        'option_c' => 'CH_4',
        'option_d' => 'C_2H_6',
        'raw_answer' => '**b) $CH_3$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Moles of C = {79.9{12 = 6.66 mol; Moles of H = {20.1{1 = 20.1 mol. Dividing by the smallest value (6.66): C = 1, H \\approx 3, giving the empirical formula CH_3.',
      ),
      12 => 
      array (
        'chapter' => 1,
        'q_num' => 13,
        'question' => 'What is the molar volume of any ideal gas at Standard Temperature and Pressure (STP)?',
        'option_a' => '11.2 L',
        'option_b' => '22.4 L',
        'option_c' => '44.8 L',
        'option_d' => '24.0 L',
        'raw_answer' => '**b) $22.4\\text{ L}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'At Standard Temperature (0^°C) and Pressure (1 atm), one mole of any gas occupies a volume of 22.4 liters.',
      ),
      13 => 
      array (
        'chapter' => 1,
        'q_num' => 14,
        'question' => 'What is the limiting reactant in a chemical reaction?',
        'option_a' => 'The reactant present in the largest mass.',
        'option_b' => 'The reactant that remains unreacted after completion.',
        'option_c' => 'The reactant that is completely consumed first and limits the product yield.',
        'option_d' => 'The product formed in the lowest quantity.',
        'raw_answer' => '**c) The reactant that is completely consumed first and limits the product yield.**',
        'correct_answer' => 'option_c',
        'explanation' => 'The limiting reactant is completely consumed first, stopping the reaction and determining the maximum theoretical yield of product.',
      ),
      14 => 
      array (
        'chapter' => 1,
        'q_num' => 15,
        'question' => 'If a reaction has a theoretical yield of 13.9 g of CaO and the actual yield measured is 13.1 g, what is the percentage yield?',
        'option_a' => '94.2\\%',
        'option_b' => '88.5\\%',
        'option_c' => '106.1\\%',
        'option_d' => '53.8\\%',
        'raw_answer' => '**a) $94.2\\%$**',
        'correct_answer' => 'option_a',
        'explanation' => 'Percentage yield = <=ft({Actual yield{Theoretical yield\\right) * 100\\% = <=ft({13.1 g{13.9 g\\right) * 100\\% = 94.2\\%. Here are 15 multiple-choice questions each for **Unit 4: Chemical Kinetics**, **Unit 5: Chemical Equilibrium**, and **Unit 6: Some Important Oxygen-Containing Organic Compounds**, complete with options, correct answers, and detailed explanations based on the Grade 11 Chemistry curriculum.',
      ),
    ),
  ),
  2 => 
  array (
    'chapter_title' => 'Solutions',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 2,
        'q_num' => 1,
        'question' => 'A homogeneous mixture of two or more substances uniform throughout is called a:',
        'option_a' => 'Suspension',
        'option_b' => 'Solution',
        'option_c' => 'Emulsion',
        'option_d' => 'Heterogeneous mixture',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'A solution is a homogeneous mixture where solute particles are uniformly dispersed at the molecular or ionic level.',
      ),
      1 => 
      array (
        'chapter' => 2,
        'q_num' => 2,
        'question' => 'In a solution, the component present in the larger amount that dissolves the other substance is called the:',
        'option_a' => 'Solute',
        'option_b' => 'Solvent',
        'option_c' => 'Precipitate',
        'option_d' => 'Electrolyte',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The solvent is the dissolving medium present in the largest quantity, while the dissolved substance is the solute.',
      ),
      2 => 
      array (
        'chapter' => 2,
        'q_num' => 3,
        'question' => 'A solution that contains the maximum amount of dissolved solute possible at a specified temperature is:',
        'option_a' => 'Unsaturated',
        'option_b' => 'Saturated',
        'option_c' => 'Dilute',
        'option_d' => 'Supersaturated',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'A saturated solution exists in dynamic equilibrium with undissolved solute at a given temperature.',
      ),
      3 => 
      array (
        'chapter' => 2,
        'q_num' => 4,
        'question' => 'What is the Molarity (M) of a solution containing 2 moles of NaCl dissolved in 4 liters of solution?',
        'option_a' => '0.5 M',
        'option_b' => '2.0 M',
        'option_c' => '8.0 M',
        'option_d' => '1.0 M',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Molarity is calculated as Moles of solute / Volume of solution in liters: M = 2 mol / 4 L = 0.5 M.',
      ),
      4 => 
      array (
        'chapter' => 2,
        'q_num' => 5,
        'question' => 'How does increasing temperature affect the solubility of most solid solutes in liquid water?',
        'option_a' => 'Decreases solubility',
        'option_b' => 'Increases solubility',
        'option_c' => 'Has no effect on solubility',
        'option_d' => 'Reduces volume',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'For most solid substances, solubility in water increases as temperature rises.',
      ),
      5 => 
      array (
        'chapter' => 2,
        'q_num' => 6,
        'question' => 'Henry\'s Law states that the solubility of a gas in a liquid solvent is directly proportional to the:',
        'option_a' => 'Temperature of the liquid',
        'option_b' => 'Partial pressure of the gas above the liquid',
        'option_c' => 'Volume of the container',
        'option_d' => 'Mass of the solvent',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Henry\'s Law specifies that increasing gas pressure above a liquid forces more gas molecules to dissolve into solution.',
      ),
      6 => 
      array (
        'chapter' => 2,
        'q_num' => 7,
        'question' => 'Substances that dissociate into free ions in aqueous solution and conduct electricity are:',
        'option_a' => 'Non-electrolytes',
        'option_b' => 'Electrolytes',
        'option_c' => 'Polymers',
        'option_d' => 'Colloids',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Electrolytes (like NaCl, HCl) form mobile ions when dissolved in water, making the solution electrically conductive.',
      ),
      7 => 
      array (
        'chapter' => 2,
        'q_num' => 8,
        'question' => 'Properties of solutions that depend solely on the NUMBER of solute particles present, rather than their chemical identity, are:',
        'option_a' => 'Chemical properties',
        'option_b' => 'Colligative properties',
        'option_c' => 'Atomic properties',
        'option_d' => 'Thermodynamic properties',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Colligative properties include vapor pressure lowering, boiling point elevation, freezing point depression, and osmotic pressure.',
      ),
      8 => 
      array (
        'chapter' => 2,
        'q_num' => 9,
        'question' => 'What is the mass percentage of a solution prepared by dissolving 10 g of sugar in 90 g of water?',
        'option_a' => '10%',
        'option_b' => '11.1%',
        'option_c' => '90%',
        'option_d' => '9%',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Total mass of solution = 10 g + 90 g = 100 g. Mass percentage = (10 g / 100 g) * 100% = 10%.',
      ),
      9 => 
      array (
        'chapter' => 2,
        'q_num' => 10,
        'question' => 'What dilution formula is used to calculate new concentration M2 when a solution of initial molarity M1 and volume V1 is diluted to volume V2?',
        'option_a' => 'M1 * V1 = M2 * V2',
        'option_b' => 'M1 / V1 = M2 / V2',
        'option_c' => 'M1 * M2 = V1 * V2',
        'option_d' => 'M1 + V1 = M2 + V2',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Dilution preserves total moles of solute: Moles = M1 * V1 = M2 * V2.',
      ),
      10 => 
      array (
        'chapter' => 2,
        'q_num' => 11,
        'question' => 'A heterogeneous mixture containing large particles that settle out upon standing is a:',
        'option_a' => 'Solution',
        'option_b' => 'Suspension',
        'option_c' => 'True liquid',
        'option_d' => 'Gas mixture',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Suspensions (like muddy water) have large suspended particles that separate under gravity when left undisturbed.',
      ),
      11 => 
      array (
        'chapter' => 2,
        'q_num' => 12,
        'question' => 'The Tyndall Effect—the scattering of a light beam as it passes through a medium—is characteristic of:',
        'option_a' => 'True solutions',
        'option_b' => 'Colloids and fine suspensions',
        'option_c' => 'Pure distilled water',
        'option_d' => 'Vacuum',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Colloidal particles are large enough to scatter light rays, making light paths visible (Tyndall Effect).',
      ),
      12 => 
      array (
        'chapter' => 2,
        'q_num' => 13,
        'question' => 'Adding a non-volatile solute to a liquid solvent causes the freezing point of the solution to:',
        'option_a' => 'Increase',
        'option_b' => 'Decrease (Freezing point depression)',
        'option_c' => 'Remain constant',
        'option_d' => 'Double',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Dissolved solute particles interfere with solvent crystal lattice formation, lowering the freezing point.',
      ),
      13 => 
      array (
        'chapter' => 2,
        'q_num' => 14,
        'question' => 'What happens to the boiling point of a solvent when a non-volatile solute is added?',
        'option_a' => 'Boiling point increases (Boiling point elevation)',
        'option_b' => 'Boiling point decreases',
        'option_c' => 'Boiling point drops to 0°C',
        'option_d' => 'No change occurs',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Presence of solute lowers vapor pressure, requiring a higher temperature to boil the liquid.',
      ),
      14 => 
      array (
        'chapter' => 2,
        'q_num' => 15,
        'question' => 'Which of the following is a non-electrolyte in water?',
        'option_a' => 'Sodium chloride (NaCl)',
        'option_b' => 'Sugar (Sucrose, C12H22O11)',
        'option_c' => 'Hydrochloric acid (HCl)',
        'option_d' => 'Sodium hydroxide (NaOH)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Sucrose dissolves as neutral intact molecules without forming ions, making its solution non-conductive.',
      ),
    ),
  ),
  3 => 
  array (
    'chapter_title' => 'Important Inorganic Compounds',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 3,
        'q_num' => 1,
        'question' => 'According to Arrhenius theory, an acid is a substance that produces which ion in aqueous solution?',
        'option_a' => 'Hydroxide ion (OH-)',
        'option_b' => 'Hydrogen ion (H+)',
        'option_c' => 'Sodium ion (Na+)',
        'option_d' => 'Chloride ion (Cl-)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Arrhenius defined acids as substances that release hydrogen ions (H+ or hydronium H3O+) in water.',
      ),
      1 => 
      array (
        'chapter' => 3,
        'q_num' => 2,
        'question' => 'According to Bronsted-Lowry theory, a base is defined as a:',
        'option_a' => 'Proton (H+) donor',
        'option_b' => 'Proton (H+) acceptor',
        'option_c' => 'Electron pair donor',
        'option_d' => 'Neutron source',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The Bronsted-Lowry concept defines a base as a proton (H+) acceptor.',
      ),
      2 => 
      array (
        'chapter' => 3,
        'q_num' => 3,
        'question' => 'A neutral aqueous solution at 25°C has a pH value equal to:',
        'option_a' => '0',
        'option_b' => '7',
        'option_c' => '14',
        'option_d' => '1',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'On the 0–14 pH scale, pH 7 represents neutral, pH < 7 is acidic, and pH > 7 is basic.',
      ),
      3 => 
      array (
        'chapter' => 3,
        'q_num' => 4,
        'question' => 'Which color change occurs when blue litmus paper is dipped into an acidic solution?',
        'option_a' => 'Turns red',
        'option_b' => 'Turns green',
        'option_c' => 'Turns yellow',
        'option_d' => 'Stays blue',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Acids turn blue litmus paper red, while bases turn red litmus paper blue.',
      ),
      4 => 
      array (
        'chapter' => 3,
        'q_num' => 5,
        'question' => 'What products are formed in a classic neutralization reaction between an acid and a metal hydroxide base?',
        'option_a' => 'Salt + Water',
        'option_b' => 'Metal + Hydrogen gas',
        'option_c' => 'Acidic oxide + Carbon dioxide',
        'option_d' => 'Base + Oxygen',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Neutralization between an acid and base yields an ionic salt and water (e.g. HCl + NaOH -> NaCl + H2O).',
      ),
      5 => 
      array (
        'chapter' => 3,
        'q_num' => 6,
        'question' => 'Non-metal oxides (such as CO2, SO2, SO3) that react with water to form acids are classified as:',
        'option_a' => 'Basic oxides',
        'option_b' => 'Acidic oxides (Acid anhydrides)',
        'option_c' => 'Amphoteric oxides',
        'option_d' => 'Neutral oxides',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Non-metallic oxides dissolve in water producing acidic solutions (e.g. SO3 + H2O -> H2SO4).',
      ),
      6 => 
      array (
        'chapter' => 3,
        'q_num' => 7,
        'question' => 'Metal oxides (such as Na2O, CaO) that react with water to form hydroxides are classified as:',
        'option_a' => 'Acidic oxides',
        'option_b' => 'Basic oxides',
        'option_c' => 'Neutral oxides',
        'option_d' => 'Amphoteric oxides',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Metal oxides react with water to yield basic metal hydroxides (e.g. CaO + H2O -> Ca(OH)2).',
      ),
      7 => 
      array (
        'chapter' => 3,
        'q_num' => 8,
        'question' => 'Oxides like Al2O3 and ZnO that react with BOTH acids and bases to produce salt and water are:',
        'option_a' => 'Acidic oxides',
        'option_b' => 'Amphoteric oxides',
        'option_c' => 'Basic oxides',
        'option_d' => 'Neutral oxides',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Amphoteric oxides possess dual acidic and basic properties.',
      ),
      8 => 
      array (
        'chapter' => 3,
        'q_num' => 9,
        'question' => 'Which of the following is a NEUTRAL oxide that does not react with acids or bases?',
        'option_a' => 'Carbon monoxide (CO)',
        'option_b' => 'Carbon dioxide (CO2)',
        'option_c' => 'Sulfur trioxide (SO3)',
        'option_d' => 'Calcium oxide (CaO)',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'CO, NO, and N2O are neutral oxides that display neither acidic nor basic properties.',
      ),
      9 => 
      array (
        'chapter' => 3,
        'q_num' => 10,
        'question' => 'Strong acids like HCl, HNO3, and H2SO4 differ from weak acids because strong acids:',
        'option_a' => 'Ionize completely in water',
        'option_b' => 'Ionize only partially in water',
        'option_c' => 'Contain no hydrogen atoms',
        'option_d' => 'Have a pH above 7',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Strong acids undergo 100% dissociation into ions in aqueous solution.',
      ),
      10 => 
      array (
        'chapter' => 3,
        'q_num' => 11,
        'question' => 'Concentrated sulfuric acid (H2SO4) acts as a powerful dehydrating agent because it removes:',
        'option_a' => 'Oxygen gas from air',
        'option_b' => 'Water elements (H2O) from organic carbohydrates like sugar',
        'option_c' => 'Carbon atoms from metals',
        'option_d' => 'Hydrogen gas from acids',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'H2SO4 has high affinity for water, dehydrating sugar into black elemental carbon residue.',
      ),
      11 => 
      array (
        'chapter' => 3,
        'q_num' => 12,
        'question' => 'An acid with a pH of 2 is how many times more acidic than a solution with a pH of 5?',
        'option_a' => '3 times',
        'option_b' => '30 times',
        'option_c' => '1,000 times',
        'option_d' => '100 times',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'Each unit decrease on the logarithmic pH scale represents a 10-fold increase in H+ concentration. Difference of 3 pH units = 10^3 = 1,000 times.',
      ),
      12 => 
      array (
        'chapter' => 3,
        'q_num' => 13,
        'question' => 'Which gas is evolved when a carbonate salt (such as CaCO3) reacts with dilute hydrochloric acid (HCl)?',
        'option_a' => 'Hydrogen gas (H2)',
        'option_b' => 'Carbon dioxide gas (CO2)',
        'option_c' => 'Oxygen gas (O2)',
        'option_d' => 'Chlorine gas (Cl2)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Acid + Carbonate -> Salt + Water + Carbon dioxide gas (CO2).',
      ),
      13 => 
      array (
        'chapter' => 3,
        'q_num' => 14,
        'question' => 'What is the chemical name of common table salt?',
        'option_a' => 'Sodium hydroxide',
        'option_b' => 'Sodium chloride (NaCl)',
        'option_c' => 'Calcium carbonate',
        'option_d' => 'Potassium nitrate',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Table salt is sodium chloride (NaCl).',
      ),
      14 => 
      array (
        'chapter' => 3,
        'q_num' => 15,
        'question' => 'Ammonia (NH3) gas dissolved in water forms a basic solution called ammonium hydroxide because it:',
        'option_a' => 'Accepts a proton from water to produce OH- ions',
        'option_b' => 'Donates H+ to water',
        'option_c' => 'Removes oxygen from air',
        'option_d' => 'Forms acidic oxide',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'NH3 + H2O <=> NH4+ + OH-, producing hydroxide ions.',
      ),
    ),
  ),
  4 => 
  array (
    'chapter_title' => 'Energy Changes and Electrochemistry',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 4,
        'q_num' => 1,
        'question' => 'How is the rate of a chemical reaction defined?',
        'option_a' => 'The ratio of the mass of reactants to products',
        'option_b' => 'The change in concentration of a reactant or product per unit time',
        'option_c' => 'The total time required for a reaction to go to completion',
        'option_d' => 'The energy required to break bonds in reactants',
        'raw_answer' => '**b) The change in concentration of a reactant or product per unit time**',
        'correct_answer' => 'option_b',
        'explanation' => 'Reaction rate measures how fast reactants are consumed or products are formed over time, given in units like mol/(L*s).',
      ),
      1 => 
      array (
        'chapter' => 4,
        'q_num' => 2,
        'question' => 'Increasing the surface area of a solid reactant increases the reaction rate because it:',
        'option_a' => 'Lowers the activation energy of the reaction',
        'option_b' => 'Increases the kinetic energy of the particles',
        'option_c' => 'Increases the frequency of collisions between reactant molecules',
        'option_d' => 'Changes the reaction mechanism',
        'raw_answer' => '**c) Increases the frequency of collisions between reactant molecules**',
        'correct_answer' => 'option_c',
        'explanation' => 'Smaller particles expose a greater surface area, allowing more collisions to occur per unit time.',
      ),
      2 => 
      array (
        'chapter' => 4,
        'q_num' => 3,
        'question' => 'According to Collision Theory, a chemical reaction occurs only if colliding particles have:',
        'option_a' => 'Minimum volume and proper orientation',
        'option_b' => 'Sufficient kinetic energy (activation energy) and correct orientation',
        'option_c' => 'High density and low kinetic energy',
        'option_d' => 'Maximum potential energy and negative charge',
        'raw_answer' => '**b) Sufficient kinetic energy (activation energy) and correct orientation**',
        'correct_answer' => 'option_b',
        'explanation' => 'Effective collisions require particles to collide with energy equal to or exceeding the activation energy (Ea) and in a favourable spatial orientation.',
      ),
      3 => 
      array (
        'chapter' => 4,
        'q_num' => 4,
        'question' => 'How does a catalyst speed up a chemical reaction?',
        'option_a' => 'By increasing the average kinetic energy of the reactants',
        'option_b' => 'By providing an alternative reaction pathway with lower activation energy',
        'option_c' => 'By shifting the equilibrium constant toward products',
        'option_d' => 'By increasing the heat of reaction (delta H)',
        'raw_answer' => '**b) By providing an alternative reaction pathway with lower activation energy**',
        'correct_answer' => 'option_b',
        'explanation' => 'Catalysts lower the activation energy hurdle, enabling a larger fraction of molecular collisions to be successful without altering delta H.',
      ),
      4 => 
      array (
        'chapter' => 4,
        'q_num' => 5,
        'question' => 'For a reaction A + B -> C, the rate law is Rate = k[A]^2[B]^1. What is the overall reaction order?',
        'option_a' => '1',
        'option_b' => '2',
        'option_c' => '3',
        'option_d' => '',
        'raw_answer' => '**c) 3**',
        'correct_answer' => 'option_c',
        'explanation' => 'The overall order of a reaction is the sum of the exponents of the concentration terms in the rate law expression (2 + 1 = 3).',
      ),
      5 => 
      array (
        'chapter' => 4,
        'q_num' => 6,
        'question' => 'What are the SI units for the rate constant k of a first-order reaction?',
        'option_a' => '1/s',
        'option_b' => 'mol/(L*s)',
        'option_c' => 'L/(mol*s)',
        'option_d' => 'L^2/(mol^2 * s)',
        'raw_answer' => '**a) $\\text{s}^{-1}$**',
        'correct_answer' => 'option_a',
        'explanation' => 'For a first-order rate law (Rate = k[A]), k = {Rate{[A] = {mol/(L*s){mol L^{-1 = 1/s.',
      ),
      6 => 
      array (
        'chapter' => 4,
        'q_num' => 7,
        'question' => 'How does an increase in temperature affect the rate of an endothermic reaction?',
        'option_a' => 'Decreases reaction rate by raising activation energy',
        'option_b' => 'Increases reaction rate by providing more molecules with E >= Ea',
        'option_c' => 'Keeps reaction rate constant while increasing enthalpy',
        'option_d' => 'Decreases reaction rate by destroying the catalyst',
        'raw_answer' => '**b) Increases reaction rate by providing more molecules with $E \\ge E_a$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Higher temperature increases the average kinetic energy of particles, significantly increasing the fraction of collisions exceeding activation energy.',
      ),
      7 => 
      array (
        'chapter' => 4,
        'q_num' => 8,
        'question' => 'The Arrhenius equation k = A e^{-Ea / RT shows that the rate constant k decreases when:',
        'option_a' => 'Temperature (T) increases',
        'option_b' => 'Activation energy (Ea) increases',
        'option_c' => 'Frequency factor (A) increases',
        'option_d' => 'A catalyst is added',
        'raw_answer' => '**b) Activation energy ($E_a$) increases**',
        'correct_answer' => 'option_b',
        'explanation' => 'Higher activation energy means fewer molecules have enough energy to react, resulting in a smaller rate constant k.',
      ),
      8 => 
      array (
        'chapter' => 4,
        'q_num' => 9,
        'question' => 'In a zero-order reaction, how does changing the reactant concentration affect the reaction rate?',
        'option_a' => 'Doubles the rate when concentration is doubled',
        'option_b' => 'Quadruples the rate when concentration is doubled',
        'option_c' => 'Has no effect on the reaction rate',
        'option_d' => 'Halves the reaction rate',
        'raw_answer' => '**c) Has no effect on the reaction rate**',
        'correct_answer' => 'option_c',
        'explanation' => 'For a zero-order reaction (Rate = k[A]^0 = k), the reaction rate is independent of reactant concentration.',
      ),
      9 => 
      array (
        'chapter' => 4,
        'q_num' => 10,
        'question' => 'What is the half-life (t(1/2)) expression for a first-order reaction?',
        'option_a' => 't(1/2) = {[A]_0{2k',
        'option_b' => 't(1/2) = {0.693{k',
        'option_c' => 't(1/2) = {1{k[A]_0',
        'option_d' => 't(1/2) = {k{0.693',
        'raw_answer' => '**b) $t_{1/2} = \\frac{0.693}{k}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'The half-life of a first-order process is independent of initial concentration and is given by t(1/2) = {\\ln(2){k \\approx {0.693{k.',
      ),
      10 => 
      array (
        'chapter' => 4,
        'q_num' => 11,
        'question' => 'The slowest step in a multi-step reaction mechanism is known as the:',
        'option_a' => 'Intermediate step',
        'option_b' => 'Rate-determining step',
        'option_c' => 'Catalytic step',
        'option_d' => 'Equilibrium step',
        'raw_answer' => '**b) Rate-determining step**',
        'correct_answer' => 'option_b',
        'explanation' => 'The rate-determining step acts as a bottleneck and dictates the overall reaction rate and rate law.',
      ),
      11 => 
      array (
        'chapter' => 4,
        'q_num' => 12,
        'question' => 'A substance formed in an elementary step of a reaction mechanism and consumed in a subsequent step is called a(n):',
        'option_a' => 'Catalyst',
        'option_b' => 'Reaction intermediate',
        'option_c' => 'Activated complex',
        'option_d' => 'Inhibitor',
        'raw_answer' => '**b) Reaction intermediate**',
        'correct_answer' => 'option_b',
        'explanation' => 'Intermediates are produced during intermediate steps and do not appear in the overall balanced chemical equation.',
      ),
      12 => 
      array (
        'chapter' => 4,
        'q_num' => 13,
        'question' => 'What happens to the activated complex (transition state) during a chemical reaction?',
        'option_a' => 'It exists indefinitely as a stable species',
        'option_b' => 'It can break apart to reform reactants or form products',
        'option_c' => 'It lowers the temperature of the system',
        'option_d' => 'It acts as an insoluble precipitate',
        'raw_answer' => '**b) It can break apart to reform reactants or form products**',
        'correct_answer' => 'option_b',
        'explanation' => 'The transition state is an unstable configuration of high potential energy that decays either forward to products or back to reactants.',
      ),
      13 => 
      array (
        'chapter' => 4,
        'q_num' => 14,
        'question' => 'If doubling [A] increases the rate by a factor of 4 while holding [B] constant, what is the order with respect to A?',
        'option_a' => '',
        'option_b' => '1',
        'option_c' => '2',
        'option_d' => '3',
        'raw_answer' => '**c) 2**',
        'correct_answer' => 'option_c',
        'explanation' => 'Since 2^x = 4, solving for x gives x = 2, indicating a second-order dependence on A.',
      ),
      14 => 
      array (
        'chapter' => 4,
        'q_num' => 15,
        'question' => 'Which factor does NOT influence the rate of a homogeneous gas-phase reaction?',
        'option_a' => 'Temperature',
        'option_b' => 'Concentration of reactants',
        'option_c' => 'Surface area of container walls',
        'option_d' => 'Presence of a catalyst',
        'raw_answer' => '**c) Surface area of container walls**',
        'correct_answer' => 'option_c',
        'explanation' => 'Surface area affects heterogeneous reactions (solid-gas or solid-liquid interface), whereas homogeneous gas reactions occur throughout the volume.',
      ),
    ),
  ),
  5 => 
  array (
    'chapter_title' => 'Metals and Nonmetals',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 5,
        'q_num' => 1,
        'question' => 'A state of dynamic chemical equilibrium is reached when:',
        'option_a' => 'All reactants are completely converted into products',
        'option_b' => 'The rates of the forward and reverse reactions are equal',
        'option_c' => 'The concentrations of reactants and products become equal',
        'option_d' => 'The reaction stops occurring entirely',
        'raw_answer' => '**b) The rates of the forward and reverse reactions are equal**',
        'correct_answer' => 'option_b',
        'explanation' => 'Equilibrium is dynamic because forward and reverse processes continue at equal rates, maintaining constant concentrations.',
      ),
      1 => 
      array (
        'chapter' => 5,
        'q_num' => 2,
        'question' => 'What is the correct equilibrium constant expression (Kc) for N_2(g) + 3H_2(g) <=> 2NH_3(g)?',
        'option_a' => 'Kc = {[N_2][H_2]^3{[NH_3]^2',
        'option_b' => 'Kc = {[NH_3]^2{[N_2][H_2]^3',
        'option_c' => 'Kc = {2[NH_3]{[N_2] + 3[H_2]',
        'option_d' => 'Kc = {[NH_3]{[N_2][H_2]',
        'raw_answer' => '**b) $K_c = \\frac{[NH_3]^2}{[N_2][H_2]^3}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'The equilibrium expression is the ratio of product concentrations raised to their stoichiometric coefficients over reactant concentrations.',
      ),
      2 => 
      array (
        'chapter' => 5,
        'q_num' => 3,
        'question' => 'Which state of matter is excluded from an equilibrium constant expression?',
        'option_a' => 'Gases',
        'option_b' => 'Aqueous solutions',
        'option_c' => 'Pure solids and liquids',
        'option_d' => 'Polyatomic ions',
        'raw_answer' => '**c) Pure solids and liquids**',
        'correct_answer' => 'option_c',
        'explanation' => 'The concentrations of pure solids and pure liquids remain constant throughout a reaction and are incorporated into the value of K.',
      ),
      3 => 
      array (
        'chapter' => 5,
        'q_num' => 4,
        'question' => 'What does a very large value of Kc (Kc >> 1) indicate about a reaction at equilibrium?',
        'option_a' => 'Reactants are strongly favored',
        'option_b' => 'Products are strongly favored',
        'option_c' => 'The reaction proceeds very slowly',
        'option_d' => 'The reaction rate is zero',
        'raw_answer' => '**b) Products are strongly favored**',
        'correct_answer' => 'option_b',
        'explanation' => 'Kc >> 1 means the numerator (products) is much larger than the denominator (reactants) at equilibrium.',
      ),
      4 => 
      array (
        'chapter' => 5,
        'q_num' => 5,
        'question' => 'According to Le Chatelier\'s Principle, what happens if the pressure is increased on 2SO_2(g) + O_2(g) <=> 2SO_3(g)?',
        'option_a' => 'Equilibrium shifts to the left',
        'option_b' => 'Equilibrium shifts to the right',
        'option_c' => 'No shift occurs',
        'option_d' => 'Kc value increases',
        'raw_answer' => '**b) Equilibrium shifts to the right**',
        'correct_answer' => 'option_b',
        'explanation' => 'Increasing pressure shifts equilibrium toward the side with fewer moles of gas (3 moles on left -> 2 moles on right).',
      ),
      5 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'For an endothermic reaction (delta H > 0), what effect does an increase in temperature have on Kc?',
        'option_a' => 'Kc increases',
        'option_b' => 'Kc decreases',
        'option_c' => 'Kc remains unchanged',
        'option_d' => 'Kc becomes zero',
        'raw_answer' => '**a) $K_c$ increases**',
        'correct_answer' => 'option_a',
        'explanation' => 'Heat is treated as a reactant in endothermic reactions. Adding heat shifts the reaction right, raising product concentration and Kc.',
      ),
      6 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'What is the relationship between Kp and Kc for a gas-phase reaction?',
        'option_a' => 'Kp = Kc(RT)^{delta n',
        'option_b' => 'Kp = Kc + RTdelta n',
        'option_c' => 'Kc = Kp(RT)^{delta n',
        'option_d' => 'Kp = {Kc{RT',
        'raw_answer' => '**a) $K_p = K_c(RT)^{\\Delta n}$**',
        'correct_answer' => 'option_a',
        'explanation' => 'delta n represents the change in moles of gas (moles of gaseous products - moles of gaseous reactants).',
      ),
      7 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'Under what condition is Kp equal to Kc?',
        'option_a' => 'When delta n = 1',
        'option_b' => 'When delta n = 0',
        'option_c' => 'When temperature is 0^°C',
        'option_d' => 'When total pressure is 1 atm',
        'raw_answer' => '**b) When $\\Delta n = 0$**',
        'correct_answer' => 'option_b',
        'explanation' => 'When there is no change in the number of gaseous moles (delta n = 0), (RT)^0 = 1, making Kp = Kc.',
      ),
      8 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'If the reaction quotient Qc is greater than Kc (Qc > Kc), how will the system respond to reach equilibrium?',
        'option_a' => 'Shift right to form more products',
        'option_b' => 'Shift left to form more reactants',
        'option_c' => 'Remain unchanged because it is at equilibrium',
        'option_d' => 'Decrease overall temperature',
        'raw_answer' => '**b) Shift left to form more reactants**',
        'correct_answer' => 'option_b',
        'explanation' => 'When Qc > Kc, product concentration is too high relative to reactants, driving the reverse reaction to restore equilibrium.',
      ),
      9 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'Adding a catalyst to a system at equilibrium results in:',
        'option_a' => 'A shift of equilibrium toward products',
        'option_b' => 'A higher numerical value for Kc',
        'option_c' => 'Faster attainment of equilibrium without shifting position',
        'option_d' => 'A lower yield of reactants',
        'raw_answer' => '**c) Faster attainment of equilibrium without shifting position**',
        'correct_answer' => 'option_c',
        'explanation' => 'Catalysts speed up both forward and reverse reaction rates equally, leaving the equilibrium position and Kc unchanged.',
      ),
      10 => 
      array (
        'chapter' => 5,
        'q_num' => 11,
        'question' => 'For the reversible process C(s) + CO_2(g) <=> 2CO(g), what is Kc?',
        'option_a' => '{[CO]^2{[C][CO_2]',
        'option_b' => '{[CO]^2{[CO_2]',
        'option_c' => '{2[CO]{[CO_2]',
        'option_d' => '{[CO_2]{[CO]^2',
        'raw_answer' => '**b) $\\frac{[CO]^2}{[CO_2]}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Solid carbon C(s) is omitted from the expression because its activity/concentration is constant.',
      ),
      11 => 
      array (
        'chapter' => 5,
        'q_num' => 12,
        'question' => 'In Haber\'s process (N_2 + 3H_2 <=> 2NH_3 + heat), maximum ammonia yield is favored by:',
        'option_a' => 'High temperature and low pressure',
        'option_b' => 'Low temperature and high pressure',
        'option_c' => 'High temperature and high pressure',
        'option_d' => 'Low temperature and low pressure',
        'raw_answer' => '**b) Low temperature and high pressure**',
        'correct_answer' => 'option_b',
        'explanation' => 'The forward reaction is exothermic (favored by lower temperature) and produces fewer gas moles (favored by high pressure).',
      ),
      12 => 
      array (
        'chapter' => 5,
        'q_num' => 13,
        'question' => 'What happens to equilibrium when an inert gas (like Helium) is added at constant volume?',
        'option_a' => 'Shifts to side with fewer gas moles',
        'option_b' => 'Shifts to side with more gas moles',
        'option_c' => 'No shift in equilibrium occurs',
        'option_d' => 'Kc decreases',
        'raw_answer' => '**c) No shift in equilibrium occurs**',
        'correct_answer' => 'option_c',
        'explanation' => 'Adding an inert gas at constant volume increases total pressure without altering partial pressures or concentrations of reacting species.',
      ),
      13 => 
      array (
        'chapter' => 5,
        'q_num' => 14,
        'question' => 'If Kc = 4.0 for A <=> B, what is Kc\' for the reverse reaction B <=> A?',
        'option_a' => '-4.0',
        'option_b' => '0.25',
        'option_c' => '2.0',
        'option_d' => '16.0',
        'raw_answer' => '**b) $0.25$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Reversing an equation inverts its equilibrium constant: K_{rev = {1{K_{fwd = {1{4.0 = 0.25.',
      ),
      14 => 
      array (
        'chapter' => 5,
        'q_num' => 15,
        'question' => 'Removing a product continuously from a chemical equilibrium reaction vessel causes the system to:',
        'option_a' => 'Shift right to produce more product',
        'option_b' => 'Shift left to consume remaining product',
        'option_c' => 'Lower its activation energy',
        'option_d' => 'Stop reacting',
        'raw_answer' => '**a) Shift right to produce more product**',
        'correct_answer' => 'option_a',
        'explanation' => 'Removing a product disturbs equilibrium; by Le Chatelier\'s principle, the system shifts right to replace it.',
      ),
    ),
  ),
  6 => 
  array (
    'chapter_title' => 'Hydrocarbons and Their Natural Sources',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 6,
        'q_num' => 1,
        'question' => 'What functional group characterizes alcohols?',
        'option_a' => 'Carbonyl group (-C=O)',
        'option_b' => 'Hydroxyl group (-OH)',
        'option_c' => 'Carboxyl group (-COOH)',
        'option_d' => 'Ether link (-O-)',
        'raw_answer' => '**b) Hydroxyl group ($-\\text{OH}$)**',
        'correct_answer' => 'option_b',
        'explanation' => 'Alcohols contain a hydroxyl group (-OH) attached to a saturated carbon atom.',
      ),
      1 => 
      array (
        'chapter' => 6,
        'q_num' => 2,
        'question' => 'Which of the following is classified as a secondary (2^°) alcohol?',
        'option_a' => 'Methanol',
        'option_b' => 'Ethanol',
        'option_c' => 'Propan-2-ol',
        'option_d' => '2-Methylpropan-2-ol',
        'raw_answer' => '**c) Propan-2-ol**',
        'correct_answer' => 'option_c',
        'explanation' => 'In propan-2-ol, the carbon bearing the -OH group is bonded to two other carbon atoms.',
      ),
      2 => 
      array (
        'chapter' => 6,
        'q_num' => 3,
        'question' => 'What is the general formula of open-chain aliphatic ethers?',
        'option_a' => 'R-OH',
        'option_b' => 'R-O-R\'',
        'option_c' => 'R-CHO',
        'option_d' => 'R-COOR\'',
        'raw_answer' => '**b) $R-\\text{O}-R\'$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Ethers feature an oxygen atom bonded to two alkyl or aryl groups.',
      ),
      3 => 
      array (
        'chapter' => 6,
        'q_num' => 4,
        'question' => 'Primary alcohols oxidize in the presence of acidified K_2Cr_2O_7 to form aldehydes, which further oxidize to:',
        'option_a' => 'Ketones',
        'option_b' => 'Esters',
        'option_c' => 'Carboxylic acids',
        'option_d' => 'Ethers',
        'raw_answer' => '**c) Carboxylic acids**',
        'correct_answer' => 'option_c',
        'explanation' => 'Oxidation of a 1^° alcohol proceeds via aldehyde intermediate to produce a carboxylic acid containing the same number of carbons.',
      ),
      4 => 
      array (
        'chapter' => 6,
        'q_num' => 5,
        'question' => 'What functional group is present in both aldehydes and ketones?',
        'option_a' => 'Carboxyl group',
        'option_b' => 'Hydroxyl group',
        'option_c' => 'Carbonyl group',
        'option_d' => 'Ester group',
        'raw_answer' => '**c) Carbonyl group**',
        'correct_answer' => 'option_c',
        'explanation' => 'Both aldehydes (R-CHO) and ketones (R-CO-R\') contain a carbon-oxygen double bond (C=O).',
      ),
      5 => 
      array (
        'chapter' => 6,
        'q_num' => 6,
        'question' => 'Which structural formula correctly identifies an aldehyde?',
        'option_a' => 'CH_3-CO-CH_3',
        'option_b' => 'CH_3-CH_2-CHO',
        'option_c' => 'CH_3-CH_2-COOH',
        'option_d' => 'CH_3-O-CH_3',
        'raw_answer' => '**b) $CH_3-CH_2-\\text{CHO}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Aldehydes contain a terminal carbonyl group (-CHO).',
      ),
      6 => 
      array (
        'chapter' => 6,
        'q_num' => 7,
        'question' => 'Tollens\' test (silver mirror test) is used to distinguish between:',
        'option_a' => 'Alcohols and Ethers',
        'option_b' => 'Aldehydes and Ketones',
        'option_c' => 'Carboxylic acids and Esters',
        'option_d' => 'Primary and Secondary alcohols',
        'raw_answer' => '**b) Aldehydes and Ketones**',
        'correct_answer' => 'option_b',
        'explanation' => 'Aldehydes reduce Tollens\' reagent (<=ft[Ag(NH_3)_2\\right]^+) to metallic silver, whereas ketones do not easily undergo oxidation.',
      ),
      7 => 
      array (
        'chapter' => 6,
        'q_num' => 8,
        'question' => 'What type of organic compound is formed by the reaction between a carboxylic acid and an alcohol in the presence of an acid catalyst?',
        'option_a' => 'Ether',
        'option_b' => 'Ester',
        'option_c' => 'Ketone',
        'option_d' => 'Acid hydride',
        'raw_answer' => '**b) Ester**',
        'correct_answer' => 'option_b',
        'explanation' => 'Esterification is the condensation reaction between a carboxylic acid and an alcohol yielding an ester and water.',
      ),
      8 => 
      array (
        'chapter' => 6,
        'q_num' => 9,
        'question' => 'What is the IUPAC name of the compound CH_3-CH_2-COOH?',
        'option_a' => 'Ethanoic acid',
        'option_b' => 'Propanoic acid',
        'option_c' => 'Methanoic acid',
        'option_d' => 'Propanone',
        'raw_answer' => '**b) Propanoic acid**',
        'correct_answer' => 'option_b',
        'explanation' => 'A 3-carbon carboxylic acid chain is named propanoic acid.',
      ),
      9 => 
      array (
        'chapter' => 6,
        'q_num' => 10,
        'question' => 'Ethers generally have lower boiling points than isomeric alcohols because:',
        'option_a' => 'Ethers have higher molar mass',
        'option_b' => 'Ethers cannot form hydrogen bonds with themselves',
        'option_c' => 'Alcohols are nonpolar',
        'option_d' => 'Ethers undergo ionic bonding',
        'raw_answer' => '**b) Ethers cannot form hydrogen bonds with themselves**',
        'correct_answer' => 'option_b',
        'explanation' => 'Alcohols participate in intermolecular hydrogen bonding via their -OH group, requiring higher thermal energy to boil.',
      ),
      10 => 
      array (
        'chapter' => 6,
        'q_num' => 11,
        'question' => 'Saponification is defined as the alkaline hydrolysis of:',
        'option_a' => 'Primary alcohols',
        'option_b' => 'Aldehydes',
        'option_c' => 'Esters (fats and oils)',
        'option_d' => 'Simple ethers',
        'raw_answer' => '**c) Esters (fats and oils)**',
        'correct_answer' => 'option_c',
        'explanation' => 'Saponification involves heating an ester/fat with a strong base (like NaOH) to produce glycerol and soap salts.',
      ),
      11 => 
      array (
        'chapter' => 6,
        'q_num' => 12,
        'question' => 'Reduction of a ketone using NaBH_4 or LiAlH_4 produces a:',
        'option_a' => 'Primary alcohol',
        'option_b' => 'Secondary alcohol',
        'option_c' => 'Tertiary alcohol',
        'option_d' => 'Carboxylic acid',
        'raw_answer' => '**b) Secondary alcohol**',
        'correct_answer' => 'option_b',
        'explanation' => 'Hydride reduction converts the carbonyl oxygen of a ketone into a secondary hydroxyl group.',
      ),
      12 => 
      array (
        'chapter' => 6,
        'q_num' => 13,
        'question' => 'What is the IUPAC name for CH_3-CO-CH_2-CH_3?',
        'option_a' => 'Butan-1-ol',
        'option_b' => 'Butan-2-one',
        'option_c' => 'Butanal',
        'option_d' => 'Ethoxyethane',
        'raw_answer' => '**b) Butan-2-one**',
        'correct_answer' => 'option_b',
        'explanation' => 'A four-carbon chain ketone with the carbonyl oxygen at position 2 is named butan-2-one.',
      ),
      13 => 
      array (
        'chapter' => 6,
        'q_num' => 14,
        'question' => 'Triglycerides (fats and oils) are structurally classified as triesters formed from glycerol and:',
        'option_a' => 'Mineral acids',
        'option_b' => 'Fatty acids',
        'option_c' => 'Aromatic aldehydes',
        'option_d' => 'Dihalides',
        'raw_answer' => '**b) Fatty acids**',
        'correct_answer' => 'option_b',
        'explanation' => 'Fats and oils are esters composed of propane-1,2,3-triol (glycerol) bonded to three long-chain carboxylic acids (fatty acids).',
      ),
      14 => 
      array (
        'chapter' => 6,
        'q_num' => 15,
        'question' => 'Fehling\'s solution yields a brick-red precipitate of Cu_2O upon reaction with:',
        'option_a' => 'Aliphatic aldehydes',
        'option_b' => 'Aromatic ketones',
        'option_c' => 'Tertiary alcohols',
        'option_d' => 'Dialkyl ethers',
        'raw_answer' => '**a) Aliphatic aldehydes**',
        'correct_answer' => 'option_a',
        'explanation' => 'Fehling\'s reagent oxidizes aliphatic aldehydes to carboxylate ions while copper(II) is reduced to a red copper(I) oxide (Cu_2O) precipitate. [Grade 11 Chemistry Unit 4 Tutorial](https://www.youtube.com/watch?v=5qzw0uNWTFw) This video provides a complete lesson overview of Unit 4 (Chemical Kinetics) tailored specifically to the Ethiopian Grade 11 Chemistry curriculum. </USER_REQUEST> <ADDITIONAL_METADATA> The current local time is: 2026-09-02T17:39:21+03:00. The user\'s current state is as follows: Active Document: c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10MathematicsQuizSeeder.php (LANGUAGE_PHP) Cursor is on line: 1 Other open documents: - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10MathematicsQuizSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\ApproveExistingTutorsSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10HistoryQuizSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\tests\\Feature\\Grade10HistoryQuizSeederTest.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\API_ENDPOINTS.md (LANGUAGE_MARKDOWN) </ADDITIONAL_METADATA>',
      ),
    ),
  ),
);
    }
}

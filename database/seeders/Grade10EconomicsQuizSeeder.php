<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade10EconomicsQuizSeeder extends Seeder
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

            $subject = Subject::where('grade_id', $grade->id)->where('title', 'Economics')->first();
            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Economics',
                    'description' => 'Grade 10 Economics Curriculum',
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
                        'description' => "Assessment quiz for Grade 10 Economics Chapter {$chapterNumber}.",
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
    'chapter_title' => 'Theory of Consumer Behaviour',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 1,
        'q_num' => 1,
        'question' => 'Which of the following best defines "utility" in economics?',
        'option_a' => 'The usefulness of a product to society',
        'option_b' => 'The total amount of money a consumer spends on goods',
        'option_c' => 'The level of satisfaction or pleasure derived from consuming a commodity',
        'option_d' => 'The physical durability of a consumer good',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Utility is defined as the level of satisfaction or pleasure derived from the consumption of a good or service. It measures the power of a commodity to satisfy human wants.',
      ),
      1 => 
      array (
        'chapter' => 1,
        'q_num' => 2,
        'question' => 'Which statement correctly describes the relativity of utility?',
        'option_a' => 'Utility is absolute and measured the same way for every individual',
        'option_b' => 'Utility is synonymous with the physical usefulness of a commodity',
        'option_c' => 'The same commodity can provide different levels of utility to different individuals, or at different times and places',
        'option_d' => 'Utility remains constant regardless of the quantity consumed',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Utility is subjective to a person\'s needs and varies from person to person, unit to unit, time to time, and place to place.',
      ),
      2 => 
      array (
        'chapter' => 1,
        'q_num' => 3,
        'question' => 'According to the Cardinal Utility Theory, satisfaction can be measured in subjective units called:',
        'option_a' => 'Indifference levels',
        'option_b' => 'Utils',
        'option_c' => 'Elasticities',
        'option_d' => 'Rates of substitution',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Cardinal Utility approach assumes that utility is measurable like height or weight and suggests a hypothetical unit of measurement called "utils".',
      ),
      3 => 
      array (
        'chapter' => 1,
        'q_num' => 4,
        'question' => 'Which of the following is NOT an assumption of the Cardinal Utility Theory?',
        'option_a' => 'The consumer is rational',
        'option_b' => 'Marginal utility of money is constant',
        'option_c' => 'Utility is measured ordinally by ranking preference bundles',
        'option_d' => 'The Law of Diminishing Marginal Utility applies',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Ordinal utility theory ranks preference bundles. Cardinal utility theory assumes utility is quantitatively measurable in numerical or monetary units.',
      ),
      4 => 
      array (
        'chapter' => 1,
        'q_num' => 5,
        'question' => 'What is Marginal Utility (MU)?',
        'option_a' => 'The total satisfaction derived from consuming all units of a commodity',
        'option_b' => 'The additional utility derived from consuming one more unit of a commodity',
        'option_c' => 'The ratio of total expenditure to consumer income',
        'option_d' => 'The maximum level of satisfaction a consumer can attain',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Marginal Utility (MU) refers to the change in total utility resulting from the consumption of one additional unit of a commodity (MU = Δ TU / Δ Q).',
      ),
      5 => 
      array (
        'chapter' => 1,
        'q_num' => 6,
        'question' => 'At the saturation point of total utility (TU), marginal utility (MU) is:',
        'option_a' => 'At its maximum',
        'option_b' => 'Equal to 1',
        'option_c' => 'Zero',
        'option_d' => 'Negative',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'When Total Utility (TU) reaches its maximum (the saturation point), Marginal Utility (MU) becomes zero. Any additional consumption beyond this point results in negative MU and declining TU.',
      ),
      6 => 
      array (
        'chapter' => 1,
        'q_num' => 7,
        'question' => 'The Law of Diminishing Marginal Utility states that as consumption of a commodity increases over a given period:',
        'option_a' => 'Total utility continuously decreases',
        'option_b' => 'The marginal utility derived from successive units goes on decreasing',
        'option_c' => 'The price of the commodity decreases',
        'option_d' => 'Marginal utility increases at an increasing rate',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Law of Diminishing Marginal Utility states that as a consumer consumes more units of a good, the additional satisfaction (MU) gained from each successive unit decreases, holding other things constant.',
      ),
      7 => 
      array (
        'chapter' => 1,
        'q_num' => 8,
        'question' => 'In the single-commodity case, a rational cardinal utility-maximizing consumer achieves equilibrium when:',
        'option_a' => 'TU_X = P_X',
        'option_b' => 'MU_X = P_X',
        'option_c' => 'MU_X = 0',
        'option_d' => 'P_X = 0',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'For a single commodity X, consumer equilibrium occurs where the marginal utility derived from the commodity equals its market price (MU_X = P_X).',
      ),
      8 => 
      array (
        'chapter' => 1,
        'q_num' => 9,
        'question' => 'If a consumer consumes two goods (A and B), the consumer equilibrium condition under the cardinal approach is:',
        'option_a' => 'MU_A × P* = MU_B × Q*',
        'option_b' => 'MU_A / P*) = MU_B / Q*',
        'option_c' => 'MU_A + MU_B = Income',
        'option_d' => 'P* / MU_A) = MU_B / Q*',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'For multiple commodities, utility maximization requires the equality of the ratios of marginal utility to price across all goods (MU_A / P*) = MU_B / Q*) = \\dots = MU_M).',
      ),
      9 => 
      array (
        'chapter' => 1,
        'q_num' => 10,
        'question' => 'A consumer consumes good X (P_X = 2 Birr) and good Y (P_Y = 4 Birr). If MU_X = 6 utils and MU_Y = 12 utils, the ratio of MU/P for both goods is:',
        'option_a' => '3 utils per Birr',
        'option_b' => '2 utils per Birr',
        'option_c' => '6 utils per Birr',
        'option_d' => '0.5 utils per Birr',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'MU_X / P_X) = 6 / 2) = 3 utils per Birr, and MU_Y / P_Y) = 12 / 4) = 3 utils per Birr.',
      ),
      10 => 
      array (
        'chapter' => 1,
        'q_num' => 11,
        'question' => 'Unlike cardinal utility theory, ordinal utility theory assumes that:',
        'option_a' => 'Utility can be measured in precise numerical units like utils',
        'option_b' => 'Consumers can rank different commodity bundles in order of preference (1st, 2nd, 3rd)',
        'option_c' => 'Total utility remains constant across all income levels',
        'option_d' => 'Marginal utility of money is always increasing',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Ordinal utility theory holds that utility cannot be measured absolutely, but consumers can rank commodity bundles according to their preferences.',
      ),
      11 => 
      array (
        'chapter' => 1,
        'q_num' => 12,
        'question' => 'If a consumer prefers bundle A over bundle B, and prefers bundle B over bundle C, preference transitivity implies:',
        'option_a' => 'Bundle C is preferred to bundle A',
        'option_b' => 'Bundle B is preferred to bundle A',
        'option_c' => 'Bundle A is preferred to bundle C',
        'option_d' => 'Bundles A and C provide zero utility',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The assumption of transitivity states that if A > B and B > C, then A > C.',
      ),
      12 => 
      array (
        'chapter' => 1,
        'q_num' => 13,
        'question' => 'The rate at which a consumer is willing to substitute one good for another while keeping total satisfaction constant is called:',
        'option_a' => 'Marginal product of labor',
        'option_b' => 'Price elasticity of demand',
        'option_c' => 'Marginal Rate of Substitution (MRS)',
        'option_d' => 'Income elasticity of supply',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The Marginal Rate of Substitution (MRS) measures the rate at which a consumer is willing to give up units of one good for an additional unit of another good while keeping total utility constant.',
      ),
      13 => 
      array (
        'chapter' => 1,
        'q_num' => 14,
        'question' => 'If total utility (TU) increases as consumption increases, marginal utility (MU) must be:',
        'option_a' => 'Zero',
        'option_b' => 'Negative',
        'option_c' => 'Positive',
        'option_d' => 'Constant',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Total utility increases as long as marginal utility remains positive (MU > 0).',
      ),
      14 => 
      array (
        'chapter' => 1,
        'q_num' => 15,
        'question' => 'Which assumption is shared by BOTH Cardinal and Ordinal utility theories?',
        'option_a' => 'Utility is cardinally measurable in utils',
        'option_b' => 'Constant marginal utility of money',
        'option_c' => 'The consumer is rational',
        'option_d' => 'Measurement of satisfaction using monetary units',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Both theories assume that the consumer is rational and seeks to maximize total satisfaction given a limited budget.',
      ),
    ),
  ),
  2 => 
  array (
    'chapter_title' => 'Theories of Demand and Supply',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 2,
        'q_num' => 1,
        'question' => 'In economics, "demand" for a commodity implies:',
        'option_a' => 'A simple wish or desire to own a product',
        'option_b' => 'Willingness to buy backed by the ability to pay at a given price and time',
        'option_c' => 'The total stock of goods available in the market',
        'option_d' => 'The quantity produced by sellers at a market price',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Demand is defined as the willingness to buy a commodity combined with the financial ability to pay for it at a prevailing price during a specific period (Demand = Willingness + Ability).',
      ),
      1 => 
      array (
        'chapter' => 2,
        'q_num' => 2,
        'question' => 'The Law of Demand states that, ceteris paribus:',
        'option_a' => 'As price increases, quantity demanded increases',
        'option_b' => 'As price increases, quantity demanded decreases',
        'option_c' => 'Price and quantity demanded are directly related',
        'option_d' => 'Income and quantity demanded are inversely related',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Law of Demand states an inverse relationship between price and quantity demanded: as price falls, quantity demanded increases, and vice versa.',
      ),
      2 => 
      array (
        'chapter' => 2,
        'q_num' => 3,
        'question' => 'If two goods X and Y are substitutes, an increase in the price of good Y will cause:',
        'option_a' => 'A decrease in demand for good X',
        'option_b' => 'An increase in demand for good X',
        'option_c' => 'No change in demand for good X',
        'option_d' => 'A decrease in the price of good X',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Substitute goods can replace one another. An increase in the price of one good (Y) leads consumers to switch to its substitute (X), increasing the demand for X.',
      ),
      3 => 
      array (
        'chapter' => 2,
        'q_num' => 4,
        'question' => 'Which of the following causes a MOVEMENT along the demand curve rather than a SHIFT of the curve?',
        'option_a' => 'A change in consumer income',
        'option_b' => 'A change in consumer tastes and preferences',
        'option_c' => 'A change in the commodity\'s own price',
        'option_d' => 'A change in the price of related goods',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'A change in the commodity\'s own price causes a movement along the demand curve (change in quantity demanded). Changes in non-price determinants shift the demand curve.',
      ),
      4 => 
      array (
        'chapter' => 2,
        'q_num' => 5,
        'question' => 'Market demand is derived by:',
        'option_a' => 'Multiplying individual demands by market prices',
        'option_b' => 'Taking the horizontal summation of all individual consumer demand curves in the market',
        'option_c' => 'Subtracting individual supply from individual demand',
        'option_d' => 'Taking the vertical summation of individual demand curves',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Market demand is the horizontal summation of the quantities demanded by all individual consumers in the market at each price level.',
      ),
      5 => 
      array (
        'chapter' => 2,
        'q_num' => 6,
        'question' => 'The Law of Supply states that, holding other factors constant, the relationship between price and quantity supplied is:',
        'option_a' => 'Inverse (negative slope)',
        'option_b' => 'Direct (positive slope)',
        'option_c' => 'Independent (zero slope)',
        'option_d' => 'Exponentially decreasing',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Law of Supply states that as the price of a product increases, the quantity supplied increases, resulting in a positively sloped supply curve.',
      ),
      6 => 
      array (
        'chapter' => 2,
        'q_num' => 7,
        'question' => 'Which factor will shift the supply curve of a product to the RIGHT?',
        'option_a' => 'An increase in input costs (e.g., labor wages)',
        'option_b' => 'An improvement in production technology',
        'option_c' => 'An increase in business taxes',
        'option_d' => 'A decrease in the number of suppliers in the market',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Technological improvements increase production efficiency and lower unit costs, shifting the supply curve rightward (increasing supply).',
      ),
      7 => 
      array (
        'chapter' => 2,
        'q_num' => 8,
        'question' => 'Market equilibrium occurs at the price level where:',
        'option_a' => 'Total fixed cost equals total variable cost',
        'option_b' => 'Quantity demanded equals quantity supplied (P* = Q*)',
        'option_c' => 'Quantity demanded exceeds quantity supplied',
        'option_d' => 'Supply is perfectly elastic',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Market equilibrium is reached when quantity demanded equals quantity supplied (P* = Q*), clearing the market.',
      ),
      8 => 
      array (
        'chapter' => 2,
        'q_num' => 9,
        'question' => 'When the market price is set ABOVE the equilibrium price, the result is:',
        'option_a' => 'Excess demand (shortage)',
        'option_b' => 'Excess supply (surplus)',
        'option_c' => 'A leftward shift of the demand curve',
        'option_d' => 'An immediate fall in quantity supplied',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'At a price above equilibrium, suppliers offer more goods than consumers demand, creating a surplus (excess supply).',
      ),
      9 => 
      array (
        'chapter' => 2,
        'q_num' => 10,
        'question' => 'Given P* = 80 - 3P and Q* = 9P - 40, the equilibrium price (||) is:',
        'option_a' => '10 Birr',
        'option_b' => '8 Birr',
        'option_c' => '12 Birr',
        'option_d' => '15 Birr',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Set P* = Q*: 80 - 3P = 9P - 40 ⇒ 120 = 12P ⇒ || = 10 Birr .',
      ),
      10 => 
      array (
        'chapter' => 2,
        'q_num' => 11,
        'question' => 'Using the equilibrium price || = 10 Birr from question 10, the equilibrium quantity (E_XY) is:',
        'option_a' => '40 units',
        'option_b' => '50 units',
        'option_c' => '60 units',
        'option_d' => '70 units',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Substitute || = 10 into P* = 80 - 3(10) = 50 units.',
      ),
      11 => 
      array (
        'chapter' => 2,
        'q_num' => 12,
        'question' => 'If demand increases while supply remains constant, what happens to equilibrium price and quantity?',
        'option_a' => 'Both price and quantity decrease',
        'option_b' => 'Price decreases and quantity increases',
        'option_c' => 'Both price and quantity increase',
        'option_d' => 'Price increases and quantity decreases',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'A rightward shift in demand along a fixed supply curve leads to higher equilibrium price and higher equilibrium quantity.',
      ),
      12 => 
      array (
        'chapter' => 2,
        'q_num' => 13,
        'question' => 'If the price elasticity of demand coefficient (||) is 0.4, demand is classified as:',
        'option_a' => 'Perfectly elastic',
        'option_b' => 'Relatively elastic',
        'option_c' => 'Relatively inelastic',
        'option_d' => 'Unitary elastic',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Demand is inelastic when 0 < || < 1. A coefficient of 0.4 indicates that quantity demanded changes by a smaller percentage than price.',
      ),
      13 => 
      array (
        'chapter' => 2,
        'q_num' => 14,
        'question' => 'If the cross-price elasticity of demand between Good X and Good Y is NEGATIVE ( < 0), the two goods are:',
        'option_a' => 'Substitutes',
        'option_b' => 'Complements',
        'option_c' => 'Independent goods',
        'option_d' => 'Inferior goods',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A negative cross-price elasticity means an increase in the price of Good Y reduces the demand for Good X, indicating that the goods are consumed together as complements.',
      ),
      14 => 
      array (
        'chapter' => 2,
        'q_num' => 15,
        'question' => 'If a 10% increase in consumer income leads to a 15% increase in the quantity demanded of a product, the income elasticity of demand is:',
        'option_a' => '-0.67 (Inferior good)',
        'option_b' => '1.5 (Luxury / Normal good)',
        'option_c' => '0.67 (Necessity)',
        'option_d' => '1.0 (Unitary elastic)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => '',
      ),
    ),
  ),
  3 => 
  array (
    'chapter_title' => 'Theories of Production and Cost',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 3,
        'q_num' => 1,
        'question' => 'The short run in production theory is defined as a time period in which:',
        'option_a' => 'All inputs are variable',
        'option_b' => 'At least one input is fixed while others are variable',
        'option_c' => 'Production cannot be altered',
        'option_d' => 'Technology is constantly changing',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The short run is defined as a operational horizon where at least one factor of production (such as capital or land) remains fixed.',
      ),
      1 => 
      array (
        'chapter' => 3,
        'q_num' => 2,
        'question' => 'Average Product of Labor (AP_L) is calculated as:',
        'option_a' => 'Δ TP / Δ L',
        'option_b' => 'TP / L',
        'option_c' => 'TP × L',
        'option_d' => 'TC / Q',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Average Product of Labor measures output per unit of labor input and is calculated by dividing Total Product (TP) by labor units (L).',
      ),
      2 => 
      array (
        'chapter' => 3,
        'q_num' => 3,
        'question' => 'When Total Product (TP) reaches its maximum level, Marginal Product (MP_L) is:',
        'option_a' => 'At its maximum',
        'option_b' => 'Equal to AP_L',
        'option_c' => 'Zero',
        'option_d' => 'Negative',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'MP_L represents the slope of the TP curve. When TP reaches its peak, its slope is zero, making MP_L = 0.',
      ),
      3 => 
      array (
        'chapter' => 3,
        'q_num' => 4,
        'question' => 'According to the relationship between AP_L and MP_L, when MP_L > AP_L:',
        'option_a' => 'AP_L is falling',
        'option_b' => 'AP_L is rising',
        'option_c' => 'AP_L is at its minimum',
        'option_d' => 'AP_L equals zero',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'As long as the marginal value added (MP_L) is greater than the average value (AP_L), the average product (AP_L) is pulled upwards.',
      ),
      4 => 
      array (
        'chapter' => 3,
        'q_num' => 5,
        'question' => 'In Stage II of short-run production:',
        'option_a' => 'MP_L is increasing',
        'option_b' => 'MP_L is positive but decreasing, and TP is increasing at a decreasing rate',
        'option_c' => 'MP_L is negative',
        'option_d' => 'TP is falling',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Stage II (diminishing returns stage) covers the range where MP_L is positive but declining, ending where MP_L = 0 (maximum TP). Rational producers operate in Stage II.',
      ),
      5 => 
      array (
        'chapter' => 3,
        'q_num' => 6,
        'question' => 'An isoquant curve shows:',
        'option_a' => 'Combinations of goods that yield equal utility to a consumer',
        'option_b' => 'Efficient combinations of two variable inputs (e.g., labor and capital) that yield the same output level',
        'option_c' => 'Total cost of producing different output levels',
        'option_d' => 'The combination of inputs with equal costs',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'An isoquant represents all technical combinations of two variable inputs (such as L and K) that produce a constant level of output.',
      ),
      6 => 
      array (
        'chapter' => 3,
        'q_num' => 7,
        'question' => 'The absolute value of the slope of an isoquant is known as the:',
        'option_a' => 'Marginal Rate of Substitution (MRS)',
        'option_b' => 'Marginal Rate of Technical Substitution (MRTS_(L,K))',
        'option_c' => 'Price Elasticity of Supply',
        'option_d' => 'Marginal Cost',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'MRTS_(L,K) is the slope of an isoquant (Δ K / Δ L) and measures the rate at which capital can be replaced by labor while keeping output constant.',
      ),
      7 => 
      array (
        'chapter' => 3,
        'q_num' => 8,
        'question' => 'If scaling up all inputs by 20% leads to a 35% increase in output, the production process exhibits:',
        'option_a' => 'Constant Returns to Scale',
        'option_b' => 'Decreasing Returns to Scale',
        'option_c' => 'Increasing Returns to Scale',
        'option_d' => 'Diminishing Marginal Utility',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Increasing Returns to Scale occurs when total output increases by a greater proportion than the proportional increase in all inputs.',
      ),
      8 => 
      array (
        'chapter' => 3,
        'q_num' => 9,
        'question' => 'Which of the following costs is INDEPENDENT of the level of output produced in the short run?',
        'option_a' => 'Total Variable Cost (TVC)',
        'option_b' => 'Total Fixed Cost (TFC)',
        'option_c' => 'Marginal Cost (MC)',
        'option_d' => 'Average Variable Cost (AVC)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Total Fixed Cost (TFC) does not vary with output changes and must be paid even if output is zero.',
      ),
      9 => 
      array (
        'chapter' => 3,
        'q_num' => 10,
        'question' => 'Average Total Cost (ATC) is equal to:',
        'option_a' => 'AFC - AVC',
        'option_b' => 'TFC + TVC',
        'option_c' => 'AFC + AVC',
        'option_d' => 'AFC × AVC',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Average Total Cost (ATC) is the sum of Average Fixed Cost (AFC) and Average Variable Cost (AVC), i.e., ATC = AFC + AVC.',
      ),
      10 => 
      array (
        'chapter' => 3,
        'q_num' => 11,
        'question' => 'What happens to Average Fixed Cost (AFC) as production output increases?',
        'option_a' => 'It increases continuously',
        'option_b' => 'It remains constant',
        'option_c' => 'It decreases continuously',
        'option_d' => 'It drops to a minimum and then rises (U-shaped)',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Because TFC is constant, AFC = TFC / Q) continuously decreases as output (Q) rises.',
      ),
      11 => 
      array (
        'chapter' => 3,
        'q_num' => 12,
        'question' => 'The Marginal Cost (MC) curve intersects both AVC and ATC curves at:',
        'option_a' => 'Their maximum points',
        'option_b' => 'Their minimum points',
        'option_c' => 'The origin',
        'option_d' => 'Output level zero',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Mathematically and graphically, the MC curve intersects both the Average Variable Cost (AVC) and Average Total Cost (ATC) curves at their respective minimum points.',
      ),
      12 => 
      array (
        'chapter' => 3,
        'q_num' => 13,
        'question' => 'If a firm\'s output is 5 units, its TFC = 100 Birr, and its TVC = 90 Birr. What is its Average Total Cost (ATC)?',
        'option_a' => '38 Birr',
        'option_b' => '18 Birr',
        'option_c' => '20 Birr',
        'option_d' => '190 Birr',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => '',
      ),
      13 => 
      array (
        'chapter' => 3,
        'q_num' => 14,
        'question' => 'The range on the Long-Run Average Cost (LAC) curve where LAC is DECREASING as output expands is characterized by:',
        'option_a' => 'Diseconomies of scale',
        'option_b' => 'Economies of scale',
        'option_c' => 'Negative marginal output',
        'option_d' => 'Constant returns to scale',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Economies of scale refer to the decline in long-run average cost as plant size and output expand.',
      ),
      14 => 
      array (
        'chapter' => 3,
        'q_num' => 15,
        'question' => 'What is the relationship between Marginal Product (MP_L) and Marginal Cost (MC)?',
        'option_a' => 'When MP_L is at its maximum, MC is at its minimum',
        'option_b' => 'When MP_L increases, MC increases',
        'option_c' => 'MP_L and MC are completely independent of each other',
        'option_d' => 'MP_L and MC curves have identical U-shapes',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Production and cost curves are inverse images. When productivity (MP_L) reaches its peak, unit production cost (MC) reaches its minimum. Here are 15 multiple-choice questions for each of Chapters 4, 5, and 6 of the Grade 10 Economics textbook, complete with the correct answers and detailed explanations.',
      ),
    ),
  ),
  4 => 
  array (
    'chapter_title' => 'Market Structure',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 4,
        'q_num' => 1,
        'question' => 'Which of the following is NOT a fundamental characteristic used to classify market structures?',
        'option_a' => 'The number of buyers and sellers in the market',
        'option_b' => 'The nature of the product (homogeneous or differentiated)',
        'option_c' => 'The degree of government involvement in setting income tax rates',
        'option_d' => 'Ease of entry into and exit from the market',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Market structures are characterized by four main features: number of buyers/sellers, nature of the product, barriers to entry/exit, and degree of pricing control. Income tax policy is a macro policy tool, not a market structure defining trait.',
      ),
      1 => 
      array (
        'chapter' => 4,
        'q_num' => 2,
        'question' => 'Under Perfect Competition, a single firm is considered a:',
        'option_a' => 'Price maker',
        'option_b' => 'Price taker',
        'option_c' => 'Price discriminator',
        'option_d' => 'Monopolist',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In a perfectly competitive market, individual firms have a negligible share of total output and cannot influence market price; they must accept the price determined by market supply and demand.',
      ),
      2 => 
      array (
        'chapter' => 4,
        'q_num' => 3,
        'question' => 'In a perfectly competitive market, the demand curve facing an individual firm is:',
        'option_a' => 'Vertical (perfectly inelastic)',
        'option_b' => 'Downward sloping',
        'option_c' => 'Horizontal (perfectly elastic)',
        'option_d' => 'Upward sloping',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Because a perfectly competitive firm can sell any quantity at the prevailing market price, its individual demand curve is horizontal (P = MR = AR).',
      ),
      3 => 
      array (
        'chapter' => 4,
        'q_num' => 4,
        'question' => 'Which market condition is necessary for a Pure Monopoly to exist?',
        'option_a' => 'Large number of small firms',
        'option_b' => 'Single seller with no close substitutes for the product',
        'option_c' => 'Free entry and exit for potential competitors',
        'option_d' => 'Differentiated products offered by competing sellers',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A monopoly exists when a single firm is the sole producer or supplier of a product that has no close substitutes, protected by high barriers to entry.',
      ),
      4 => 
      array (
        'chapter' => 4,
        'q_num' => 5,
        'question' => 'What rule dictates the profit-maximizing output level for firms in ALL market structures?',
        'option_a' => 'P = ATC',
        'option_b' => 'MR = MC',
        'option_c' => 'TR = TFC',
        'option_d' => 'AR = AFC',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Every firm maximizes profit (or minimizes loss) by producing at the level of output where Marginal Revenue (MR) equals Marginal Cost (MC).',
      ),
      5 => 
      array (
        'chapter' => 4,
        'q_num' => 6,
        'question' => 'Which market structure combines elements of monopoly (product differentiation) and competition (many sellers)?',
        'option_a' => 'Perfect Competition',
        'option_b' => 'Oligopoly',
        'option_c' => 'Monopolistic Competition',
        'option_d' => 'Monopsony',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Monopolistic competition is characterized by many buyers and sellers offering differentiated products with relative freedom of entry and exit.',
      ),
      6 => 
      array (
        'chapter' => 4,
        'q_num' => 7,
        'question' => 'In an Oligopoly market, "interdependence" means that:',
        'option_a' => 'Firms ignore the price and output decisions of competitors',
        'option_b' => 'Each firm’s business decisions directly impact and depend on rival actions',
        'option_c' => 'Government determines the price and production volume',
        'option_d' => 'All products are mandatory identical agricultural commodities',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Because an oligopoly consists of a few large firms, each firm must consider how its competitors will react to changes in price, output, or advertising strategies.',
      ),
      7 => 
      array (
        'chapter' => 4,
        'q_num' => 8,
        'question' => 'A market structure dominated by a small number of large, powerful firms is called an:',
        'option_a' => 'Oligopoly',
        'option_b' => 'Monopsony',
        'option_c' => 'Perfect Competition',
        'option_d' => 'Pure Monopoly',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'An oligopoly is a market structure with a few large producers dominating the industry.',
      ),
      8 => 
      array (
        'chapter' => 4,
        'q_num' => 9,
        'question' => 'Which barrier to entry gives a company exclusive legal rights to produce an invention or innovation?',
        'option_a' => 'Economies of scale',
        'option_b' => 'Control of essential raw materials',
        'option_c' => 'Patent / Copyright laws',
        'option_d' => 'Predatory pricing',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Patents and legal copyrights grant legal exclusivity to inventors/producers, creating institutional barriers to entry against competitors.',
      ),
      9 => 
      array (
        'chapter' => 4,
        'q_num' => 10,
        'question' => 'Natural monopolies occur primarily because of:',
        'option_a' => 'Government legislation prohibiting private production',
        'option_b' => 'Substantial economies of scale that allow one large firm to serve the entire market at lower average cost than multiple firms',
        'option_c' => 'Strong brand loyalty created by advertisement',
        'option_d' => 'Unrestricted entry of low-cost international firms',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A natural monopoly arises when massive fixed costs create economies of scale over the entire range of market demand, making single-firm production most cost-effective.',
      ),
      10 => 
      array (
        'chapter' => 4,
        'q_num' => 11,
        'question' => 'Under Monopolistic Competition, product differentiation can be achieved through:',
        'option_a' => 'Physical characteristics and quality',
        'option_b' => 'Branding, packaging, and location',
        'option_c' => 'Customer service and advertising',
        'option_d' => 'All of the above',
        'raw_answer' => 'D',
        'correct_answer' => 'option_d',
        'explanation' => 'Monopolistically competitive firms differentiate products via physical features, service quality, location, design, packaging, and brand reputation.',
      ),
      11 => 
      array (
        'chapter' => 4,
        'q_num' => 12,
        'question' => 'A firm in perfect competition should temporarily SHUT DOWN in the short run if market price drops below:',
        'option_a' => 'Average Total Cost (ATC)',
        'option_b' => 'Average Variable Cost (AVC)',
        'option_c' => 'Total Fixed Cost (TFC)',
        'option_d' => 'Marginal Revenue (MR)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In the short run, if price falls below minimum AVC, the firm cannot cover even its variable operating costs and minimizes losses by shutting down operations (P < AVC).',
      ),
      12 => 
      array (
        'chapter' => 4,
        'q_num' => 13,
        'question' => 'For a firm operating under Pure Monopoly, Marginal Revenue (MR) is:',
        'option_a' => 'Equal to price (P) at all levels of output',
        'option_b' => 'Greater than price (P)',
        'option_c' => 'Less than price (P) for all positive output levels',
        'option_d' => 'Independent of changes in output',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Because a monopolist faces a downward-sloping market demand curve, it must reduce the price on all units sold to sell one additional unit, making MR < P.',
      ),
      13 => 
      array (
        'chapter' => 4,
        'q_num' => 14,
        'question' => 'Non-price competition (such as marketing, product design, and customer service) is most prominent in:',
        'option_a' => 'Perfect competition',
        'option_b' => 'Monopolistic competition and Oligopoly',
        'option_c' => 'Pure Monopoly',
        'option_d' => 'Pure Monopsony',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Firms in monopolistic competition and oligopoly heavily utilize non-price competition to differentiate their products without triggering price wars.',
      ),
      14 => 
      array (
        'chapter' => 4,
        'q_num' => 15,
        'question' => 'In long-run equilibrium, a perfectly competitive firm earns:',
        'option_a' => 'Excessive economic profit',
        'option_b' => 'Zero economic profit (Normal profit)',
        'option_c' => 'Permanent economic losses',
        'option_d' => 'Infinite revenue',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Free entry and exit ensure that abnormal profits attract new entrants (or losses force firms out), adjusting market supply until all remaining firms earn zero economic profit (P = ATC).',
      ),
    ),
  ),
  5 => 
  array (
    'chapter_title' => 'Banking and Finance',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 5,
        'q_num' => 1,
        'question' => 'Money serves as a "medium of exchange" because it:',
        'option_a' => 'Enables individuals to defer payment to a future date',
        'option_b' => 'Facilitates trade by eliminating the need for a double coincidence of wants',
        'option_c' => 'Provides a benchmark to compare the value of different goods',
        'option_d' => 'Maintains identical purchasing power across generations',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'As a medium of exchange, money facilitates trade by eliminating the inefficiencies of barter trade, which requires a mutual double coincidence of wants.',
      ),
      1 => 
      array (
        'chapter' => 5,
        'q_num' => 2,
        'question' => 'Which function of money allows it to act as a common measurement unit for calculating prices and debt?',
        'option_a' => 'Store of value',
        'option_b' => 'Standard of deferred payment',
        'option_c' => 'Unit of account',
        'option_d' => 'Medium of exchange',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'As a unit of account, money provides a standardized numerical metric for expressing prices, valuing assets, and keeping financial records.',
      ),
      2 => 
      array (
        'chapter' => 5,
        'q_num' => 3,
        'question' => 'Commercial banks create money primarily by:',
        'option_a' => 'Printing physical paper currency notes',
        'option_b' => 'Accepting deposits and lending out excess reserves to borrowers',
        'option_c' => 'Selling gold bullion to foreign central banks',
        'option_d' => 'Collecting tax revenues on behalf of the government',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Commercial banks create deposit money through fractional reserve banking by issuing loans using excess reserves generated from customer deposits.',
      ),
      3 => 
      array (
        'chapter' => 5,
        'q_num' => 4,
        'question' => 'What is the primary role of a country’s Central Bank (e.g., National Bank of Ethiopia)?',
        'option_a' => 'Accepting savings deposits from general public consumers',
        'option_b' => 'Providing commercial business loans to individual retail customers',
        'option_c' => 'Formulating monetary policy, issuing currency, and supervising the financial system',
        'option_d' => 'Setting consumer price tags for retail commodities',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Central banks regulate monetary policy, manage currency issuance, control foreign reserves, and oversee financial institution stability. They do not conduct retail banking with individuals.',
      ),
      4 => 
      array (
        'chapter' => 5,
        'q_num' => 5,
        'question' => 'Money whose value is backed by law as legal tender rather than intrinsic material value (like gold or silver) is:',
        'option_a' => 'Commodity money',
        'option_b' => 'Fiat money',
        'option_c' => 'Credit money',
        'option_d' => 'Representative money',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Fiat money has no intrinsic commodity value; it functions as legal tender by government decree and general public confidence.',
      ),
      5 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'The required reserve ratio set by the central bank refers to the:',
        'option_a' => 'Percentage of total revenue a firm must save for emergency operations',
        'option_b' => 'Minimum proportion of customer deposits a commercial bank must hold as reserves',
        'option_c' => 'Maximum interest rate commercial banks can charge on mortgage loans',
        'option_d' => 'Tax rate applied to central bank profit profits',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The reserve requirement is a regulatory requirement mandating that commercial banks hold a specified fraction of deposit liabilities as vault cash or central bank deposits.',
      ),
      6 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'Electronic Banking (E-Banking) refers to:',
        'option_a' => 'The physical transfer of cash between bank tellers',
        'option_b' => 'Financial services delivered through electronic channels like ATMs, mobile apps, and internet portals',
        'option_c' => 'In-person money transfer services executed at post office desks',
        'option_d' => 'Cash payments collected manually at retail counters',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'E-banking encompasses automated, electronic delivery of banking products and transactions over digital networks.',
      ),
      7 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'The formula for the Deposit Expansion Multiplier (m) based on the reserve requirement (r) is:',
        'option_a' => 'm = 1 - r',
        'option_b' => 'm = 1 / r)',
        'option_c' => 'm = r × 100',
        'option_d' => 'm = r / Deposits)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The theoretical simple money multiplier is calculated as the reciprocal of the required reserve ratio (m = 1 / r).',
      ),
      8 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'If the central bank sets the reserve requirement at 10% (0.10), an initial deposit of 1,000 Birr can potentially create total bank deposits of:',
        'option_a' => '1,000 Birr',
        'option_b' => '5,000 Birr',
        'option_c' => '10,000 Birr',
        'option_d' => '100,000 Birr',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => '',
      ),
      9 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'Which policy tool can a central bank use to REDUCE money supply and curb high inflation?',
        'option_a' => 'Lowering the reserve requirement ratio',
        'option_b' => 'Purchasing government bonds in the open market',
        'option_c' => 'Increasing the discount/policy interest rate',
        'option_d' => 'Printing unlimited physical currency notes',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Raising the central bank discount rate makes commercial bank borrowing expensive, reducing loan expansion and contracting money supply to tame inflation.',
      ),
      10 => 
      array (
        'chapter' => 5,
        'q_num' => 11,
        'question' => 'Which of the following is considered an asset for a commercial bank?',
        'option_a' => 'Savings deposits made by customers',
        'option_b' => 'Loans extended to individuals and businesses',
        'option_c' => 'Demand deposits held for clients',
        'option_d' => 'Shareholder capital equity balances',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Loans issued generate interest income for the bank and are financial assets. Customer deposits are liabilities because the bank owes those funds back to depositors.',
      ),
      11 => 
      array (
        'chapter' => 5,
        'q_num' => 12,
        'question' => 'Microfinance institutions (MFIs) primarily target:',
        'option_a' => 'Large multinational corporations requiring investment banking',
        'option_b' => 'Low-income households, small businesses, and rural entrepreneurs lacking access to traditional banking',
        'option_c' => 'Central bank foreign exchange management projects',
        'option_d' => 'High-net-worth real estate development firms',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Microfinance institutions provide micro-loans, savings, and financial services to low-income populations excluded from formal commercial banking systems.',
      ),
      12 => 
      array (
        'chapter' => 5,
        'q_num' => 13,
        'question' => 'Inflation is defined as:',
        'option_a' => 'A one-time increase in the price of a single commodity',
        'option_b' => 'A sustained increase in the general price level of goods and services over time',
        'option_c' => 'A continuous rise in the value/purchasing power of domestic currency',
        'option_d' => 'A reduction in manufacturing production costs',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Inflation refers to a persistent, generalized upward movement in the overall price level of goods and services across the economy over time.',
      ),
      13 => 
      array (
        'chapter' => 5,
        'q_num' => 14,
        'question' => 'Which form of money includes demand deposits, debit cards, and checkable account balances?',
        'option_a' => 'Commodity money',
        'option_b' => 'Bank/Credit money',
        'option_c' => 'Foreign reserve bullion',
        'option_d' => 'Fixed asset money',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Checkable demand deposits and digital debit funds created inside commercial banking systems are referred to as bank money or credit money.',
      ),
      14 => 
      array (
        'chapter' => 5,
        'q_num' => 15,
        'question' => 'Financial intermediaries are institutions that:',
        'option_a' => 'Collect taxes on behalf of state revenues',
        'option_b' => 'Channel funds from net savers to net borrowers',
        'option_c' => 'Set price floors for commercial manufacturing goods',
        'option_d' => 'Manufacture currency paper notes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Financial intermediaries (like commercial banks) bridge the gap between surplus economic units (savers) and deficit economic units (borrowers/investors).',
      ),
    ),
  ),
  6 => 
  array (
    'chapter_title' => 'Economic Growth',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 6,
        'q_num' => 1,
        'question' => 'Economic growth is best defined as:',
        'option_a' => 'An increase in a country’s population size',
        'option_b' => 'A sustained increase in a nation\'s real output of goods and services (Real GDP) over time',
        'option_c' => 'An increase in the nominal price of consumer goods',
        'option_d' => 'A decline in foreign trade transactions',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Economic growth measures the expansion of an economy\'s productive potential, quantified as a sustained increase in real Gross Domestic Product (Real GDP).',
      ),
      1 => 
      array (
        'chapter' => 6,
        'q_num' => 2,
        'question' => 'Gross Domestic Product (GDP) measures:',
        'option_a' => 'The total wealth owned by all citizens living abroad',
        'option_b' => 'The total market value of all final goods and services produced within a country\'s borders in a given year',
        'option_c' => 'Total government expenditure on social security services',
        'option_d' => 'The volume of physical raw materials harvested annually',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'GDP reflects the total monetary value of final goods and services produced domestically within a specified timeframe.',
      ),
      2 => 
      array (
        'chapter' => 6,
        'q_num' => 3,
        'question' => 'Why is REAL GDP preferred over NOMINAL GDP when measuring actual economic growth?',
        'option_a' => 'Real GDP excludes foreign trade statistics',
        'option_b' => 'Real GDP adjusts for price changes (inflation) using constant base-year prices',
        'option_c' => 'Real GDP includes informal economy transactions',
        'option_d' => 'Real GDP is simpler to compute',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Nominal GDP can increase simply due to higher prices (inflation). Real GDP strips out price fluctuations by using constant base-year prices to measure genuine output changes.',
      ),
      3 => 
      array (
        'chapter' => 6,
        'q_num' => 4,
        'question' => 'Real GDP Per Capita is calculated as:',
        'option_a' => 'Real GDP × Population',
        'option_b' => 'Real GDP / Total Population',
        'option_c' => 'Nominal GDP / Inflation Rate',
        'option_d' => 'Total Exports / Total Population',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Real GDP per capita measures average output per person and is computed by dividing Real GDP by total population.',
      ),
      4 => 
      array (
        'chapter' => 6,
        'q_num' => 5,
        'question' => 'Which of the following is a primary source of long-term economic growth?',
        'option_a' => 'Accumulation of physical capital (machinery, infrastructure)',
        'option_b' => 'Human capital development (education, training, health)',
        'option_c' => 'Technological advancement and innovation',
        'option_d' => 'All of the above',
        'raw_answer' => 'D',
        'correct_answer' => 'option_d',
        'explanation' => 'Economic growth is driven by increases in physical capital, upgrades in human capital, technological improvements, and efficient resource allocation.',
      ),
      5 => 
      array (
        'chapter' => 6,
        'q_num' => 6,
        'question' => 'The Production Possibilities Curve (PPC) shifts OUTWARD when:',
        'option_a' => 'Unemployment rate increases',
        'option_b' => 'An economy experiences technological progress or an increase in productive resources',
        'option_c' => 'Government raises business consumption taxes',
        'option_d' => 'Demand for imported consumer products drops',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'An outward shift of the PPC illustrates economic growth—an expansion in the total potential output capacity due to more resources or improved technology.',
      ),
      6 => 
      array (
        'chapter' => 6,
        'q_num' => 7,
        'question' => 'Human capital refers to:',
        'option_a' => 'The total quantity of physical industrial machinery',
        'option_b' => 'The accumulated knowledge, skills, health, and training embodied in workers',
        'option_c' => 'The number of labor hours spent on manual work',
        'option_d' => 'Total monetary savings held by workers in commercial banks',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Human capital includes economic value derived from a workforce\'s education, skills, technical expertise, and health status.',
      ),
      7 => 
      array (
        'chapter' => 6,
        'q_num' => 8,
        'question' => 'Which factor acts as a major institutional barrier/obstacle to economic growth in developing nations?',
        'option_a' => 'Adequate infrastructure development',
        'option_b' => 'Political instability, weak legal systems, and high corruption',
        'option_c' => 'High investment in research and development',
        'option_d' => 'Well-developed financial markets',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Weak institutions, corruption, insecurity, and political instability discourage domestic and foreign investment, harming long-run growth.',
      ),
      8 => 
      array (
        'chapter' => 6,
        'q_num' => 9,
        'question' => 'Technological progress leads to economic growth because it:',
        'option_a' => 'Increases input cost requirements',
        'option_b' => 'Allows firms to produce greater output using the same or fewer resources',
        'option_c' => 'Forces low-tech workers into mandatory retirement',
        'option_d' => 'Decreases total labor productivity',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Technological advancements improve production efficiency, allowing higher output levels to be generated from given inputs.',
      ),
      9 => 
      array (
        'chapter' => 6,
        'q_num' => 10,
        'question' => 'If a country\'s Real GDP grows at 5% in a year while its population grows at 2%, Real GDP per capita grows at approximately:',
        'option_a' => '7%',
        'option_b' => '3%',
        'option_c' => '10%',
        'option_d' => '2.5%',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => '',
      ),
      10 => 
      array (
        'chapter' => 6,
        'q_num' => 11,
        'question' => 'Economic growth differs from "Economic Development" in that economic development also includes:',
        'option_a' => 'Structural, institutional, and qualitative improvements in overall living standards, health, and literacy',
        'option_b' => 'Simple increases in nominal aggregate income',
        'option_c' => 'Increases in population size',
        'option_d' => 'Currency devaluation measures',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Growth is a quantitative increase in output (Real GDP), whereas economic development is a broader concept encompassing qualitative improvements in quality of life, education, health, equity, and institutional frameworks.',
      ),
      11 => 
      array (
        'chapter' => 6,
        'q_num' => 12,
        'question' => 'A sustained period of declining Real GDP lasting for at least two consecutive quarters is known as a:',
        'option_a' => 'Boom',
        'option_b' => 'Recession',
        'option_c' => 'Expansion',
        'option_d' => 'Peak',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A technical recession is defined as two consecutive quarters (6 months) of negative Real GDP growth.',
      ),
      12 => 
      array (
        'chapter' => 6,
        'q_num' => 13,
        'question' => 'High population growth rates can hinder growth in per-capita income if:',
        'option_a' => 'Population growth outpaces total real output growth',
        'option_b' => 'Human capital quality improves rapidly',
        'option_c' => 'Capital investment grows faster than population',
        'option_d' => 'Technological adoption accelerates',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'If population grows faster than overall economic output (GDP), output per person (Real GDP per capita) declines over time.',
      ),
      13 => 
      array (
        'chapter' => 6,
        'q_num' => 14,
        'question' => 'Which economic sector is typically dominant in the initial stages of growth in low-income developing nations?',
        'option_a' => 'High-tech service sector',
        'option_b' => 'Agriculture and primary resource sector',
        'option_c' => 'Advanced heavy manufacturing sector',
        'option_d' => 'Financial technology software sector',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In early stages of economic development, primary production (agriculture and resource extraction) employs the majority of the labor force.',
      ),
      14 => 
      array (
        'chapter' => 6,
        'q_num' => 15,
        'question' => 'Savings play a crucial role in economic growth because savings:',
        'option_a' => 'Reduce funds available in financial markets',
        'option_b' => 'Provide the capital pool necessary to finance business investments in capital goods',
        'option_c' => 'Directly increase government tax revenue collections',
        'option_d' => 'Decrease total bank deposits',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Domestic savings are funneled through financial institutions into productive physical capital investment, driving economic growth. [Grade 10 Economics Unit 4 Part 1](https://www.youtube.com/watch?v=5YMmzruJHrM) This video provides a helpful overview of Market Structures (Unit 4) from the Grade 10 Economics curriculum to reinforce the concepts covered in these questions. Here are 15 multiple-choice questions for Chapters 7 and 8 of the Grade 10 Economics curriculum, complete with correct answers and detailed explanations.',
      ),
    ),
  ),
  7 => 
  array (
    'chapter_title' => 'The Ethiopian Economy',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 7,
        'q_num' => 1,
        'question' => 'Which sector historically dominates the Ethiopian economy in terms of employment generation?',
        'option_a' => 'Industrial sector',
        'option_b' => 'Service sector',
        'option_c' => 'Agricultural sector',
        'option_d' => 'Mining sector',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Agriculture is the backbone of the Ethiopian economy, historically employing the vast majority of the rural labor force and serving as the primary source of livelihood.',
      ),
      1 => 
      array (
        'chapter' => 7,
        'q_num' => 2,
        'question' => 'Crop production, livestock rearing, forestry, and fishing in Ethiopia are primary components of the:',
        'option_a' => 'Industrial sector',
        'option_b' => 'Agricultural sector',
        'option_c' => 'Service sector',
        'option_d' => 'Financial sector',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The agricultural sector is broadly classified into sub-sectors including crop production, livestock farming, forestry, hunting, and fishing.',
      ),
      2 => 
      array (
        'chapter' => 7,
        'q_num' => 3,
        'question' => 'Which commodity traditionally accounts for the largest share of Ethiopia\'s total merchandise export earnings?',
        'option_a' => 'Leather and leather products',
        'option_b' => 'Coffee',
        'option_c' => 'Cut flowers',
        'option_d' => 'Oilseeds',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Coffee is Ethiopia\'s major agricultural export item, generating a significant portion of the country\'s foreign exchange revenues.',
      ),
      3 => 
      array (
        'chapter' => 7,
        'q_num' => 4,
        'question' => 'A major structural feature of smallholder farming in Ethiopia is its heavy reliance on:',
        'option_a' => 'Large-scale mechanized irrigation systems',
        'option_b' => 'Rain-fed agriculture',
        'option_c' => 'Advanced synthetic hydroponics',
        'option_d' => 'Automated robotic harvesting',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Smallholder agriculture in Ethiopia is predominantly rain-fed, making crop yields highly vulnerable to seasonal weather variability and droughts.',
      ),
      4 => 
      array (
        'chapter' => 7,
        'q_num' => 5,
        'question' => 'Land degradation, soil erosion, and climate-induced droughts in Ethiopia represent major constraints on:',
        'option_a' => 'Industrial manufacturing',
        'option_b' => 'Agricultural productivity',
        'option_c' => 'Telecommunication services',
        'option_d' => 'E-banking operations',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Environmental issues such as deforestation, land degradation, and erratic rainfall directly degrade topsoil quality and reduce agricultural output.',
      ),
      5 => 
      array (
        'chapter' => 7,
        'q_num' => 6,
        'question' => 'The industrial sector in Ethiopia includes which of the following primary sub-sectors?',
        'option_a' => 'Manufacturing, construction, mining, and electricity/water supply',
        'option_b' => 'Crop production, forestry, and animal husbandry',
        'option_c' => 'Wholesale trade, retail trade, and transport',
        'option_d' => 'Banking, insurance, and real estate',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The secondary (industrial) sector consists of manufacturing, construction, mining, quarrying, electricity, gas, and water supply.',
      ),
      6 => 
      array (
        'chapter' => 7,
        'q_num' => 7,
        'question' => 'The sub-sector that has recently driven significant economic growth within the Ethiopian industrial sector is:',
        'option_a' => 'Handicrafts',
        'option_b' => 'Construction',
        'option_c' => 'Traditional mining',
        'option_d' => 'Heavy machinery production',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Large-scale public and private investments in infrastructure, roads, housing, and industrial parks have made construction a primary growth engine.',
      ),
      7 => 
      array (
        'chapter' => 7,
        'q_num' => 8,
        'question' => 'Light manufacturing in Ethiopia is heavily concentrated in industries processing local raw materials, such as:',
        'option_a' => 'Microprocessors and semiconductors',
        'option_b' => 'Textiles, apparel, leather products, and food processing',
        'option_c' => 'Automobiles and aircraft assembly',
        'option_d' => 'Heavy chemicals and plastics',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Agro-processing industries—such as food, beverages, textiles, and leather goods—leverage domestic agricultural inputs to support light manufacturing.',
      ),
      8 => 
      array (
        'chapter' => 7,
        'q_num' => 9,
        'question' => 'Which sector currently contributes the largest single share to Ethiopia\'s Gross Domestic Product (GDP)?',
        'option_a' => 'Industrial sector',
        'option_b' => 'Agricultural sector',
        'option_c' => 'Service sector',
        'option_d' => 'Mining sector',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'In recent years, structural economic shifts have expanded the service sector (including trade, transport, telecommunications, and finance) to comprise the largest percentage share of national GDP.',
      ),
      9 => 
      array (
        'chapter' => 7,
        'q_num' => 10,
        'question' => 'Service sub-sectors in Ethiopia include:',
        'option_a' => 'Transport, communications, trade, hotel/catering, and financial services',
        'option_b' => 'Crop farming and livestock breeding',
        'option_c' => 'Manufacturing and mining',
        'option_d' => 'Road construction and quarrying',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The service sector encompasses tertiary activities including transportation, warehousing, communications, retail trade, hospitality, and financial intermediation.',
      ),
      10 => 
      array (
        'chapter' => 7,
        'q_num' => 11,
        'question' => '"Structural transformation" in an economy refers to a long-term process where:',
        'option_a' => 'The share of agriculture in GDP and employment declines while industry and high-productivity services grow',
        'option_b' => 'An economy switches entirely to subsistence agricultural production',
        'option_c' => 'The government eliminates all domestic manufacturing enterprises',
        'option_d' => 'Service sector output drops to zero',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Structural transformation involves reallocating economic factors from low-productivity sectors (such as traditional subsistence agriculture) toward higher-productivity sectors (such as industry and modern services).',
      ),
      11 => 
      array (
        'chapter' => 7,
        'q_num' => 12,
        'question' => 'Industrial Parks developed across various regions of Ethiopia aim to promote:',
        'option_a' => 'Traditional rural subsistence farming',
        'option_b' => 'Export-oriented manufacturing, foreign direct investment (FDI), and job creation',
        'option_c' => 'Import expansion of finished consumer goods',
        'option_d' => 'Unregulated informal retail market trade',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Industrial parks serve as specialized manufacturing hubs offering streamlined infrastructure, tax incentives, and logistics to attract FDI and boost manufactured exports.',
      ),
      12 => 
      array (
        'chapter' => 7,
        'q_num' => 13,
        'question' => 'Which factor acts as a major bottleneck to industrial sector expansion in Ethiopia?',
        'option_a' => 'Foreign exchange shortages and infrastructure deficits',
        'option_b' => 'Overproduction of industrial spare parts',
        'option_c' => 'Excess abundance of skilled technical labor',
        'option_d' => 'Low domestic demand for agricultural products',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Key challenges restricting industrial growth include foreign exchange constraints for imported capital inputs, logistics bottlenecks, and reliable power access.',
      ),
      13 => 
      array (
        'chapter' => 7,
        'q_num' => 14,
        'question' => 'Modernizing the agricultural sector through rural credit, fertilizers, and improved seed access is intended to:',
        'option_a' => 'Lower crop yields per hectare',
        'option_b' => 'Boost agricultural productivity and foster linkages with domestic industries',
        'option_c' => 'Increase reliance on food import aid',
        'option_d' => 'Drive smallholder farmers out of production',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Providing modern inputs enhances agricultural yield per hectare, ensuring food security and supplying raw materials for agro-processing manufacturing.',
      ),
      14 => 
      array (
        'chapter' => 7,
        'q_num' => 15,
        'question' => 'The expansion of telecommunications and digital mobile payment ecosystems in Ethiopia primarily supports the growth of the:',
        'option_a' => 'Agricultural primary sector',
        'option_b' => 'Service and financial sectors',
        'option_c' => 'Heavy mining sub-sector',
        'option_d' => 'Forestry sector',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Digital connectivity and mobile financial services boost tertiary economic activities by enabling modern banking, e-commerce, logistics, and business transactions.',
      ),
    ),
  ),
  8 => 
  array (
    'chapter_title' => 'Business Startups and Innovation',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 8,
        'q_num' => 1,
        'question' => '"Absolute poverty" is defined as a situation where an individual or household:',
        'option_a' => 'Earns less income than the top 10% of the population',
        'option_b' => 'Lacks the minimum income required to meet basic human needs (food, clean water, shelter, clothing)',
        'option_c' => 'Cannot afford luxury consumer goods',
        'option_d' => 'Has a lower standard of living relative to neighbors',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Absolute poverty measures severe deprivation of basic human needs based on a fixed universal benchmark, regardless of surrounding social distribution.',
      ),
      1 => 
      array (
        'chapter' => 8,
        'q_num' => 2,
        'question' => '"Relative poverty" refers to a condition where an individual\'s standard of living:',
        'option_a' => 'Is below the international absolute poverty threshold of $2.15 per day',
        'option_b' => 'Is significantly lower than the average or median standard of living in their specific society',
        'option_c' => 'Fails to meet daily minimum physiological food energy requirements',
        'option_d' => 'Is measured strictly by physical health indicators',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Relative poverty evaluates income or consumption levels in relation to the overall living standard of the broa <truncated 6884 bytes> NOTE: The output was truncated because it was too long. Use a more targeted query or a smaller range to get the information you need.',
      ),
      2 => 
      array (
        'chapter' => 8,
        'q_num' => 3,
        'question' => 'The Gini Coefficient is a statistical metric used to measure:',
        'option_a' => 'National real GDP growth rate',
        'option_b' => 'Income or wealth inequality within a population',
        'option_c' => 'Annual consumer price inflation',
        'option_d' => 'Total labor force unemployment',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Gini coefficient measures income inequality, ranging from 0 (perfect equality) to 1 (maximum inequality).',
      ),
      3 => 
      array (
        'chapter' => 8,
        'q_num' => 4,
        'question' => 'A Gini coefficient value of 0 represents:',
        'option_a' => 'Perfect income equality',
        'option_b' => 'Absolute income inequality',
        'option_c' => 'High poverty concentration',
        'option_d' => 'Severe economic depression',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'A Gini coefficient of 0 indicates that every individual receives an identical share of total income (perfect equality).',
      ),
      4 => 
      array (
        'chapter' => 8,
        'q_num' => 5,
        'question' => 'The Lorenz Curve graphically illustrates:',
        'option_a' => 'The relationship between inflation and unemployment',
        'option_b' => 'The cumulative percentage of income earned against the cumulative percentage of population',
        'option_c' => 'Short-run cost minimization curves',
        'option_d' => 'Tax revenue changes at different tax rates',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The Lorenz curve plots cumulative population percentage against cumulative income received to display inequality visually.',
      ),
      5 => 
      array (
        'chapter' => 8,
        'q_num' => 6,
        'question' => 'The international absolute poverty line established by the World Bank is used to measure:',
        'option_a' => 'Relative wealth disparities in developed nations',
        'option_b' => 'The proportion of population lacking basic minimum food and non-food survival needs',
        'option_c' => 'Average corporate profit tax rates',
        'option_d' => 'National debt per capita',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The absolute poverty line specifies a fixed consumption threshold necessary to meet basic human survival requirements.',
      ),
      6 => 
      array (
        'chapter' => 8,
        'q_num' => 7,
        'question' => 'The Multidimensional Poverty Index (MPI) assesses poverty across which three core dimensions?',
        'option_a' => 'Health, Education, and Living Standards',
        'option_b' => 'Income, Taxes, and Exports',
        'option_c' => 'Savings, Investments, and Loans',
        'option_d' => 'Technology, Manufacturing, and Mining',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The MPI measures acute poverty by evaluating deprivations in health (nutrition, child mortality), education, and living conditions.',
      ),
      7 => 
      array (
        'chapter' => 8,
        'q_num' => 8,
        'question' => 'The Human Development Index (HDI) developed by the UNDP combines indicators for:',
        'option_a' => 'Military spending, external debt, and inflation',
        'option_b' => 'Life expectancy, expected/mean years of schooling, and GNI per capita',
        'option_c' => 'Commercial bank deposits, tax revenue, and stock indices',
        'option_d' => 'Industrial manufacturing growth rates',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'HDI synthesizes health (life expectancy), knowledge (education), and decent standard of living (GNI per capita).',
      ),
      8 => 
      array (
        'chapter' => 8,
        'q_num' => 9,
        'question' => 'A progressive income tax system reduces income inequality by:',
        'option_a' => 'Taxing low-income earners at a higher percentage rate',
        'option_b' => 'Applying higher tax percentage rates to higher income brackets',
        'option_c' => 'Charging a flat fixed tax amount to all citizens regardless of income',
        'option_d' => 'Exempting foreign corporations from all taxes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Progressive taxation levies proportionally higher tax rates on higher earners to redistribute income.',
      ),
      9 => 
      array (
        'chapter' => 8,
        'q_num' => 10,
        'question' => 'Ethiopia’s Productive Safety Net Program (PSNP) is designed primarily to:',
        'option_a' => 'Finance commercial airline expansion',
        'option_b' => 'Provide food/cash transfers to vulnerable rural households to prevent asset depletion and hunger',
        'option_c' => 'Subsidize luxury imported vehicles',
        'option_d' => 'Regulate central bank exchange rates',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'PSNP is a social protection initiative that supports chronically food-insecure households in rural Ethiopia.',
      ),
      10 => 
      array (
        'chapter' => 8,
        'q_num' => 11,
        'question' => 'The Headcount Ratio (P₀) measures:',
        'option_a' => 'The proportion of the total population living below the poverty line',
        'option_b' => 'The total income earned by top executives',
        'option_c' => 'The percentage of graduates entering university',
        'option_d' => 'The velocity of money circulation',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The Headcount Ratio calculates the percentage of individuals whose income or consumption falls below the poverty threshold.',
      ),
      11 => 
      array (
        'chapter' => 8,
        'q_num' => 12,
        'question' => 'The Poverty Gap Index (P₁) provides information about:',
        'option_a' => 'The exact distance/depth by which poor households fall below the poverty line',
        'option_b' => 'The total number of commercial bank branches',
        'option_c' => 'The percentage of urban high-income earners',
        'option_d' => 'The rate of foreign currency devaluation',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The Poverty Gap Index captures the depth of poverty by calculating the average shortfall of the poor from the poverty line.',
      ),
      12 => 
      array (
        'chapter' => 8,
        'q_num' => 13,
        'question' => 'Which factor is a major structural cause of persistent income inequality in developing nations?',
        'option_a' => 'Equal access to credit for all rural farmers',
        'option_b' => 'Unequal distribution of land, capital, and access to quality education',
        'option_c' => 'Universal health insurance coverage',
        'option_d' => 'Balanced regional infrastructure investments',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Unequal ownership of physical assets and unequal access to education and financial credit perpetuate economic disparities.',
      ),
      13 => 
      array (
        'chapter' => 8,
        'q_num' => 14,
        'question' => 'Social and economic mobility refers to the:',
        'option_a' => 'Movement of workers between physical office buildings',
        'option_b' => 'Ability of individuals or households to move up socio-economic income brackets over time',
        'option_c' => 'Export of raw agricultural commodities',
        'option_d' => 'Decline in local transport services',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Economic mobility measures how effectively individuals can improve their financial status relative to their parents or earlier life.',
      ),
      14 => 
      array (
        'chapter' => 8,
        'q_num' => 15,
        'question' => 'Inclusive economic growth is defined as growth that:',
        'option_a' => 'Benefits only capital owners and urban elites',
        'option_b' => 'Generates widespread opportunities and distributes economic gains equitably across all population segments',
        'option_c' => 'Focuses exclusively on military expansion',
        'option_d' => 'Reduces total national employment',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Inclusive growth ensures that all socio-economic groups contribute to and benefit from economic expansion, reducing poverty.',
      ),
    ),
  ),
);
    }
}

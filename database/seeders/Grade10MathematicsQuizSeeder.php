<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade10MathematicsQuizSeeder extends Seeder
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

            $subject = Subject::where('grade_id', $grade->id)->where('title', 'Mathematics')->first();
            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Mathematics',
                    'description' => 'Grade 10 Mathematics Curriculum',
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
                        'description' => "Assessment quiz for Grade 10 Mathematics Chapter {$chapterNumber}.",
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
    'chapter_title' => 'Relations and Functions',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 1,
        'q_num' => 1,
        'question' => 'Which of the following describes an arithmetic pattern?',
        'option_a' => '2, 4, 8, 16, 32',
        'option_b' => '65, 60, 55, 50, 45',
        'option_c' => '3, 9, 27, 81',
        'option_d' => '100, 50, 25, 12.5',
        'raw_answer' => '**b) $65, 60, 55, 50, 45$**',
        'correct_answer' => 'option_b',
        'explanation' => 'An arithmetic pattern is based on adding or subtracting a constant difference between consecutive terms. In sequence **b**, each term decreases by 5 (65 - 5 = 60, 60 - 5 = 55, etc.). Options a, c, and d represent geometric patterns where terms are multiplied or divided by a constant ratio.',
      ),
      1 => 
      array (
        'chapter' => 1,
        'q_num' => 2,
        'question' => 'In the Cartesian coordinate system, which quadrant contains points where the x-coordinate (abscissa) is negative and the y-coordinate (ordinate) is positive?',
        'option_a' => 'Quadrant I',
        'option_b' => 'Quadrant II',
        'option_c' => 'Quadrant III',
        'option_d' => 'Quadrant IV',
        'raw_answer' => '**b) Quadrant II**',
        'correct_answer' => 'option_b',
        'explanation' => 'Quadrant I has positive x and positive y (+, +). Quadrant II has negative x and positive y (-, +). Quadrant III has negative x and negative y (-, -). Quadrant IV has positive x and negative y (+, -).',
      ),
      2 => 
      array (
        'chapter' => 1,
        'q_num' => 3,
        'question' => 'What is the domain of the relation R = \\{(-2, 1), (-1, 0), (0, 0), (4, 2), (3, 5)\\?',
        'option_a' => '\\{1, 0, 2, 5\\',
        'option_b' => '\\{-2, -1, 0, 3, 4\\',
        'option_c' => '\\{-2, -1, 0, 1, 2, 3, 4, 5\\',
        'option_d' => '\\{-2, 1, 4, 5\\',
        'raw_answer' => '**b) $\\{-2, -1, 0, 3, 4\\}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'The domain of a relation is the set of all first components (x-coordinates) of the ordered pairs. Taking the first element of each pair gives \\{-2, -1, 0, 4, 3\\.',
      ),
      3 => 
      array (
        'chapter' => 1,
        'q_num' => 4,
        'question' => 'Which of the following set of ordered pairs represents a **function**?',
        'option_a' => '\\{(2, 3), (2, 5), (3, 6)\\',
        'option_b' => '\\{(4, 3), (2, 2), (-3, 4), (-3, -3)\\',
        'option_c' => '\\{(1, 2), (2, 3), (3, 4), (4, 5)\\',
        'option_d' => '\\{(1, -1), (1, -6), (4, 2)\\',
        'raw_answer' => '**c) $\\{(1, 2), (2, 3), (3, 4), (4, 5)\\}$**',
        'correct_answer' => 'option_c',
        'explanation' => 'A function is a relation in which no two distinct ordered pairs have the same first element (domain value). In option **c**, every first coordinate (1, 2, 3, 4) is unique. In options a, b, and d, inputs like 2, -3, or 1 map to multiple different outputs.',
      ),
      4 => 
      array (
        'chapter' => 1,
        'q_num' => 5,
        'question' => 'What is the domain of the function f(x) = sqrt(x - 2)?',
        'option_a' => '{x in R | x > 2',
        'option_b' => '{x in R | x >= 2',
        'option_c' => '{x in R | x <= 2',
        'option_d' => '{x in R | x != 2',
        'raw_answer' => '**b) $\\{x \\in \\mathbb{R} \\mid x \\ge 2\\}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'For a real-valued square root function to be defined, the expression inside the radical must be non-negative (x - 2 >= 0). Solving this inequality gives x >= 2.',
      ),
      5 => 
      array (
        'chapter' => 1,
        'q_num' => 6,
        'question' => 'Given f(x) = 2x + 4 and g(x) = 3x - 1, what is (f + g)(2)?',
        'option_a' => '10',
        'option_b' => '11',
        'option_c' => '13',
        'option_d' => '15',
        'raw_answer' => '**c) $13$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The sum of functions is defined as (f + g)(x) = f(x) + g(x) = (2x + 4) + (3x - 1) = 5x + 3. Evaluating at x = 2 gives (f + g)(2) = 5(2) + 3 = 10 + 3 = 13.',
      ),
      6 => 
      array (
        'chapter' => 1,
        'q_num' => 7,
        'question' => 'Given f(x) = x^2 and g(x) = x - 3, what is the domain of the quotient function (f/g)(x)?',
        'option_a' => 'Real numbers (R)',
        'option_b' => 'Real numbers except 0',
        'option_c' => 'Real numbers except 3',
        'option_d' => '(0, infinity)',
        'raw_answer' => '**c) $\\mathbb{R} \\setminus \\{3\\}$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The quotient function is (f/g)(x) = f(x)/g(x) = x^2 / (x - 3). The denominator cannot be zero (g(x) != 0), so x - 3 != 0 \\implies x != 3. Hence, the domain is all real numbers except 3.',
      ),
      7 => 
      array (
        'chapter' => 1,
        'q_num' => 8,
        'question' => 'What mathematical test is used on a graph to determine whether it represents a function?',
        'option_a' => 'Horizontal Line Test',
        'option_b' => 'Vertical Line Test',
        'option_c' => 'Diagonal Line Test',
        'option_d' => 'Slope Test',
        'raw_answer' => '**b) Vertical Line Test**',
        'correct_answer' => 'option_b',
        'explanation' => 'The Vertical Line Test states that a relation is a function if and only if no vertical line intersects its graph in more than one point.',
      ),
      8 => 
      array (
        'chapter' => 1,
        'q_num' => 9,
        'question' => 'A function f: A -> B is called **one-to-one (injective)** if:',
        'option_a' => 'Every element in B has at least one pre-image in A.',
        'option_b' => 'Distinct elements in A have distinct images in B (f(x_1) = f(x_2) \\implies x_1 = x_2).',
        'option_c' => 'The domain equals the range.',
        'option_d' => 'It is represented by a quadratic curve.',
        'raw_answer' => '**b) Distinct elements in $A$ have distinct images in $B$ ($f(x_1) = f(x_2) \\implies x_1 = x_2$).**',
        'correct_answer' => 'option_b',
        'explanation' => 'By definition, a function is one-to-one (injective) if each element in the domain maps to a unique, distinct image in the co-domain. Option **a** describes an onto (surjective) function.',
      ),
      9 => 
      array (
        'chapter' => 1,
        'q_num' => 10,
        'question' => 'Which of the following functions is a **decreasing** linear function?',
        'option_a' => 'f(x) = 3x - 1',
        'option_b' => 'f(x) = 2x + 1',
        'option_c' => 'f(x) = -2x + 6',
        'option_d' => 'f(x) = 2',
        'raw_answer' => '**c) $f(x) = -2x + 6$**',
        'correct_answer' => 'option_c',
        'explanation' => 'A linear function f(x) = ax + b is decreasing if its slope (the coefficient a) is negative (a < 0). For f(x) = -2x + 6, a = -2, which is less than 0.',
      ),
      10 => 
      array (
        'chapter' => 1,
        'q_num' => 11,
        'question' => 'What are the x-intercept and y-intercept of the linear function f(x) = 3x - 6?',
        'option_a' => 'x-intercept (2, 0), y-intercept (0, -6)',
        'option_b' => 'x-intercept (-2, 0), y-intercept (0, 6)',
        'option_c' => 'x-intercept (0, 2), y-intercept (-6, 0)',
        'option_d' => 'x-intercept (3, 0), y-intercept (0, -6)',
        'raw_answer' => '**a) $x$-intercept $(2, 0)$, $y$-intercept $(0, -6)$**',
        'correct_answer' => 'option_a',
        'explanation' => 'To find the x-intercept, set f(x) = 0 \\implies 3x - 6 = 0 \\implies x = 2, giving (2, 0). To find the y-intercept, set x = 0 \\implies f(0) = 3(0) - 6 = -6, giving (0, -6).',
      ),
      11 => 
      array (
        'chapter' => 1,
        'q_num' => 12,
        'question' => 'What is the vertex of the parabola given by f(x) = (x - 3)^2 + 2?',
        'option_a' => '(-3, 2)',
        'option_b' => '(3, -2)',
        'option_c' => '(3, 2)',
        'option_d' => '(-3, -2)',
        'raw_answer' => '**c) $(3, 2)$**',
        'correct_answer' => 'option_c',
        'explanation' => 'For a quadratic function in vertex form f(x) = a(x - k)^2 + c, the vertex coordinates are (k, c). Here, k = 3 and c = 2, so the vertex is (3, 2).',
      ),
      12 => 
      array (
        'chapter' => 1,
        'q_num' => 13,
        'question' => 'What is the maximum value of the quadratic function f(x) = -(x - 3)^2 + 1?',
        'option_a' => '3',
        'option_b' => '1',
        'option_c' => '-3',
        'option_d' => '-1',
        'raw_answer' => '**b) $1$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Since the leading coefficient is negative (a = -1), the parabola opens downward and has a maximum value at its vertex. For f(x) = -(x - 3)^2 + 1, the vertex is (3, 1), making 1 the maximum y-value.',
      ),
      13 => 
      array (
        'chapter' => 1,
        'q_num' => 14,
        'question' => 'How many real solutions exist for a quadratic equation ax^2 + bx + c = 0 if its graph does not intersect the x-axis?',
        'option_a' => 'Two real solutions',
        'option_b' => 'One real solution',
        'option_c' => 'No real solution',
        'option_d' => 'Infinitely many real solutions',
        'raw_answer' => '**c) No real solution**',
        'correct_answer' => 'option_c',
        'explanation' => 'Graphically, the x-intercepts of a function represent the real roots of the equation f(x) = 0. If the graph does not cross or touch the x-axis, there are no real solutions.',
      ),
      14 => 
      array (
        'chapter' => 1,
        'q_num' => 15,
        'question' => 'What is the solution set in interval notation for the quadratic inequality x^2 - 6x + 8 < 0?',
        'option_a' => '(-infinity, 2) U (4, infinity)',
        'option_b' => '[2, 4]',
        'option_c' => '(2, 4)',
        'option_d' => '(-infinity, 8)',
        'raw_answer' => '**c) $(2, 4)$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The roots of x^2 - 6x + 8 = 0 are x = 2 and x = 4. Since a = 1 > 0, the parabola opens upward. The region where f(x) < 0 (below the x-axis) lies strictly between the two roots, yielding the open interval (2, 4). Here are multiple-choice questions for **Chapter 4** and **Chapter 5** of the Grade 10 Mathematics curriculum, complete with answers and detailed explanations.',
      ),
    ),
  ),
  2 => 
  array (
    'chapter_title' => 'Polynomial Functions',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 2,
        'q_num' => 1,
        'question' => 'Which of the following expressions is a Polynomial Function?',
        'option_a' => 'f(x) = 3x^3 - 2x^2 + 5x - 7',
        'option_b' => 'f(x) = 2/x + 5',
        'option_c' => 'f(x) = sqrt(x) + 3',
        'option_d' => 'f(x) = 2^x',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'A polynomial function has non-negative integer exponents. Options b, c, and d involve negative exponents (1/x), fractional exponents (sqrt(x)), or variable exponents (2^x).',
      ),
      1 => 
      array (
        'chapter' => 2,
        'q_num' => 2,
        'question' => 'What is the degree of the polynomial function f(x) = 4x^3 - 2x^5 + 7x - 1?',
        'option_a' => '3',
        'option_b' => '5',
        'option_c' => '4',
        'option_d' => '1',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The degree of a polynomial is the highest power of x with a non-zero coefficient. Here, the highest exponent is 5 (from term -2x^5).',
      ),
      2 => 
      array (
        'chapter' => 2,
        'q_num' => 3,
        'question' => 'What is the leading coefficient of f(x) = -3x^4 + 5x^2 - 9?',
        'option_a' => '5',
        'option_b' => '-9',
        'option_c' => '-3',
        'option_d' => '4',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'The leading coefficient is the coefficient of the term with the highest degree. The term with highest power is -3x^4, so the leading coefficient is -3.',
      ),
      3 => 
      array (
        'chapter' => 2,
        'q_num' => 4,
        'question' => 'What is the value of f(2) for the polynomial f(x) = 2x^3 - 3x^2 + x - 5?',
        'option_a' => '1',
        'option_b' => '3',
        'option_c' => '-1',
        'option_d' => '5',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Substitute x = 2: f(2) = 2(2^3) - 3(2^2) + 2 - 5 = 2(8) - 3(4) + 2 - 5 = 16 - 12 + 2 - 5 = 1.',
      ),
      4 => 
      array (
        'chapter' => 2,
        'q_num' => 5,
        'question' => 'According to the Remainder Theorem, when a polynomial f(x) is divided by (x - c), the remainder is equal to:',
        'option_a' => 'f(0)',
        'option_b' => 'f(c)',
        'option_c' => 'f(-c)',
        'option_d' => 'c',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The Remainder Theorem states that if a polynomial f(x) is divided by (x - c), the remainder R is equal to f(c).',
      ),
      5 => 
      array (
        'chapter' => 2,
        'q_num' => 6,
        'question' => 'What is the remainder when f(x) = x^3 - 2x^2 + 4x - 8 is divided by (x - 2)?',
        'option_a' => '0',
        'option_b' => '4',
        'option_c' => '8',
        'option_d' => '-4',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'By the Remainder Theorem, R = f(2) = 2^3 - 2(2^2) + 4(2) - 8 = 8 - 8 + 8 - 8 = 0.',
      ),
      6 => 
      array (
        'chapter' => 2,
        'q_num' => 7,
        'question' => 'According to the Factor Theorem, (x - c) is a factor of f(x) if and only if:',
        'option_a' => 'f(c) = 1',
        'option_b' => 'f(c) = 0',
        'option_c' => 'f(0) = c',
        'option_d' => 'f(c) > 0',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The Factor Theorem states that (x - c) is a factor of a polynomial f(x) if and only if f(c) = 0 (the remainder is zero).',
      ),
      7 => 
      array (
        'chapter' => 2,
        'q_num' => 8,
        'question' => 'Which value is a root (zero) of the polynomial f(x) = x^3 - 6x^2 + 11x - 6?',
        'option_a' => 'x = 1',
        'option_b' => 'x = 0',
        'option_c' => 'x = -1',
        'option_d' => 'x = 4',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Evaluate f(1) = 1^3 - 6(1^2) + 11(1) - 6 = 1 - 6 + 11 - 6 = 0. Since f(1) = 0, x = 1 is a zero.',
      ),
      8 => 
      array (
        'chapter' => 2,
        'q_num' => 9,
        'question' => 'The Rational Root Theorem states that any rational zero p/q of a polynomial with integer coefficients must satisfy:',
        'option_a' => 'p is a factor of the constant term a_0 and q is a factor of the leading coefficient a_n',
        'option_b' => 'p is a factor of leading coefficient and q is factor of constant term',
        'option_c' => 'p + q = 0',
        'option_d' => 'p * q = 1',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'For rational root p/q in lowest terms, numerator p divides the constant term a_0 and denominator q divides the leading coefficient a_n.',
      ),
      9 => 
      array (
        'chapter' => 2,
        'q_num' => 10,
        'question' => 'What is the end behavior of the polynomial function f(x) = -2x^4 + 3x^2?',
        'option_a' => 'Rises to the left and falls to the right',
        'option_b' => 'Falls to the left and rises to the right',
        'option_c' => 'Falls to both the left and the right (f(x) -> -infinity as x -> +/- infinity)',
        'option_d' => 'Rises to both the left and the right',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'For an even-degree polynomial (n = 4) with a negative leading coefficient (a = -2), the graph opens downward on both ends.',
      ),
      10 => 
      array (
        'chapter' => 2,
        'q_num' => 11,
        'question' => 'What is the end behavior of an odd-degree polynomial f(x) = x^3 - 4x with a positive leading coefficient?',
        'option_a' => 'Falls to the left and rises to the right (as x -> -infinity, f(x) -> -infinity; as x -> +infinity, f(x) -> +infinity)',
        'option_b' => 'Rises to left and falls to right',
        'option_c' => 'Falls to both ends',
        'option_d' => 'Rises to both ends',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Odd-degree polynomials with positive leading coefficients fall on the left and rise on the right.',
      ),
      11 => 
      array (
        'chapter' => 2,
        'q_num' => 12,
        'question' => 'What is the maximum number of turning points that the graph of a polynomial function of degree n can have?',
        'option_a' => 'n',
        'option_b' => 'n - 1',
        'option_c' => 'n + 1',
        'option_d' => '2n',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'A polynomial of degree n can have at most n - 1 local turning points (peaks and troughs).',
      ),
      12 => 
      array (
        'chapter' => 2,
        'q_num' => 13,
        'question' => 'If a root x = 3 of a polynomial function has an even multiplicity of 2, how does the graph behave at (3, 0)?',
        'option_a' => 'Crosses the x-axis',
        'option_b' => 'Touches the x-axis and turns around without crossing',
        'option_c' => 'Forms a vertical asymptote',
        'option_d' => 'Disappears',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'At roots with even multiplicity (like 2, 4), the graph touches the x-axis and reverses direction (tangent to x-axis).',
      ),
      13 => 
      array (
        'chapter' => 2,
        'q_num' => 14,
        'question' => 'The Intermediate Value Theorem guarantees that if f(a) < 0 and f(b) > 0 for a continuous polynomial function f, then:',
        'option_a' => 'There is at least one real zero c between a and b where f(c) = 0',
        'option_b' => 'f(x) has a maximum at x = a',
        'option_c' => 'f(x) is constant',
        'option_d' => 'f(a + b) = 0',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'If a continuous function changes signs between x = a and x = b, it must cross the x-axis at least once in the interval (a, b).',
      ),
      14 => 
      array (
        'chapter' => 2,
        'q_num' => 15,
        'question' => 'What is the complete factorization of the difference of cubes x^3 - 8?',
        'option_a' => '(x - 2)(x^2 + 2x + 4)',
        'option_b' => '(x - 2)(x^2 - 2x + 4)',
        'option_c' => '(x + 2)(x^2 - 2x + 4)',
        'option_d' => '(x - 2)^3',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'The difference of cubes formula is a^3 - b^3 = (a - b)(a^2 + ab + b^2). For x^3 - 2^3: (x - 2)(x^2 + 2x + 4).',
      ),
    ),
  ),
  3 => 
  array (
    'chapter_title' => 'Exponential and Logarithmic Functions',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 3,
        'q_num' => 1,
        'question' => 'An Exponential Function is defined in the form f(x) = a^x where the base a must satisfy:',
        'option_a' => 'a > 0 and a != 1',
        'option_b' => 'a < 0',
        'option_c' => 'a = 1',
        'option_d' => 'a = 0',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'By definition, an exponential function has a positive constant base unequal to 1 (a > 0 and a != 1).',
      ),
      1 => 
      array (
        'chapter' => 3,
        'q_num' => 2,
        'question' => 'What is the simplified value of 2^(-3)?',
        'option_a' => '-6',
        'option_b' => '-8',
        'option_c' => '1/8',
        'option_d' => '1/6',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'By negative exponent rule, a^(-n) = 1 / (a^n). Thus, 2^(-3) = 1 / (2^3) = 1/8.',
      ),
      2 => 
      array (
        'chapter' => 3,
        'q_num' => 3,
        'question' => 'Which exponent law correctly simplifies (a^m) * (a^n)?',
        'option_a' => 'a^(m * n)',
        'option_b' => 'a^(m + n)',
        'option_c' => 'a^(m - n)',
        'option_d' => 'a^(m / n)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'When multiplying terms with the same base, add their exponents: (a^m) * (a^n) = a^(m + n).',
      ),
      3 => 
      array (
        'chapter' => 3,
        'q_num' => 4,
        'question' => 'What is the solution to the exponential equation 3^(2x - 1) = 27?',
        'option_a' => 'x = 1',
        'option_b' => 'x = 2',
        'option_c' => 'x = 3',
        'option_d' => 'x = 4',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Rewrite 27 as 3^3: 3^(2x - 1) = 3^3 => 2x - 1 = 3 => 2x = 4 => x = 2.',
      ),
      4 => 
      array (
        'chapter' => 3,
        'q_num' => 5,
        'question' => 'What is the domain of the basic exponential function f(x) = 2^x?',
        'option_a' => 'Real numbers (R), i.e., (-infinity, +infinity)',
        'option_b' => '(0, +infinity)',
        'option_c' => '[0, +infinity)',
        'option_d' => 'Real numbers except 0',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Exponential functions accept any real number input x, so the domain is all real numbers (-infinity, +infinity).',
      ),
      5 => 
      array (
        'chapter' => 3,
        'q_num' => 6,
        'question' => 'What is the range of the basic exponential function f(x) = 2^x?',
        'option_a' => 'Real numbers (R)',
        'option_b' => '(0, +infinity)',
        'option_c' => '[0, +infinity)',
        'option_d' => '(-infinity, 0)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => '2^x is always strictly positive for all real x, so its range is strictly positive real numbers (0, +infinity).',
      ),
      6 => 
      array (
        'chapter' => 3,
        'q_num' => 7,
        'question' => 'The logarithmic statement y = log_a(x) is equivalent to which exponential statement?',
        'option_a' => 'x = a^y',
        'option_b' => 'y = a^x',
        'option_c' => 'a = x^y',
        'option_d' => 'x = y^a',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'By definition of logarithms, y = log_a(x) holds if and only if a^y = x (for a > 0, a != 1, x > 0).',
      ),
      7 => 
      array (
        'chapter' => 3,
        'q_num' => 8,
        'question' => 'What is the value of log_2(32)?',
        'option_a' => '3',
        'option_b' => '4',
        'option_c' => '5',
        'option_d' => '16',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'Since 2^5 = 32, by definition log_2(32) = 5.',
      ),
      8 => 
      array (
        'chapter' => 3,
        'q_num' => 9,
        'question' => 'What is the value of log_10(0.01)?',
        'option_a' => '2',
        'option_b' => '-2',
        'option_c' => '-1',
        'option_d' => '10',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Since 0.01 = 10^(-2), log_10(0.01) = -2.',
      ),
      9 => 
      array (
        'chapter' => 3,
        'q_num' => 10,
        'question' => 'Which logarithmic property correctly expands log_a(x * y)?',
        'option_a' => 'log_a(x) * log_a(y)',
        'option_b' => 'log_a(x) + log_a(y)',
        'option_c' => 'log_a(x) - log_a(y)',
        'option_d' => 'log_a(x / y)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The Product Rule of logarithms states log_a(x * y) = log_a(x) + log_a(y).',
      ),
      10 => 
      array (
        'chapter' => 3,
        'q_num' => 11,
        'question' => 'Which logarithmic property correctly expands log_a(x / y)?',
        'option_a' => 'log_a(x) - log_a(y)',
        'option_b' => 'log_a(x) / log_a(y)',
        'option_c' => 'log_a(x) + log_a(y)',
        'option_d' => 'log_a(x * y)',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'The Quotient Rule of logarithms states log_a(x / y) = log_a(x) - log_a(y).',
      ),
      11 => 
      array (
        'chapter' => 3,
        'q_num' => 12,
        'question' => 'Which property allows rewriting log_a(x^k)?',
        'option_a' => 'k + log_a(x)',
        'option_b' => 'k * log_a(x)',
        'option_c' => '(log_a(x))^k',
        'option_d' => 'log_a(k * x)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The Power Rule of logarithms states log_a(x^k) = k * log_a(x).',
      ),
      12 => 
      array (
        'chapter' => 3,
        'q_num' => 13,
        'question' => 'What is the domain of the logarithmic function f(x) = log_2(x - 3)?',
        'option_a' => 'x > 3, i.e., (3, +infinity)',
        'option_b' => 'x >= 3',
        'option_c' => 'Real numbers (R)',
        'option_d' => 'x != 3',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'The argument of a logarithm must be strictly positive: x - 3 > 0 => x > 3.',
      ),
      13 => 
      array (
        'chapter' => 3,
        'q_num' => 14,
        'question' => 'What is the vertical asymptote of the logarithmic graph f(x) = log_2(x)?',
        'option_a' => 'y = 0 (x-axis)',
        'option_b' => 'x = 0 (y-axis)',
        'option_c' => 'x = 1',
        'option_d' => 'y = 1',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'As x approaches 0 from the right, f(x) -> -infinity, making x = 0 (the y-axis) a vertical asymptote.',
      ),
      14 => 
      array (
        'chapter' => 3,
        'q_num' => 15,
        'question' => 'What is the mathematical relationship between f(x) = 2^x and g(x) = log_2(x)?',
        'option_a' => 'They are identical functions',
        'option_b' => 'They are inverse functions of each other',
        'option_c' => 'They are perpendicular lines',
        'option_d' => 'They have the same range',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Exponential and logarithmic functions with the same base are inverse functions of each other, mirrored across the line y = x.',
      ),
    ),
  ),
  4 => 
  array (
    'chapter_title' => 'Trigonometric Functions',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 4,
        'q_num' => 1,
        'question' => 'In a right-angled triangle ABC with the right angle at C, if side a = 3 and side b = 4, what is the value of sin(A)?',
        'option_a' => '3/4',
        'option_b' => '4/5',
        'option_c' => '3/5',
        'option_d' => '5/3',
        'raw_answer' => '**c) $\\frac{3}{5}$**',
        'correct_answer' => 'option_c',
        'explanation' => 'Using the Pythagorean theorem, the hypotenuse c = sqrt(a^2 + b^2) = sqrt(3^2 + 4^2) = sqrt(25) = 5. By definition, sin(A) = \\frac{Opposite{Hypotenuse = \\frac{a{c = 3/5.',
      ),
      1 => 
      array (
        'chapter' => 4,
        'q_num' => 2,
        'question' => 'What is the exact value of tan(45°) + cos(60°)?',
        'option_a' => '1/2',
        'option_b' => '3/2',
        'option_c' => '1',
        'option_d' => '\\frac{sqrt(3) + 1{2',
        'raw_answer' => '**b) $\\frac{3}{2}$**',
        'correct_answer' => 'option_b',
        'explanation' => 'tan(45°) = 1 and cos(60°) = 1/2. Adding them gives 1 + 1/2 = 3/2.',
      ),
      2 => 
      array (
        'chapter' => 4,
        'q_num' => 3,
        'question' => 'If theta is an angle in standard position whose terminal side passes through the point (-3, 4), what is the value of cos(theta)?',
        'option_a' => '-3/5',
        'option_b' => '4/5',
        'option_c' => '-4/5',
        'option_d' => '3/5',
        'raw_answer' => '**a) $-\\frac{3}{5}$**',
        'correct_answer' => 'option_a',
        'explanation' => 'The distance from the origin is r = sqrt((-3)^2 + 4^2) = 5. By definition, cos(theta) = \\frac{x{r = \\frac{-3{5 = -3/5.',
      ),
      3 => 
      array (
        'chapter' => 4,
        'q_num' => 4,
        'question' => 'Which trigonometric identity is correct for any angle theta?',
        'option_a' => 'sin^2(theta) - cos^2(theta) = 1',
        'option_b' => 'tan(theta) = \\frac{cos(theta){sin(theta)',
        'option_c' => 'sin^2(theta) + cos^2(theta) = 1',
        'option_d' => 'sec(theta) = \\frac{1{sin(theta)',
        'raw_answer' => '**c) $\\sin^2(\\theta) + \\cos^2(\\theta) = 1$**',
        'correct_answer' => 'option_c',
        'explanation' => 'sin^2(theta) + cos^2(theta) = 1 is the fundamental Pythagorean trigonometric identity.',
      ),
      4 => 
      array (
        'chapter' => 4,
        'q_num' => 5,
        'question' => 'What is the period of the basic sine function f(x) = \\sin(x)?',
        'option_a' => '90° (pi/2 rad)',
        'option_b' => '180° (pi rad)',
        'option_c' => '360° (2pi rad)',
        'option_d' => '720° (4pi rad)',
        'raw_answer' => '**c) $360^\\circ$ ($2\\pi$ rad)**',
        'correct_answer' => 'option_c',
        'explanation' => 'The sine function repeats its values in regular cycles of 360° or 2pi radians.',
      ),
      5 => 
      array (
        'chapter' => 4,
        'q_num' => 6,
        'question' => 'What is the period of the tangent function f(x) = tan(x)?',
        'option_a' => '90° (pi/2 rad)',
        'option_b' => '180° (pi rad)',
        'option_c' => '360° (2*pi rad)',
        'option_d' => '720° (4*pi rad)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Unlike sine and cosine (period 360°), the tangent function repeats every 180° (pi radians).',
      ),
      6 => 
      array (
        'chapter' => 4,
        'q_num' => 7,
        'question' => 'What is the amplitude of the trigonometric function f(x) = 3 * sin(2x)?',
        'option_a' => '2',
        'option_b' => '3',
        'option_c' => '6',
        'option_d' => '1.5',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'For f(x) = A * sin(Bx), the amplitude is |A| = 3.',
      ),
      7 => 
      array (
        'chapter' => 4,
        'q_num' => 8,
        'question' => 'What is the exact value of sin(90°)?',
        'option_a' => '0',
        'option_b' => '1',
        'option_c' => '1/2',
        'option_d' => 'sqrt(2)/2',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'At 90° (pi/2 radians), the y-coordinate on the unit circle is 1, so sin(90°) = 1.',
      ),
      8 => 
      array (
        'chapter' => 4,
        'q_num' => 9,
        'question' => 'What is the exact value of cos(180°)?',
        'option_a' => '0',
        'option_b' => '1',
        'option_c' => '-1',
        'option_d' => '1/2',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'At 180° (pi radians), the x-coordinate on the unit circle is -1, so cos(180°) = -1.',
      ),
      9 => 
      array (
        'chapter' => 4,
        'q_num' => 10,
        'question' => 'How many radians are equivalent to 180 degrees?',
        'option_a' => 'pi/2 radians',
        'option_b' => 'pi radians',
        'option_c' => '2*pi radians',
        'option_d' => '3*pi/2 radians',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'By definition of angle conversion, 180° = pi radians.',
      ),
      10 => 
      array (
        'chapter' => 4,
        'q_num' => 11,
        'question' => 'The reciprocal trigonometric function secant sec(theta) is defined as:',
        'option_a' => '1 / sin(theta)',
        'option_b' => '1 / cos(theta)',
        'option_c' => '1 / tan(theta)',
        'option_d' => 'sin(theta) / cos(theta)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Secant is the reciprocal of cosine: sec(theta) = 1 / cos(theta).',
      ),
      11 => 
      array (
        'chapter' => 4,
        'q_num' => 12,
        'question' => 'The reciprocal trigonometric function cosecant csc(theta) is defined as:',
        'option_a' => '1 / sin(theta)',
        'option_b' => '1 / cos(theta)',
        'option_c' => '1 / tan(theta)',
        'option_d' => 'cos(theta) / sin(theta)',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Cosecant is the reciprocal of sine: csc(theta) = 1 / sin(theta).',
      ),
      12 => 
      array (
        'chapter' => 4,
        'q_num' => 13,
        'question' => 'The cotangent ratio cot(theta) can be expressed as:',
        'option_a' => 'sin(theta) / cos(theta)',
        'option_b' => 'cos(theta) / sin(theta)',
        'option_c' => '1 / cos(theta)',
        'option_d' => 'sin(theta) * cos(theta)',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Cotangent is the reciprocal of tangent: cot(theta) = 1 / tan(theta) = cos(theta) / sin(theta).',
      ),
      13 => 
      array (
        'chapter' => 4,
        'q_num' => 14,
        'question' => 'In which quadrant are both sin(theta) < 0 and cos(theta) < 0?',
        'option_a' => 'Quadrant I',
        'option_b' => 'Quadrant II',
        'option_c' => 'Quadrant III',
        'option_d' => 'Quadrant IV',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'In Quadrant III, both x-coordinates (cos) and y-coordinates (sin) are negative.',
      ),
      14 => 
      array (
        'chapter' => 4,
        'q_num' => 15,
        'question' => 'What is the exact value of sin(30°)?',
        'option_a' => '1/2',
        'option_b' => 'sqrt(3)/2',
        'option_c' => '1',
        'option_d' => 'sqrt(2)/2',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'For a special 30°-60°-90° right triangle, sin(30°) = 1/2.',
      ),
    ),
  ),
  5 => 
  array (
    'chapter_title' => 'Circles',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'What is the equation of a circle centered at the origin (0, 0) with a radius of 5?',
        'option_a' => 'x^2 + y^2 = 5',
        'option_b' => 'x^2 + y^2 = 25',
        'option_c' => '(x - 5)^2 + (y - 5)^2 = 25',
        'option_d' => 'x^2 - y^2 = 25',
        'raw_answer' => '**b) $x^2 + y^2 = 25$**',
        'correct_answer' => 'option_b',
        'explanation' => 'The standard equation of a circle centered at (0,0) with radius r is x^2 + y^2 = r^2. Substituting r = 5 yields x^2 + y^2 = 5^2 = 25.',
      ),
      1 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'What are the center and radius of the circle given by (x - 3)^2 + (y + 2)^2 = 16?',
        'option_a' => 'Center: (-3, 2), Radius: 16',
        'option_b' => 'Center: (3, -2), Radius: 16',
        'option_c' => 'Center: (3, -2), Radius: 4',
        'option_d' => 'Center: (-3, 2), Radius: 4',
        'raw_answer' => '**c) Center: $(3, -2)$, Radius: $4$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The standard form is (x - h)^2 + (y - k)^2 = r^2. Comparing terms gives h = 3, k = -2, and r = sqrt(16) = 4.',
      ),
      2 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'A straight line that intersects a circle at exactly one point is called a:',
        'option_a' => 'Secant',
        'option_b' => 'Chord',
        'option_c' => 'Tangent',
        'option_d' => 'Diameter',
        'raw_answer' => '**c) Tangent**',
        'correct_answer' => 'option_c',
        'explanation' => 'By definition, a line that touches a circle at only one distinct point is a tangent line. A secant line intersects a circle at two points.',
      ),
      3 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'What is the relationship between a tangent line to a circle and the radius drawn to the point of tangency?',
        'option_a' => 'They are parallel.',
        'option_b' => 'They are perpendicular (90°).',
        'option_c' => 'They form a 45° angle.',
        'option_d' => 'They have equal lengths.',
        'raw_answer' => '**b) They are perpendicular ($90^\\circ$).**',
        'correct_answer' => 'option_b',
        'explanation' => 'A fundamental circle theorem states that a tangent at any point on a circle is perpendicular to the radius through the point of contact.',
      ),
      4 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'If an inscribed angle subtends an arc of 80° on a circle, what is the measure of the inscribed angle?',
        'option_a' => '160°',
        'option_b' => '80°',
        'option_c' => '40°',
        'option_d' => '20°',
        'raw_answer' => '**c) $40^\\circ$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The Inscribed Angle Theorem states that the measure of an inscribed angle is half the measure of its intercepted arc. Therefore, \\frac{80°{2 = 40°. Here are multiple-choice questions for **Chapter 6 (Solid Figures)** and **Chapter 7 (Coordinate Geometry)** of the Grade 10 Mathematics curriculum, complete with answers and detailed explanations.',
      ),
      5 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'What is the formula for the circumference (C) of a circle with radius r?',
        'option_a' => 'C = pi * r^2',
        'option_b' => 'C = 2 * pi * r',
        'option_c' => 'C = pi * r',
        'option_d' => 'C = 4 * pi * r',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The circumference of a circle is calculated using C = 2 * pi * r (or C = pi * d).',
      ),
      6 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'What is the formula for the area (A) of a circle with radius r?',
        'option_a' => 'A = 2 * pi * r',
        'option_b' => 'A = pi * r^2',
        'option_c' => 'A = pi^2 * r',
        'option_d' => 'A = (1/2) * pi * r^2',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The area enclosed by a circle of radius r is given by A = pi * r^2.',
      ),
      7 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'An angle subtended by a diameter in a semicircle always measures:',
        'option_a' => '45°',
        'option_b' => '90° (Right angle)',
        'option_c' => '180°',
        'option_d' => '60°',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Thales\'s Theorem states that an angle inscribed in a semicircle is always a right angle (90°).',
      ),
      8 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'What is the length of an arc (L) subtended by a central angle theta in a circle of radius r?',
        'option_a' => 'L = (theta / 360°) * 2 * pi * r',
        'option_b' => 'L = (theta / 360°) * pi * r^2',
        'option_c' => 'L = theta * r^2',
        'option_d' => 'L = 2 * pi * r',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Arc length is the fraction of total circumference: L = (theta / 360°) * 2 * pi * r.',
      ),
      9 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'What is the area of a sector of a circle with central angle theta and radius r?',
        'option_a' => 'A = (theta / 360°) * pi * r^2',
        'option_b' => 'A = (theta / 360°) * 2 * pi * r',
        'option_c' => 'A = pi * r^2',
        'option_d' => 'A = (theta / 180°) * pi * r^2',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Sector area is the fraction of total circle area: A = (theta / 360°) * pi * r^2.',
      ),
      10 => 
      array (
        'chapter' => 5,
        'q_num' => 11,
        'question' => 'In a cyclic quadrilateral (a 4-sided polygon inscribed in a circle), opposite angles are:',
        'option_a' => 'Equal',
        'option_b' => 'Supplementary (add up to 180°)',
        'option_c' => 'Complementary (add up to 90°)',
        'option_d' => 'Perpendicular',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'A key circle theorem states that opposite angles of a cyclic quadrilateral sum to 180°.',
      ),
      11 => 
      array (
        'chapter' => 5,
        'q_num' => 12,
        'question' => 'If the area of a circle is 49*pi cm^2, what is its diameter?',
        'option_a' => '7 cm',
        'option_b' => '14 cm',
        'option_c' => '28 cm',
        'option_d' => '49 cm',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Area = pi * r^2 = 49*pi => r^2 = 49 => r = 7 cm. Diameter d = 2 * r = 14 cm.',
      ),
      12 => 
      array (
        'chapter' => 5,
        'q_num' => 13,
        'question' => 'Circles that lie in the same plane and share the exact same center point are called:',
        'option_a' => 'Congruent circles',
        'option_b' => 'Concentric circles',
        'option_c' => 'Tangent circles',
        'option_d' => 'Secant circles',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Concentric circles are circles of different radii that share a common center.',
      ),
      13 => 
      array (
        'chapter' => 5,
        'q_num' => 14,
        'question' => 'The perpendicular line segment drawn from the center of a circle to a chord:',
        'option_a' => 'Trisects the chord',
        'option_b' => 'Bisects the chord into two equal halves',
        'option_c' => 'Equals the radius',
        'option_d' => 'Forms a 45° angle',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'A perpendicular line from the center to a chord bisects the chord (splits it into two equal lengths).',
      ),
      14 => 
      array (
        'chapter' => 5,
        'q_num' => 15,
        'question' => 'A straight line that cuts through a circle at TWO distinct points is called a:',
        'option_a' => 'Tangent line',
        'option_b' => 'Secant line',
        'option_c' => 'Radius',
        'option_d' => 'Asymptote',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'A secant line intersects a circle at two distinct points, extending beyond the circle.',
      ),
    ),
  ),
  6 => 
  array (
    'chapter_title' => 'Solid Figures',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 6,
        'q_num' => 1,
        'question' => 'What is the total surface area of a cube with an edge length of 4 cm?',
        'option_a' => '16 cm^2',
        'option_b' => '64 cm^2',
        'option_c' => '96 cm^2',
        'option_d' => '128 cm^2',
        'raw_answer' => '**c) $96\\text{ cm}^2$**',
        'correct_answer' => 'option_c',
        'explanation' => 'A cube has 6 identical square faces. The surface area is given by SA = 6s^2. Substituting s = 4: SA = 6 * (4)^2 = 6 * 16 = 96 cm^2.',
      ),
      1 => 
      array (
        'chapter' => 6,
        'q_num' => 2,
        'question' => 'What is the volume of a right circular cylinder with a base radius of 3 cm and a height of 10 cm?',
        'option_a' => '30pi cm^3',
        'option_b' => '60pi cm^3',
        'option_c' => '90pi cm^3',
        'option_d' => '300pi cm^3',
        'raw_answer' => '**c) $90\\pi\\text{ cm}^3$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The volume of a cylinder is V = pi r^2 h. Substituting r = 3 and h = 10: V = pi (3)^2 (10) = 9pi * 10 = 90pi cm^3.',
      ),
      2 => 
      array (
        'chapter' => 6,
        'q_num' => 3,
        'question' => 'What is the total surface area of a solid sphere with radius r = 5 cm?',
        'option_a' => '20pi cm^2',
        'option_b' => '50pi cm^2',
        'option_c' => '100pi cm^2',
        'option_d' => '\\frac{500{3pi cm^2',
        'raw_answer' => '**c) $100\\pi\\text{ cm}^2$**',
        'correct_answer' => 'option_c',
        'explanation' => 'The formula for the surface area of a sphere is SA = 4pi r^2. Substituting r = 5: SA = 4pi (5)^2 = 4pi (25) = 100pi cm^2.',
      ),
      3 => 
      array (
        'chapter' => 6,
        'q_num' => 4,
        'question' => 'A cone has a base radius of 6 cm and a height of 8 cm. What is its slant height (l)?',
        'option_a' => '10 cm',
        'option_b' => '14 cm',
        'option_c' => 'sqrt(28) cm',
        'option_d' => '12 cm',
        'raw_answer' => '**a) $10\\text{ cm}$**',
        'correct_answer' => 'option_a',
        'explanation' => 'The slant height l, radius r, and height h form a right triangle: l = sqrt(r^2 + h^2). Substituting r = 6 and h = 8: l = sqrt(6^2 + 8^2) = sqrt(36 + 64) = sqrt(100) = 10 cm.',
      ),
      4 => 
      array (
        'chapter' => 6,
        'q_num' => 5,
        'question' => 'What is the ratio of the volume of a cone to the volume of a cylinder having the same base radius and height?',
        'option_a' => '1 : 2',
        'option_b' => '1 : 3',
        'option_c' => '2 : 3',
        'option_d' => '1 : 4',
        'raw_answer' => '**b) $1 : 3$**',
        'correct_answer' => 'option_b',
        'explanation' => 'The volume of a cylinder is V_{cylinder = pi r^2 h and the volume of a cone is V_{cone = \\frac{1{3pi r^2 h. The ratio V_{cone : V_{cylinder is \\frac{1{3 : 1, or 1 : 3.',
      ),
      5 => 
      array (
        'chapter' => 6,
        'q_num' => 6,
        'question' => 'What is the formula for the volume (V) of a sphere with radius r?',
        'option_a' => 'V = 4 * pi * r^2',
        'option_b' => 'V = (4/3) * pi * r^3',
        'option_c' => 'V = (1/3) * pi * r^3',
        'option_d' => 'V = 2 * pi * r^3',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The volume of a sphere is given by V = (4/3) * pi * r^3.',
      ),
      6 => 
      array (
        'chapter' => 6,
        'q_num' => 7,
        'question' => 'What is the lateral surface area (LSA) of a cylinder with base radius r and height h?',
        'option_a' => 'LSA = 2 * pi * r * h',
        'option_b' => 'LSA = pi * r^2 * h',
        'option_c' => 'LSA = 2 * pi * r^2',
        'option_d' => 'LSA = pi * r * l',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'The lateral (curved) surface area of a cylinder is LSA = 2 * pi * r * h.',
      ),
      7 => 
      array (
        'chapter' => 6,
        'q_num' => 8,
        'question' => 'What is the lateral surface area of a right circular cone with radius r and slant height l?',
        'option_a' => 'LSA = pi * r * l',
        'option_b' => 'LSA = 2 * pi * r * l',
        'option_c' => 'LSA = pi * r^2 * l',
        'option_d' => 'LSA = (1/3) * pi * r * l',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'The lateral surface area of a cone is LSA = pi * r * l.',
      ),
      8 => 
      array (
        'chapter' => 6,
        'q_num' => 9,
        'question' => 'What is the volume of a cube with an edge length of s = 3 cm?',
        'option_a' => '9 cm^3',
        'option_b' => '27 cm^3',
        'option_c' => '54 cm^3',
        'option_d' => '18 cm^3',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Volume of a cube V = s^3 = 3^3 = 27 cm^3.',
      ),
      9 => 
      array (
        'chapter' => 6,
        'q_num' => 10,
        'question' => 'What is the volume of a rectangular prism (box) with length l = 5 cm, width w = 4 cm, and height h = 3 cm?',
        'option_a' => '12 cm^3',
        'option_b' => '60 cm^3',
        'option_c' => '94 cm^3',
        'option_d' => '30 cm^3',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'Volume of a rectangular prism is V = l * w * h = 5 * 4 * 3 = 60 cm^3.',
      ),
      10 => 
      array (
        'chapter' => 6,
        'q_num' => 11,
        'question' => 'What is the total surface area (TSA) of a closed cylinder with radius r and height h?',
        'option_a' => 'TSA = 2 * pi * r * h + 2 * pi * r^2',
        'option_b' => 'TSA = 2 * pi * r * h',
        'option_c' => 'TSA = pi * r^2 * h',
        'option_d' => 'TSA = pi * r * h + pi * r^2',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Total surface area = Lateral area + 2 * Base area = 2 * pi * r * h + 2 * pi * r^2.',
      ),
      11 => 
      array (
        'chapter' => 6,
        'q_num' => 12,
        'question' => 'What is the total surface area (TSA) of a closed right circular cone with radius r and slant height l?',
        'option_a' => 'TSA = pi * r * l + pi * r^2',
        'option_b' => 'TSA = pi * r * l',
        'option_c' => 'TSA = 2 * pi * r * l',
        'option_d' => 'TSA = (1/3) * pi * r^2 * h',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Total surface area = Lateral area + Base circular area = pi * r * l + pi * r^2.',
      ),
      12 => 
      array (
        'chapter' => 6,
        'q_num' => 13,
        'question' => 'What is the volume of a pyramid with base area B and height h?',
        'option_a' => 'V = B * h',
        'option_b' => 'V = (1/3) * B * h',
        'option_c' => 'V = (1/2) * B * h',
        'option_d' => 'V = 3 * B * h',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'The volume of any pyramid is V = (1/3) * Base Area * Height = (1/3) * B * h.',
      ),
      13 => 
      array (
        'chapter' => 6,
        'q_num' => 14,
        'question' => 'What solid shape is formed when the top section of a cone or pyramid is sliced off by a plane parallel to its base?',
        'option_a' => 'Frustum',
        'option_b' => 'Torus',
        'option_c' => 'Prism',
        'option_d' => 'Ellipsoid',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'A frustum is the truncated bottom portion of a cone or pyramid created by a parallel cutting plane.',
      ),
      14 => 
      array (
        'chapter' => 6,
        'q_num' => 15,
        'question' => 'Euler\'s Formula relating Vertices (V), Edges (E), and Faces (F) of any convex polyhedron is:',
        'option_a' => 'V - E + F = 2',
        'option_b' => 'V + E + F = 2',
        'option_c' => 'V - F = E',
        'option_d' => 'V + F - E = 0',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Euler\'s Polyhedron Formula states V - E + F = 2 (or V + F = E + 2).',
      ),
    ),
  ),
  7 => 
  array (
    'chapter_title' => 'Coordinate Geometry',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 7,
        'q_num' => 6,
        'question' => 'What is the distance between the two points A(2, -1) and B(5, 3)?',
        'option_a' => '5',
        'option_b' => '7',
        'option_c' => 'sqrt(13)',
        'option_d' => '25',
        'raw_answer' => '**a) $5$**',
        'correct_answer' => 'option_a',
        'explanation' => 'Using the distance formula d = sqrt((x2 - x1)^2 + (y2 - y1)^2): d = \\sqrt{(5 - 2)^2 + (3 - (-1))^2 = sqrt(3^2 + 4^2) = \\sqrt{9 + 16 = sqrt(25) = 5.',
      ),
      1 => 
      array (
        'chapter' => 7,
        'q_num' => 7,
        'question' => 'What are the coordinates of the midpoint of the line segment joining P(-4, 6) and Q(2, -2)?',
        'option_a' => '(-1, 2)',
        'option_b' => '(-3, 4)',
        'option_c' => '(1, -2)',
        'option_d' => '(-2, 4)',
        'raw_answer' => '**a) $(-1, 2)$**',
        'correct_answer' => 'option_a',
        'explanation' => 'The midpoint formula is M = <=ft(\\frac{x_1 + x_2{2, \\frac{y_1 + y_2{2\\right). Calculating for P and Q: M = <=ft(\\frac{-4 + 2{2, \\frac{6 + (-2){2\\right) = <=ft(\\frac{-2{2, \\frac{4{2\\right) = (-1, 2).',
      ),
      2 => 
      array (
        'chapter' => 7,
        'q_num' => 8,
        'question' => 'What is the slope of the line passing through the points (1, 4) and (3, 10)?',
        'option_a' => '2',
        'option_b' => '3',
        'option_c' => '\\frac{1{3',
        'option_d' => '6',
        'raw_answer' => '**b) $3$**',
        'correct_answer' => 'option_b',
        'explanation' => 'Slope m = \\frac{y_2 - y_1{x_2 - x_1. Substituting the given points: m = \\frac{10 - 4{3 - 1 = \\frac{6{2 = 3.',
      ),
      3 => 
      array (
        'chapter' => 7,
        'q_num' => 9,
        'question' => 'If Line L_1 has a slope of 4, what is the slope of a line L_2 that is **perpendicular** to L_1?',
        'option_a' => '4',
        'option_b' => '-4',
        'option_c' => '1/4',
        'option_d' => '-1/4',
        'raw_answer' => '**d) $-\\frac{1}{4}$**',
        'correct_answer' => 'option_d',
        'explanation' => 'Two non-vertical lines are perpendicular if and only if the product of their slopes is -1 (m_1 * m_2 = -1). Thus, m_2 = -\\frac{1{m_1 = -1/4.',
      ),
      4 => 
      array (
        'chapter' => 7,
        'q_num' => 10,
        'question' => 'What is the slope-intercept form of the equation of the line passing through (0, 5) with a slope of -2?',
        'option_a' => 'y = -2x - 5',
        'option_b' => 'y = 5x - 2',
        'option_c' => 'y = -2x + 5',
        'option_d' => '2x + y + 5 = 0',
        'raw_answer' => '**c) $y = -2x + 5$**',
        'correct_answer' => 'option_c',
        'explanation' => 'Slope-intercept form is y = mx + b, where m is the slope and b is the y-intercept. Here, m = -2 and the y-intercept (0, b) is (0, 5), giving y = -2x + 5. </USER_REQUEST> <ADDITIONAL_METADATA> The current local time is: 2026-09-02T17:29:56+03:00. The user\'s current state is as follows: Active Document: c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\ApproveExistingTutorsSeeder.php (LANGUAGE_PHP) Cursor is on line: 27 Other open documents: - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\API_ENDPOINTS.md (LANGUAGE_MARKDOWN) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\tests\\Feature\\ApproveExistingTutorsSeederTest.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\ApproveExistingTutorsSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10EconomicsQuizSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10HistoryQuizSeeder.php (LANGUAGE_PHP) </ADDITIONAL_METADATA>',
      ),
      5 => 
      array (
        'chapter' => 7,
        'q_num' => 6,
        'question' => 'If two non-vertical lines L1 and L2 are PARALLEL, what is the relationship between their slopes m1 and m2?',
        'option_a' => 'm1 = m2',
        'option_b' => 'm1 * m2 = -1',
        'option_c' => 'm1 + m2 = 0',
        'option_d' => 'm1 * m2 = 1',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Parallel lines have identical steepness, meaning their slopes are equal (m1 = m2).',
      ),
      6 => 
      array (
        'chapter' => 7,
        'q_num' => 7,
        'question' => 'What is the slope of any HORIZONTAL line parallel to the x-axis?',
        'option_a' => '0',
        'option_b' => '1',
        'option_c' => 'Undefined',
        'option_d' => '-1',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Since y does not change along a horizontal line (change in y = 0), the slope m = 0 / change in x = 0.',
      ),
      7 => 
      array (
        'chapter' => 7,
        'q_num' => 8,
        'question' => 'What is the slope of any VERTICAL line parallel to the y-axis?',
        'option_a' => '0',
        'option_b' => '1',
        'option_c' => 'Undefined (division by zero)',
        'option_d' => '-1',
        'raw_answer' => 'c',
        'correct_answer' => 'option_c',
        'explanation' => 'Along a vertical line, x remains constant (change in x = 0), resulting in division by zero; hence slope is undefined.',
      ),
      8 => 
      array (
        'chapter' => 7,
        'q_num' => 9,
        'question' => 'What is the distance d of any point P(x, y) from the origin (0, 0)?',
        'option_a' => 'd = sqrt(x^2 + y^2)',
        'option_b' => 'd = x + y',
        'option_c' => 'd = x^2 + y^2',
        'option_d' => 'd = sqrt(x + y)',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Applying distance formula with (0,0) gives d = sqrt((x - 0)^2 + (y - 0)^2) = sqrt(x^2 + y^2).',
      ),
      9 => 
      array (
        'chapter' => 7,
        'q_num' => 10,
        'question' => 'What is the equation of the vertical line passing through the point (4, -2)?',
        'option_a' => 'x = 4',
        'option_b' => 'y = -2',
        'option_c' => 'y = 4x - 2',
        'option_d' => 'x + y = 2',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'A vertical line has constant x-coordinates; for point (4, -2), its equation is x = 4.',
      ),
      10 => 
      array (
        'chapter' => 7,
        'q_num' => 11,
        'question' => 'What is the equation of the horizontal line passing through the point (4, -2)?',
        'option_a' => 'x = 4',
        'option_b' => 'y = -2',
        'option_c' => 'y = -2x + 4',
        'option_d' => 'x - y = 6',
        'raw_answer' => 'b',
        'correct_answer' => 'option_b',
        'explanation' => 'A horizontal line has constant y-coordinates; for point (4, -2), its equation is y = -2.',
      ),
      11 => 
      array (
        'chapter' => 7,
        'q_num' => 12,
        'question' => 'What is the Point-Slope form of a linear equation passing through (x1, y1) with slope m?',
        'option_a' => 'y - y1 = m(x - x1)',
        'option_b' => 'y = mx + b',
        'option_c' => 'Ax + By + C = 0',
        'option_d' => 'x / a + y / b = 1',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'The Point-Slope form is y - y1 = m(x - x1).',
      ),
      12 => 
      array (
        'chapter' => 7,
        'q_num' => 13,
        'question' => 'What is the General Linear Equation form for a straight line?',
        'option_a' => 'Ax + By + C = 0 (where A and B are not both zero)',
        'option_b' => 'y = mx + b',
        'option_c' => 'y - y1 = m(x - x1)',
        'option_d' => 'x^2 + y^2 = r^2',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'The General Form of a linear equation is Ax + By + C = 0.',
      ),
      13 => 
      array (
        'chapter' => 7,
        'q_num' => 14,
        'question' => 'What is the slope of the line given by 3x + 2y = 6?',
        'option_a' => '-1.5 (-3/2)',
        'option_b' => '3',
        'option_c' => '2',
        'option_d' => '1.5 (3/2)',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Rearrange to slope-intercept form: 2y = -3x + 6 => y = -1.5x + 3. The slope m is -1.5.',
      ),
      14 => 
      array (
        'chapter' => 7,
        'q_num' => 15,
        'question' => 'What are the coordinates of the midpoint between the origin (0, 0) and point (6, -8)?',
        'option_a' => '(3, -4)',
        'option_b' => '(6, -8)',
        'option_c' => '(-3, 4)',
        'option_d' => '(3, 4)',
        'raw_answer' => 'a',
        'correct_answer' => 'option_a',
        'explanation' => 'Midpoint M = ((0 + 6)/2, (0 + (-8))/2) = (3, -4).',
      ),
    ),
  ),
);
    }
}

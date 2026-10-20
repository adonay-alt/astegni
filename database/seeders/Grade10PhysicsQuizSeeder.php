<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade10PhysicsQuizSeeder extends Seeder
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

            $subject = Subject::where('grade_id', $grade->id)->where('title', 'Physics')->first();
            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Physics',
                    'description' => 'Grade 10 Physics Curriculum',
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
                        'description' => "Assessment quiz for Grade 10 Physics Chapter {$chapterNumber}.",
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
    'chapter_title' => 'Vector Quantities',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 1,
        'q_num' => 1,
        'question' => 'Which of the following physical quantities is classified as a scalar quantity?',
        'option_a' => 'Displacement',
        'option_b' => 'Mass',
        'option_c' => 'Acceleration',
        'option_d' => 'Weight',
        'raw_answer' => 'B) Mass',
        'correct_answer' => 'option_b',
        'explanation' => 'Scalar quantities are physical quantities that are completely specified by a magnitude and a unit, without any direction. Mass, density, temperature, and volume are scalars. Displacement, acceleration, and weight require both magnitude and direction, making them vector quantities.',
      ),
      1 => 
      array (
        'chapter' => 1,
        'q_num' => 2,
        'question' => 'An arrow-tipped line segment is used to represent a vector graphically. What does the length of the arrow represent?',
        'option_a' => 'The direction of the vector',
        'option_b' => 'The unit of the vector',
        'option_c' => 'The magnitude of the vector',
        'option_d' => 'The frame of reference',
        'raw_answer' => 'C) The magnitude of the vector',
        'correct_answer' => 'option_c',
        'explanation' => 'Geometrically, a vector is represented by an arrow where the arrow head represents the direction and the length of the arrow drawn to scale represents the magnitude of the vector.',
      ),
      2 => 
      array (
        'chapter' => 1,
        'q_num' => 3,
        'question' => 'A vector with zero magnitude and no specific direction is called a:',
        'option_a' => 'Unit vector',
        'option_b' => 'Equal vector',
        'option_c' => 'Negative vector',
        'option_d' => 'Null vector',
        'raw_answer' => 'D) Null vector',
        'correct_answer' => 'option_d',
        'explanation' => 'A zero vector or null vector is defined as a vector having zero magnitude and no specific direction. A unit vector has a magnitude equal to one.',
      ),
      3 => 
      array (
        'chapter' => 1,
        'q_num' => 4,
        'question' => 'Vector Vector B has a magnitude of 12 units pointing due East. What is the description of the negative vector, -Vector B?',
        'option_a' => 'Magnitude of 12 units pointing due West',
        'option_b' => 'Magnitude of -12 units pointing due East',
        'option_c' => 'Magnitude of 0 units',
        'option_d' => 'Magnitude of 12 units pointing due North',
        'raw_answer' => 'A) Magnitude of 12 units pointing due West',
        'correct_answer' => 'option_a',
        'explanation' => 'The negative of a vector has the exact same magnitude as the original vector but points in the opposite direction. Therefore, flipping the direction from East to West gives -Vector B with a magnitude of 12 units.',
      ),
      4 => 
      array (
        'chapter' => 1,
        'q_num' => 5,
        'question' => 'Two vectors Vector A and Vector B have magnitudes of 8 units and 6 units, respectively. What are the maximum and minimum possible magnitudes of their resultant vector?',
        'option_a' => 'Max = 14 units, Min = 2 units',
        'option_b' => 'Max = 10 units, Min = 2 units',
        'option_c' => 'Max = 14 units, Min = 0 units',
        'option_d' => 'Max = 48 units, Min = 1.33 units',
        'raw_answer' => 'A) Max = 14 units, Min = 2 units',
        'correct_answer' => 'option_a',
        'explanation' => 'The resultant is maximum when two vectors act in the same direction (|R| = A + B = 8 + 6 = 14) and minimum when they act in opposite directions (|R| = |A - B| = 8 - 6 = 2).',
      ),
      5 => 
      array (
        'chapter' => 1,
        'q_num' => 6,
        'question' => 'Which graphical method of vector addition is specifically used when adding more than two vectors by placing them head-to-tail in sequence?',
        'option_a' => 'Parallelogram method',
        'option_b' => 'Triangle method',
        'option_c' => 'Polygon method',
        'option_d' => 'Pythagorean method',
        'raw_answer' => 'C) Polygon method',
        'correct_answer' => 'option_c',
        'explanation' => 'The polygon rule of vector addition is used for adding a large number of vectors (more than two) by arranging each successive vector head-to-tail and drawing the resultant vector from the tail of the first to the head of the last vector.',
      ),
      6 => 
      array (
        'chapter' => 1,
        'q_num' => 7,
        'question' => 'Two perpendicular vectors Vector A = 3 N (along x-axis) and Vector B = 4 N (along y-axis) act on an object. What is the magnitude of the resultant force?',
        'option_a' => '7 N',
        'option_b' => '1 N',
        'option_c' => '5 N',
        'option_d' => '12 N',
        'raw_answer' => 'C) 5 N',
        'correct_answer' => 'option_c',
        'explanation' => 'When two vectors are perpendicular to each other, the magnitude of their resultant is calculated using the Pythagorean theorem: |R| = √(A² + B²) = √(3² + 4²) = √(9 + 16) = √25 = 5 N.',
      ),
      7 => 
      array (
        'chapter' => 1,
        'q_num' => 8,
        'question' => 'A force vector of magnitude F = 20 N makes an angle of 30° with the horizontal x-axis. What is the value of its horizontal component Fₓ?',
        'option_a' => '10.0 N',
        'option_b' => '17.3 N',
        'option_c' => '20.0 N',
        'option_d' => '15.0 N',
        'raw_answer' => 'B) 17.3 N',
        'correct_answer' => 'option_b',
        'explanation' => 'The horizontal component is calculated using Fₓ = F cosθ = 20 cos(30°) = 20 × 0.866 = 17.32 N.',
      ),
      8 => 
      array (
        'chapter' => 1,
        'q_num' => 9,
        'question' => 'What is the process of breaking a single vector down into two or more independent components called?',
        'option_a' => 'Vector addition',
        'option_b' => 'Vector multiplication',
        'option_c' => 'Vector resolution',
        'option_d' => 'Vector combination',
        'raw_answer' => 'C) Vector resolution',
        'correct_answer' => 'option_c',
        'explanation' => 'Breaking a vector into its individual orthogonal or directional components is referred to as resolving the vector or vector resolution.',
      ),
      9 => 
      array (
        'chapter' => 1,
        'q_num' => 10,
        'question' => 'If the rectangular components of a displacement vector are Sₓ = 6 km and Sᵧ = 8 km, what is the angle θ the vector makes with the positive x-axis?',
        'option_a' => '53.1°',
        'option_b' => '36.9°',
        'option_c' => '45.0°',
        'option_d' => '60.0°',
        'raw_answer' => 'A) 53.1°',
        'correct_answer' => 'option_a',
        'explanation' => 'The angle θ is found using the tangent trigonometric function: θ = tan⁻¹ ≤ ft(Sᵧ / Sₓ)) = tan⁻¹ ≤ ft(8 / 6)) = tan⁻¹(1.333) ≈ 53.13°.',
      ),
      10 => 
      array (
        'chapter' => 1,
        'q_num' => 11,
        'question' => 'Six vectors are added tail-to-head in a closed loop, ending exactly at the starting point of the first vector. What is the magnitude of the resultant vector?',
        'option_a' => 'Sum of all six magnitudes',
        'option_b' => 'Zero',
        'option_c' => 'Equal to the length of the last vector',
        'option_d' => 'Cannot be determined',
        'raw_answer' => 'B) Zero',
        'correct_answer' => 'option_b',
        'explanation' => 'According to the polygon law, the resultant vector connects the tail of the first vector to the head of the last vector. Since the head of the last vector ends at the tail of the first vector, the displacement distance is zero.',
      ),
      11 => 
      array (
        'chapter' => 1,
        'q_num' => 12,
        'question' => 'Which of the following operations is allowed when graphically adding vectors?',
        'option_a' => 'Changing the magnitude of the vector',
        'option_b' => 'Rotating the direction of the vector',
        'option_c' => 'Moving the vector parallel to itself',
        'option_d' => 'Scaling the vector selectively',
        'raw_answer' => 'C) Moving the vector parallel to itself',
        'correct_answer' => 'option_c',
        'explanation' => 'During graphical addition, a parallel translation of a vector is permissible as long as neither its magnitude (length) nor its direction (angle) is changed.',
      ),
      12 => 
      array (
        'chapter' => 1,
        'q_num' => 13,
        'question' => 'If a vector has rectangular components Aₓ = 12 units and Aᵧ = 5 units, what is the overall magnitude of vector Vector A?',
        'option_a' => '17 units',
        'option_b' => '7 units',
        'option_c' => '13 units',
        'option_d' => '169 units',
        'raw_answer' => 'C) 13 units',
        'correct_answer' => 'option_c',
        'explanation' => 'Using the magnitude formula |A| = √(Aₓ² + Aᵧ²) = √(12² + 5²) = √(144 + 25) = √169 = 13 units.',
      ),
      13 => 
      array (
        'chapter' => 1,
        'q_num' => 14,
        'question' => 'Vector Vector C is 6 m in the x-direction and Vector Vector D is 8 m in the y-direction. What is the direction of Vector C + Vector D relative to the positive x-axis?',
        'option_a' => '36.9°',
        'option_b' => '53.1°',
        'option_c' => '45.0°',
        'option_d' => '90.0°',
        'raw_answer' => 'A) 36.9°',
        'correct_answer' => 'option_a',
        'explanation' => 'The direction angle is θ = tan⁻¹ ≤ ft(Dᵧ / Cₓ)) = tan⁻¹ ≤ ft(8 / 6)) = tan⁻¹(1.333) ≈ 53.13° relative to the y-axis, but with respect to the x-axis, θ = tan⁻¹ ≤ ft(8 / 6)) = 53.1° relative to the vertical, or tan⁻¹ ≤ ft(y / x)) = tan⁻¹ ≤ ft(8 / 6)) = 53.1°. Wait, checking tan⁻¹(8/6) = 53.13° from horizontal. If Cₓ = 6 (horizontal) and Dᵧ = 8 (vertical), tanθ = 8/6 ⇒ θ = 53.1°. (Note: Option B gives 53.1°). Let\'s verify: tanθ = Opposite / Adjacent) = 8 / 6) ⇒ θ = 53.1°. Therefore, the correct angle relative to x-axis is **53.1°**.',
      ),
      14 => 
      array (
        'chapter' => 1,
        'q_num' => 15,
        'question' => 'Which statement is true regarding scalar and vector addition?',
        'option_a' => 'Scalars and vectors can be added directly together.',
        'option_b' => 'Vectors can only be added if they are of the same physical nature.',
        'option_c' => 'Vector addition ignores the direction of vectors.',
        'option_d' => 'The resultant of two vectors is always greater than individual vectors.',
        'raw_answer' => 'B) Vectors can only be added if they are of the same physical nature.',
        'correct_answer' => 'option_b',
        'explanation' => 'Scalars and vectors can never be added together. Furthermore, vector addition requires that the vectors represent the same physical quantity (e.g., two forces or two velocities can be added, but a force and a velocity cannot).',
      ),
    ),
  ),
  2 => 
  array (
    'chapter_title' => 'Uniformly Accelerated Motion',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 2,
        'q_num' => 1,
        'question' => 'A person walks 70 m East, then turns around and walks 30 m West. What is the total distance and net displacement of the person?',
        'option_a' => 'Distance = 100 m, Displacement = 40 m East',
        'option_b' => 'Distance = 40 m, Displacement = 100 m East',
        'option_c' => 'Distance = 100 m, Displacement = 100 m East',
        'option_d' => 'Distance = 40 m, Displacement = 40 m West',
        'raw_answer' => 'A) Distance = 100 m, Displacement = 40 m East',
        'correct_answer' => 'option_a',
        'explanation' => 'Distance is the total path length traveled (70 m + 30 m = 100 m). Displacement is the overall change in position (\\vec{Δ s} = 70 m East - 30 m West = 40 m East).',
      ),
      1 => 
      array (
        'chapter' => 2,
        'q_num' => 2,
        'question' => 'The reading displayed on a vehicle\'s speedometer corresponds to its:',
        'option_a' => 'Average speed',
        'option_b' => 'Average velocity',
        'option_c' => 'Instantaneous speed',
        'option_d' => 'Instantaneous acceleration',
        'raw_answer' => 'C) Instantaneous speed',
        'correct_answer' => 'option_c',
        'explanation' => 'The speedometer measures how fast a vehicle is moving at a specific instant in time, which is the magnitude of the instantaneous velocity (instantaneous speed).',
      ),
      2 => 
      array (
        'chapter' => 2,
        'q_num' => 3,
        'question' => 'A car travels a distance of 100 km at a constant speed of 50 km/h and then another 100 km at a speed of 100 km/h. What is the average speed for the total 200 km trip?',
        'option_a' => '75.0 km/h',
        'option_b' => '66.7 km/h',
        'option_c' => '80.0 km/h',
        'option_d' => '60.0 km/h',
        'raw_answer' => 'B) 66.7 km/h',
        'correct_answer' => 'option_b',
        'explanation' => 'Time taken for first leg: t₁ = 100 / 50) = 2 h. Time for second leg: t₂ = 100 / 100) = 1 h. Total time = 3 h. Total distance = 200 km. Average speed = Total distance / Total time) = 200 km / 3 h) ≈ 66.7 km/h.',
      ),
      3 => 
      array (
        'chapter' => 2,
        'q_num' => 4,
        'question' => 'An automobile accelerates from rest (0 m/s) to a speed of 21 m/s along a straight road in 5 s. What is its average acceleration?',
        'option_a' => '4.2 m/s²',
        'option_b' => '105 m/s²',
        'option_c' => '2.1 m/s²',
        'option_d' => '8.4 m/s²',
        'raw_answer' => 'A) 4.2 m/s²',
        'correct_answer' => 'option_a',
        'explanation' => 'Average acceleration is defined as a = v - v₀ / Δ t) = 21 - 0 / 5) = 4.2 m/s².',
      ),
      4 => 
      array (
        'chapter' => 2,
        'q_num' => 5,
        'question' => 'When an object is slowing down, its acceleration vector points:',
        'option_a' => 'In the same direction as its velocity vector',
        'option_b' => 'Opposite to the direction of its velocity vector',
        'option_c' => 'Perpendicular to its velocity vector',
        'option_d' => 'Towards the origin frame of reference',
        'raw_answer' => 'B) Opposite to the direction of its velocity vector',
        'correct_answer' => 'option_b',
        'explanation' => 'Deceleration occurs when an object slows down, meaning its acceleration is directed opposite to the direction of motion (velocity).',
      ),
      5 => 
      array (
        'chapter' => 2,
        'q_num' => 6,
        'question' => 'A car starts from rest (v₀ = 0) and accelerates uniformly at 8 m/s². How far does it travel in 5 s?',
        'option_a' => '40 m',
        'option_b' => '200 m',
        'option_c' => '100 m',
        'option_d' => '80 m',
        'raw_answer' => 'C) 100 m',
        'correct_answer' => 'option_c',
        'explanation' => 'Using the kinematic equation s = v₀ t + 1 / 2)at²: s = (0)(5) + 1 / 2)(8)(5)² = 0.5 × 8 × 25 = 100 m.',
      ),
      6 => 
      array (
        'chapter' => 2,
        'q_num' => 7,
        'question' => 'An airplane lands with an initial speed of 70 m/s and decelerates uniformly at 1.5 m/s² for 40 s. What is its final velocity?',
        'option_a' => '10 m/s',
        'option_b' => '130 m/s',
        'option_c' => '60 m/s',
        'option_d' => '0 m/s',
        'raw_answer' => 'A) 10 m/s',
        'correct_answer' => 'option_a',
        'explanation' => 'Using v = v₀ + at, where a = -1.5 m/s² due to deceleration: v = 70 + (-1.5 × 40) = 70 - 60 = 10 m/s.',
      ),
      7 => 
      array (
        'chapter' => 2,
        'q_num' => 8,
        'question' => 'In the absence of air resistance, all objects dropped near the Earth\'s surface experience a constant downward gravitational acceleration (g) of approximately:',
        'option_a' => '0 m/s²',
        'option_b' => '9.8 m/s²',
        'option_c' => '1.5 m/s²',
        'option_d' => '3.5 m/s²',
        'raw_answer' => 'B) 9.8 m/s²',
        'correct_answer' => 'option_b',
        'explanation' => 'Free-fall acceleration near the surface of Earth in the absence of air resistance is a constant value equal to g ≈ 9.80 m/s² directed downward.',
      ),
      8 => 
      array (
        'chapter' => 2,
        'q_num' => 9,
        'question' => 'A rock is dropped from rest from the top of a building and falls freely under gravity (g = 9.8 m/s²) for 1.0 s. What is the height fallen?',
        'option_a' => '9.8 m',
        'option_b' => '4.9 m',
        'option_c' => '19.6 m',
        'option_d' => '1.0 m',
        'raw_answer' => 'B) 4.9 m',
        'correct_answer' => 'option_b',
        'explanation' => 'For free fall from rest (v₀ = 0), h = 1 / 2)gt² = 1 / 2)(9.8)(1)² = 4.9 m.',
      ),
      9 => 
      array (
        'chapter' => 2,
        'q_num' => 10,
        'question' => 'On a position-time (s-t) graph, what physical quantity does the slope of a tangent line drawn at a specific point represent?',
        'option_a' => 'Average acceleration',
        'option_b' => 'Instantaneous velocity',
        'option_c' => 'Total distance',
        'option_d' => 'Instantaneous acceleration',
        'raw_answer' => 'B) Instantaneous velocity',
        'correct_answer' => 'option_b',
        'explanation' => 'The slope of the tangent line to the curve on a position-time graph gives the rate of change of position with time at that specific instant, which is instantaneous velocity.',
      ),
      10 => 
      array (
        'chapter' => 2,
        'q_num' => 11,
        'question' => 'On a velocity-time (v-t) graph, the total area enclosed between the plot line and the time axis represents the object\'s:',
        'option_a' => 'Acceleration',
        'option_b' => 'Speed',
        'option_c' => 'Displacement',
        'option_d' => 'Frame of reference',
        'raw_answer' => 'C) Displacement',
        'correct_answer' => 'option_c',
        'explanation' => 'The geometric area under a velocity-time graph over a specified time interval equals the total displacement of the moving object.',
      ),
      11 => 
      array (
        'chapter' => 2,
        'q_num' => 12,
        'question' => 'What does the slope of a velocity-time (v-t) graph represent?',
        'option_a' => 'Position',
        'option_b' => 'Acceleration',
        'option_c' => 'Displacement',
        'option_d' => 'Relative velocity',
        'raw_answer' => 'B) Acceleration',
        'correct_answer' => 'option_b',
        'explanation' => 'The slope of a velocity-time line (Δ v / Δ t) gives the rate of change of velocity, which is acceleration.',
      ),
      12 => 
      array (
        'chapter' => 2,
        'q_num' => 13,
        'question' => 'Two cars A and B are traveling in the **opposite** directions on a straight highway with speeds vₐ = 100 km/h (right) and v_B = 100 km/h (left). What is the relative velocity of car A with respect to car B (vₐ_B)?',
        'option_a' => '0 km/h',
        'option_b' => '100 km/h',
        'option_c' => '200 km/h',
        'option_d' => '-100 km/h',
        'raw_answer' => 'C) 200 km/h',
        'correct_answer' => 'option_c',
        'explanation' => 'Relative velocity formula is vₐ_B = vₐ - v_B. Setting right as positive (vₐ = +100) and left as negative (v_B = -100), vₐ_B = 100 - (-100) = 200 km/h.',
      ),
      13 => 
      array (
        'chapter' => 2,
        'q_num' => 14,
        'question' => 'If two automobiles are traveling in the **same** direction down a highway at identical speeds of 100 km/h, what is the relative speed of one automobile with respect to the other?',
        'option_a' => '200 km/h',
        'option_b' => '100 km/h',
        'option_c' => '0 km/h',
        'option_d' => '50 km/h',
        'raw_answer' => 'C) 0 km/h',
        'correct_answer' => 'option_c',
        'explanation' => 'For two objects moving in the same direction, vₐ_B = vₐ - v_B = 100 - 100 = 0 km/h. To an observer in car B, car A appears stationary.',
      ),
      14 => 
      array (
        'chapter' => 2,
        'q_num' => 15,
        'question' => 'An acceleration-time (a-t) graph for an object undergoing uniformly accelerated motion is represented by:',
        'option_a' => 'A parabola curving upwards',
        'option_b' => 'A straight line parallel to the time axis',
        'option_c' => 'A vertical straight line',
        'option_d' => 'A sloped line passing through the origin',
        'raw_answer' => 'B) A straight line parallel to the time axis',
        'correct_answer' => 'option_b',
        'explanation' => 'In uniformly accelerated motion, the acceleration remains constant over time; thus, its graph against time is a horizontal straight line parallel to the x-axis.',
      ),
    ),
  ),
  3 => 
  array (
    'chapter_title' => 'Elasticity and Static Equilibrium of Rigid Body',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 3,
        'q_num' => 1,
        'question' => 'What property describes a material\'s ability to regain its original shape and size after external deforming forces are removed?',
        'option_a' => 'Plasticity',
        'option_b' => 'Stress',
        'option_c' => 'Elasticity',
        'option_d' => 'Strain',
        'raw_answer' => 'C) Elasticity',
        'correct_answer' => 'option_c',
        'explanation' => 'Elasticity is the physical property of a material that enables it to restore its initial dimensions once deforming forces cease to act.',
      ),
      1 => 
      array (
        'chapter' => 3,
        'q_num' => 2,
        'question' => 'If a body stays permanently deformed and shows no tendency to return to its original shape after removing the deforming force, it exhibits:',
        'option_a' => 'Elastic deformation',
        'option_b' => 'Plasticity',
        'option_c' => 'Young\'s Modulus',
        'option_d' => 'Static equilibrium',
        'raw_answer' => 'B) Plasticity',
        'correct_answer' => 'option_b',
        'explanation' => 'Plasticity is the property by which a material undergoes permanent, irreversible deformation under applied force without returning to its original state.',
      ),
      2 => 
      array (
        'chapter' => 3,
        'q_num' => 3,
        'question' => 'What is the maximum deforming force up to which a body retains its elastic property before becoming permanently deformed?',
        'option_a' => 'Breaking stress',
        'option_b' => 'Strain point',
        'option_c' => 'Elastic limit',
        'option_d' => 'Shear limit',
        'raw_answer' => 'C) Elastic limit',
        'correct_answer' => 'option_c',
        'explanation' => 'The elastic limit is defined as the maximum stress or deforming force a material can withstand without undergoing permanent plastic deformation.',
      ),
      3 => 
      array (
        'chapter' => 3,
        'q_num' => 4,
        'question' => 'Density (ρ) is defined mathematically as:',
        'option_a' => 'Mass divided by Volume (m / V)',
        'option_b' => 'Volume divided by Mass (V / m)',
        'option_c' => 'Force divided by Area (F / A)',
        'option_d' => 'Mass times Volume (m × V)',
        'raw_answer' => 'A) Mass divided by Volume (m / V)',
        'correct_answer' => 'option_a',
        'explanation' => 'Density is defined as the mass of a substance contained per unit volume: ρ = m / V).',
      ),
      4 => 
      array (
        'chapter' => 3,
        'q_num' => 5,
        'question' => 'What is the standard SI unit of density?',
        'option_a' => 'g/cm³',
        'option_b' => 'kg/m³',
        'option_c' => 'N/m²',
        'option_d' => 'kg/m²',
        'raw_answer' => 'B) kg/m³',
        'correct_answer' => 'option_b',
        'explanation' => 'The SI standard unit for measuring mass is kilogram (kg) and for volume is cubic meter (m³), making the unit kg/m³.',
      ),
      5 => 
      array (
        'chapter' => 3,
        'q_num' => 6,
        'question' => 'Specific gravity (or relative density) of a substance is defined as the ratio of the density of the substance to the density of:',
        'option_a' => 'Air at room temperature',
        'option_b' => 'Pure water at 4°C',
        'option_c' => 'Mercury at 0°C',
        'option_d' => 'Alcohol at 20°C',
        'raw_answer' => 'B) Pure water at 4°C',
        'correct_answer' => 'option_b',
        'explanation' => 'Specific gravity is a relative ratio comparing a material\'s density to the density of standard pure water at 4°C (where water reaches its maximum density of 1000 kg/m³).',
      ),
      6 => 
      array (
        'chapter' => 3,
        'q_num' => 7,
        'question' => 'What are the units of specific gravity?',
        'option_a' => 'kg/m³',
        'option_b' => 'N/m²',
        'option_c' => 'g/cm³',
        'option_d' => 'Dimensionless (no units)',
        'raw_answer' => 'D) Dimensionless (no units)',
        'correct_answer' => 'option_d',
        'explanation' => 'Because specific gravity is a ratio of two identical density quantities (ρ_substance / ρ_water), the units cancel out completely, making it unitless.',
      ),
      7 => 
      array (
        'chapter' => 3,
        'q_num' => 8,
        'question' => 'A mineral sample has a mass of 54 g (0.054 kg) and a volume of 20 cm³ (2 × 10⁻⁵ m³). What is its density?',
        'option_a' => '2.7 × 10³ kg/m³',
        'option_b' => '1.0 × 10³ kg/m³',
        'option_c' => '7.86 × 10³ kg/m³',
        'option_d' => '540 kg/m³',
        'raw_answer' => 'A) 2.7 × 10³ kg/m³',
        'correct_answer' => 'option_a',
        'explanation' => 'ρ = m / V) = 0.054 kg / 2 × 10⁻⁵ m³ = 2700 kg/m³ = 2.7 × 10³ kg/m³.',
      ),
      8 => 
      array (
        'chapter' => 3,
        'q_num' => 9,
        'question' => 'Stress is defined as the deforming force acting per unit area. What is its SI unit?',
        'option_a' => 'Pascal (Pa) or N/m²',
        'option_b' => 'Newton (N)',
        'option_c' => 'Joule (J)',
        'option_d' => 'Meter (m)',
        'raw_answer' => 'A) Pascal (Pa) or N/m²',
        'correct_answer' => 'option_a',
        'explanation' => 'Stress equals Force divided by Area (Stress = F / A). Its SI unit is Newton per square meter (N/m²), which is defined as the Pascal (Pa).',
      ),
      9 => 
      array (
        'chapter' => 3,
        'q_num' => 10,
        'question' => 'Which type of stress occurs when equal and opposite forces pull on an object causing its length to elongate?',
        'option_a' => 'Compressive stress',
        'option_b' => 'Tensile stress',
        'option_c' => 'Shear stress',
        'option_d' => 'Bulk stress',
        'raw_answer' => 'B) Tensile stress',
        'correct_answer' => 'option_b',
        'explanation' => 'Tensile stress occurs when forces act outward to stretch or elongate an object. Compressive stress occurs when forces compress or shorten an object.',
      ),
      10 => 
      array (
        'chapter' => 3,
        'q_num' => 11,
        'question' => 'Linear (tensile) strain is calculated as:',
        'option_a' => 'Force × Area',
        'option_b' => 'Change in length divided by original length (Δ L / L_0)',
        'option_c' => 'Original length divided by change in length (L_0 / Δ L)',
        'option_d' => 'Stress divided by Area',
        'raw_answer' => 'B) Change in length divided by original length (Δ L / L_0)',
        'correct_answer' => 'option_b',
        'explanation' => 'Tensile strain measures fractional deformation, given by the ratio of change in length (Δ L) to the original starting length (L_0).',
      ),
      11 => 
      array (
        'chapter' => 3,
        'q_num' => 12,
        'question' => 'Strain is a physical quantity that has:',
        'option_a' => 'Units of meters',
        'option_b' => 'Units of Newtons',
        'option_c' => 'No units (it is dimensionless)',
        'option_d' => 'Units of N/m²',
        'raw_answer' => 'C) No units (it is dimensionless)',
        'correct_answer' => 'option_c',
        'explanation' => 'Since strain is the ratio of two identical physical dimensions (e.g., length divided by length m / m), it is a dimensionless number without units.',
      ),
      12 => 
      array (
        'chapter' => 3,
        'q_num' => 13,
        'question' => 'Why are old bridges often declared unsafe for public use after long periods of service?',
        'option_a' => 'The density of the bridge steel increases over time.',
        'option_b' => 'The metal structure undergoes repeated strains leading to a loss of elastic strength.',
        'option_c' => 'The bridge loses its specific gravity.',
        'option_d' => 'Earth\'s gravity increases near old structures.',
        'raw_answer' => 'B) The metal structure undergoes repeated strains leading to a loss of elastic strength.',
        'correct_answer' => 'option_b',
        'explanation' => 'Over long periods of exposure to fluctuating stress and strain loads, materials undergo structural fatigue and lose their elastic strength beyond safety tolerances.',
      ),
      13 => 
      array (
        'chapter' => 3,
        'q_num' => 14,
        'question' => 'What happens to a solid material if it is subjected to a deforming stress that exceeds its elastic limit?',
        'option_a' => 'It immediately returns to its original shape.',
        'option_b' => 'It undergoes permanent plastic deformation or fractures.',
        'option_c' => 'Its mass decreases to zero.',
        'option_d' => 'It acts as a perfectly rigid body.',
        'raw_answer' => 'B) It undergoes permanent plastic deformation or fractures.',
        'correct_answer' => 'option_b',
        'explanation' => 'Beyond the elastic limit point, the material loses its elastic recovery property and undergoes permanent plastic deformation or failure.',
      ),
      14 => 
      array (
        'chapter' => 3,
        'q_num' => 15,
        'question' => 'If a force squeezes an object uniformly from all surrounding sides (such as a submarine deep underwater), the stress experienced is called:',
        'option_a' => 'Shear stress',
        'option_b' => 'Tensile stress',
        'option_c' => 'Volume (Bulk) stress',
        'option_d' => 'Linear strain',
        'raw_answer' => 'C) Volume (Bulk) stress',
        'correct_answer' => 'option_c',
        'explanation' => 'When deforming forces act uniformly from all directions inward on the total volume of an object, it is subjected to bulk stress (or volume stress). </USER_REQUEST> <ADDITIONAL_METADATA> The current local time is: 2026-09-02T14:26:07+03:00. The user\'s current state is as follows: Active Document: c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10HistoryQuizSeeder.php (LANGUAGE_PHP) Cursor is on line: 1 Other open documents: - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\database\\seeders\\Grade10HistoryQuizSeeder.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\tests\\Feature\\Grade10HistoryQuizSeederTest.php (LANGUAGE_PHP) - c:\\xampp\\htdocs\\api6\\apiv2.asteghi.com\\API_ENDPOINTS.md (LANGUAGE_MARKDOWN) </ADDITIONAL_METADATA>',
      ),
    ),
  ),
  4 => 
  array (
    'chapter_title' => 'Static and Current Electricity',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 4,
        'q_num' => 1,
        'question' => 'According to Coulomb\'s Law, the electrostatic force between two point charges is inversely proportional to the:',
        'option_a' => 'Product of the charges (q_1 q_2)',
        'option_b' => 'Distance between the charges (r)',
        'option_c' => 'Square of the distance between the charges (r²)',
        'option_d' => 'Permittivity of free space (ε_0)',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Coulomb\'s Law states F = k |q_1 q_2| / r²), meaning electrostatic force is inversely proportional to the square of the separation distance (r²).',
      ),
      1 => 
      array (
        'chapter' => 4,
        'q_num' => 2,
        'question' => 'What is the SI unit of Electric Field Intensity (E)?',
        'option_a' => 'Volt (V)',
        'option_b' => 'Newton per Coulomb (N/C)',
        'option_c' => 'Joule per Coulomb (J/C)',
        'option_d' => 'Ohm (Ω)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Electric field intensity is force per unit charge (E = F / q), measured in Newtons per Coulomb (N/C) or Volts per meter (V/m).',
      ),
      2 => 
      array (
        'chapter' => 4,
        'q_num' => 3,
        'question' => 'Electric potential difference (V) between two points is defined as the work done (W) per unit charge (q). Its SI unit is the:',
        'option_a' => 'Ampere (A)',
        'option_b' => 'Watt (W)',
        'option_c' => 'Volt (V)',
        'option_d' => 'Farad (F)',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Electric potential difference is V = W / q), measured in Volts (V), where 1 V = 1 J/C.',
      ),
      3 => 
      array (
        'chapter' => 4,
        'q_num' => 4,
        'question' => 'A circuit component has a resistance of 4 Ω. If a voltage of 12 V is applied across it, what current flows through it according to Ohm\'s Law?',
        'option_a' => '48 A',
        'option_b' => '3 A',
        'option_c' => '0.33 A',
        'option_d' => '16 A',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Ohm\'s Law states V = I R ⇒ I = V / R) = 12 V / 4 Ω) = 3 A.',
      ),
      4 => 
      array (
        'chapter' => 4,
        'q_num' => 5,
        'question' => 'The electrical resistance (R) of a uniform conductor is directly proportional to its length (L) and inversely proportional to its:',
        'option_a' => 'Resistivity (ρ)',
        'option_b' => 'Cross-sectional area (A)',
        'option_c' => 'Voltage (V)',
        'option_d' => 'Current (I)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The resistance equation is R = ρ L / A), showing R is inversely proportional to cross-sectional area A.',
      ),
      5 => 
      array (
        'chapter' => 4,
        'q_num' => 6,
        'question' => 'Three resistors of resistances 2 Ω, 3 Ω, and 5 Ω are connected in SERIES. What is the equivalent resistance (Rₑq)?',
        'option_a' => '10 Ω',
        'option_b' => '0.96 Ω',
        'option_c' => '1 Ω',
        'option_d' => '30 Ω',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'For series connection, Rₑq = R₁ + R₂ + R_3 = 2 + 3 + 5 = 10 Ω.',
      ),
      6 => 
      array (
        'chapter' => 4,
        'q_num' => 7,
        'question' => 'Two identical resistors of 6 Ω each are connected in PARALLEL. What is their combined equivalent resistance?',
        'option_a' => '12 Ω',
        'option_b' => '3 Ω',
        'option_c' => '36 Ω',
        'option_d' => '1 Ω',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'For parallel connection, 1 / Rₑq = 1 / R₁ + 1 / R₂ = 1 / 6 + 1 / 6 = 2 / 6 ⇒ Rₑq = 3 Ω.',
      ),
      7 => 
      array (
        'chapter' => 4,
        'q_num' => 8,
        'question' => 'The electrical power dissipated in a resistor carrying current I under potential difference V is given by:',
        'option_a' => 'P = I V',
        'option_b' => 'P = I² R',
        'option_c' => 'P = V² / R)',
        'option_d' => 'All of the above',
        'raw_answer' => 'D',
        'correct_answer' => 'option_d',
        'explanation' => 'Electric power equations include P = IV, P = I² R, and P = V² / R). All three expressions are mathematically equivalent using Ohm\'s Law.',
      ),
      8 => 
      array (
        'chapter' => 4,
        'q_num' => 9,
        'question' => 'What is the commercial unit of electrical energy commonly used by electric utility companies?',
        'option_a' => 'Watt (W)',
        'option_b' => 'Kilowatt-hour (kWh)',
        'option_c' => 'Coulomb (C)',
        'option_d' => 'Volt-Ampere (VA)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Kilowatt-hour (kWh) is the commercial unit of energy, where 1 kWh = 3.6 × 10⁶ J.',
      ),
      9 => 
      array (
        'chapter' => 4,
        'q_num' => 10,
        'question' => 'Capacitance (C) of a capacitor is defined as the ratio of stored charge (Q) to potential difference (V). Its SI unit is the:',
        'option_a' => 'Henry (H)',
        'option_b' => 'Farad (F)',
        'option_c' => 'Tesla (T)',
        'option_d' => 'Weber (Wb)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Capacitance is C = Q / V), measured in Farads (F), where 1 F = 1 C/V.',
      ),
      10 => 
      array (
        'chapter' => 4,
        'q_num' => 11,
        'question' => 'If the plate area (A) of a parallel-plate capacitor is doubled while keeping plate separation (d) constant, its capacitance will:',
        'option_a' => 'Double',
        'option_b' => 'Halve',
        'option_c' => 'Quadruple',
        'option_d' => 'Remain unchanged',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Parallel plate capacitance formula is C = ε_0 A / d). Since C \\propto A, doubling A doubles C.',
      ),
      11 => 
      array (
        'chapter' => 4,
        'q_num' => 12,
        'question' => 'Kirchhoff\'s Junction Rule (Current Law), which states \\sum I_in = \\sum I_out, is a direct consequence of the conservation of:',
        'option_a' => 'Energy',
        'option_b' => 'Electric Charge',
        'option_c' => 'Momentum',
        'option_d' => 'Mass',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Kirchhoff\'s Junction Rule expresses the Law of Conservation of Electric Charge at any circuit junction node.',
      ),
      12 => 
      array (
        'chapter' => 4,
        'q_num' => 13,
        'question' => 'Kirchhoff\'s Loop Rule (Voltage Law), which states \\sum V = 0 around any closed loop, is based on the conservation of:',
        'option_a' => 'Energy',
        'option_b' => 'Charge',
        'option_c' => 'Power',
        'option_d' => 'Resistance',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Kirchhoff\'s Loop Rule is a statement of the Law of Conservation of Energy applied to electric potential changes in a closed circuit.',
      ),
      13 => 
      array (
        'chapter' => 4,
        'q_num' => 14,
        'question' => 'An electrical safety device containing a thin wire designed to melt and open a circuit when excessive current flows is a:',
        'option_a' => 'Transformer',
        'option_b' => 'Fuse',
        'option_c' => 'Capacitor',
        'option_d' => 'Voltmeter',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A fuse is a sacrificial protective device that melts when current exceeds its specified rating, cutting off dangerous current.',
      ),
      14 => 
      array (
        'chapter' => 4,
        'q_num' => 15,
        'question' => 'Electric current (I) is defined as the rate of flow of electric charge (Q). Which formula correctly expresses this relationship?',
        'option_a' => 'I = Q / t)',
        'option_b' => 'I = Q × t',
        'option_c' => 'I = t / Q)',
        'option_d' => 'I = Q² t',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Electric current is the time rate of charge flow: I = Q / Δ t), measured in Amperes (A = C/s).',
      ),
    ),
  ),
  5 => 
  array (
    'chapter_title' => 'Magnetism',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 5,
        'q_num' => 1,
        'question' => 'Which fundamental property applies to all magnets?',
        'option_a' => 'Like magnetic poles attract, unlike poles repel',
        'option_b' => 'Like magnetic poles repel, unlike poles attract',
        'option_c' => 'Magnetic monopoles exist isolated in nature',
        'option_d' => 'Magnets attract non-magnetic plastics',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The fundamental law of magnetism states that like poles (N-N or S-S) repel each other, while opposite poles (N-S) attract.',
      ),
      1 => 
      array (
        'chapter' => 5,
        'q_num' => 2,
        'question' => 'Outside a bar magnet, magnetic field lines are conventionally drawn pointing:',
        'option_a' => 'From South pole to North pole',
        'option_b' => 'From North pole to South pole',
        'option_c' => 'Radially outward in all directions',
        'option_d' => 'In straight parallel lines to infinity',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'By convention, external magnetic field lines emerge from the North pole and enter the South pole, forming continuous closed loops.',
      ),
      2 => 
      array (
        'chapter' => 5,
        'q_num' => 3,
        'question' => 'Hans Christian Oersted discovered in 1820 that:',
        'option_a' => 'A moving magnet induces a voltage in a coil',
        'option_b' => 'An electric current produces a magnetic field around it',
        'option_c' => 'Light is an electromagnetic wave',
        'option_d' => 'Gravity affects magnetic poles',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Oersted discovered electromagnetism when he noticed a compass needle deflected near a current-carrying wire.',
      ),
      3 => 
      array (
        'chapter' => 5,
        'q_num' => 4,
        'question' => 'The magnetic force (F) exerted on a charge q moving with velocity v at an angle θ to magnetic field B is calculated as:',
        'option_a' => 'F = q v B sinθ',
        'option_b' => 'F = q v / B)',
        'option_c' => 'F = q v B cosθ',
        'option_d' => 'F = I L / B)',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The Lorentz magnetic force formula is F = q v B sinθ. Maximum force occurs when velocity is perpendicular to the field (θ = 90°).',
      ),
      4 => 
      array (
        'chapter' => 5,
        'q_num' => 5,
        'question' => 'What is the magnetic force experienced by a charge moving PARALLEL (θ = 0°) to a magnetic field?',
        'option_a' => 'Maximum force (q v B)',
        'option_b' => 'Zero',
        'option_c' => 'Half of maximum force',
        'option_d' => 'Infinite force',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Since sin(0°) = 0, F = q v B sin(0°) = 0. No magnetic force acts when motion is parallel to magnetic field lines.',
      ),
      5 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'What is the SI unit of Magnetic Field Strength (B)?',
        'option_a' => 'Weber (Wb)',
        'option_b' => 'Tesla (T)',
        'option_c' => 'Ampere per meter (A/m)',
        'option_d' => 'Henry (H)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Magnetic flux density/field strength B is measured in Tesla (T), where 1 T = 1 N/(A · m).',
      ),
      6 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'Faraday\'s Law of Electromagnetic Induction states that the induced Electromotive Force (EMF (E) in a coil is proportional to the:',
        'option_a' => 'Static magnetic field strength',
        'option_b' => 'Time rate of change of magnetic flux (Δ Φ_B / Δ t)',
        'option_c' => 'Electrical resistance of the wire',
        'option_d' => 'Temperature of the magnet',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Faraday\'s Law states EMF (E) = -N Δ Φ_B / Δ t), where induced EMF equals the rate of change of magnetic flux.',
      ),
      7 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'Lenz\'s Law states that the direction of an induced current always:',
        'option_a' => 'Aids the change in magnetic flux producing it',
        'option_b' => 'Opposes the change in magnetic flux that causes it',
        'option_c' => 'Points towards the geographic north pole',
        'option_d' => 'Remains static regardless of flux change',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Lenz\'s Law reflects energy conservation: the induced magnetic field opposes the original flux change that created it.',
      ),
      8 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'A transformer has Nₚ = 100 primary turns and Nₛ = 500 secondary turns. If input primary voltage is Vₚ = 120 V, what is the output secondary voltage (Vₛ)?',
        'option_a' => '24 V',
        'option_b' => '600 V',
        'option_c' => '120 V',
        'option_d' => '500 V',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Transformer formula: Vₛ / Vₚ) = Nₛ / Nₚ) ⇒ Vₛ = 120 × ≤ ft(500 / 100)) = 120 × 5 = 600 V.',
      ),
      9 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'A transformer that increases voltage (Vₛ > Vₚ) is classified as a:',
        'option_a' => 'Step-down transformer',
        'option_b' => 'Step-up transformer',
        'option_c' => 'Auto-transformer',
        'option_d' => 'Isolation transformer',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A step-up transformer has more secondary turns than primary turns (Nₛ > Nₚ), increasing secondary voltage.',
      ),
      10 => 
      array (
        'chapter' => 5,
        'q_num' => 11,
        'question' => 'In an ideal lossless transformer (P_primary = P_secondary), increasing secondary voltage causes secondary current to:',
        'option_a' => 'Increase proportionally',
        'option_b' => 'Decrease proportionally',
        'option_c' => 'Remain unchanged',
        'option_d' => 'Drop to zero',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Since Vₚ Iₚ = Vₛ Iₛ, stepping up voltage decreases output current proportionally to conserve power.',
      ),
      11 => 
      array (
        'chapter' => 5,
        'q_num' => 12,
        'question' => 'A long helical coil of wire wrapped tightly into many cylindrical loops is called a:',
        'option_a' => 'Solenoid',
        'option_b' => 'Rheostat',
        'option_c' => 'Dielectric',
        'option_d' => 'Galvanometer',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'A solenoid is a coil of wire designed to produce a strong uniform magnetic field inside its core when carrying electric current.',
      ),
      12 => 
      array (
        'chapter' => 5,
        'q_num' => 13,
        'question' => 'Which materials exhibit strong magnetic properties due to aligned atomic magnetic domains?',
        'option_a' => 'Diamagnetic materials (e.g., copper, gold)',
        'option_b' => 'Ferromagnetic materials (e.g., iron, nickel, cobalt)',
        'option_c' => 'Paramagnetic materials (e.g., aluminum)',
        'option_d' => 'Non-magnetic plastics',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Ferromagnetic materials contain magnetic domains that easily align under an external field, producing strong magnetization.',
      ),
      13 => 
      array (
        'chapter' => 5,
        'q_num' => 14,
        'question' => 'An electric motor is an electromechanical device that converts:',
        'option_a' => 'Mechanical energy into electrical energy',
        'option_b' => 'Electrical energy into mechanical energy',
        'option_c' => 'Chemical energy into thermal energy',
        'option_d' => 'Nuclear energy into light energy',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Electric motors utilize magnetic forces on current-carrying conductors to convert electrical power into rotational mechanical work.',
      ),
      14 => 
      array (
        'chapter' => 5,
        'q_num' => 15,
        'question' => 'An electric generator converts mechanical energy into electrical energy based on the principle of:',
        'option_a' => 'Electrostatic attraction',
        'option_b' => 'Electromagnetic induction',
        'option_c' => 'Thermoelectric effect',
        'option_d' => 'Photoelectric effect',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Generators rotate coils within a magnetic field to induce electromotive force (EMF) via electromagnetic induction.',
      ),
    ),
  ),
  6 => 
  array (
    'chapter_title' => 'Electromagnetic Waves and Geometrical Optics',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 6,
        'q_num' => 1,
        'question' => 'What is the constant speed of all electromagnetic waves in a vacuum (c)?',
        'option_a' => '3.0 × 10⁸ m/s',
        'option_b' => '340 m/s',
        'option_c' => '1.5 × 10⁸ m/s',
        'option_d' => '9.8 m/s²',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'All electromagnetic waves travel through a vacuum at the speed of light: c ≈ 3.0 × 10⁸ m/s.',
      ),
      1 => 
      array (
        'chapter' => 6,
        'q_num' => 2,
        'question' => 'Which list correctly orders electromagnetic waves in order of INCREASING frequency (and photon energy)?',
        'option_a' => 'Gamma rays, X-rays, Visible, Radio waves',
        'option_b' => 'Radio waves, Microwaves, Visible light, X-rays, Gamma rays',
        'option_c' => 'Ultraviolet, Infrared, Microwaves, Radio waves',
        'option_d' => 'X-rays, Visible light, Microwaves, Radio waves',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In order of increasing frequency: Radio waves < Microwaves < Infrared < Visible < Ultraviolet < X-rays < Gamma rays.',
      ),
      2 => 
      array (
        'chapter' => 6,
        'q_num' => 3,
        'question' => 'According to the Law of Reflection, the angle of reflection (θ_r) is equal to the angle of incidence (θ_i). Both angles are measured relative to the:',
        'option_a' => 'Mirror surface plane',
        'option_b' => 'Normal line perpendicular to the reflecting surface',
        'option_c' => 'Horizontal ground',
        'option_d' => 'Refracted ray line',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Incidence and reflection angles are measured relative to the normal—an imaginary line perpendicular to the surface at the point of incidence.',
      ),
      3 => 
      array (
        'chapter' => 6,
        'q_num' => 4,
        'question' => 'The index of refraction (n) of a medium is calculated as n = c / v). If light travels at v = 2.0 × 10⁸ m/s in glass, what is the refractive index of the glass?',
        'option_a' => '1.0',
        'option_b' => '1.5',
        'option_c' => '2.0',
        'option_d' => '0.67',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'n = c / v) = 3.0 × 10⁸ / 2.0 × 10⁸) = 1.5. Refractive index is unitless.',
      ),
      4 => 
      array (
        'chapter' => 6,
        'q_num' => 5,
        'question' => 'Snell\'s Law of Refraction relates the angles and refractive indices at a boundary using which equation?',
        'option_a' => 'n_1 sinθ_1 = n_2 sinθ_2',
        'option_b' => 'n_1 cosθ_1 = n_2 cosθ_2',
        'option_c' => 'n_1 / sinθ_1) = n_2 / sinθ_2',
        'option_d' => 'n_1 + n_2 = θ_1 + θ_2',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Snell\'s Law states n_1 sinθ_1 = n_2 sinθ_2, governing how light bends when passing between media.',
      ),
      5 => 
      array (
        'chapter' => 6,
        'q_num' => 6,
        'question' => 'Total Internal Reflection occurs ONLY when light travels from a medium of:',
        'option_a' => 'Lower refractive index to higher refractive index at any angle',
        'option_b' => 'Higher refractive index to lower refractive index at an angle greater than the critical angle (θ > θ_c)',
        'option_c' => 'Vacuum to water along the normal line',
        'option_d' => 'Air into diamond at 0° angle',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Total internal reflection requires n_1 > n_2 and an incidence angle exceeding the critical angle (θ_i > θ_c).',
      ),
      6 => 
      array (
        'chapter' => 6,
        'q_num' => 7,
        'question' => 'What is the critical angle formula (sinθ_c) for light traveling from medium 1 (n_1) into medium 2 (n_2, where n_1 > n_2)?',
        'option_a' => 'sinθ_c = n_2 / n_1)',
        'option_b' => 'sinθ_c = n_1 / n_2)',
        'option_c' => 'sinθ_c = n_1 × n_2',
        'option_d' => 'sinθ_c = n_1 - n_2',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Setting θ_2 = 90° in Snell\'s Law (n_1 sinθ_c = n_2 sin 90°) yields sinθ_c = n_2 / n_1).',
      ),
      7 => 
      array (
        'chapter' => 6,
        'q_num' => 8,
        'question' => 'The thin lens equation relating focal length (f), object distance (dₒ), and image distance (dᵢ) is:',
        'option_a' => '1 / f) = 1 / dₒ) + 1 / dᵢ',
        'option_b' => 'f = dₒ + dᵢ',
        'option_c' => '1 / f) = 1 / dₒ) - 1 / dᵢ',
        'option_d' => 'f = dₒ × dᵢ',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The thin lens formula is 1 / f) = 1 / dₒ) + 1 / dᵢ).',
      ),
      8 => 
      array (
        'chapter' => 6,
        'q_num' => 9,
        'question' => 'An object is placed 20 cm (dₒ = 20) in front of a converging lens of focal length f = 10 cm. Where is the image formed (dᵢ)?',
        'option_a' => '10 cm behind the lens',
        'option_b' => '20 cm behind the lens',
        'option_c' => '5 cm behind the lens',
        'option_d' => '30 cm behind the lens',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Using 1 / dᵢ) = 1 / f) - 1 / dₒ) = 1 / 10) - 1 / 20) = 1 / 20) ⇒ dᵢ = 20 cm.',
      ),
      9 => 
      array (
        'chapter' => 6,
        'q_num' => 10,
        'question' => 'Which type of lens is thicker at the center than at the edges and converges parallel light rays to a real focus point?',
        'option_a' => 'Concave (diverging) lens',
        'option_b' => 'Convex (converging) lens',
        'option_c' => 'Plane parallel glass plate',
        'option_d' => 'Cylindrical concave lens',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A convex lens is thicker in the middle and converges incident parallel light rays toward a real focal point.',
      ),
      10 => 
      array (
        'chapter' => 6,
        'q_num' => 11,
        'question' => 'A vision defect where a person can see distant objects clearly but struggles to focus on near objects is called hyperopia (farsightedness). It is corrected using:',
        'option_a' => 'Convex (converging) lenses',
        'option_b' => 'Concave (diverging) lenses',
        'option_c' => 'Cylindrical lenses only',
        'option_d' => 'Flat plane glass',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Hyperopia occurs when light focuses behind the retina; convex lenses add convergence to focus light properly on the retina.',
      ),
      11 => 
      array (
        'chapter' => 6,
        'q_num' => 12,
        'question' => 'Myopia (nearsightedness) causes light rays from distant objects to focus IN FRONT of the retina. It is corrected using:',
        'option_a' => 'Convex lenses',
        'option_b' => 'Concave (diverging) lenses',
        'option_c' => 'Bifocal convex lenses',
        'option_d' => 'Prism lenses',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Concave lenses diverge incoming parallel light rays slightly before entering the eye, shifting the focal point back onto the retina.',
      ),
      12 => 
      array (
        'chapter' => 6,
        'q_num' => 13,
        'question' => 'A convex spherical mirror (like a car side-view mirror) always forms images that are:',
        'option_a' => 'Real, inverted, and magnified',
        'option_b' => 'Virtual, upright, and diminished (smaller)',
        'option_c' => 'Real, upright, and same size',
        'option_d' => 'Virtual, inverted, and enlarged',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Convex mirrors diverge light rays, producing virtual, upright, and reduced images with a wider field of view.',
      ),
      13 => 
      array (
        'chapter' => 6,
        'q_num' => 14,
        'question' => 'The separation of white light into its component spectral colors when passing through a glass prism is called:',
        'option_a' => 'Interference',
        'option_b' => 'Diffraction',
        'option_c' => 'Dispersion',
        'option_d' => 'Polarization',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Dispersion occurs because different wavelengths (colors) of light experience slightly different refractive indices in glass.',
      ),
      14 => 
      array (
        'chapter' => 6,
        'q_num' => 15,
        'question' => 'Optical fibers transmit light signals over long distances with minimal loss by utilizing the principle of:',
        'option_a' => 'Diffuse reflection',
        'option_b' => 'Total Internal Reflection',
        'option_c' => 'Double refraction',
        'option_d' => 'Polarized absorption',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Optical fibers trap light inside a high-index core surrounded by lower-index cladding using continuous Total Internal Reflection.',
      ),
    ),
  ),
);
    }
}

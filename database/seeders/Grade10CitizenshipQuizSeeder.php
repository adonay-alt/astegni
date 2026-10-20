<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Grade10CitizenshipQuizSeeder extends Seeder
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

            $subject = Subject::where('grade_id', $grade->id)->where('title', 'Citizenship Education')->first();
            if (!$subject) {
                $subject = Subject::where('grade_id', $grade->id)->where('title', 'LIKE', '%Citizen%')->first();
            }
            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Citizenship Education',
                    'description' => 'Grade 10 Citizenship Education Curriculum',
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
                        'description' => "Assessment quiz for Grade 10 Citizenship Education Chapter {$chapterNumber}.",
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
    'chapter_title' => 'Democracy and Democratization',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 1,
        'q_num' => 1,
        'question' => 'What are the two Greek words from which the term "democracy" is derived?',
        'option_a' => 'Demo and Polis',
        'option_b' => 'Demo and Kratos',
        'option_c' => 'Demos and Archy',
        'option_d' => 'Kratia and Ethnos',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The word democracy comes from the Greek words "Demo" (meaning people) and "Kratos" (meaning power or rule). Together, they denote government or rule by the people.',
      ),
      1 => 
      array (
        'chapter' => 1,
        'q_num' => 2,
        'question' => 'How is democratization best defined?',
        'option_a' => 'A sudden, overnight political overhaul',
        'option_b' => 'A transition process toward a more democratic political system',
        'option_c' => 'The complete removal of all government institutions',
        'option_d' => 'A rule established exclusively by autocratic regimes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Democratization is a dynamic, complex, and long-term political transition through which a political system moves toward democracy or becomes more democratic.',
      ),
      2 => 
      array (
        'chapter' => 1,
        'q_num' => 3,
        'question' => 'Which actor in the democratization process primarily serves as an alternative program generator and vector to attain political power through free and fair elections?',
        'option_a' => 'Civil societies',
        'option_b' => 'Interest groups',
        'option_c' => 'Political parties',
        'option_d' => 'Mass media',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Political parties function as vectors of democracy by competing in elections to attain state power and formulating alternative practical ideas/programs for the government.',
      ),
      3 => 
      array (
        'chapter' => 1,
        'q_num' => 4,
        'question' => 'What main objective distinguishes interest groups from political parties?',
        'option_a' => 'Interest groups seek to directly run and win elections.',
        'option_b' => 'Interest groups exist solely to organize national referendums.',
        'option_c' => 'Interest groups aim to influence government policy without seeking state power.',
        'option_d' => 'Interest groups hold total control over national military forces.',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Interest groups (or pressure groups) are organized bodies autonomous from political parties that aim to influence government policy and decisions rather than winning state power.',
      ),
      4 => 
      array (
        'chapter' => 1,
        'q_num' => 5,
        'question' => 'For mass media to effectively contribute to building a democratic culture, it must be:',
        'option_a' => 'State-owned and state-directed',
        'option_b' => 'Restricted to print materials only',
        'option_c' => 'Free, independent, and provided with adequate resources',
        'option_d' => 'Limited in the amount of news it broadcasts',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Media plays a central role in educating citizens and nurturing democratic culture, provided it operates freely, independently, and has sufficient resources to inform the public objectively.',
      ),
      5 => 
      array (
        'chapter' => 1,
        'q_num' => 6,
        'question' => 'Which of the following is NOT a duty performed by an Independent National Electoral Commission (INEC)?',
        'option_a' => 'Registering eligible voters',
        'option_b' => 'Conducting elections and referendums',
        'option_c' => 'Passing judicial sentences on civil cases',
        'option_d' => 'Delimiting electoral constituencies',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'An INEC\'s main responsibilities include organizing, supervising, and conducting elections/referendums, registering political parties/voters, and delimiting boundaries—not adjudicating civil cases.',
      ),
      6 => 
      array (
        'chapter' => 1,
        'q_num' => 7,
        'question' => 'Which democratic institution acts as a watchdog over public policies and provides expert advice to the government?',
        'option_a' => 'Electoral Commission',
        'option_b' => 'Pressure groups',
        'option_c' => 'Monarchy',
        'option_d' => 'Oligarchic councils',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Pressure groups serve as watchdogs over public policies, offer expert advice, and bridge communication between citizens and government officials.',
      ),
      7 => 
      array (
        'chapter' => 1,
        'q_num' => 8,
        'question' => 'Social transformation at the societal level involves:',
        'option_a' => 'Small-scale updates to an individual\'s personal resume',
        'option_b' => 'Large-scale structural changes across cultural, social, economic, and political spheres',
        'option_c' => 'Restricting all civic dialog and community interaction',
        'option_d' => 'Enforcing strict, unchangeable traditional social hierarchies',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Societal social transformation refers to large-scale restructuring across all aspects of collective life, including culture, politics, economy, and social relations.',
      ),
      8 => 
      array (
        'chapter' => 1,
        'q_num' => 9,
        'question' => 'What is active civic engagement characterized by?',
        'option_a' => 'Complete withdrawal from public matters',
        'option_b' => 'Commitment to participate in and contribute to local or national development',
        'option_c' => 'Strict obedience to military orders without discussion',
        'option_d' => 'Blind acceptance of all information provided on social media',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Civic engagement encompasses active participation in civic, political, and community life to improve one\'s neighborhood, community, or nation.',
      ),
      9 => 
      array (
        'chapter' => 1,
        'q_num' => 10,
        'question' => 'The term "social capital" refers to:',
        'option_a' => 'The total money stored in national reserve banks',
        'option_b' => 'The value derived from social networks, trust, and mutual cooperation',
        'option_c' => 'Physical infrastructures like highways and bridges',
        'option_d' => 'The total taxation collected from private companies',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Social capital represents the network of connections between people, fostering benefits such as trust, reciprocity, information sharing, and cooperation.',
      ),
      10 => 
      array (
        'chapter' => 1,
        'q_num' => 11,
        'question' => 'What are the three core values central to the concept of democracy?',
        'option_a' => 'Authority, Power, Monarchy',
        'option_b' => 'Liberty, Justice, Equality',
        'option_c' => 'Bureaucracy, Wealth, Force',
        'option_d' => 'Centralization, Sovereignty, Hegemony',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Democratic theory revolves around three core foundational values: Liberty (freedom), Justice (fairness), and Equality.',
      ),
      11 => 
      array (
        'chapter' => 1,
        'q_num' => 12,
        'question' => 'Procedural justice is primarily concerned with:',
        'option_a' => 'Fair distribution of societal benefits and burdens',
        'option_b' => 'Proportional responses to correct injuries and wrongs',
        'option_c' => 'Ensuring impartial and open procedures for decision-making',
        'option_d' => 'Direct distribution of financial subsidies to families',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Procedural justice requires that methods used to gather information and make legal or social decisions adhere to impartiality, fairness, and openness.',
      ),
      12 => 
      array (
        'chapter' => 1,
        'q_num' => 13,
        'question' => 'Eliminating discrimination based on class, sex, ethnicity, or race relates to which aspect of democracy?',
        'option_a' => 'Economic aspect',
        'option_b' => 'Political aspect',
        'option_c' => 'Social aspect',
        'option_d' => 'Military aspect',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'The social aspect of democracy mandates that all human beings are treated as equals regardless of class, sex, color, or ethnicity.',
      ),
      13 => 
      array (
        'chapter' => 1,
        'q_num' => 14,
        'question' => 'Which right falls under the political aspect of democracy?',
        'option_a' => 'The right to fair wages and leisure',
        'option_b' => 'The right to contest in elections and vote',
        'option_c' => 'The right to absolute wealth equalisation',
        'option_d' => 'The right to be free from natural disasters',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The political aspect guarantees democratic political privileges, including voting, running for office, holding public office, and freedom of speech/association.',
      ),
      14 => 
      array (
        'chapter' => 1,
        'q_num' => 15,
        'question' => 'Fair distribution of national resources and equitable access to economic opportunities represent:',
        'option_a' => 'Economic equality',
        'option_b' => 'Corrective justice',
        'option_c' => 'Social hierarchy',
        'option_d' => 'Authoritarianism',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Economic equality implies that all citizens are entitled to fair assessment, access, and distribution of national resources and financial opportunities.',
      ),
    ),
  ),
  2 => 
  array (
    'chapter_title' => 'Citizens in the Digital Technology Age',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 2,
        'q_num' => 1,
        'question' => 'Moderate and balanced use of digital technology generally leads to:',
        'option_a' => 'Complete social isolation',
        'option_b' => 'Positive emotional and functional benefits for citizens',
        'option_c' => 'Severe digital eyestrain and psychological disorders',
        'option_d' => 'The total destruction of national culture',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Moderate and purposeful technology use yields positive outcomes, such as enhancing well-being, connectivity, and communication.',
      ),
      1 => 
      array (
        'chapter' => 2,
        'q_num' => 2,
        'question' => 'How can digital technology assist workers in regulating workplace emotions?',
        'option_a' => 'By completely replacing human workers with automated machinery',
        'option_b' => 'By providing flexible remote work and digital tools that minimize heated face-to-face friction',
        'option_c' => 'By monitoring employee heart rates continuously',
        'option_d' => 'By eliminating all forms of personal leave',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Digital remote platforms allow employees and managers to coordinate work without face-to-face interaction, reducing potential emotional workplace conflicts.',
      ),
      2 => 
      array (
        'chapter' => 2,
        'q_num' => 3,
        'question' => 'Experiencing events primarily through digital photos, videos, and social media rather than directly in person is called:',
        'option_a' => 'Primary engagement',
        'option_b' => 'Secondhand living',
        'option_c' => 'Authentic socialization',
        'option_d' => 'Digital citizenship',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Secondhand living describes a state where individuals filter life through screens, cameras, and media uploads instead of directly experiencing the moment.',
      ),
      3 => 
      array (
        'chapter' => 2,
        'q_num' => 4,
        'question' => 'Anonymity in online spaces can negatively affect social interactions by:',
        'option_a' => 'Encouraging ethical citizenship',
        'option_b' => 'Increasing anti-social behaviors such as cyberbullying, trolling, and fraud',
        'option_c' => 'Guaranteeing absolute truthfulness in all conversations',
        'option_d' => 'Preventing any identity theft',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'When users hide behind pseudonyms, the lack of immediate consequences often leads to negative anti-social acts like harassment, trolling, and scamming.',
      ),
      4 => 
      array (
        'chapter' => 2,
        'q_num' => 5,
        'question' => 'Which social benefit is provided by digital technologies in the education sector?',
        'option_a' => 'Elimination of all research databases',
        'option_b' => 'Access to virtual laboratories, academic sharing, and remote learning platforms',
        'option_c' => 'Automatic awarding of university degrees without exams',
        'option_d' => 'Complete removal of teacher-student interaction',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Educational technologies allow learners to conduct online research, access virtual labs, collaborate on projects, and acquire global knowledge easily.',
      ),
      5 => 
      array (
        'chapter' => 2,
        'q_num' => 6,
        'question' => 'Digitalization in the banking sector allows customers to:',
        'option_a' => 'Print real legal tender notes at home',
        'option_b' => 'Perform remote money transfers, view statements, and pay bills online',
        'option_c' => 'Avoid paying for goods and services completely',
        'option_d' => 'Secure physical cash inside digital emails',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Online banking platforms enable users to conduct financial operations like bill payments and wire transfers securely from mobile devices or computers.',
      ),
      6 => 
      array (
        'chapter' => 2,
        'q_num' => 7,
        'question' => 'Which physical health issue is commonly linked to excessive screen usage?',
        'option_a' => 'Improved physical posture',
        'option_b' => 'Digital eyestrain and musculoskeletal strain',
        'option_c' => 'Enhanced cardiovascular resistance',
        'option_d' => 'Absolute immunity from infectious diseases',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Long hours in front of digital devices can lead to eyestrain, headaches, blurred vision, and posture-related neck or shoulder issues.',
      ),
      7 => 
      array (
        'chapter' => 2,
        'q_num' => 8,
        'question' => 'By exposing individuals to diverse global perspectives and facts, digital technology aids in developing:',
        'option_a' => 'Uncritical acceptance of rumors',
        'option_b' => 'Critical thinking skills',
        'option_c' => 'Total dogmatism',
        'option_d' => 'Extreme localized prejudice',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Accessing diverse sources empowers individuals to analyze claims, verify facts, and evaluate ideas rather than accepting opinions blindly.',
      ),
      8 => 
      array (
        'chapter' => 2,
        'q_num' => 9,
        'question' => 'What happens when a digital user faces conflicting ethical ideas online that contradict their local norms?',
        'option_a' => 'Universal consensus',
        'option_b' => 'Ethical dilemma',
        'option_c' => 'Total agreement',
        'option_d' => 'Instant enlightenment',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Exposure to conflicting cultural or philosophical viewpoints online can lead users into an ethical dilemma regarding which values to follow.',
      ),
      9 => 
      array (
        'chapter' => 2,
        'q_num' => 10,
        'question' => 'In digital ethics, "Etiquette" refers to:',
        'option_a' => 'Knowledge of coding languages',
        'option_b' => 'Understanding how one\'s online behavior affects others and maintaining respectful standards',
        'option_c' => 'Downloading copyrighted files without paying',
        'option_d' => 'Installing hardware firewalls on a home router',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Digital etiquette involves demonstrating respectful, courteous, and appropriate standard behavior online toward fellow network users.',
      ),
      10 => 
      array (
        'chapter' => 2,
        'q_num' => 11,
        'question' => 'Failing to give proper credit to an original author when copying online material for a project is an example of violating:',
        'option_a' => 'Digital security',
        'option_b' => 'Academic integrity through plagiarism',
        'option_c' => 'Hardware maintenance',
        'option_d' => 'Network speed rules',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Copying text or ideas directly from the web without proper citation constitutes plagiarism, an unethical academic practice.',
      ),
      11 => 
      array (
        'chapter' => 2,
        'q_num' => 12,
        'question' => 'Which ethical digital principle highlights installing anti-virus software and protecting personal identities?',
        'option_a' => 'Netiquette',
        'option_b' => 'Security',
        'option_c' => 'Piracy',
        'option_d' => 'Alienation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Digital security involves safeguarding computing devices and personal identity against malware, viruses, unauthorized breaches, and harm.',
      ),
      12 => 
      array (
        'chapter' => 2,
        'q_num' => 13,
        'question' => 'How does digital technology expand employment opportunities for young people?',
        'option_a' => 'By banning all international businesses',
        'option_b' => 'By facilitating remote work opportunities and flexible global freelance connections',
        'option_c' => 'By replacing all manual labor positions immediately',
        'option_d' => 'By making online job searching illegal',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Internet connectivity enables remote employment, allowing young citizens to apply for and perform work globally from home.',
      ),
      13 => 
      array (
        'chapter' => 2,
        'q_num' => 14,
        'question' => 'Unlawful distribution and copying of copyrighted audio, movies, or e-books is known as:',
        'option_a' => 'Digital literacy',
        'option_b' => 'Digital media piracy',
        'option_c' => 'Ethical networking',
        'option_d' => 'Data archiving',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Digital piracy involves unauthorized copying, sharing, or distribution of copyrighted intellectual property.',
      ),
      14 => 
      array (
        'chapter' => 2,
        'q_num' => 15,
        'question' => 'What is "Identity Theft"?',
        'option_a' => 'Legally changing one\'s official name in court',
        'option_b' => 'Fraudulent use of another person\'s personal identity or credentials',
        'option_c' => 'Creating a backup of system software',
        'option_d' => 'Forgetting a social media profile password',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Identity theft occurs when someone fraudulently assumes another person\'s identity or private information, often for financial gain or deception.',
      ),
    ),
  ),
  3 => 
  array (
    'chapter_title' => 'Understanding Good Governance',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 3,
        'q_num' => 1,
        'question' => 'Good governance is best described as:',
        'option_a' => 'The exclusive rule of political elites without public input',
        'option_b' => 'Efficient, accountable, transparent, and fair management of public affairs and resources',
        'option_c' => 'Arbitrary decision-making by military courts',
        'option_d' => 'Governing strictly without formal legal frameworks',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Good governance refers to processes where public institutions conduct public affairs efficiently, manage public resources responsibly, and remain accountable to citizens.',
      ),
      1 => 
      array (
        'chapter' => 3,
        'q_num' => 2,
        'question' => 'The core principle of "Accountability" requires public officials to:',
        'option_a' => 'Act according to personal preferences',
        'option_b' => 'Be answerable to the public for their actions and official decisions',
        'option_c' => 'Hide government budgets from citizens',
        'option_d' => 'Maintain lifelong positions without public evaluation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Accountability obligates public officials to take responsibility for their duties and answer to the citizens regarding public decisions.',
      ),
      2 => 
      array (
        'chapter' => 3,
        'q_num' => 3,
        'question' => 'Transparency in public governance ensures that:',
        'option_a' => 'Government records remain completely top secret',
        'option_b' => 'Information is accessible, freely available, and rules are clearly followed',
        'option_c' => 'Laws are applied based on social class',
        'option_d' => 'Citizens are excluded from legislative discussions',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Transparency means that administrative choices, laws, and budget details are clear and directly accessible to affected citizens.',
      ),
      3 => 
      array (
        'chapter' => 3,
        'q_num' => 4,
        'question' => 'Which principle ensures that all members of society—especially vulnerable groups—feel included and have opportunities to maintain well-being?',
        'option_a' => 'Arbitrary power',
        'option_b' => 'Equity and Inclusiveness',
        'option_c' => 'Red-tapism',
        'option_d' => 'Exclusivity',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Equity and inclusiveness mandate that all groups are provided fair opportunities to improve or maintain their standard of living.',
      ),
      4 => 
      array (
        'chapter' => 3,
        'q_num' => 5,
        'question' => 'A government institution that responds to stakeholder requests within a reasonable and reasonable timeframe is demonstrating:',
        'option_a' => 'Rigidity',
        'option_b' => 'Responsiveness',
        'option_c' => 'Bureaucratic delay',
        'option_d' => 'Autocracy',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Responsiveness requires institutions to serve all legitimate stakeholders promptly and within a reasonable timeframe.',
      ),
      5 => 
      array (
        'chapter' => 3,
        'q_num' => 6,
        'question' => 'How does good governance contribute directly to economic development?',
        'option_a' => 'By granting monopolies to personal friends of government leaders',
        'option_b' => 'By enforcing the rule of law, protecting property rights, and allocating resources efficiently',
        'option_c' => 'By increasing administrative corruption',
        'option_d' => 'By avoiding public infrastructure projects',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Good governance protects property rights, ensures impartial legal enforcement, reduces corruption, and fosters equitable economic growth.',
      ),
      6 => 
      array (
        'chapter' => 3,
        'q_num' => 7,
        'question' => 'Good governance promotes long-term peace by:',
        'option_a' => 'Silencing minority populations',
        'option_b' => 'Providing inclusive channels for citizens to voice grievances and participate in governance',
        'option_c' => 'Spending national wealth exclusively on military armaments',
        'option_d' => 'Preventing civil society organizations from operating',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Including diverse social groups in governance and providing peaceful avenues to resolve disputes prevents social grievances and ensures political stability.',
      ),
      7 => 
      array (
        'chapter' => 3,
        'q_num' => 8,
        'question' => 'Why is rampant poverty considered a major challenge to good governance?',
        'option_a' => 'Poor citizens always refuse to follow national laws.',
        'option_b' => 'Struggling citizens often face social exclusion and struggle to make their voices heard by policymakers.',
        'option_c' => 'Poverty eliminates all forms of corruption automatically.',
        'option_d' => 'Poverty prevents state institutions from existing.',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Extreme poverty isolates individuals, forcing them to focus on immediate survival, which hinders their active participation in governance processes.',
      ),
      8 => 
      array (
        'chapter' => 3,
        'q_num' => 9,
        'question' => 'How does corruption damage the overall economic development of a country?',
        'option_a' => 'It balances national budgets smoothly.',
        'option_b' => 'It distorts economic systems, creates budget deficits, and misallocates public money.',
        'option_c' => 'It guarantees equal income for all working citizens.',
        'option_d' => 'It improves foreign investments rapidly.',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Corruption diverts resources away from public infrastructure and essential services, causing financial deficits and market distortions.',
      ),
      9 => 
      array (
        'chapter' => 3,
        'q_num' => 10,
        'question' => 'What outcome occurs when a government operates without transparency and restricts public feedback?',
        'option_a' => 'High social trust',
        'option_b' => 'Inefficient service delivery and lack of public empowerment',
        'option_c' => 'Rapid economic prosperity',
        'option_d' => 'Total elimination of administrative errors',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Lack of good governance leads to low responsiveness, public disenfranchisement, corruption, and sub-standard public service delivery.',
      ),
      10 => 
      array (
        'chapter' => 3,
        'q_num' => 11,
        'question' => 'What is the general definition of corruption?',
        'option_a' => 'Delivering public services ahead of scheduled deadlines',
        'option_b' => 'The misuse of public power or office for personal gain',
        'option_c' => 'Strictly abiding by constitutional directives',
        'option_d' => 'Volunteering in community development projects',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Corruption involves using entrusted authority or public office for illegal private advantages.',
      ),
      11 => 
      array (
        'chapter' => 3,
        'q_num' => 12,
        'question' => 'An official appointing their relative to a position over more qualified applicants is committing:',
        'option_a' => 'Embezzlement',
        'option_b' => 'Nepotism',
        'option_c' => 'Extortion',
        'option_d' => 'Arbitration',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Nepotism is a form of favoritism where an official uses authority to hire or privilege relatives.',
      ),
      12 => 
      array (
        'chapter' => 3,
        'q_num' => 13,
        'question' => 'Demanding money from a citizen under the threat of physical force or administrative harassment is termed:',
        'option_a' => 'Extortion',
        'option_b' => 'Fraud',
        'option_c' => 'Equity',
        'option_d' => 'Plagiarism',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Extortion involves obtaining money, property, or favors through coercion, intimidation, or threats.',
      ),
      13 => 
      array (
        'chapter' => 3,
        'q_num' => 14,
        'question' => 'How can independent mass media contribute to combating corruption?',
        'option_a' => 'By hiding corrupt acts committed by high-ranking officials',
        'option_b' => 'By investigating, exposing, and reporting corrupt practices to the public',
        'option_c' => 'By promoting false economic propaganda',
        'option_d' => 'By replacing court judges in formal trials',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Investigative journalism uncovers fraudulent acts, informs citizens, and pressures oversight institutions to hold wrongdoers accountable.',
      ),
      14 => 
      array (
        'chapter' => 3,
        'q_num' => 15,
        'question' => 'Simplifying complex administrative procedures helps fight corruption because it:',
        'option_a' => 'Gives officials total discretionary power',
        'option_b' => 'Reduces bureaucratic confusion, delay, and opportunities for bribery requests',
        'option_c' => 'Prevents citizens from accessing government offices',
        'option_d' => 'Eliminates the need for public records',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Clear, simplified, and predictable administrative processes reduce unnecessary bureaucratic delays and eliminate spaces where bribery thrives. Here are the 15 multiple-choice questions for **Chapter 4**, **Chapter 5**, and **Chapter 6** based on the Grade 10 Citizenship Education material, along with their correct answers and explanations.',
      ),
    ),
  ),
  4 => 
  array (
    'chapter_title' => 'Peace and Indigenous Conflict Resolution Mechanisms',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 4,
        'q_num' => 1,
        'question' => 'What is the fundamental difference between conflict management and conflict resolution?',
        'option_a' => 'Conflict management completely eliminates the root causes, while resolution suppresses them.',
        'option_b' => 'Conflict management focuses on containing and controlling negative aspects, while resolution addresses underlying root causes.',
        'option_c' => 'Conflict management relies only on force, while resolution uses legal courts exclusively.',
        'option_d' => 'Conflict management is performed only by citizens, while resolution is done by foreign states.',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Conflict management aims to control, contain, and limit the destructive effects of a conflict, whereas conflict resolution goes deeper to resolve the underlying systemic and structural root causes.',
      ),
      1 => 
      array (
        'chapter' => 4,
        'q_num' => 2,
        'question' => 'Which non-violent conflict resolution mechanism involves a neutral third party who helps disputing parties communicate but lacks the power to enforce a binding decision?',
        'option_a' => 'Adjudication',
        'option_b' => 'Mediation',
        'option_c' => 'Arbitration',
        'option_d' => 'Litigation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Mediation involves a neutral third party (a mediator) who facilitates dialogue and assists parties in reaching a mutually acceptable voluntary agreement without forcing a decision.',
      ),
      2 => 
      array (
        'chapter' => 4,
        'q_num' => 3,
        'question' => 'How does indigenous conflict resolution differ from formal legal court systems?',
        'option_a' => 'Indigenous mechanisms emphasize community harmony and restoration rather than purely retributive punishment.',
        'option_b' => 'Indigenous mechanisms require written international legal treaties.',
        'option_c' => 'Indigenous mechanisms are always organized and funded directly by state governments.',
        'option_d' => 'Indigenous mechanisms only address conflicts involving economic corporations.',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Indigenous conflict resolution processes rely on traditional social norms, local elders, and restorative justice to rebuild community relationships rather than solely assigning legal blame or punishment.',
      ),
      3 => 
      array (
        'chapter' => 4,
        'q_num' => 4,
        'question' => 'In Ethiopia, traditional dispute resolution institutions (such as the *Gadaa* system\'s *Jaarsumma*) rely primarily on:',
        'option_a' => 'Modern electronic surveillance systems',
        'option_b' => 'Elders, shared social values, and community consensus',
        'option_c' => 'Federal supreme court verdicts',
        'option_d' => 'Armed peacekeeping forces',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Ethiopian indigenous institutions rely on respected community elders (*Jaarsolii*), oral traditions, moral authority, and communal consensus to reconcile conflicting parties.',
      ),
      4 => 
      array (
        'chapter' => 4,
        'q_num' => 5,
        'question' => 'Negative peace is defined as:',
        'option_a' => 'The complete absence of structural violence and inequality',
        'option_b' => 'The mere absence of direct physical violence or war',
        'option_c' => 'A state of perpetual social harmony across all social sectors',
        'option_d' => 'The presence of inclusive economic governance',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Negative peace refers strictly to the absence of direct physical violence or active conflict, even if underlying tensions or systemic inequalities remain unaddressed.',
      ),
      5 => 
      array (
        'chapter' => 4,
        'q_num' => 6,
        'question' => 'Positive peace is characterized by:',
        'option_a' => 'Active military deployment across urban areas',
        'option_b' => 'The presence of justice, social equality, and structural harmony',
        'option_c' => 'Strict censorship to prevent disagreements',
        'option_d' => 'The temporary suspension of civil rights during disputes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Positive peace goes beyond the lack of physical violence to include the presence of fair social structures, justice, equity, human rights, and well-being for all members of society.',
      ),
      6 => 
      array (
        'chapter' => 4,
        'q_num' => 7,
        'question' => 'What is the primary objective of "peacebuilding" in post-conflict societies?',
        'option_a' => 'To rebuild physical infrastructure without addressing social grievances',
        'option_b' => 'To transform underlying structures and foster reconciliation to prevent conflict re-occurrence',
        'option_c' => 'To establish permanent military rule over local communities',
        'option_d' => 'To isolate the nation completely from international diplomatic relations',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Peacebuilding focuses on long-term structural transformation, addressing root causes of conflict, promoting social justice, and fostering reconciliation to prevent violence from recurring.',
      ),
      7 => 
      array (
        'chapter' => 4,
        'q_num' => 8,
        'question' => 'Which of the following is a major cause of interpersonal conflict among youth?',
        'option_a' => 'Open communication and mutual respect',
        'option_b' => 'Misunderstandings, peer pressure, and resource competition',
        'option_c' => 'Strict adherence to institutional rules',
        'option_d' => 'Shared community goals',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Interpersonal disputes among youth often stem from poor communication, misinterpretations, peer pressure, rivalry, and competition over limited resources or recognition.',
      ),
      8 => 
      array (
        'chapter' => 4,
        'q_num' => 9,
        'question' => 'In alternative dispute resolution (ADR), arbitration differs from mediation because an arbitrator:',
        'option_a' => 'Listens to both sides and issues a binding or formal decision',
        'option_b' => 'Assists parties in drafting their own voluntary compromise',
        'option_c' => 'Refuses to hear evidence from either party',
        'option_d' => 'Encourages physical combat to resolve issues',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Unlike a mediator who only facilitates negotiation, an arbitrator acts like a private judge who reviews evidence and renders a formal (often legally binding) decision.',
      ),
      9 => 
      array (
        'chapter' => 4,
        'q_num' => 10,
        'question' => 'What role do women historically play in traditional Ethiopian peacebuilding processes?',
        'option_a' => 'They are completely excluded from all social mediation',
        'option_b' => 'They act as peacemakers and moral authority figures (e.g., through institutions like *Siqqee*)',
        'option_c' => 'They serve exclusively as combat strategists',
        'option_d' => 'They manage formal federal tax collections',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In many Ethiopian traditional societies, women\'s social institutions (such as *Siqqee* among the Oromo) play vital roles in demanding peace, mediating disputes, and stopping violence.',
      ),
      10 => 
      array (
        'chapter' => 4,
        'q_num' => 11,
        'question' => 'Which skill is essential for individuals participating in effective conflict resolution?',
        'option_a' => 'Aggressive confrontation',
        'option_b' => 'Active listening and empathy',
        'option_c' => 'Emotional suppression',
        'option_d' => 'Unilateral decision-making',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Effective conflict resolution requires active listening, clear communication, empathy, and open-mindedness to understand the interests of opposing parties.',
      ),
      11 => 
      array (
        'chapter' => 4,
        'q_num' => 12,
        'question' => 'Structural violence refers to:',
        'option_a' => 'Direct physical assaults between two individuals',
        'option_b' => 'Systematic social, economic, or political inequalities that harm individuals by preventing them from meeting basic needs',
        'option_c' => 'Natural disasters that damage buildings and bridges',
        'option_d' => 'Verbal arguments between family members',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Structural violence occurs when social, economic, or political arrangements indirectly harm people by depriving them of basic rights, equal opportunities, or necessary resources.',
      ),
      12 => 
      array (
        'chapter' => 4,
        'q_num' => 13,
        'question' => 'Which phase of peace operations immediately follows the cessation of active hostilties to stabilize the environment?',
        'option_a' => 'Disarmament, Demobilization, and Reintegration (DDR)',
        'option_b' => 'Total economic privatization',
        'option_c' => 'Constitutional dissolution',
        'option_d' => 'International border closure',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'DDR programs are essential early post-conflict peacebuilding steps designed to disarm ex-combatants, dismantle armed groups, and integrate individuals back into civilian society.',
      ),
      13 => 
      array (
        'chapter' => 4,
        'q_num' => 14,
        'question' => 'What is negotiation in the context of conflict resolution?',
        'option_a' => 'A process where a judge decides the case without consulting the parties',
        'option_b' => 'A direct discussion between disputing parties aimed at reaching an agreement without third-party intervention',
        'option_c' => 'A violent struggle to compel one party to surrender unconditionally',
        'option_d' => 'A public referendum conducted across the entire nation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Negotiation is a direct communication process between conflicting parties to discuss their differences and reach a mutually acceptable compromise without external intermediaries.',
      ),
      14 => 
      array (
        'chapter' => 4,
        'q_num' => 15,
        'question' => 'Which strategy promotes long-term national cohesion in a multicultural society?',
        'option_a' => 'Imposing one single culture on all minority groups',
        'option_b' => 'Inclusive dialogue, cultural tolerance, and equitable governance',
        'option_c' => 'Restricting the freedom of association for civic groups',
        'option_d' => 'Ignoring minority rights in national policy formulation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Long-term social stability in diversity relies on inclusive national dialogue, respect for human rights, cultural tolerance, and fair representation across all ethnic and social groups.',
      ),
    ),
  ),
  5 => 
  array (
    'chapter_title' => 'Federalism in Ethiopia',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 5,
        'q_num' => 1,
        'question' => 'Critical thinking is best defined as:',
        'option_a' => 'Accepting all provided information without question',
        'option_b' => 'The objective analysis, evaluation, and logical synthesis of information to form a reasoned judgment',
        'option_c' => 'Rejecting all opinions expressed by authority figures',
        'option_d' => 'Memorizing facts for examination purposes only',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Critical thinking involves systematically analyzing, evaluating, questioning assumptions, and synthesizing information to reach well-founded, logical conclusions.',
      ),
      1 => 
      array (
        'chapter' => 5,
        'q_num' => 2,
        'question' => 'Which of the following is a primary characteristic of a critical thinker?',
        'option_a' => 'Cognitive bias and dogmatism',
        'option_b' => 'Intellectual humility, curiosity, and open-mindedness',
        'option_c' => 'Hasty generalization',
        'option_d' => 'Reliance on emotional reactions',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Critical thinkers demonstrate intellectual humility (acknowledging what they do not know), curiosity, open-mindedness, and logical discipline.',
      ),
      2 => 
      array (
        'chapter' => 5,
        'q_num' => 3,
        'question' => 'What logical fallacy is committed when an argument attacks the person making it rather than addressing the argument itself?',
        'option_a' => 'Straw Man',
        'option_b' => 'Ad Hominem',
        'option_c' => 'Appeal to Emotion',
        'option_d' => 'Slippery Slope',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'An *Ad Hominem* fallacy occurs when someone rejects a claim by attacking the opponent\'s personal character or background instead of addressing the logical substance of their argument.',
      ),
      3 => 
      array (
        'chapter' => 5,
        'q_num' => 4,
        'question' => 'In problem-solving, what is the crucial first step?',
        'option_a' => 'Implementing an immediate solution',
        'option_b' => 'Clearly identifying and defining the problem',
        'option_c' => 'Evaluating alternative outcomes',
        'option_d' => 'Assigning blame to team members',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Before solutions can be formulated or evaluated, the problem must be accurately identified and defined to ensure efforts address the true root cause.',
      ),
      4 => 
      array (
        'chapter' => 5,
        'q_num' => 5,
        'question' => 'What is the "Straw Man" fallacy?',
        'option_a' => 'Arguing that a small first step will inevitably lead to extreme chain events',
        'option_b' => 'Misrepresenting or exaggerating an opponent\'s argument to make it easier to attack',
        'option_c' => 'Relying strictly on statistical data to make decisions',
        'option_d' => 'Claiming a statement is true simply because many people believe it',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A Straw Man fallacy involves distorting or oversimplifying an opponent\'s actual position so that it becomes easy to refute or ridicule.',
      ),
      5 => 
      array (
        'chapter' => 5,
        'q_num' => 6,
        'question' => 'Deductive reasoning moves from:',
        'option_a' => 'Specific observations to broad general theories',
        'option_b' => 'General premises or theories to specific logical conclusions',
        'option_c' => 'Emotional feelings to random guesses',
        'option_d' => 'Personal anecdotes to absolute historical facts',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Deductive reasoning starts with general statements or premises assumed to be true and applies them to reach a specific, necessary logical conclusion.',
      ),
      6 => 
      array (
        'chapter' => 5,
        'q_num' => 7,
        'question' => 'Inductive reasoning is characterized by:',
        'option_a' => 'Moving from specific instances to general broader conclusions',
        'option_b' => 'Guaranteeing absolute certainty in every conclusion reached',
        'option_c' => 'Rejecting empirical observation entirely',
        'option_d' => 'Deriving conclusions without any supporting evidence',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Inductive reasoning gathers specific facts, examples, or observations to formulate broader generalizations or probable conclusions.',
      ),
      7 => 
      array (
        'chapter' => 5,
        'q_num' => 8,
        'question' => 'Which cognitive barrier prevents individuals from critically evaluating information because they favor evidence that supports their pre-existing beliefs?',
        'option_a' => 'Egocentrism',
        'option_b' => 'Confirmation bias',
        'option_c' => 'Intellectual courage',
        'option_d' => 'Logical consistency',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Confirmation bias is the tendency to search for, interpret, and recall information in a way that confirms one\'s pre-existing beliefs while ignoring counter-evidence.',
      ),
      8 => 
      array (
        'chapter' => 5,
        'q_num' => 9,
        'question' => 'How does critical thinking support democratic decision-making among citizens?',
        'option_a' => 'By encouraging citizens to follow government propaganda uncritically',
        'option_b' => 'By enabling citizens to evaluate political promises, detect media manipulation, and make informed civic choices',
        'option_c' => 'By ensuring citizens vote only based on family traditions',
        'option_d' => 'By discouraging participation in public debates',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'In a democracy, critical thinking allows citizens to analyze political proposals, identify misinformation, make informed decisions during elections, and hold officials accountable.',
      ),
      9 => 
      array (
        'chapter' => 5,
        'q_num' => 10,
        'question' => 'When evaluating the credibility of an online information source, a critical thinker should look for:',
        'option_a' => 'Vibrant colors and catchy headlines',
        'option_b' => 'Author expertise, verifiable evidence, peer review, and absence of strong bias',
        'option_c' => 'The total number of likes on social media posts',
        'option_d' => 'Anonymous statements without source references',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Source credibility is determined by the author\'s qualifications, supporting objective evidence, cross-referencing capabilities, and lack of commercial or ideological bias.',
      ),
      10 => 
      array (
        'chapter' => 5,
        'q_num' => 11,
        'question' => 'What does "Intellectual Humility" mean in critical thinking?',
        'option_a' => 'Pretending to know the answer to every complex issue',
        'option_b' => 'Recognizing the limitations of one\'s own knowledge and being willing to revise beliefs in light of new evidence',
        'option_c' => 'Deferring blindly to superiors without questioning',
        'option_d' => 'Lacking confidence in basic reasoning abilities',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Intellectual humility involves acknowledging that one\'s knowledge is limited and being open to changing one\'s mind when presented with stronger evidence or better arguments.',
      ),
      11 => 
      array (
        'chapter' => 5,
        'q_num' => 12,
        'question' => 'Which problem-solving strategy involves breaking a complex problem into smaller, manageable sub-problems?',
        'option_a' => 'Trial and error',
        'option_b' => 'Deconstruction / Chunking',
        'option_c' => 'Blind guessing',
        'option_d' => 'Wishful thinking',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Deconstructing or breaking down a large, complex challenge into smaller components makes it easier to systematically analyze and solve each part.',
      ),
      12 => 
      array (
        'chapter' => 5,
        'q_num' => 13,
        'question' => 'An argument that assumes a conclusion is true simply because it has not been proven false commits which fallacy?',
        'option_a' => 'Appeal to Ignorance (*Argumentum ad Ignorantiam*)',
        'option_b' => 'False Dilemma',
        'option_c' => 'Red Herring',
        'option_d' => 'Circular Reasoning',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The Appeal to Ignorance fallacy claims that a premise must be true simply because it has not yet been proven false (or vice versa).',
      ),
      13 => 
      array (
        'chapter' => 5,
        'q_num' => 14,
        'question' => 'What is a "False Dilemma" (or False Dichotomy) fallacy?',
        'option_a' => 'Presenting only two extreme options when other valid alternatives exist',
        'option_b' => 'Using overly technical language to confuse an opponent',
        'option_c' => 'Repeating the same claim in different words as proof',
        'option_d' => 'Quoting a celebrity to prove a scientific point',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'A False Dilemma artificially limits choices to only two contrasting alternatives, ignoring other viable options or middle-ground solutions.',
      ),
      14 => 
      array (
        'chapter' => 5,
        'q_num' => 15,
        'question' => 'In the context of problem-solving, "Brainstorming" is used during which phase?',
        'option_a' => 'Final auditing',
        'option_b' => 'Generating a wide array of creative ideas and potential solutions',
        'option_c' => 'Assigning legal penalties',
        'option_d' => 'Writing official meeting minutes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Brainstorming is a creative technique used early in the problem-solving process to generate diverse potential solutions before narrowing them down and evaluating them.',
      ),
    ),
  ),
  6 => 
  array (
    'chapter_title' => 'Human Rights',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 6,
        'q_num' => 1,
        'question' => 'Constitutionalism is the political principle that:',
        'option_a' => 'State authority is unlimited and absolute',
        'option_b' => 'Government power is defined, limited, and bounded by law to protect citizens\' rights',
        'option_c' => 'The military holds ultimate authority over judicial decisions',
        'option_d' => 'Monarchs rule by divine right without legislative checks',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Constitutionalism asserts that government authority must be derived from and limited by a fundamental body of law (the constitution) to prevent tyranny and protect fundamental freedoms.',
      ),
      1 => 
      array (
        'chapter' => 6,
        'q_num' => 2,
        'question' => 'What was a defining characteristic of Ethiopia\'s 1931 Constitution?',
        'option_a' => 'It established a multi-party democratic federation.',
        'option_b' => 'It was Ethiopia\'s first written constitution and centralized power under the Emperor.',
        'option_c' => 'It completely abolished the monarchy and established a socialist republic.',
        'option_d' => 'It was drafted through a direct popular referendum of all citizens.',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Promulgated by Emperor Haile Selassie I, the 1931 Constitution was Ethiopia\'s first written constitution, designed primarily to centralize state power and modernize state administration under monarchical control.',
      ),
      2 => 
      array (
        'chapter' => 6,
        'q_num' => 3,
        'question' => 'The revised 1955 Constitution of Ethiopia introduced:',
        'option_a' => 'Provisions regarding human rights and nominal separation of powers, while maintaining supreme imperial sovereignty',
        'option_b' => 'Direct democratic elections for the head of state',
        'option_c' => 'Complete regional autonomy for all provinces',
        'option_d' => 'A Marxist-Leninist single-party government',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The revised 1955 Constitution included an expanded chapter on human rights and detailed government structures, though absolute authority remained concentrated in the hands of the Emperor.',
      ),
      3 => 
      array (
        'chapter' => 6,
        'q_num' => 4,
        'question' => 'The 1987 Constitution of Ethiopia established which type of state structure?',
        'option_a' => 'Absolute Monarchy',
        'option_b' => 'People\'s Democratic Republic of Ethiopia (PDRE) under a socialist single-party system',
        'option_c' => 'Federal Democratic Republic',
        'option_d' => 'Decentrailized Confederation',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Drafted under the Derg regime, the 1987 Constitution formally created the PDRE, establishing a unitary socialist state led by the Workers\' Party of Ethiopia (WPE).',
      ),
      4 => 
      array (
        'chapter' => 6,
        'q_num' => 5,
        'question' => 'What major structural transition in Ethiopian governance was introduced by the 1995 FDRE Constitution?',
        'option_a' => 'From a federal state to a centralized unitary monarchy',
        'option_b' => 'From a unitary centralized structure to an ethnic-based multicultural federation',
        'option_c' => 'From a parliamentary democracy to a presidential dictatorship',
        'option_d' => 'From a constitutional state to absolute anarchy',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The 1995 Constitution of the Federal Democratic Republic of Ethiopia (FDRE) restructured the country from a centralized unitary state into a multinational federal state.',
      ),
      5 => 
      array (
        'chapter' => 6,
        'q_num' => 6,
        'question' => 'According to Article 8 of the 1995 FDRE Constitution, ultimate sovereignty resides in:',
        'option_a' => 'The Prime Minister',
        'option_b' => 'The House of Peoples\' Representatives',
        'option_c' => 'The Nations, Nationalities, and Peoples of Ethiopia',
        'option_d' => 'The Supreme Court',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Article 8 explicitly states that all sovereign power resides in the Nations, Nationalities, and Peoples of Ethiopia, expressed through their representatives and direct democratic participation.',
      ),
      6 => 
      array (
        'chapter' => 6,
        'q_num' => 7,
        'question' => 'Under the 1995 FDRE Constitution, which organ has the power to interpret the Constitution?',
        'option_a' => 'The Federal Supreme Court',
        'option_b' => 'The House of Federation (HoF) supported by the Council of Constitutional Inquiry (CCI)',
        'option_c' => 'The Prime Minister\'s Cabinet',
        'option_d' => 'The Ministry of Justice',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Unlike systems where the Supreme Court interprets the constitution, Ethiopia\'s 1995 Constitution vests ultimate authority for constitutional interpretation in the House of Federation (assisted by the Council of Constitutional Inquiry).',
      ),
      7 => 
      array (
        'chapter' => 6,
        'q_num' => 8,
        'question' => 'Which house in the FDRE parliament represents the Nations, Nationalities, and Peoples directly?',
        'option_a' => 'House of Peoples\' Representatives (HoPR)',
        'option_b' => 'House of Federation (HoF)',
        'option_c' => 'House of Lords',
        'option_d' => 'State Council',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The House of Federation is the upper house composed of representatives of Nations, Nationalities, and Peoples, with each nation guaranteed at least one representative plus additional seats based on population.',
      ),
      8 => 
      array (
        'chapter' => 6,
        'q_num' => 9,
        'question' => 'The House of Peoples\' Representatives (HoPR) serves as the:',
        'option_a' => 'Judicial organ of the federal state',
        'option_b' => 'Highest legislative authority of the Federal Government',
        'option_c' => 'Advisory board for economic planning',
        'option_d' => 'Executive arm of the armed forces',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The HoPR is the highest legislative body of the federal government, responsible for enacting federal laws, approving budgets, and overseeing executive ministries.',
      ),
      9 => 
      array (
        'chapter' => 6,
        'q_num' => 10,
        'question' => 'Rule of Law means that:',
        'option_a' => 'Government officials are above the law due to their official duties',
        'option_b' => 'All citizens and state institutions are equally subject to public, non-arbitrary laws',
        'option_c' => 'Laws can be altered daily without public notice',
        'option_d' => 'Only police officers are required to obey legal statutes',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Rule of law dictates that every individual, including leaders and public officials, is accountable to publicly promulgated, equal, and independently enforced laws.',
      ),
      10 => 
      array (
        'chapter' => 6,
        'q_num' => 11,
        'question' => 'Human rights provisions in Chapter Three of the 1995 FDRE Constitution are divided into:',
        'option_a' => 'Individual and corporate rights',
        'option_b' => 'Fundamental Human Rights and Democratic Rights',
        'option_c' => 'Temporary rights and permanent rights',
        'option_d' => 'Urban rights and rural rights',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Chapter Three of the 1995 FDRE Constitution categorizes fundamental rights into two main sections: Human Rights (Articles 14–28) and Democratic Rights (Articles 29–44).',
      ),
      11 => 
      array (
        'chapter' => 6,
        'q_num' => 12,
        'question' => 'What constitutional mechanism prevents one branch of government from accumulating unchecked power?',
        'option_a' => 'Separation of powers with checks and balances',
        'option_b' => 'Military dictatorship',
        'option_c' => 'Unitary centralization',
        'option_d' => 'Judicial immunity',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The separation of powers divides government functions among the legislative, executive, and judicial branches, ensuring each branch checks the others to prevent tyranny.',
      ),
      12 => 
      array (
        'chapter' => 6,
        'q_num' => 13,
        'question' => 'A rigid constitution is one that:',
        'option_a' => 'Can never be changed under any circumstances',
        'option_b' => 'Requires special, complex procedures and broad consensus to amend',
        'option_c' => 'Can be altered through ordinary parliamentary votes',
        'option_d' => 'Is unwritten and preserved only through oral tradition',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A rigid constitution requires a formal, rigorous, and special amendment process (such as supermajority votes in federal and state parliaments) rather than standard legislative procedures.',
      ),
      13 => 
      array (
        'chapter' => 6,
        'q_num' => 14,
        'question' => 'In the FDRE constitution, state and religion are declared to be:',
        'option_a' => 'Unified under a single official state religion',
        'option_b' => 'Completely separate, establishing a secular state system',
        'option_c' => 'Managed directly by religious leaders',
        'option_d' => 'Subject to religious veto power over federal laws',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Article 11 of the FDRE Constitution establishes state secularism: state and religion are separate, there is no state religion, and the state shall not interfere in religious affairs (and vice versa).',
      ),
      14 => 
      array (
        'chapter' => 6,
        'q_num' => 15,
        'question' => 'The principle of constitutional supremacy implies that:',
        'option_a' => 'International treaties always supersede national law automatically',
        'option_b' => 'Any law, customary practice, or decision of an organ of state that contravenes the constitution shall be null and void',
        'option_c' => 'Executive orders carry higher authority than constitutional texts',
        'option_d' => 'Regional council statutes override federal constitutional provisions',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Constitutional supremacy (e.g., Article 9 of the FDRE Constitution) dictates that the constitution is the supreme law of the land, rendering any conflicting law, custom, or official act invalid. Here are the 15 multiple-choice questions for **Chapter 7** and **Chapter 8** based on the Grade 10 Citizenship Education material, along with their correct answers and explanations.',
      ),
    ),
  ),
  7 => 
  array (
    'chapter_title' => 'Patriotism',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 7,
        'q_num' => 1,
        'question' => 'Foreign policy is best defined as:',
        'option_a' => 'The set of internal laws governing state security and local police forces',
        'option_b' => 'A strategy and set of principles outlining how a nation interacts with other countries and international organizations',
        'option_c' => 'A set of rules regulating only international private business contracts',
        'option_d' => 'Direct orders given by foreign ambassadors to host nations',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Foreign policy consists of self-interest strategies chosen by a state to safeguard its national interests and achieve goals within its international relations.',
      ),
      1 => 
      array (
        'chapter' => 7,
        'q_num' => 2,
        'question' => 'What is the primary objective of a nation\'s foreign policy?',
        'option_a' => 'Expanding national territorial borders into neighboring states',
        'option_b' => 'Protecting national interest, sovereignty, territorial integrity, and economic development',
        'option_c' => 'Eliminating foreign languages from national educational curricula',
        'option_d' => 'Directing the internal legislative processes of foreign countries',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'A state\'s foreign policy is fundamentally designed to protect its national interest, maintain sovereignty, ensure territorial integrity, and foster economic prosperity.',
      ),
      2 => 
      array (
        'chapter' => 7,
        'q_num' => 3,
        'question' => 'The practice of conducting negotiations, maintaining peaceful relations, and managing international interactions between states is known as:',
        'option_a' => 'Hegemony',
        'option_b' => 'Diplomacy',
        'option_c' => 'Isolationism',
        'option_d' => 'Militarism',
        'raw_answer' => 'C',
        'correct_answer' => 'option_c',
        'explanation' => 'Diplomacy is the art and practice of conducting negotiations between representatives of states to build relationships, resolve disputes, and advance national interests peacefully.',
      ),
      3 => 
      array (
        'chapter' => 7,
        'q_num' => 4,
        'question' => 'Which historical event in 1896 established Ethiopia as a symbol of anti-colonial resistance and verified its national sovereignty on the global stage?',
        'option_a' => 'The Battle of Adwa',
        'option_b' => 'The League of Nations Crisis',
        'option_c' => 'The Berlin Conference',
        'option_d' => 'The Treaty of Wuchale signing',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The Ethiopian victory over colonial forces at the Battle of Adwa in 1896 defended Ethiopia\'s independence and inspired pan-African anti-colonial liberation movements.',
      ),
      4 => 
      array (
        'chapter' => 7,
        'q_num' => 5,
        'question' => 'What is Constitutional Patriotism defined as?',
        'option_a' => 'Blind loyalty to one\'s government regardless of its actions',
        'option_b' => 'Devotion to democratic principles, human rights, and the rule of law enshrined in the constitution',
        'option_c' => 'Ethnocentric pride in one\'s specific regional background',
        'option_d' => 'Military expansion into foreign territories',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Constitutional patriotism centers national attachment on shared democratic values, human rights, and constitutional principles rather than ethnic or chauvinistic identity.',
      ),
      5 => 
      array (
        'chapter' => 7,
        'q_num' => 6,
        'question' => 'Formal diplomatic relations, treaties, and official negotiations conducted between TWO individual sovereign nations are called:',
        'option_a' => 'Multilateral Diplomacy',
        'option_b' => 'Bilateral Diplomacy',
        'option_c' => 'Unilateral Action',
        'option_d' => 'Commercial Isolationism',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Bilateral diplomacy refers to political, economic, or cultural relations between exactly two sovereign states.',
      ),
      6 => 
      array (
        'chapter' => 7,
        'q_num' => 7,
        'question' => 'Diplomatic negotiations involving THREE or more nations to resolve shared regional or global challenges are:',
        'option_a' => 'Bilateral Diplomacy',
        'option_b' => 'Multilateral Diplomacy',
        'option_c' => 'Public Relations',
        'option_d' => 'Internal State Audit',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Multilateral diplomacy involves multiple nations cooperating through international forums like the United Nations (UN) or African Union (AU).',
      ),
      7 => 
      array (
        'chapter' => 7,
        'q_num' => 8,
        'question' => 'Ethiopia was a founding member of which international movement during the Cold War that promoted non-alignment with major military blocs?',
        'option_a' => 'North Atlantic Treaty Organization (NATO)',
        'option_b' => 'Non-Aligned Movement (NAM)',
        'option_c' => 'Warsaw Pact',
        'option_d' => 'European Union',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Ethiopia was a key founding state of the Non-Aligned Movement (NAM), advocating peace and independence from Superpower blocs during the Cold War.',
      ),
      8 => 
      array (
        'chapter' => 7,
        'q_num' => 9,
        'question' => 'The headquarters of the African Union (AU) is permanently located in which diplomatic capital city?',
        'option_a' => 'Nairobi, Kenya',
        'option_b' => 'Addis Ababa, Ethiopia',
        'option_c' => 'Cairo, Egypt',
        'option_d' => 'Pretoria, South Africa',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Addis Ababa serves as the diplomatic capital of Africa, hosting the headquarters of the African Union and UNECA.',
      ),
      9 => 
      array (
        'chapter' => 7,
        'q_num' => 10,
        'question' => 'The ability of a country to influence international outcomes through cultural appeal, attraction, and values rather than military force is:',
        'option_a' => 'Hard Power',
        'option_b' => 'Soft Power',
        'option_c' => 'Coercive Power',
        'option_d' => 'Economic Blockade',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Soft power relies on persuasion, cultural influence, diplomacy, and positive reputation rather than military coercion (hard power).',
      ),
      10 => 
      array (
        'chapter' => 7,
        'q_num' => 11,
        'question' => 'The legal mechanism by which one state surrenders a convicted or suspected criminal to another state for prosecution is called:',
        'option_a' => 'Extradition',
        'option_b' => 'Naturalization',
        'option_c' => 'Expatriation',
        'option_d' => 'Emigration',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Extradition is a formal legal process governed by international treaties enabling states to transfer accused criminals across borders.',
      ),
      11 => 
      array (
        'chapter' => 7,
        'q_num' => 12,
        'question' => 'Which international landmark treaty established standard guidelines for diplomatic immunity and privileges for foreign ambassadors?',
        'option_a' => 'Treaty of Versailles (1919)',
        'option_b' => 'Vienna Convention on Diplomatic Relations (1961)',
        'option_c' => 'Geneva Conventions (1949)',
        'option_d' => 'Kyoto Protocol (1997)',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'The 1961 Vienna Convention codified diplomatic immunity, protecting foreign diplomats from local prosecution to ensure smooth inter-state communications.',
      ),
      12 => 
      array (
        'chapter' => 7,
        'q_num' => 13,
        'question' => 'A state official assigned by a country to reside in a foreign nation to protect commercial interests and assist visiting citizens is a:',
        'option_a' => 'Consul',
        'option_b' => 'Prime Minister',
        'option_c' => 'Governor',
        'option_d' => 'Chief Justice',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Consuls handle administrative consular services, visa issuance, and assistance to national citizens traveling abroad.',
      ),
      13 => 
      array (
        'chapter' => 7,
        'q_num' => 14,
        'question' => 'What is National Interest in international relations?',
        'option_a' => 'A country\'s goals and ambitions (economic, military, cultural) that guide its foreign policy',
        'option_b' => 'Personal financial investments of high-ranking politicians',
        'option_c' => 'The interest rate charged by central banks',
        'option_d' => 'Tourist expenditure in regional national parks',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'National interest encompasses a state\'s core strategic objectives—security, economic growth, and cultural preservation—that direct its foreign policy.',
      ),
      14 => 
      array (
        'chapter' => 7,
        'q_num' => 15,
        'question' => 'Active civic participation by citizens in national development projects (such as tree planting or infrastructure building) is an expression of:',
        'option_a' => 'Chauvinism',
        'option_b' => 'Patriotism in action',
        'option_c' => 'Passive citizenship',
        'option_d' => 'Subversion',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Patriotism in action is demonstrated when citizens actively contribute their labor, knowledge, and care to improve their nation.',
      ),
    ),
  ),
  8 => 
  array (
    'chapter_title' => 'Globalization and Global Issues',
    'questions' => 
    array (
      0 => 
      array (
        'chapter' => 8,
        'q_num' => 1,
        'question' => 'The growing worldwide integration and interdependence of economies, cultures, technologies, and populations is called:',
        'option_a' => 'Globalization',
        'option_b' => 'Regionalism',
        'option_c' => 'Isolationism',
        'option_d' => 'Protectionism',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Globalization refers to the interconnectedness of world societies driven by trade, technology, cultural exchange, and communication.',
      ),
      1 => 
      array (
        'chapter' => 8,
        'q_num' => 2,
        'question' => 'The gap between individuals or societies with easy access to modern digital information technology and those without access is the:',
        'option_a' => 'Digital Divide',
        'option_b' => 'Economic Monopoly',
        'option_c' => 'Brain Drain',
        'option_d' => 'Cultural Homogenization',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The Digital Divide highlights inequalities in internet access, hardware availability, and digital literacy between developed and developing regions.',
      ),
      2 => 
      array (
        'chapter' => 8,
        'q_num' => 3,
        'question' => 'Development that satisfies present societal needs without compromising the capacity of future generations to meet their own needs is:',
        'option_a' => 'Rapid Industrialization',
        'option_b' => 'Sustainable Development',
        'option_c' => 'Uncontrolled Extraction',
        'option_d' => 'Short-term Economic Expansion',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Sustainable development balances economic growth, social inclusion, and environmental conservation for long-term ecological balance.',
      ),
      3 => 
      array (
        'chapter' => 8,
        'q_num' => 4,
        'question' => 'The departure of highly educated, skilled professionals (doctors, engineers, scientists) from developing countries to wealthier nations is:',
        'option_a' => 'Brain Gain',
        'option_b' => 'Brain Drain',
        'option_c' => 'Cultural Diversity',
        'option_d' => 'Demographic Transition',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Brain drain deprives developing nations of crucial human capital and expertise when skilled workers emigrate for better pay or opportunities.',
      ),
      4 => 
      array (
        'chapter' => 8,
        'q_num' => 5,
        'question' => 'Which international landmark document adopted by the United Nations General Assembly in 1948 established universal fundamental human rights?',
        'option_a' => 'Universal Declaration of Human Rights (UDHR)',
        'option_b' => 'Geneva Conventions',
        'option_c' => 'Paris Climate Agreement',
        'option_d' => 'African Charter of 1963',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Adopted on December 10, 1948, the UDHR outlines 30 basic human rights and freedoms that belong to every person globally.',
      ),
      5 => 
      array (
        'chapter' => 8,
        'q_num' => 6,
        'question' => 'The global environmental challenge characterized by rising global temperatures, melting polar caps, and extreme weather shifts is:',
        'option_a' => 'Climate Change (Global Warming)',
        'option_b' => 'Eutrophication',
        'option_c' => 'Tectonic Drift',
        'option_d' => 'Urban Sprawl',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Human industrial greenhouse gas emissions are the primary driver of global climate change and atmospheric warming.',
      ),
      6 => 
      array (
        'chapter' => 7,
        'q_num' => 7,
        'question' => 'The illegal recruitment, transportation, or trade of human beings through force, fraud, or coercion for forced labor or exploitation is:',
        'option_a' => 'Human Trafficking',
        'option_b' => 'Legal Migration',
        'option_c' => 'Extradition',
        'option_d' => 'Expatriation',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Human trafficking is a severe global crime and human rights violation involving modern-day forced labor or commercial exploitation.',
      ),
      7 => 
      array (
        'chapter' => 8,
        'q_num' => 8,
        'question' => 'What is the main goal of the Sustainable Development Goals (SDGs) established by the United Nations for 2030?',
        'option_a' => 'To eradicate poverty, protect the planet, and ensure peace and prosperity for all',
        'option_b' => 'To establish a single worldwide military force',
        'option_c' => 'To privatize all international public schools',
        'option_d' => 'To replace national currencies with a single digital coin',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'The 17 SDGs provide a global blueprint to end poverty, reduce inequality, improve health/education, and combat climate change by 2030.',
      ),
      8 => 
      array (
        'chapter' => 8,
        'q_num' => 9,
        'question' => 'The spread of dominant Western consumer culture, products, and values across the world, sometimes overwhelming local traditions, is known as:',
        'option_a' => 'Cultural Imperialism (Cultural Homogenization)',
        'option_b' => 'Cultural Pluralism',
        'option_c' => 'Ethnocentrism',
        'option_d' => 'Multiculturalism',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Cultural imperialism describes how dominant global mass media and products can overshadow local cultural traditions.',
      ),
      9 => 
      array (
        'chapter' => 8,
        'q_num' => 10,
        'question' => 'What is an economic trade agreement that ensures fair prices, decent working conditions, and fair terms for farmers in developing nations?',
        'option_a' => 'Fair Trade',
        'option_b' => 'Protectionist Tariff',
        'option_c' => 'Free Trade Monopoly',
        'option_d' => 'Structural Adjustment',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Fair Trade initiatives empower small agricultural producers in developing countries by guaranteeing sustainable prices and ethical practices.',
      ),
      10 => 
      array (
        'chapter' => 8,
        'q_num' => 11,
        'question' => 'Which international health agency under the UN leads global public health responses against pandemics and epidemic outbreaks?',
        'option_a' => 'World Health Organization (WHO)',
        'option_b' => 'World Trade Organization (WTO)',
        'option_c' => 'International Monetary Fund (IMF)',
        'option_d' => 'UNESCO',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'WHO coordinates international public health initiatives, monitors disease outbreaks, and establishes global health guidelines.',
      ),
      11 => 
      array (
        'chapter' => 8,
        'q_num' => 12,
        'question' => 'The process by which returning emigrants bring valuable skills, international experience, and capital back to their home country is:',
        'option_a' => 'Brain Gain',
        'option_b' => 'Brain Drain',
        'option_c' => 'Alienation',
        'option_d' => 'Depopulation',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Brain gain occurs when returning diaspora members contribute knowledge, investments, and skills to their native economy.',
      ),
      12 => 
      array (
        'chapter' => 8,
        'q_num' => 13,
        'question' => 'Transnational corporations (TNCs) are business enterprises that:',
        'option_a' => 'Own or control production facilities and assets in multiple countries',
        'option_b' => 'Operate exclusively within a single municipality',
        'option_c' => 'Are owned and managed directly by the United Nations',
        'option_d' => 'Prohibit cross-border trade',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Transnational (or multinational) corporations operate across national borders, driving international trade and investment.',
      ),
      13 => 
      array (
        'chapter' => 8,
        'q_num' => 14,
        'question' => 'What is the term for money sent back home by migrant workers to their families in their native country?',
        'option_a' => 'Remittances',
        'option_b' => 'Tariffs',
        'option_c' => 'Subsidies',
        'option_d' => 'Dividends',
        'raw_answer' => 'A',
        'correct_answer' => 'option_a',
        'explanation' => 'Remittances are financial transfers from overseas diaspora workers to family members back home, providing vital economic support.',
      ),
      14 => 
      array (
        'chapter' => 8,
        'q_num' => 15,
        'question' => 'How can digital global citizenship be practiced responsibly by young people?',
        'option_a' => 'By spreading unverified news on social media',
        'option_b' => 'By respecting online diversity, advocating human rights, and using digital tools for positive social impact',
        'option_c' => 'By engaging in cyberbullying and hacking',
        'option_d' => 'By ignoring global environmental concerns',
        'raw_answer' => 'B',
        'correct_answer' => 'option_b',
        'explanation' => 'Responsible global digital citizens leverage technology to promote human rights, foster intercultural understanding, and solve global problems.',
      ),
    ),
  ),
);
    }
}

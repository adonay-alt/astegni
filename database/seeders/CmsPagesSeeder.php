<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class CmsPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds rich, professional English & Amharic content for FAQ, Privacy Policy, Terms, and Cookies Policy.
     */
    public function run(): void
    {
        // Clear public CMS cache
        Cache::forget('cms_page_faq_en');
        Cache::forget('cms_page_faq_am');
        Cache::forget('cms_page_privacy_en');
        Cache::forget('cms_page_privacy_am');
        Cache::forget('cms_page_terms_en');
        Cache::forget('cms_page_terms_am');
        Cache::forget('cms_page_cookies_en');
        Cache::forget('cms_page_cookies_am');

        // =========================================================================
        // 1. FAQ PAGE (Frequently Asked Questions)
        // =========================================================================
        $faqPage = Page::firstOrCreate(
            ['slug' => 'faq'],
            [
                'title' => 'Frequently Asked Questions',
                'title_am' => 'ተደጋግመው የሚጠየቁ ጥያቄዎች',
                'subtitle' => 'Find quick answers to common questions about Astegni learning, tutors, and exam preparation.',
                'subtitle_am' => 'ስለ አስጠኚ የመማሪያ መድረክ፣ አስተማሪዎች እና የፈተና ዝግጅት የተለመዱ ጥያቄዎችን መልስ ያግኙ።',
                'type' => 'faq',
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $faqPage->update([
            'title' => 'Frequently Asked Questions',
            'title_am' => 'ተደጋግመው የሚጠየቁ ጥያቄዎች',
            'subtitle' => 'Find quick answers to common questions about Astegni learning, tutors, and exam preparation.',
            'subtitle_am' => 'ስለ አስጠኚ የመማሪያ መድረክ፣ አስተማሪዎች እና የፈተና ዝግጅት የተለመዱ ጥያቄዎችን መልስ ያግኙ።',
            'is_published' => true,
        ]);

        // Clean out all old/untranslated FAQ questions to ensure 100% complete bilingual support
        PageSection::where('page_id', $faqPage->id)->delete();

        $faqItems = [
            [
                'question' => 'How do I search and hire verified tutors on Astegni?',
                'question_am' => 'በአስጠኚ ላይ የተረጋገጡ አስተማሪዎችን እንዴት መፈለግ እና መቅጠር እችላለሁ?',
                'answer' => 'You can find qualified tutors by navigating to the Tutors page and filtering by your grade level, subject, stream, and city or sub-city location. You can view their profiles, verified credentials, ratings, and student reviews before sending a tutoring request.',
                'answer_am' => 'ወደ አስተማሪዎች ገጽ በመሄድ በክፍል ደረጃ፣ በትምህርት አይነት፣ በስትሪም እና በመኖሪያ አካባቢዎ በማጣራት ብቁ አስተማሪዎችን ማግኘት ይችላሉ። የማስተማር ክፍለ-ጊዜ ከመያዝዎ በፊት የአስተማሪዎችን ፕሮፋይል፣ የተረጋገጡ ማስረጃዎችን፣ የተማሪዎች ደረጃ እና አስተያየቶችን መመልከት ይችላሉ።',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'question' => 'Are curriculum textbook PDFs, study notes, and video lessons free?',
                'question_am' => 'የስርዓተ ትምህርት የመማሪያ መጽሐፍት ፒዲኤፍ፣ የጥናት ማስታወሻዎች እና የቪዲዮ ትምህርቶች ነፃ ናቸው?',
                'answer' => 'Yes! Registered students have free, unlimited streaming access to official Ethiopian curriculum textbooks (Grades 9 to 12), AI-curated chapter study summaries, formula cheat-sheets, and curated video explanations.',
                'answer_am' => 'አዎ! የተመዘገቡ ተማሪዎች ከ9ኛ እስከ 12ኛ ክፍል ያሉትን ይፋዊ የኢትዮጵያ ስርዓተ ትምህርት የመማሪያ መጽሐፍት፣ በ AI የተዘጋጁ የምዕራፍ ማጠቃለያ ማስታወሻዎችን፣ የቀመር ሰንጠረዦችን እና የቪዲዮ ማብራሪያዎችን ያለ ምንም ክፍያ በነፃ ማግኘት ይችላሉ።',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'question' => 'How do interactive chapter quizzes work and how is my score tracked?',
                'question_am' => 'በይነተገናኝ የምዕራፍ የልምምድ ፈተናዎች እንዴት ይሰራሉ እና ውጤቴ እንዴት ይመዘገባል?',
                'answer' => 'Each curriculum chapter features interactive multiple-choice practice quizzes. After answering, you receive instant answer feedback with step-by-step explanations. Your scores and chapter progress are automatically saved to your student profile so you can track your academic growth.',
                'answer_am' => 'እያንዳንዱ የስርዓተ ትምህርት ምዕራፍ በይነተገናኝ የብዙ ምርጫ የልምምድ ጥያቄዎችን ያካትታል። ከመለሱ በኋላ ደረጃ በደረጃ ማብራሪያዎችን የያዘ ፈጣን ግብረመልስ ያገኛሉ። የትምህርት እድገትዎን ለመከታተል ውጤቶችዎ እና የምዕራፍ ሂደቶችዎ በራስ-ሰር በፕሮፋይልዎ ውስጥ ይቀመጣሉ።',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'question' => 'How can I practice with Ethiopian National Exam past papers?',
                'question_am' => 'ያለፉ የኢትዮጵያ የብሔራዊ ፈተና ጥያቄዎችን እንዴት መለማመድ እችላለሁ?',
                'answer' => 'Head to the National Exams section and select your target matric year, subject, and stream (Natural or Social Science). You can solve timed past exam papers with verified answers and detailed solution walk-throughs created by top educators.',
                'answer_am' => 'ወደ ብሔራዊ ፈተናዎች ክፍል በመሄድ የሚፈልጉትን የማትሪክ የፈተና ዓመት፣ የትምህርት አይነት እና ስትሪም (የተፈጥሮ ወይም የማህበራዊ ሳይንስ) ይምረጡ። በከፍተኛ አስተማሪዎች የተዘጋጁ የተረጋገጡ መልሶች እና ዝርዝር የመፍትሔ ማብራሪያዎችን በያዙ ፈተናዎች እራስዎን በሰዓት መፈተሽ ይችላሉ።',
                'sort_order' => 4,
                'is_published' => true,
            ],
            [
                'question' => 'How do I reset my password if I forgot my login credentials?',
                'question_am' => 'የመግቢያ የይለፍ ቃሌን ከረሳሁት እንዴት መቀየር እችላለሁ?',
                'answer' => 'On the login screen, tap "Forgot Password", enter your registered phone number or email, and submit the 6-digit OTP sent to your device. Once verified, you can securely enter a new password and log back into your account.',
                'answer_am' => 'በመግቢያ ገጹ ላይ "የይለፍ ቃል ረሳሁ" የሚለውን ይጫኑ፣ የተመዘገቡበትን የስልክ ቁጥር ወይም ኢሜይል ያስገቡ እና ወደ መሳሪያዎ የተላከውን ባለ 6-አሃዝ የማረጋገጫ ኮድ (OTP) ያስገቡ። ከተረጋገጠ በኋላ አዲስ የይለፍ ቃል በማስገባት በቀላሉ መግባት ይችላሉ።',
                'sort_order' => 5,
                'is_published' => true,
            ],
            [
                'question' => 'How do educators apply to become tutors on Astegni?',
                'question_am' => 'አስተማሪዎች በአስጠኚ ላይ ለማስተማር እንዴት ማመልከት ይችላሉ?',
                'answer' => 'Educators can sign up, complete the tutor application form, upload their academic degrees and identification, and specify their teaching subjects and rates. Our academic review team verifies credentials within 24 to 48 hours before activating public tutor profiles.',
                'answer_am' => 'አስተማሪዎች በመመዝገብ፣ የአስተማሪነት ማመልከቻ ቅጹን በመሙላት፣ የትምህርት ማስረጃዎችን እና መታወቂያቸውን በመጫን እንዲሁም የሚያስተምሯቸውን የትምህርት አይነቶች እና ዋጋቸውን መግለጽ ይችላሉ። የእኛ የአካዳሚክ ቡድን ማረጋገጫውን በ24 እስከ 48 ሰዓታት ውስጥ በማጠናቀቅ ፕሮፋይላቸውን ያነቃል።',
                'sort_order' => 6,
                'is_published' => true,
            ],
            [
                'question' => 'What payment methods are supported for tutoring and premium resources?',
                'question_am' => 'ለግል ማስተማር እና ለፕሪሚየም የትምህርት መርጃዎች ምን ዓይነት የክፍያ አማራጮች ይገኛሉ?',
                'answer' => 'We support secure local Ethiopian payment gateways including Telebirr, CBE Birr, Chapa, and direct bank transfers. All transactions are securely encrypted and verified instantly.',
                'answer_am' => 'ቴሌብር፣ የኢትዮጵያ ንግድ ባንክ (CBE Birr)፣ ቻፓ እና ቀጥተኛ የባንክ ማስተላለፍን ጨምሮ አስተማማኝ የአገር ውስጥ የክፍያ አማራጮችን እንደግፋለን። ሁሉም ክፍያዎች ደህንነታቸው የተጠበቀ እና በቅጽበት የሚረጋገጡ ናቸው።',
                'sort_order' => 7,
                'is_published' => true,
            ],
            [
                'question' => 'Can I access Astegni on my mobile phone or tablet?',
                'question_am' => 'አስጠኚን በሞባይል ስልኬ ወይም በታብሌቴ መጠቀም እችላለሁ?',
                'answer' => 'Yes! Astegni is fully optimized for all mobile smartphones, tablets, laptops, and desktop computers. You can smoothly read textbooks, take quizzes, and chat with tutors on any modern web browser without needing high-speed internet.',
                'answer_am' => 'አዎ! አስጠኚ ለሁሉም ዘመናዊ ስልኮች፣ ታብሌቶች እና ኮምፒውተሮች ሙሉ ለሙሉ የተመቻቸ ነው። ፈጣን የኢንተርኔት ግንኙነት ሳያስፈልግዎት በማንኛውም የድረ-ገጽ አሳሽ መጽሐፍትን ማንበብ፣ ጥያቄዎችን መመለስ እና ከአስተማሪዎች ጋር መገናኘት ይችላሉ።',
                'sort_order' => 8,
                'is_published' => true,
            ],
        ];

        foreach ($faqItems as $item) {
            PageSection::create(array_merge($item, ['page_id' => $faqPage->id]));
        }

        // =========================================================================
        // 2. PRIVACY POLICY PAGE
        // =========================================================================
        $privacyPage = Page::firstOrCreate(
            ['slug' => 'privacy'],
            [
                'title' => 'Privacy Policy',
                'title_am' => 'የግላዊነት ፖሊሲ',
                'subtitle' => 'Learn how Astegni collects, protects, uses, and respects your personal information.',
                'subtitle_am' => 'አስጠኚ የእርስዎን የግል መረጃ እንዴት እንደሚሰበስብ፣ እንደሚጠብቅ፣ እንደሚጠቀም እና እንደሚያከብር ይወቁ።',
                'type' => 'legal',
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $privacySections = [
            [
                'heading' => 'Information We Collect',
                'heading_am' => 'የምንሰበስባቸው መረጃዎች',
                'body' => 'We collect information necessary to deliver and improve our educational services. This includes your name, email address, phone number, grade level, and profile photo when you register. For tutors, we also collect academic credentials, teaching experience, and identification documents for verification.',
                'body_am' => 'የትምህርት አገልግሎታችንን ለማቅረብ እና ለማሻሻል አስፈላጊ የሆኑ መረጃዎችን እንሰበስባለን። ይህ በሚመዘገቡበት ጊዜ ስምዎን፣ የኢሜይል አድራሻዎን፣ የስልክ ቁጥርዎን፣ የክፍል ደረጃዎን እና የፕሮፋይል ፎቶዎን ያካትታል። ለአስተማሪዎች ለማረጋገጫ የሚሆኑ የትምህርት ማስረጃዎችን፣ የማስተማር ልምድ እና የመታወቂያ ሰነዶችን እንሰበስባለን።',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'heading' => 'How We Use Your Information',
                'heading_am' => 'መረጃዎን እንዴት እንደምንጠቀምበት',
                'body' => 'Your information is utilized to personalize your learning dashboard, recommend tailored textbook notes and quizzes, facilitate verified tutor connections, process payments, and provide customer support. We do not sell your personal data to third parties or advertisers.',
                'body_am' => 'የእርስዎ መረጃ የመማሪያ ዳሽቦርድዎን ግላዊ ለማድረግ፣ ተስማሚ የማጠቃለያ ማስታወሻዎችን እና ጥያቄዎችን ለመጠቆም፣ ከተረጋገጡ አስተማሪዎች ጋር ለማገናኘት፣ ክፍያዎችን ለማስኬድ እና የደንበኞች ድጋፍ ለመስጠት ጥቅም ላይ ይውላል። የግል መረጃዎን ለሶስተኛ ወገኖች ወይም ለማስታወቂያ ሰሪዎች አንሸጥም።',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'heading' => 'Data Security and Protection',
                'heading_am' => 'የመረጃ ደህንነት እና ጥበቃ',
                'body' => 'We employ modern technical and organizational measures, including TLS 1.3 encryption, secure password hashing (Bcrypt), and token-based API authentication (Laravel Sanctum) to safeguard your data against unauthorized access, loss, or disclosure.',
                'body_am' => 'መረጃዎን ካልተፈቀደ መዳረሻ፣ መጥፋት ወይም ይፋ ከመሆን ለመጠበቅ እንደ TLS 1.3 ምስጠራ፣ ደህንነቱ የተጠበቀ የይለፍ ቃል ሀሺንግ (Bcrypt) እና በቶከን ላይ የተመሰረተ የማረጋገጫ ስርዓት (Laravel Sanctum) የመሳሰሉ ዘመናዊ የቴክኒክ እና የድርጅት ጥበቃዎችን እንጠቀማለን።',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'heading' => 'Payment Processing & Financial Data',
                'heading_am' => 'የክፍያ ሂደት እና የፋይናንስ መረጃ',
                'body' => 'Payment transactions for premium educational resources or tutoring sessions are processed securely through certified Ethiopian payment gateways (such as Chapa). Astegni never stores your payment card numbers, bank PINs, or mobile money credentials on our servers.',
                'body_am' => 'ለፕሪሚየም የትምህርት ቁሳቁሶች ወይም ለአስተማሪ ክፍለ-ጊዜዎች የሚደረጉ የክፍያ ሂደቶች በተረጋገጡ የኢትዮጵያ የክፍያ ስርዓቶች (እንደ ጫፓ) በኩል ደህንነታቸው ተጠብቆ ይከናወናሉ። አስጠኚ የባንክ ካርድ ቁጥሮችን፣ የባንክ ፒን ወይም የሞባይል ገንዘብ ምስጢራዊ መረጃዎችን በሰርቨሮቹ ላይ አያስቀምጥም።',
                'sort_order' => 4,
                'is_published' => true,
            ],
            [
                'heading' => 'Your Privacy Rights & Account Deletion',
                'heading_am' => 'የመረጃ መብቶችዎ እና መለያ የመሰረዝ መብት',
                'body' => 'You maintain full ownership of your data. You may review, edit, or update your profile details at any time from Settings. You also possess the right to permanently request the deletion of your account and all associated personal data directly from the Astegni app.',
                'body_am' => 'የመረጃዎ ሙሉ ባለቤት እርስዎ ነዎት። በማንኛውም ጊዜ በመተግበሪያው ቅንብሮች ውስጥ የመለያዎን መረጃ መመልከት፣ ማስተካከል ወይም ማዘመን ይችላሉ። እንዲሁም መለያዎ እና ተያያዥ የግል መረጃዎችዎ በቋሚነት እንዲሰረዙ በቀጥታ ከአስጠኚ መተግበሪያ የመጠየቅ ሙሉ መብት አለዎት።',
                'sort_order' => 5,
                'is_published' => true,
            ],
        ];

        foreach ($privacySections as $sec) {
            $section = PageSection::where('page_id', $privacyPage->id)
                ->where('heading', $sec['heading'])
                ->first();

            if ($section) {
                $section->update([
                    'heading_am' => $section->heading_am ?: $sec['heading_am'],
                    'body_am' => $section->body_am ?: $sec['body_am'],
                ]);
            } else {
                PageSection::create(array_merge($sec, ['page_id' => $privacyPage->id]));
            }
        }

        // =========================================================================
        // 3. TERMS OF SERVICE PAGE
        // =========================================================================
        $termsPage = Page::firstOrCreate(
            ['slug' => 'terms'],
            [
                'title' => 'Terms of Service',
                'title_am' => 'የአገልግሎት ውሎች',
                'subtitle' => 'Terms, conditions, and legal agreements governing the use of Astegni educational platform.',
                'subtitle_am' => 'የአስጠኚ የትምህርት መድረክ አጠቃቀምን የሚመለከቱ ውሎች፣ ደንቦች እና ህጋዊ ስምምነቶች።',
                'type' => 'legal',
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $termsSections = [
            [
                'heading' => 'Acceptance of Agreement',
                'heading_am' => 'የውሎች ተቀባይነት',
                'body' => 'By downloading, browsing, or utilizing the Astegni mobile application, website, or API services, you agree to comply with and be legally bound by these Terms of Service, along with our Privacy Policy and Community Guidelines.',
                'body_am' => 'የአስጠኚ የሞባይል መተግበሪያን፣ ድረ-ገጽን ወይም የኤፒአይ አገልግሎቶችን በመጠቀም ወይም በመግባት በእነዚህ የአገልግሎት ውሎች፣ በግላዊነት ፖሊሲያችን እና በማህበረሰብ መመሪያዎቻችን ለመገዛት ተስማምተዋል።',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'heading' => 'User Accounts and Academic Integrity',
                'heading_am' => 'የተጠቃሚ መለያዎች እና የአካዳሚክ ታማኝነት',
                'body' => 'Users must provide accurate, current, and complete registration information. You are responsible for safeguarding your login credentials. Astegni is designed to foster genuine academic learning; utilizing the platform for academic dishonesty, cheating, or impersonation is strictly prohibited.',
                'body_am' => 'ተጠቃሚዎች ትክክለኛ፣ ወቅታዊ እና የተሟላ የምዝገባ መረጃ መስጠት አለባቸው። የመግቢያ መረጃዎን ደህንነት የመጠበቅ ኃላፊነት የእርስዎ ነው። አስጠኚ እውነተኛ የመማር ሂደትን ለማበረታታት የተዘጋጀ ሲሆን መድረኩን ለፈተና ማጭበርበር ወይም በሌላ ሰው ስም ለመጠቀም መሞከር በጥብቅ የተከለከለ ነው።',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'heading' => 'Community Conduct and Safe Interaction',
                'heading_am' => 'የማህበረሰብ ስነ-ምግባር እና ደህንነቱ የተጠበቀ ግንኙነት',
                'body' => 'Students, parents, and tutors must maintain respectful, professional communication at all times. Harassment, abusive language, hate speech, inappropriate behavior, or off-platform solicitation that violates user safety will result in immediate permanent account termination.',
                'body_am' => 'ተማሪዎች፣ ወላጆች እና አስተማሪዎች ሁልጊዜ የተከበረ እና ሙያዊ ግንኙነት ማድረግ አለባቸው። ማስፈራራት፣ የስድብ ቃላት፣ የጥላቻ ንግግር፣ ተገቢ ያልሆነ ባህሪ ወይም የተጠቃሚዎችን ደህንነት የሚጎዳ ማንኛውም እንቅስቃሴ ወዲያውኑ መለያን በቋሚነት ማገድን ያስከትላል።',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'heading' => 'Intellectual Property Rights',
                'heading_am' => 'የአእምሯዊ ንብረት መብቶች',
                'body' => 'All educational curriculum notes, interactive question banks, software code, user interface designs, trademarks, and brand assets on Astegni are the intellectual property of Astegni or its licensed contributors and are protected by applicable intellectual property laws.',
                'body_am' => 'በአስጠኚ ላይ ያሉ ሁሉም የትምህርት ማጠቃለያ ማስታወሻዎች፣ በይነተገናኝ የጥያቄ ባንኮች፣ የሶፍትዌር ኮድ፣ የመተግበሪያ ዲዛይኖች፣ የንግድ ምልክቶች እና የብራንድ ሀብቶች የአስጠኚ ወይም የፈቃድ ሰጪዎቹ አእምሯዊ ንብረት ናቸው።',
                'sort_order' => 4,
                'is_published' => true,
            ],
            [
                'heading' => 'Subscriptions, Purchases, and Refunds',
                'heading_am' => 'ክፍያዎች፣ ግዢዎች እና የተመላሽ ገንዘብ ፖሊሲ',
                'body' => 'Certain specialized educational materials, mock exams, or tutoring packages require payment. All fees are clearly disclosed prior to confirmation. In the event of confirmed service failure or verified technical discrepancy, refunds will be handled in accordance with our refund policy.',
                'body_am' => 'አንዳንድ ልዩ የትምህርት ቁሳቁሶች፣ የሞዴል ፈተናዎች ወይም የአስተማሪ ፓኬጆች ክፍያ ሊጠይቁ ይችላሉ። ሁሉም ዋጋዎች ከመክፈልዎ በፊት በግልጽ ይታያሉ። የተረጋገጠ የአገልግሎት መቋረጥ ወይም የቴክኒክ ችግር ካጋጠመ ተመላሽ ገንዘብ በተመላሽ ፖሊሲያችን መሰረት ይስተናገዳል።',
                'sort_order' => 5,
                'is_published' => true,
            ],
        ];

        foreach ($termsSections as $sec) {
            $section = PageSection::where('page_id', $termsPage->id)
                ->where('heading', $sec['heading'])
                ->first();

            if ($section) {
                $section->update([
                    'heading_am' => $section->heading_am ?: $sec['heading_am'],
                    'body_am' => $section->body_am ?: $sec['body_am'],
                ]);
            } else {
                PageSection::create(array_merge($sec, ['page_id' => $termsPage->id]));
            }
        }

        // =========================================================================
        // 4. COOKIES POLICY PAGE
        // =========================================================================
        $cookiesPage = Page::firstOrCreate(
            ['slug' => 'cookies'],
            [
                'title' => 'Cookies Policy',
                'title_am' => 'የኩኪዎች ፖሊሲ',
                'subtitle' => 'Understanding how cookies, tokens, and local storage keep your Astegni experience secure and seamless.',
                'subtitle_am' => 'ኩኪዎች፣ ቶከኖች እና የአካባቢ ማከማቻዎች የእርስዎን የአስጠኚ ተሞክሮ እንዴት ደህንነቱ የተጠበቀ እና ምቹ እንደሚያደርጉት ይረዱ።',
                'type' => 'legal',
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $cookiesSections = [
            [
                'heading' => 'What Are Cookies and Local Storage?',
                'heading_am' => 'ኩኪዎች እና የአካባቢ ማከማቻዎች ምንድን ናቸው?',
                'body' => 'Cookies and mobile local storage are small data files saved on your smartphone or browser when you access Astegni. They enable our services to remember your preferences (such as selected grade level and language) across different sessions.',
                'body_am' => 'ኩኪዎች እና የሞባይል የአካባቢ ማከማቻዎች ወደ አስጠኚ ሲገቡ በስማርትፎንዎ ወይም በአሳሽዎ ላይ የሚቀመጡ ትናንሽ የመረጃ ፋይሎች ናቸው። መድረኩ የእርስዎን ምርጫዎች (እንደ መረጡት የክፍል ደረጃ እና ቋንቋ) እንዲያስታውስ ያስችሉታል።',
                'sort_order' => 1,
                'is_published' => true,
            ],
            [
                'heading' => 'Essential Authentication Cookies and Tokens',
                'heading_am' => 'አስፈላጊ የማረጋገጫ ኩኪዎች እና ቶከኖች',
                'body' => 'These cookies and secure bearer tokens are strictly necessary for core app operation. They maintain your authenticated login session, preserve your study quiz answers in real-time, and protect your account against cross-site security threats.',
                'body_am' => 'እነዚህ ኩኪዎች እና ደህንነታቸው የተጠበቀ የማረጋገጫ ቶከኖች ለመተግበሪያው መሰረታዊ ስራ እጅግ አስፈላጊ ናቸው። የገቡበትን ክፍለ-ጊዜ ደህንነቱ የተጠበቀ እንዲሆን ያደርጋሉ፣ የፈተና ጥያቄዎችን ሲመልሱ መልሶችዎ እንዳይጠፉ ይከላከላሉ፣ እንዲሁም መለያዎን ከደህንነት ስጋቶች ይጠብቃሉ።',
                'sort_order' => 2,
                'is_published' => true,
            ],
            [
                'heading' => 'Preferences and Functional Storage',
                'heading_am' => 'የምርጫዎች እና የተግባር ማከማቻዎች',
                'body' => 'Functional storage retains your custom settings, such as your dark/light theme preference, Amharic/English language selection, and recently viewed textbook chapters, ensuring you can seamlessly pick up right where you left off.',
                'body_am' => 'የተግባር ማከማቻ የእርስዎን ብጁ ምርጫዎች ያስታውሳል፤ ለምሳሌ የመረጡትን የጨለማ/ብርሃን ገጽታ፣ የአማርኛ/እንግሊዝኛ ቋንቋ ምርጫ እና በቅርብ ጊዜ ያነበቧቸውን የመማሪያ መጽሐፍ ምዕራፎች በማስታወስ ያቆሙበትን በቀላሉ እንዲቀጥሉ ይረዳል።',
                'sort_order' => 3,
                'is_published' => true,
            ],
            [
                'heading' => 'Managing Your Cookie and Storage Preferences',
                'heading_am' => 'የኩኪ እና የማከማቻ ምርጫዎችን ማስተካከል',
                'body' => 'You can control or clear stored cookies and app cache at any time via your browser settings or mobile app settings. Please note that clearing essential authentication data will require you to re-enter your password on your next visit.',
                'body_am' => 'በማንኛውም ጊዜ በአሳሽዎ ወይም በመተግበሪያው ቅንብሮች በኩል የተቀመጡ ኩኪዎችን እና የመተግበሪያውን ካሽ ማጽዳት ይችላሉ። ነገር ግን አስፈላጊ የማረጋገጫ መረጃዎችን ማጽዳት በሚቀጥለው ጊዜ ሲገቡ የይለፍ ቃልዎን ዳግም ማስገባት እንደሚፈልግ ልብ ይበሉ።',
                'sort_order' => 4,
                'is_published' => true,
            ],
        ];

        foreach ($cookiesSections as $sec) {
            $section = PageSection::where('page_id', $cookiesPage->id)
                ->where('heading', $sec['heading'])
                ->first();

            if ($section) {
                $section->update([
                    'heading_am' => $section->heading_am ?: $sec['heading_am'],
                    'body_am' => $section->body_am ?: $sec['body_am'],
                ]);
            } else {
                PageSection::create(array_merge($sec, ['page_id' => $cookiesPage->id]));
            }
        }
    }
}

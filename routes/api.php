<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HelloController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\GradesController;
use App\Http\Controllers\Api\SubjectsController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\TextbookController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\TutorsController;
use App\Http\Controllers\Api\AdminTutorController;
use App\Http\Controllers\Api\AdminLearningController;
use App\Http\Controllers\Api\AdminNoteController;
use App\Http\Controllers\Api\AdminQuizController;
use App\Http\Controllers\Api\AdminAuditLogController;
use App\Http\Controllers\Api\AdminAiPromptController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\NationalExamController;
use App\Http\Controllers\Api\NationalExamYearController;
use App\Http\Controllers\Api\NationalExamSubjectController;
use App\Http\Controllers\Api\NationalExamQuestionController;
use App\Http\Controllers\Api\AdminNationalExamController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StreamController;
use App\Http\Controllers\Api\ProfileController;

use App\Http\Controllers\Api\PlatformReviewController;
use App\Http\Controllers\Api\ExamNoteController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\LatestUpdatesController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\AdminContactController;
use App\Http\Controllers\Api\CmsPageController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\AdminStaffController;
use App\Http\Controllers\Api\LegalController;
use App\Http\Controllers\Api\ContactSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
*/

// ==========================================
// 1. PUBLIC ROUTES (Unauthenticated)
// ==========================================
Route::middleware(['auth:sanctum', \App\Http\Middleware\RefreshTokenExpiry::class])->group(function () {
    // all your existing protected routes
});
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('api.login')->name('login');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('api.verify_otp');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:6,1')->name('api.resend_otp');
Route::post('/auth/google', [AuthController::class, 'googleLogin'])->middleware('throttle:10,1')->name('api.auth.google');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.forgot_password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.reset_password');
Route::get('/reset-password/{token}', function (string $token) {
    return response()->json(['status' => 'success', 'token' => $token]);
})->name('password.reset');

Route::get('/latest-updates', [LatestUpdatesController::class, 'index'])->name('api.latest_updates.index');
Route::get('/latest-updates/{id}', [LatestUpdatesController::class, 'show'])->name('api.latest_updates.show');
Route::get('/platform-reviews', [PlatformReviewController::class, 'index'])->name('api.platform_reviews.index');
Route::get('/featured-lessons', [ChapterController::class, 'getFeatured'])->name('api.featured_lessons');

// 1. Public route: anyone can send a message
Route::post('/contact', [ContactController::class, 'store'])->name('api.contact.store');
Route::post('/contacts', [ContactController::class, 'store'])->name('api.contacts.store');

Route::get('/pages/{slug}', [CmsPageController::class, 'show'])->name('api.pages.show');

// Public Educational Content & Tutor Discovery Routes
Route::get('/grades', [GradesController::class, 'index'])->name('api.grades');
Route::get('/grades/{grade_id}/subjects', [SubjectsController::class, 'getByGrade'])->name('api.grades.subjects.public')->where('grade_id', '[0-9]+');
Route::get('/subjects', [SubjectsController::class, 'index'])->name('api.subjects');
Route::get('/subjects/{id}', [SubjectsController::class, 'show'])->name('api.subjects.show.public')->where('id', '[0-9]+');
Route::get('/tutors', [TutorsController::class, 'index'])->name('api.tutors');
Route::get('/tutor-subjects', [TutorsController::class, 'getSubjects'])->name('api.tutors.subjects');
Route::get('/tutors/subjects', [TutorsController::class, 'getSubjects'])->name('api.tutors.subjects_alias');
Route::get('/tutors/grades', [TutorsController::class, 'getGrades'])->name('api.tutors.grades');
Route::get('/tutors/{id}', [TutorsController::class, 'show'])->name('api.tutors.show')->where('id', '[0-9]+');
Route::get('/tutors/{id}/reviews', [TutorsController::class, 'getReviews'])->name('api.tutors.reviews')->where('id', '[0-9]+');

// Public National Exam Discovery Catalog Routes
Route::get('/national-exams', [NationalExamController::class, 'index'])->name('api.national_exams.public_index');
Route::get('/national-exams/{id}', [NationalExamController::class, 'show'])->name('api.national_exams.public_show')->where('id', '[0-9]+');
Route::get('/national-exams/{national_exam_id}/years', [NationalExamController::class, 'getYearsByExam'])->name('api.national_exams.public_years')->where('national_exam_id', '[0-9]+');
Route::get('/national-exam-years', [NationalExamYearController::class, 'index'])->name('api.national_exam_years.public_index');
Route::get('/national-exam-years/{id}', [NationalExamYearController::class, 'show'])->name('api.national_exam_years.public_show')->where('id', '[0-9]+');
Route::get('/national-exam-years/{year_id}/subjects', [NationalExamController::class, 'getSubjectsByYear'])->name('api.national_exam_years.public_subjects')->where('year_id', '[0-9]+');

Route::get('/hello', [HelloController::class, 'index'])->name('api.hello');
Route::get('/health', [HealthController::class, 'index'])->name('api.health');

Route::get('/purchases/return', [PurchaseController::class, 'returnFromPayment'])->name('purchases.return');

// Chapa's server calls these directly (server-to-server) — no Sanctum
// token is ever sent with a webhook call, so these MUST stay outside
// the auth:sanctum group below. Moving them here fixes purchases never
// completing (they were previously inside auth:sanctum, which crashed
// every webhook call with "Route [login] not defined" and silently
// prevented the purchase status from ever updating to "success").
Route::post('/vp/chapa/webhook', [PurchaseController::class, 'webhook']);
Route::post('/webhooks/chapa', [PurchaseController::class, 'webhook'])
    ->name('chapa.webhook');

Route::get('/legal/cookie-policy', [LegalController::class, 'cookiePolicy']);

// ==========================================
// 2. AUTHENTICATED USER ROUTES (Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Authenticated User Identity (Resolved via Bearer Token)
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::get('/me', [AuthController::class, 'user'])->name('api.me');

    // User Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::post('/profile', [ProfileController::class, 'update'])->name('api.profile.update_post');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('api.profile.update_patch');

    // Grades & Streams Navigation
    Route::get('/grades/{grade_id}/chapters', [ChapterController::class, 'getByGrade'])->name('api.grades.chapters');
    Route::get('/grades/{grade_id}/streams', [StreamController::class, 'getByGrade'])->name('api.grades.streams');
    Route::get('/streams', [StreamController::class, 'index'])->name('api.streams.index');
    Route::get('/streams/{id}', [StreamController::class, 'show'])->name('api.streams.show');

    // Protected Tutor Routes
    Route::post('/tutors/register', [TutorsController::class, 'register'])->name('api.tutors.register');
    Route::post('/tutor/register', [TutorsController::class, 'register'])->name('api.tutor.register_alias');
    Route::put('/tutors/profile', [TutorsController::class, 'updateProfile'])->name('api.tutors.update_profile');
    Route::post('/tutors/profile', [TutorsController::class, 'updateProfile'])->name('api.tutors.update_profile_post');
    Route::put('/tutor/profile', [TutorsController::class, 'updateProfile'])->name('api.tutor.update_profile_alias');
    Route::post('/tutor/profile', [TutorsController::class, 'updateProfile'])->name('api.tutor.update_profile_post_alias');
    Route::post('/tutors/{id}/reviews', [TutorsController::class, 'storeReview'])->name('api.tutors.store_review')->where('id', '[0-9]+');
    Route::match(['get', 'post', 'put'], '/tutors/notifications', [TutorsController::class, 'notifications'])->name('api.tutors.notifications');
    Route::match(['get', 'post', 'put'], '/tutor/notifications', [TutorsController::class, 'notifications'])->name('api.tutor.notifications_alias');

    // Chapters & Nested Resources
    Route::get('/chapters', [ChapterController::class, 'index'])->name('api.chapters.index');
    Route::get('/chapters/{id}/quiz', [ChapterController::class, 'getQuiz'])->name('api.chapters.get_quiz');
    Route::post('/chapters/{id}/chat', [ChapterController::class, 'chat'])->middleware('throttle:25,1')->name('api.chapters.chat');
    Route::get('/grades/{grade}/subjects/{subject}/chapters/{chapterNumber}/content', [ChapterController::class, 'getContentByLookup'])->name('api.grades.subjects.chapters.content');
    Route::get('/grades/{grade}/subjects/{subject}/chapters/{chapterNumber}/quiz', [ChapterController::class, 'getQuizByLookup'])->name('api.grades.subjects.chapters.quiz');
    Route::post('/grades/{grade}/subjects/{subject}/chapters/{chapterNumber}/chat', [ChapterController::class, 'chatByLookup'])->middleware('throttle:25,1')->name('api.grades.subjects.chapters.chat');
    Route::get('/chapters/{chapterId}/notes', [NoteController::class, 'getByChapter'])->name('api.chapters.notes');
    Route::get('/chapters/{chapter_id}/textbooks', [TextbookController::class, 'getByChapter'])->name('api.chapters.textbooks');
    Route::get('/chapters/{chapter_id}/resources', [ResourceController::class, 'getByChapter'])->name('api.chapters.resources');
    Route::get('/chapters/{chapterId}/resources/{resourceId}/download', [ChapterController::class, 'downloadResource'])->name('api.chapters.resources.download');
    Route::get('/chapters/{chapter_id}/quizzes', [QuizController::class, 'getByChapter'])->name('api.chapters.quizzes');
    Route::get('/chapters/{chapter_id}/videos', [VideoController::class, 'getByChapter'])->name('api.chapters.videos');
    Route::get('/chapters/{chapter_id}/content', [ChapterController::class, 'getContent'])->name('api.chapters.content');
    Route::get('/chapters/{id}', [ChapterController::class, 'show'])->name('api.chapters.show');
    Route::get('/chapters/grade/{gradeId}', [ChapterController::class, 'getByGrade'])->name('api.chapters.by-grade');
    Route::get('/chapters/subject/{subjectId}', [ChapterController::class, 'getBySubject'])
    ->name('api.chapters.by-subject');

    // Quizzes & Results
    Route::get('/quizzes', [QuizController::class, 'index'])->name('api.quizzes.index');
    Route::get('/quizzes/{id}', [QuizController::class, 'show'])->name('api.quizzes.show');
    Route::get('/quizzes/{id}/questions', [QuizController::class, 'getQuestions'])->name('api.quizzes.questions');
    Route::get('/quiz-questions', [QuizController::class, 'indexQuestions'])->name('api.quiz_questions.index');
    Route::post('/quizzes/{quiz_id}/submit', [QuizController::class, 'submit'])->name('api.quizzes.submit');
    Route::get('/quizzes/{quiz_id}/result', [QuizController::class, 'result'])->name('api.quizzes.result');

    // Notes
    Route::get('/notes', [NoteController::class, 'index'])->name('api.notes.index');
    Route::get('/notes/{id}', [NoteController::class, 'show'])->name('api.notes.show');

    // Textbooks & Direct PDF Streaming
    Route::get('/textbooks', [TextbookController::class, 'index'])->name('api.textbooks.index');
    Route::get('/textbooks/{id}', [TextbookController::class, 'show'])->name('api.textbooks.show');
    Route::get('/textbooks/{id}/stream', [PdfController::class, 'streamPdf'])->name('api.textbooks.stream');
    Route::get('/pdf/{id}/stream', [PdfController::class, 'streamPdf'])->name('api.pdf.stream');
    Route::get('/grades/{grade}/subjects/{subject}/chapters/{chapter}/pdf', [PdfController::class, 'streamByHierarchy'])->name('api.grades.subjects.chapters.pdf');

    // Resources
    Route::get('/resources', [ResourceController::class, 'index'])->name('api.resources.index');
    Route::get('/resources/{id}', [ResourceController::class, 'show'])->name('api.resources.show');

    // Videos
    Route::get('/videos', [VideoController::class, 'index'])->name('api.videos.index');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->name('api.videos.show');

    // National Exam System Routes
    Route::get('/national-exams', [NationalExamController::class, 'index'])->name('api.national_exams.index');
    Route::post('/national-exams', [NationalExamController::class, 'store'])->name('api.national_exams.store');
    Route::get('/national-exams/{id}', [NationalExamController::class, 'show'])->name('api.national_exams.show');
    Route::put('/national-exams/{id}', [NationalExamController::class, 'update'])->name('api.national_exams.update');
    Route::delete('/national-exams/{id}', [NationalExamController::class, 'destroy'])->name('api.national_exams.destroy');
    Route::get('/national-exams/{national_exam_id}/years', [NationalExamController::class, 'getYearsByExam'])->name('api.national_exams.years');
    Route::post('/national-exams/{national_exam_id}/submit', [NationalExamController::class, 'submit'])->name('api.national_exams.submit');
    Route::get('/national-exams/{national_exam_id}/result', [NationalExamController::class, 'result'])->name('api.national_exams.result');

    Route::get('/national-exam-years', [NationalExamYearController::class, 'index'])->name('api.national_exam_years.index');
    Route::post('/national-exam-years', [NationalExamYearController::class, 'store'])->name('api.national_exam_years.store');
    Route::get('/national-exam-years/{id}', [NationalExamYearController::class, 'show'])->name('api.national_exam_years.show');
    Route::put('/national-exam-years/{id}', [NationalExamYearController::class, 'update'])->name('api.national_exam_years.update');
    Route::delete('/national-exam-years/{id}', [NationalExamYearController::class, 'destroy'])->name('api.national_exam_years.destroy');
    Route::get('/national-exam-years/{year_id}/subjects', [NationalExamController::class, 'getSubjectsByYear'])->name('api.national_exam_years.subjects');

    Route::get('/national-exam-subjects', [NationalExamSubjectController::class, 'index'])->name('api.national_exam_subjects.index');
    Route::post('/national-exam-subjects', [NationalExamSubjectController::class, 'store'])->name('api.national_exam_subjects.store');
    Route::get('/national-exam-subjects/{id}', [NationalExamSubjectController::class, 'show'])->name('api.national_exam_subjects.show');
    Route::put('/national-exam-subjects/{id}', [NationalExamSubjectController::class, 'update'])->name('api.national_exam_subjects.update');
    Route::delete('/national-exam-subjects/{id}', [NationalExamSubjectController::class, 'destroy'])->name('api.national_exam_subjects.destroy');
    Route::get('/national-exam-subjects/{subject_id}/questions', [NationalExamController::class, 'getQuestionsBySubject'])->name('api.national_exam_subjects.questions');
    Route::get('/national-exam-subjects/{subject_id}/result', [NationalExamController::class, 'subjectResult'])->name('api.national_exam_subjects.result');

    Route::get('/national-exam-questions', [NationalExamQuestionController::class, 'index'])->name('api.national_exam_questions.index');
    Route::post('/national-exam-questions', [NationalExamQuestionController::class, 'store'])->name('api.national_exam_questions.store');
    Route::get('/national-exam-questions/{id}', [NationalExamQuestionController::class, 'show'])->name('api.national_exam_questions.show');
    Route::put('/national-exam-questions/{id}', [NationalExamQuestionController::class, 'update'])->name('api.national_exam_questions.update');
    Route::delete('/national-exam-questions/{id}', [NationalExamQuestionController::class, 'destroy'])->name('api.national_exam_questions.destroy');
    
    // Auth System Extensions
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('api.change_password');
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->name('api.refresh');

    // Profile Photo
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('api.profile.photo');

    // Subjects Search & Recently Viewed
    Route::get('/subjects/search', [SubjectsController::class, 'search'])->name('api.subjects.search');
    Route::get('/subjects/recently-viewed', [SubjectsController::class, 'getRecentlyViewed'])->name('api.subjects.recently_viewed');
    Route::post('/subjects/recently-viewed', [SubjectsController::class, 'recordRecentlyViewed'])->name('api.subjects.recently_viewed_post');
    Route::put('/subjects/recently-viewed', [SubjectsController::class, 'recordRecentlyViewed'])->name('api.subjects.recently_viewed_put');

    // Exam Short Notes
    Route::get('/exam-notes', [ExamNoteController::class, 'index'])->name('api.exam_notes.index');
    Route::get('/exam-notes/{id}', [ExamNoteController::class, 'show'])->name('api.exam_notes.show');

    // Bookmarks
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('api.bookmarks.index');
    Route::post('/bookmarks', [BookmarkController::class, 'store'])->name('api.bookmarks.store');
    Route::delete('/bookmarks/{chapter_id}', [BookmarkController::class, 'destroy'])->name('api.bookmarks.destroy');

    // My Results
    Route::get('/my-results', [ResultController::class, 'myResults'])->name('api.my_results');

    // Tutor Application Status & Remove Profile
    Route::get('/tutor/application-status', [TutorsController::class, 'getApplicationStatus'])->name('api.tutor.application_status');
    Route::get('/tutors/application-status', [TutorsController::class, 'getApplicationStatus'])->name('api.tutors.application_status_alias');
    Route::delete('/tutor/profile', [TutorsController::class, 'removeProfile'])->name('api.tutor.remove_profile');
    Route::delete('/tutors/profile', [TutorsController::class, 'removeProfile'])->name('api.tutors.remove_profile_alias');

    Route::post('/platform-reviews', [PlatformReviewController::class, 'store'])->name('api.platform_reviews.store');
    Route::delete('/tutors/account', [TutorsController::class, 'destroyAccount'])->name('api.tutors.delete');

    // In-App Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.unread_count');
    Route::get('/notifications/latest', [NotificationController::class, 'latest'])->name('api.notifications.latest');
    Route::get('/notifications/stream', [NotificationController::class, 'stream'])->name('api.notifications.stream');
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.read_all');
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.mark_as_read');
    Route::post('/resources/{resource_id}/purchase', [PurchaseController::class, 'initiate'])->name('api.resources.purchase');
    Route::get('/purchases/{tx_ref}/status', [PurchaseController::class, 'checkStatus'])->name('api.purchases.status');
});

// Public: Display on website footer / contact page
Route::get('/contact-settings', [ContactSettingController::class, 'show'])
    ->name('api.contact_settings.show');

// Protected: Admin updates the settings
Route::middleware(['auth:sanctum'])->group(function () {
    Route::match(['put', 'post'], '/contact-settings', [ContactSettingController::class, 'update'])
        ->name('api.contact_settings.update');
});
// Legacy progress fallback route
Route::any('/progress/{any?}', fn() => response()->json(['status' => 'success', 'data' => []]))->where('any', '.*');
Route::get('/video-count', [VideoController::class, 'count'])
    ->name('api.video_count');

// ==========================================
// 3. ADMIN ROUTES (Sanctum + Admin)
// ==========================================
Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // ----------------------------------------------------
    // SUPPORT & COMMUNICATIONS (Contacts & Settings)
    // Accessible strictly to super_admin and support_moderator
    // ----------------------------------------------------
    Route::middleware('role:super_admin,support_moderator,support_agent')->group(function () {
        Route::get('/admin/settings/contact', [ContactSettingController::class, 'getAdmin'])->name('api.admin.settings.contact.get');
        Route::put('/admin/settings/contact', [ContactSettingController::class, 'updateAdmin'])->name('api.admin.settings.contact.update');

        Route::get('/admin/contacts', [AdminContactController::class, 'index'])->name('api.admin.contacts.index');
        Route::patch('/admin/contacts/{id}/read', [AdminContactController::class, 'markAsRead'])->name('api.admin.contacts.read');
        Route::patch('/admin/contacts/{id}/status', [AdminContactController::class, 'markAsRead'])->name('api.admin.contacts.status');
        Route::post('/admin/contacts/{id}/reply', [AdminContactController::class, 'reply'])->name('api.admin.contacts.reply');
        Route::delete('/admin/contacts/{id}', [AdminContactController::class, 'destroy'])->name('api.admin.contacts.destroy');
    });

    // ----------------------------------------------------
    // SUPER ADMIN ONLY: Telemetry, Audit Logs, Staff/RBAC, CMS, Updates, Platform Reviews
    // ----------------------------------------------------
    Route::middleware('role:super_admin')->group(function () {
        // Admin Dashboard Telemetry
        Route::get('/admin/dashboard/summary', [AdminDashboardController::class, 'summary'])->name('api.admin.dashboard.summary');
        Route::get('/admin/dashboard/student-distribution', [AdminDashboardController::class, 'studentDistribution'])->name('api.admin.dashboard.student_distribution');

        // Audit Logs
        Route::get('/admin/audit-logs', [AdminAuditLogController::class, 'index'])->name('api.admin.audit_logs.index');
        Route::post('/admin/audit-logs', [AdminAuditLogController::class, 'store'])->name('api.admin.audit_logs.store');
        Route::post('/admin/audit-logs/prune', [AdminAuditLogController::class, 'prune'])->name('api.admin.audit_logs.prune');
        Route::get('/admin/audit-logs/{id}', [AdminAuditLogController::class, 'show'])->name('api.admin.audit_logs.show')->where('id', '[0-9]+');
        Route::delete('/admin/audit-logs/{id}', [AdminAuditLogController::class, 'destroy'])->name('api.admin.audit_logs.destroy')->where('id', '[0-9]+');

        // Staff & RBAC
        Route::prefix('admin/staff')->group(function () {
            Route::get('/', [AdminStaffController::class, 'index'])->name('api.admin.staff.index');
            Route::get('/lookup-user', [AdminStaffController::class, 'lookupUser'])->name('api.admin.staff.lookup');
            Route::post('/', [AdminStaffController::class, 'store'])->name('api.admin.staff.store');
            Route::post('/promote', [AdminStaffController::class, 'promoteUser'])->name('api.admin.staff.promote');
            Route::get('/{id}', [AdminStaffController::class, 'show'])->name('api.admin.staff.show')->where('id', '[0-9]+');
            Route::put('/{id}', [AdminStaffController::class, 'update'])->name('api.admin.staff.update')->where('id', '[0-9]+');
            Route::patch('/{id}/status', [AdminStaffController::class, 'updateStatus'])->name('api.admin.staff.update_status')->where('id', '[0-9]+');
            Route::put('/{id}/role', [AdminStaffController::class, 'updateRole'])->name('api.admin.staff.update_role')->where('id', '[0-9]+');
            Route::post('/{id}/revoke', [AdminStaffController::class, 'revokeAccess'])->name('api.admin.staff.revoke')->where('id', '[0-9]+');
            Route::delete('/{id}', [AdminStaffController::class, 'destroy'])->name('api.admin.staff.destroy')->where('id', '[0-9]+');
        });

        // Platform Reviews Moderation
        Route::get('/admin/platform-reviews', [PlatformReviewController::class, 'adminIndex'])->name('api.admin.platform_reviews.index');
        Route::patch('/admin/platform-reviews/{id}/status', [PlatformReviewController::class, 'updateStatus'])->name('api.admin.platform_reviews.update_status');
        Route::delete('/admin/platform-reviews/{id}', [PlatformReviewController::class, 'destroy'])->name('api.admin.platform_reviews.destroy');

        // Latest Updates
        Route::get('/admin/latest-updates', [LatestUpdatesController::class, 'adminIndex'])->name('api.admin.latest_updates.index');
        Route::post('/admin/latest-updates', [LatestUpdatesController::class, 'store'])->name('api.admin.latest_updates.store');
        Route::put('/admin/latest-updates/{id}', [LatestUpdatesController::class, 'update'])->name('api.admin.latest_updates.update');
        Route::post('/admin/latest-updates/{id}', [LatestUpdatesController::class, 'update'])->name('api.admin.latest_updates.update_post');
        Route::delete('/admin/latest-updates/{id}', [LatestUpdatesController::class, 'destroy'])->name('api.admin.latest_updates.destroy');
        Route::post('/latest-updates', [LatestUpdatesController::class, 'store'])->name('api.latest_updates.store');
        Route::put('/latest-updates/{id}', [LatestUpdatesController::class, 'update'])->name('api.latest_updates.update');
        Route::post('/latest-updates/{id}', [LatestUpdatesController::class, 'update'])->name('api.latest_updates.update_post');
        Route::delete('/latest-updates/{id}', [LatestUpdatesController::class, 'destroy'])->name('api.latest_updates.destroy');

        // CMS Pages & FAQs
        Route::get('/admin/pages', [CmsPageController::class, 'index'])->name('api.admin.pages.index');
        Route::get('/admin/pages/{id}', [CmsPageController::class, 'adminShow'])->name('api.admin.pages.show');
        Route::post('/admin/pages', [CmsPageController::class, 'store'])->name('api.admin.pages.store');
        Route::put('/admin/pages/{id}', [CmsPageController::class, 'update'])->name('api.admin.pages.update');
        Route::delete('/admin/pages/{id}', [CmsPageController::class, 'destroy'])->name('api.admin.pages.delete');
        Route::put('/admin/pages/{id}/publish', [CmsPageController::class, 'publish'])->name('api.admin.pages.publish');
        Route::put('/admin/pages/{id}/unpublish', [CmsPageController::class, 'unpublish'])->name('api.admin.pages.unpublish');

        Route::post('/admin/pages/{id}/sections', [CmsPageController::class, 'storeSection'])->name('api.admin.pages.sections.store');
        Route::put('/admin/pages/{pageId}/sections/{sectionId}', [CmsPageController::class, 'updateSection'])->name('api.admin.pages.sections.update');
        Route::delete('/admin/pages/{pageId}/sections/{sectionId}', [CmsPageController::class, 'destroySection'])->name('api.admin.pages.sections.delete');
        Route::put('/admin/pages/{id}/sections/reorder', [CmsPageController::class, 'reorderSections'])->name('api.admin.pages.sections.reorder');

        Route::get('/admin/faqs', [CmsPageController::class, 'adminFaqIndex'])->name('api.admin.faqs.index');
        Route::post('/admin/faqs', [CmsPageController::class, 'adminFaqStore'])->name('api.admin.faqs.store');
        Route::get('/admin/faqs/{id}', [CmsPageController::class, 'adminFaqShow'])->name('api.admin.faqs.show');
        Route::put('/admin/faqs/{id}', [CmsPageController::class, 'adminFaqUpdate'])->name('api.admin.faqs.update');
        Route::delete('/admin/faqs/{id}', [CmsPageController::class, 'adminFaqDestroy'])->name('api.admin.faqs.destroy');
        Route::put('/admin/faqs/reorder', [CmsPageController::class, 'adminFaqReorder'])->name('api.admin.faqs.reorder');
        Route::patch('/admin/faqs/{id}/toggle-status', [CmsPageController::class, 'adminFaqToggleStatus'])->name('api.admin.faqs.toggle_status');
    });

    // ----------------------------------------------------
    // TUTOR MODERATOR & SUPER ADMIN: Tutor Marketplace & Reviews
    // ----------------------------------------------------
    Route::middleware('role:super_admin,tutor_moderator')->group(function () {
        Route::get('/admin/tutors', [AdminTutorController::class, 'index'])->name('api.admin.tutors.index');
        Route::get('/admin/tutors/{id}', [AdminTutorController::class, 'show'])->name('api.admin.tutors.show')->where('id', '[0-9]+');
        Route::patch('/admin/tutors/bulk-status', [AdminTutorController::class, 'bulkUpdateStatus'])->name('api.admin.tutors.bulk_status');
        Route::patch('/admin/tutors/{id}/status', [AdminTutorController::class, 'updateStatus'])->name('api.admin.tutors.update_status')->where('id', '[0-9]+');
        Route::get('/admin/tutor-reviews', [AdminTutorController::class, 'reviews'])->name('api.admin.tutor_reviews.index');
        Route::get('/admin/tutors/reviews', [AdminTutorController::class, 'reviews'])->name('api.admin.tutors.reviews.alias');
        Route::patch('/admin/tutor-reviews/{id}/status', [AdminTutorController::class, 'updateReviewStatus'])->name('api.admin.tutor_reviews.update_status')->where('id', '[0-9]+');
    });

    // ----------------------------------------------------
    // ACADEMIC EDITOR & SUPER ADMIN: Curriculum (LMS) & National Exam Engine
    // ----------------------------------------------------
    Route::middleware('role:super_admin,academic_editor')->group(function () {
        Route::get('/admin/cohort-distribution', [AdminLearningController::class, 'cohortDistribution'])->name('api.admin.cohort_distribution');

        // Streams
        Route::post('/admin/streams', [StreamController::class, 'store'])->name('api.admin.streams.store');
        Route::put('/admin/streams/{id}', [StreamController::class, 'update'])->name('api.admin.streams.update');
        Route::delete('/admin/streams/{id}', [StreamController::class, 'delete'])->name('api.admin.streams.delete');

        // Subjects
        Route::get('/admin/subjects', [SubjectsController::class, 'adminIndex'])->name('api.admin.subjects.index');
        Route::get('/admin/subjects/{id}', [SubjectsController::class, 'show'])->name('api.admin.subjects.show')->where('id', '[0-9]+');
        Route::get('/admin/grades/{grade_id}/subjects', [SubjectsController::class, 'getByGrade'])->name('api.admin.grades.subjects')->where('grade_id', '[0-9]+');
        Route::post('/admin/subjects', [SubjectsController::class, 'store'])->name('api.admin.subjects.store');
        Route::put('/admin/subjects/{id}', [SubjectsController::class, 'update'])->name('api.admin.subjects.update')->where('id', '[0-9]+');
        Route::patch('/admin/subjects/{id}', [SubjectsController::class, 'update'])->name('api.admin.subjects.update_patch')->where('id', '[0-9]+');
        Route::delete('/admin/subjects/{id}', [SubjectsController::class, 'destroy'])->name('api.admin.subjects.destroy')->where('id', '[0-9]+');

        // Chapters & Notes
        Route::get('/admin/chapters', [AdminLearningController::class, 'indexChapters'])->name('api.admin.chapters.index');
        Route::get('/admin/chapters/{id}', [AdminLearningController::class, 'showChapter'])->name('api.admin.chapters.show')->where('id', '[0-9]+');
        Route::get('/admin/grades/{grade_id}/chapters', [AdminLearningController::class, 'getChaptersByGrade'])->name('api.admin.grades.chapters')->where('grade_id', '[0-9]+');
        Route::get('/admin/subjects/{subject_id}/chapters', [AdminLearningController::class, 'getChaptersBySubject'])->name('api.admin.subjects.chapters')->where('subject_id', '[0-9]+');
        Route::post('/admin/chapters', [AdminLearningController::class, 'storeChapter'])->name('api.admin.chapters.store');
        Route::put('/admin/chapters/{id}', [AdminLearningController::class, 'updateChapter'])->name('api.admin.chapters.update')->where('id', '[0-9]+');
        Route::patch('/admin/chapters/{id}', [AdminLearningController::class, 'updateChapter'])->name('api.admin.chapters.update_patch')->where('id', '[0-9]+');
        Route::delete('/admin/chapters/{id}', [AdminLearningController::class, 'deleteChapter'])->name('api.admin.chapters.delete')->where('id', '[0-9]+');
        Route::post('/admin/chapters/{chapterId}/generate-notes', [AdminNoteController::class, 'generateAi'])->name('api.admin.chapters.generate_notes')->where('chapterId', '[0-9]+');
        Route::post('/admin/chapters/{id}/generate-quiz', [AdminQuizController::class, 'generateAi'])->name('api.admin.chapters.generate_quiz')->where('id', '[0-9]+');
        Route::get('/admin/chapters/{id}/quizzes', [AdminQuizController::class, 'getByChapter'])->name('api.admin.chapters.quizzes')->where('id', '[0-9]+');
        Route::post('/admin/chapters/{id}/quizzes', [AdminQuizController::class, 'storeQuiz'])->name('api.admin.chapters.quizzes.store')->where('id', '[0-9]+');
        Route::get('/admin/chapters/{id}/quiz', [AdminQuizController::class, 'getByChapter'])->name('api.admin.chapters.quiz')->where('id', '[0-9]+');
        Route::post('/admin/chapters/{id}/quiz/questions', [AdminLearningController::class, 'storeChapterQuizQuestion'])->name('api.admin.chapters.quiz.questions')->where('id', '[0-9]+');
        Route::delete('/admin/chapters/{id}/quiz-questions', [AdminLearningController::class, 'deleteAllChapterQuizQuestions'])->name('api.admin.chapters.quiz.questions.delete_all')->where('id', '[0-9]+');

        // Notes CRUD
        Route::get('/admin/chapters/{chapterId}/notes', [AdminNoteController::class, 'getByChapter'])->name('api.admin.chapters.notes')->where('chapterId', '[0-9]+');
        Route::post('/admin/chapters/{chapterId}/notes', [AdminNoteController::class, 'store'])->name('api.admin.chapters.notes.store')->where('chapterId', '[0-9]+');
        Route::post('/admin/notes', [AdminNoteController::class, 'store'])->name('api.admin.notes.store');
        Route::put('/admin/notes/{id}', [AdminNoteController::class, 'update'])->name('api.admin.notes.update')->where('id', '[0-9]+');
        Route::patch('/admin/notes/{id}/activate', [AdminNoteController::class, 'activate'])->name('api.admin.notes.activate')->where('id', '[0-9]+');
        Route::patch('/admin/notes/{id}/toggle-visibility', [AdminNoteController::class, 'toggleVisibility'])->name('api.admin.notes.toggle_visibility')->where('id', '[0-9]+');
        Route::delete('/admin/notes/{id}', [AdminNoteController::class, 'destroy'])->name('api.admin.notes.delete')->where('id', '[0-9]+');

        // AI Prompts & Settings
        Route::get('/admin/ai-prompts', [AdminAiPromptController::class, 'index'])->name('api.admin.ai_prompts.index');
        Route::put('/admin/ai-prompts', [AdminAiPromptController::class, 'update'])->name('api.admin.ai_prompts.update');
        Route::post('/admin/ai-prompts/reset-notes', [AdminAiPromptController::class, 'resetNotesPrompt'])->name('api.admin.ai_prompts.reset_notes');

        // Textbooks, Resources, Videos
        Route::get('/admin/chapters/{id}/textbook', [AdminLearningController::class, 'getChapterTextbook'])->name('api.admin.chapters.textbook.get')->where('id', '[0-9]+');
        Route::post('/admin/chapters/{id}/textbook', [AdminLearningController::class, 'storeChapterTextbook'])->name('api.admin.chapters.textbook.store')->where('id', '[0-9]+');
        Route::delete('/admin/chapters/{id}/textbook', [AdminLearningController::class, 'deleteChapterTextbook'])->name('api.admin.chapters.textbook.delete')->where('id', '[0-9]+');

        Route::get('/admin/chapters/{id}/resources', [AdminLearningController::class, 'getChapterResources'])->name('api.admin.chapters.resources.get')->where('id', '[0-9]+');
        Route::post('/admin/chapters/{id}/resources', [AdminLearningController::class, 'storeChapterResource'])->name('api.admin.chapters.resources.store')->where('id', '[0-9]+');

        Route::get('/admin/chapters/{id}/videos', [AdminLearningController::class, 'getChapterVideos'])->name('api.admin.chapters.videos.get')->where('id', '[0-9]+');
        Route::post('/admin/chapters/{id}/videos', [AdminLearningController::class, 'storeChapterVideo'])->name('api.admin.chapters.videos.store')->where('id', '[0-9]+');

        Route::post('/admin/textbooks', [AdminLearningController::class, 'storeTextbook'])->name('api.admin.textbooks.store');
        Route::put('/admin/textbooks/{id}', [AdminLearningController::class, 'updateTextbook'])->name('api.admin.textbooks.update');
        Route::delete('/admin/textbooks/{id}', [AdminLearningController::class, 'deleteTextbook'])->name('api.admin.textbooks.delete');

        Route::get('/admin/resources', [AdminLearningController::class, 'indexResources'])->name('api.admin.resources.index');
        Route::post('/admin/resources', [AdminLearningController::class, 'storeResource'])->name('api.admin.resources.store');
        Route::put('/admin/resources/{id}', [AdminLearningController::class, 'updateResource'])->name('api.admin.resources.update');
        Route::patch('/admin/resources/{id}/price', [AdminLearningController::class, 'updatePrice'])->name('api.admin.resources.price.update')->where('id', '[0-9]+');
        Route::delete('/admin/resources/{id}/price', [AdminLearningController::class, 'deletePrice'])->name('api.admin.resources.price.delete')->where('id', '[0-9]+');
        Route::delete('/admin/resources/{id}', [AdminLearningController::class, 'deleteResource'])->name('api.admin.resources.delete');

        Route::post('/admin/videos', [AdminLearningController::class, 'storeVideo'])->name('api.admin.videos.store');
        Route::put('/admin/videos/{id}', [AdminLearningController::class, 'updateVideo'])->name('api.admin.videos.update');
        Route::delete('/admin/videos/{id}', [AdminLearningController::class, 'deleteVideo'])->name('api.admin.videos.delete');

        Route::post('/admin/quizzes', [AdminLearningController::class, 'storeQuiz'])->name('api.admin.quizzes.store');
        Route::put('/admin/quizzes/{id}', [AdminLearningController::class, 'updateQuiz'])->name('api.admin.quizzes.update');
        Route::delete('/admin/quizzes/{id}', [AdminLearningController::class, 'deleteQuiz'])->name('api.admin.quizzes.delete');

        Route::post('/admin/quizzes/{quiz_id}/questions', [AdminQuizController::class, 'storeQuestion'])->name('api.admin.quizzes.questions.store');
        Route::post('/admin/quizzes/{quiz_id}/questions/batch', [AdminLearningController::class, 'storeQuizQuestionsBatch'])->name('api.admin.quizzes.questions.batch');
        Route::post('/admin/quizzes/{quiz_id}/generate-questions', [AdminLearningController::class, 'generateQuizQuestions'])->name('api.admin.quizzes.generate_questions');
        Route::delete('/admin/quizzes/{quiz_id}/questions', [AdminLearningController::class, 'deleteAllQuizQuestions'])->name('api.admin.quizzes.questions.delete_all');
        Route::post('/admin/quiz-questions/bulk-delete', [AdminLearningController::class, 'bulkDeleteQuizQuestions'])->name('api.admin.quiz_questions.bulk_delete');
        Route::put('/admin/quiz-questions/{id}', [AdminQuizController::class, 'updateQuestion'])->name('api.admin.quizzes.questions.update');
        Route::delete('/admin/quiz-questions/{id}', [AdminQuizController::class, 'deleteQuestion'])->name('api.admin.quizzes.questions.delete');

        // National Exams Studio & Question Bank
        Route::prefix('admin/national-exams')->group(function () {
            Route::get('/overview', [AdminNationalExamController::class, 'overview'])->name('api.admin.national_exams.overview');
            Route::get('/', [AdminNationalExamController::class, 'index'])->name('api.admin.national_exams.index');
            Route::post('/years', [AdminNationalExamController::class, 'storeYear'])->name('api.admin.national_exams.years.store');
            Route::put('/years/{id}', [AdminNationalExamController::class, 'updateYear'])->name('api.admin.national_exams.years.update');
            Route::patch('/years/{id}', [AdminNationalExamController::class, 'updateYear'])->name('api.admin.national_exams.years.patch');
            Route::delete('/years/{id}', [AdminNationalExamController::class, 'destroyYear'])->name('api.admin.national_exams.years.destroy');
            Route::get('/years/{yearId}/subjects/{subjectId}/questions', [AdminNationalExamController::class, 'getQuestions'])->name('api.admin.national_exams.questions.by_year_subject');
            Route::post('/questions', [AdminNationalExamController::class, 'storeQuestion'])->name('api.admin.national_exams.questions.store');
            Route::put('/questions/{id}', [AdminNationalExamController::class, 'updateQuestion'])->name('api.admin.national_exams.questions.update');
            Route::patch('/questions/{id}', [AdminNationalExamController::class, 'updateQuestion'])->name('api.admin.national_exams.questions.patch');
            Route::delete('/questions/{id}', [AdminNationalExamController::class, 'destroyQuestion'])->name('api.admin.national_exams.questions.destroy');
            Route::post('/questions/batch-import', [AdminNationalExamController::class, 'batchImport'])->name('api.admin.national_exams.questions.batch_import');
        });

        Route::put('/admin/national-exam-subjects/{id}', [AdminNationalExamController::class, 'updateSubject'])->name('api.admin.national_exam_subjects.update');

        Route::get('/admin/national-exams', [AdminNationalExamController::class, 'indexNationalExams'])->name('api.admin.national_exams.index_alt');
        Route::post('/admin/national-exams', [AdminNationalExamController::class, 'storeNationalExam'])->name('api.admin.national_exams.store');
        Route::put('/admin/national-exams/{id}', [AdminNationalExamController::class, 'updateNationalExam'])->name('api.admin.national_exams.update');
        Route::delete('/admin/national-exams/{id}', [AdminNationalExamController::class, 'deleteNationalExam'])->name('api.admin.national_exams.delete');

        Route::delete('/admin/national-exams/years/{id}', [AdminNationalExamController::class, 'destroyYear'])->name('api.admin.national_exams.years.destroy_alt');
        Route::post('/admin/national-exam-years', [AdminNationalExamController::class, 'storeYear'])->name('api.admin.national_exam_years.store');
        Route::put('/admin/national-exam-years/{id}', [AdminNationalExamController::class, 'updateYear'])->name('api.admin.national_exam_years.update');
        Route::delete('/admin/national-exam-years/{id}', [AdminNationalExamController::class, 'destroyYear'])->name('api.admin.national_exam_years.delete');

        Route::post('/admin/national-exams/years/{yearId}/subjects', [AdminNationalExamController::class, 'storeSubject'])->name('api.admin.national_exams.years.subjects.store');
        Route::put('/admin/national-exams/subjects/{id}', [AdminNationalExamController::class, 'updateSubject'])->name('api.admin.national_exams.subjects.update');
        Route::delete('/admin/national-exams/subjects/{id}', [AdminNationalExamController::class, 'destroySubject'])->name('api.admin.national_exams.subjects.destroy');
        Route::post('/admin/national-exam-subjects', [AdminNationalExamController::class, 'storeSubject'])->name('api.admin.national_exam_subjects.store');
        Route::delete('/admin/national-exam-subjects/{id}', [AdminNationalExamController::class, 'destroySubject'])->name('api.admin.national_exam_subjects.delete');

        Route::get('/admin/national-exams/years/{yearId}/subjects/{subjectId}/questions', [AdminNationalExamController::class, 'getYearSubjectQuestions'])->name('api.admin.national_exams.year_subject_questions');
        Route::post('/admin/national-exams/questions', [AdminNationalExamController::class, 'storeNationalExamQuestion'])->name('api.admin.national_exams.questions.store_alt');
        Route::put('/admin/national-exams/questions/{id}', [AdminNationalExamController::class, 'updateNationalExamQuestion'])->name('api.admin.national_exams.questions.update_alt');
        Route::delete('/admin/national-exams/questions/{id}', [AdminNationalExamController::class, 'deleteNationalExamQuestion'])->name('api.admin.national_exams.questions.delete_alt');
        Route::post('/admin/national-exams/questions/batch-import', [AdminNationalExamController::class, 'batchImportQuestions'])->name('api.admin.national_exams.questions.batch_import_alt');

        Route::post('/admin/national-exam-questions', [AdminNationalExamController::class, 'storeNationalExamQuestion'])->name('api.admin.national_exam_questions.store');
        Route::put('/admin/national-exam-questions/{id}', [AdminNationalExamController::class, 'updateNationalExamQuestion'])->name('api.admin.national_exam_questions.update');
        Route::delete('/admin/national-exam-questions/{id}', [AdminNationalExamController::class, 'deleteNationalExamQuestion'])->name('api.admin.national_exam_questions.delete');
        Route::post('/admin/national-exams/import', [AdminNationalExamController::class, 'importExam'])->name('api.admin.national_exams.import');
    });
});



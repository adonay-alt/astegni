import { AdminAuditLog, StaffMember, RolePermission } from "@/types/settings"
import { PlatformUpdate, CmsPage } from "@/types/cms"

export const MOCK_AUDIT_LOGS: AdminAuditLog[] = [
  {
    id: 1,
    admin_name: "Abebe Bekele",
    admin_email: "abebe@asteghi.com",
    action: "tutor.approved",
    auditable_type: "App\\Models\\TutorProfile",
    auditable_id: 2,
    ip_address: "197.156.104.22",
    user_agent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0",
    created_at: "2026-09-10T08:15:00Z",
    old_values: {
      id: 2,
      name: "Yohannes Tesfaye",
      status: "pending",
      subjects: ["Biology", "Chemistry"],
    },
    new_values: {
      id: 2,
      name: "Yohannes Tesfaye",
      status: "approved",
      verified_by: "Abebe Bekele",
      approved_at: "2026-09-10T08:15:00Z",
    },
  },
  {
    id: 2,
    admin_name: "Super Admin",
    admin_email: "admin@asteghi.com",
    action: "user.role_updated",
    auditable_type: "App\\Models\\User",
    auditable_id: 201,
    ip_address: "197.156.104.22",
    user_agent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0",
    created_at: "2026-09-10T07:40:00Z",
    old_values: { role: "student" },
    new_values: {
      target_user_id: 201,
      target_name: "Abel Kebede",
      role: "academic_editor",
    },
  },
  {
    id: 3,
    admin_name: "Mulugeta Asrat",
    admin_email: "mulugeta.curriculum@asteghi.com",
    action: "chapter.ai_notes_regenerated",
    auditable_type: "App\\Models\\Chapter",
    auditable_id: 101,
    ip_address: "197.156.90.14",
    user_agent: "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)",
    created_at: "2026-09-09T18:20:00Z",
    old_values: {
      chapter_id: 101,
      title: "Thermodynamics & Heat",
      ai_notes_status: "draft",
      word_count: 1420,
    },
    new_values: {
      chapter_id: 101,
      title: "Thermodynamics & Heat",
      ai_notes_status: "ready",
      word_count: 3850,
      model_used: "EdTech AI Pipeline",
    },
  },
  {
    id: 4,
    admin_name: "Mulugeta Asrat",
    admin_email: "mulugeta.curriculum@asteghi.com",
    action: "national_exam.questions_imported",
    auditable_type: "App\\Models\\NationalExam",
    auditable_id: 1,
    ip_address: "197.156.90.14",
    user_agent: "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)",
    created_at: "2026-09-08T12:00:00Z",
    old_values: {
      exam_id: 1,
      total_questions: 585,
    },
    new_values: {
      exam_id: 1,
      imported_batch_count: 65,
      total_questions: 650,
      file_source: "euee_2016_physics_natural.csv",
    },
  },
  {
    id: 5,
    admin_name: "Selamawit Moderator",
    admin_email: "selam.mod@asteghi.com",
    action: "platform_review.approved",
    auditable_type: "App\\Models\\PlatformReview",
    auditable_id: 2,
    ip_address: "197.156.110.8",
    user_agent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
    created_at: "2026-09-05T12:10:00Z",
    old_values: {
      review_id: 2,
      status: "pending",
      rating: 5,
    },
    new_values: {
      review_id: 2,
      status: "approved",
      visible_on_homepage: true,
    },
  },
]

export const MOCK_STAFF: StaffMember[] = []

export const RBAC_PERMISSIONS_MATRIX: RolePermission[] = [
  {
    module: "Tutor Application Verification",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: true,
  },
  {
    module: "Platform Testimonials Moderation",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
  },
  {
    module: "LMS Curriculum & Chapters CRUD",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
  },
  {
    module: "AI Synthesis Notes Trigger",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
  },
  {
    module: "National Exam Question Bank",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
  },
  {
    module: "Bulk Question CSV/JSON Import",
    super_admin: true,
    academic_editor: true,
    tutor_moderator: false,
  },
  {
    module: "CMS Static Pages & FAQ Editor",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
  },
  {
    module: "Platform Latest Updates & Announcements Posting",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
  },
  {
    module: "Immutable Audit Log Trail",
    super_admin: true,
    academic_editor: false,
    tutor_moderator: false,
  },
]

export const MOCK_UPDATES: PlatformUpdate[] = []

export const MOCK_CMS_PAGES: CmsPage[] = [
  {
    id: 1,
    title: "Terms of Service",
    slug: "terms",
    type: "legal",
    is_published: true,
    last_updated: "2026-08-20T10:00:00Z",
    sections_count: 3,
    sections: [
      {
        id: "sec_1",
        title: "1. Acceptance of Educational Services",
        content: "By accessing Asteghi EdTech platforms, students, parents, and certified tutors agree to comply with Ethiopian curriculum guidelines and copyright regulations protecting national textbook slices.",
        sort_order: 1,
      },
      {
        id: "sec_2",
        title: "2. Tutor Accreditation and Conduct",
        content: "Tutors registered on the Asteghi marketplace must provide valid Ethiopian higher education credentials and adhere to strict safeguarding standards during private sessions.",
        sort_order: 2,
      },
      {
        id: "sec_3",
        title: "3. Account Security and Device Limits",
        content: "Each student subscription allows access on up to three concurrent devices. Sharing credentials across different individuals is strictly monitored.",
        sort_order: 3,
      },
    ],
  },
  {
    id: 2,
    title: "Privacy Policy",
    slug: "privacy",
    type: "legal",
    is_published: true,
    last_updated: "2026-08-15T14:30:00Z",
    sections_count: 2,
    sections: [
      {
        id: "sec_p1",
        title: "Data Collection & Student Information",
        content: "Asteghi collects names, school affiliations, grade levels, and telephone numbers solely to personalize curriculum progression and send exam readiness alerts.",
        sort_order: 1,
      },
      {
        id: "sec_p2",
        title: "Protection of Educational Progress Data",
        content: "Individual quiz attempt results and national exam trial metrics are kept confidential and are not shared with third parties without parental or institutional consent.",
        sort_order: 2,
      },
    ],
  },
  {
    id: 3,
    title: "Frequently Asked Questions (FAQ)",
    slug: "faq",
    type: "faq",
    is_published: true,
    last_updated: "2026-09-01T12:00:00Z",
    sections_count: 3,
    sections: [
      {
        id: "faq_1",
        title: "How do I stream textbook PDF chapters on low-bandwidth connections?",
        content: "Our PDF streaming engine slices chapters into optimized chunks. You only stream the pages currently visible, saving over 70% mobile data compared to downloading whole books.",
        sort_order: 1,
      },
      {
        id: "faq_2",
        title: "Are the National Exam questions identical to official ministry papers?",
        content: "Yes, all entrance examination papers from 2014 E.C. through 2016 E.C. are digitized verbatim from official Ministry of Education assessments with verified answer keys.",
        sort_order: 2,
      },
      {
        id: "faq_3",
        title: "How are tutors verified on Asteghi?",
        content: "Every tutor must pass an administrative audit verifying university graduation degrees, teaching experience, and phone identity before their profile is unlocked for bookings.",
        sort_order: 3,
      },
    ],
  },
]

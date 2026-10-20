<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminStaffController extends Controller
{
    /**
     * Display a listing of all administrative staff members with role KPIs.
     */
    public function index(Request $request): JsonResponse
    {
        $staffMembers = User::where('is_admin', 1)
            ->orWhereIn('role', ['super_admin', 'academic_editor', 'tutor_moderator', 'support_moderator', 'support_agent'])
            ->with(['studentProfile', 'tutorProfile'])
            ->orderBy('id', 'desc')
            ->get();

        $formatted = $staffMembers->map(function ($user) {
            $effectiveRole = $user->role;
            if (empty($effectiveRole) || $effectiveRole === 'student') {
                $effectiveRole = $user->is_admin ? 'super_admin' : 'student';
            }

            return [
                'id' => (int) $user->id,
                'name' => $user->name ?: ($user->first_name . ' ' . $user->last_name),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $effectiveRole,
                'is_admin' => (bool) $user->is_admin,
                'profile_photo_url' => $user->profile_photo_url,
                'is_active' => (bool) ($user->is_active ?? true),
                'status' => ($user->is_active ?? true) ? 'active' : 'inactive',
                'has_student_profile' => $user->studentProfile !== null,
                'has_tutor_profile' => $user->tutorProfile !== null,
                'tutor_status' => $user->tutorProfile?->status,
                'created_at' => $user->created_at?->toISOString() ?? $user->created_at,
                'last_active_at' => $user->updated_at?->toISOString() ?? $user->created_at?->toISOString(),
            ];
        });

        $superAdminsCount = $formatted->where('role', 'super_admin')->count();
        $academicEditorsCount = $formatted->where('role', 'academic_editor')->count();
        $tutorModeratorsCount = $formatted->where('role', 'tutor_moderator')->count();
        $supportModeratorsCount = $formatted->filter(fn($u) => in_array($u['role'], ['support_moderator', 'support_agent']))->count();

        $rolesDefinition = [
            [
                'role' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Unrestricted access to all modules, audit trails, and RBAC management.',
                'color' => 'rose',
                'is_staff' => true,
                'count' => $superAdminsCount,
            ],
            [
                'role' => 'academic_editor',
                'name' => 'Academic Editor',
                'description' => 'Curriculum LMS (Grades, Subjects, Chapters, Notes, Quizzes, Media) and National Exams.',
                'color' => 'blue',
                'is_staff' => true,
                'count' => $academicEditorsCount,
            ],
            [
                'role' => 'tutor_moderator',
                'name' => 'Tutor Moderator',
                'description' => 'Tutor verification queue, dossier review, and credential approval.',
                'color' => 'amber',
                'is_staff' => true,
                'count' => $tutorModeratorsCount,
            ],
            [
                'role' => 'support_moderator',
                'name' => 'Support Moderator',
                'description' => 'Support inquiries, official reply dispatch, and platform contact info settings.',
                'color' => 'emerald',
                'is_staff' => true,
                'count' => $supportModeratorsCount,
            ],
        ];

        $permissionsMatrix = [
            [
                'module' => 'Support Inquiries & Official Reply Dispatch',
                'description' => 'Triage incoming student inquiries, manage ticket status, and dispatch authenticated email replies',
                'category' => 'Support',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => false,
                'support_moderator' => true,
            ],
            [
                'module' => 'Platform Contact Info Settings',
                'description' => 'Update official platform support email address, primary hotline, and secondary phone numbers',
                'category' => 'Support',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => false,
                'support_moderator' => true,
            ],
            [
                'module' => 'Tutor Application Verification & Dossiers',
                'description' => 'Review teacher applications, verify degrees, and inspect national IDs',
                'category' => 'Tutors',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => true,
                'support_moderator' => false,
            ],
            [
                'module' => 'Platform Testimonials & Review Moderation',
                'description' => 'Approve, flag, or reject public student testimonials for landing showcase',
                'category' => 'Marketing',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'LMS Curriculum, Grades & Chapters Management',
                'description' => 'Create and update grades, subjects, chapters, and curriculum syllabus hierarchy',
                'category' => 'Curriculum',
                'super_admin' => true,
                'academic_editor' => true,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'Chapter Media Assets (Textbooks, PDFs, Videos)',
                'description' => 'Upload and attach textbook PDFs, resources, and video lectures to chapters',
                'category' => 'Curriculum',
                'super_admin' => true,
                'academic_editor' => true,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'AI Notes Synthesis & Quizzes Question Studio',
                'description' => 'Trigger AI synthesis notes generation and craft chapter quizzes and practice problems',
                'category' => 'Curriculum',
                'super_admin' => true,
                'academic_editor' => true,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'National Exam Question Bank (Years & Subjects)',
                'description' => 'Maintain past entrance exam repositories across years and subject disciplines',
                'category' => 'Exams',
                'super_admin' => true,
                'academic_editor' => true,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'Bulk Questions Batch Import (JSON/CSV)',
                'description' => 'Bulk ingest hundreds of standardized questions with answer keys from external files',
                'category' => 'Exams',
                'super_admin' => true,
                'academic_editor' => true,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'CMS Static Pages, Terms & Policy Editor',
                'description' => 'Author and modify Terms of Service, Privacy Policy, and FAQ content',
                'category' => 'CMS & Content',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'Platform Latest Updates & Announcements Posting',
                'description' => 'Publish, toggle, and manage live banner alerts and public changelogs',
                'category' => 'CMS & Content',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'Immutable Operational Audit Trail',
                'description' => 'Read-only access to chronological security logs tracking all administrative actions',
                'category' => 'Security',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
            [
                'module' => 'Staff Directory & RBAC Access Management',
                'description' => 'Invite new staff, change roles, elevate accounts, and revoke administrator access',
                'category' => 'IAM',
                'super_admin' => true,
                'academic_editor' => false,
                'tutor_moderator' => false,
                'support_moderator' => false,
            ],
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Staff members retrieved successfully',
            'data' => $formatted->values(),
            'roles' => $rolesDefinition,
            'permissions_matrix' => $permissionsMatrix,
            'kpis' => [
                'total_staff' => $formatted->count(),
                'super_admins' => $superAdminsCount,
                'academic_editors' => $academicEditorsCount,
                'tutor_moderators' => $tutorModeratorsCount,
                'support_moderators' => $supportModeratorsCount,
            ],
        ]);
    }

    /**
     * Search and lookup an existing user by email to preview their account before promotion.
     */
    public function lookupUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = strtolower(trim($request->query('email')));

        $user = User::whereRaw('LOWER(email) = ?', [$query])
            ->orWhere('id', is_numeric($query) ? (int) $query : 0)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => "No user record found matching '{$query}'.",
            ], 404);
        }

        $effectiveRole = $user->role ?? ($user->is_admin ? 'super_admin' : 'student');

        return response()->json([
            'status' => 'success',
            'message' => 'User found',
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name ?: ($user->first_name . ' ' . $user->last_name),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $effectiveRole,
                'is_admin' => (bool) $user->is_admin,
                'is_staff' => (bool) ($user->is_admin || in_array($effectiveRole, ['super_admin', 'academic_editor', 'tutor_moderator', 'support_moderator', 'support_agent'])),
                'profile_photo_url' => $user->profile_photo_url,
                'created_at' => $user->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Create and onboard a new administrative staff member directly.
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Resolve first_name and last_name from single name or individual fields
        $fullName = trim($request->input('name') ?? $request->input('full_name') ?? '');
        if (!empty($fullName)) {
            $parts = explode(' ', $fullName, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? 'Staff';
        } else {
            $firstName = $request->input('first_name', 'Staff');
            $lastName = $request->input('last_name', 'Member');
        }

        // 2. Resolve role slug from display text or clean value
        $rawRole = strtolower($request->input('role', 'academic_editor'));
        if (str_contains($rawRole, 'academic')) {
            $role = 'academic_editor';
        } elseif (str_contains($rawRole, 'tutor')) {
            $role = 'tutor_moderator';
        } elseif (str_contains($rawRole, 'support')) {
            $role = 'support_moderator';
        } elseif (str_contains($rawRole, 'super') || str_contains($rawRole, 'admin')) {
            $role = 'super_admin';
        } else {
            $role = 'academic_editor';
        }

        // 3. Validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $isActive = true;
        if ($request->has('is_active')) {
            $isActive = $request->boolean('is_active');
        } elseif ($request->has('status')) {
            $isActive = $request->input('status') === 'active';
        }

        // 4. Create User in MySQL with is_admin = 1
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role' => $role,
            'is_admin' => 1,
            'email_verified_at' => now(),
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'is_active')) {
            $userData['is_active'] = $isActive;
        }
        $user = User::create($userData);

        // 5. Record in admin audit log
        if (class_exists(\App\Services\AuditLogService::class)) {
            \App\Services\AuditLogService::log(
                'staff.created',
                $user,
                null,
                [
                    'email' => $user->email,
                    'role'  => $user->role,
                    'name'  => trim("{$user->first_name} {$user->last_name}"),
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Staff member {$firstName} {$lastName} created successfully.",
            'data' => [
                'id' => (int) $user->id,
                'name' => "{$user->first_name} {$user->last_name}",
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => (bool) $user->is_admin,
                'is_active' => (bool) ($user->is_active ?? true),
                'status' => ($user->is_active ?? true) ? 'active' : 'inactive',
                'created_at' => $user->created_at?->toISOString() ?? $user->created_at,
            ]
        ], 201);
    }

    /**
     * Promote an existing platform user (student or tutor) to an administrative staff role.
     */
    public function promoteUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:user_id|nullable|email',
            'user_id' => 'required_without:email|nullable|integer',
            'role' => 'required|string|in:super_admin,academic_editor,tutor_moderator,support_moderator,support_agent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = null;
        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
        } elseif ($request->filled('email')) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($request->email))])->first();
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found to promote.',
            ], 404);
        }

        $oldValues = [
            'role' => $user->role,
            'is_admin' => $user->is_admin,
        ];

        $user->role = $request->role;
        $user->is_admin = 1;
        $user->save();

        // Record in admin audit log
        if (class_exists(\App\Services\AuditLogService::class)) {
            \App\Services\AuditLogService::log(
                'staff.promoted',
                $user,
                $oldValues,
                [
                    'role' => $user->role,
                    'is_admin' => 1,
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "User {$user->name} has been promoted to '{$user->role}'.",
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => true,
                'created_at' => $user->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Update the assigned administrative role for an existing staff member.
     */
    public function updateRole(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:super_admin,academic_editor,tutor_moderator,support_moderator,support_agent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff member not found.',
            ], 404);
        }

        // Prevent demoting the only remaining Super Admin
        if ($user->role === 'super_admin' && $request->role !== 'super_admin') {
            $superAdminsCount = User::where('role', 'super_admin')
                ->where('is_admin', 1)
                ->count();

            if ($superAdminsCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot demote the only remaining Super Admin on the platform.',
                ], 422);
            }
        }

        $oldValues = ['role' => $user->role];
        $user->role = $request->role;
        $user->is_admin = 1;
        $user->save();

        // Record in admin audit log
        if (class_exists(\App\Services\AuditLogService::class)) {
            \App\Services\AuditLogService::log(
                'staff.role_updated',
                $user,
                $oldValues,
                ['role' => $user->role]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Role for {$user->name} updated to '{$user->role}'.",
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => true,
            ],
        ]);
    }

    /**
     * Revoke staff privileges, resetting the account to standard student status.
     */
    public function revokeAccess(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff member not found.',
            ], 404);
        }

        // Prevent self-revocation
        $currentAuthId = auth()->id();
        if ($currentAuthId && (int) $currentAuthId === (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot revoke your own staff privileges.',
            ], 403);
        }

        // Prevent revoking the only Super Admin
        if ($user->role === 'super_admin') {
            $superAdminsCount = User::where('role', 'super_admin')
                ->where('is_admin', 1)
                ->count();

            if ($superAdminsCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot revoke privileges from the only remaining Super Admin.',
                ], 422);
            }
        }

        $oldValues = [
            'role' => $user->role,
            'is_admin' => $user->is_admin,
        ];

        $user->is_admin = 0;
        $user->role = 'student';
        $user->save();

        // Revoke active Sanctum tokens for the revoked user
        try {
            $user->tokens()->delete();
        } catch (\Throwable $e) {}

        // Record in admin audit log
        if (class_exists(\App\Services\AuditLogService::class)) {
            \App\Services\AuditLogService::log(
                'staff.revoked',
                $user,
                $oldValues,
                [
                    'role' => 'student',
                    'is_admin' => 0,
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Staff access revoked for {$user->name}. Account reset to standard student status.",
        ]);
    }

    /**
     * Display detailed profile data for a specific staff member.
     */
    public function show($id): JsonResponse
    {
        $user = User::with(['studentProfile', 'tutorProfile'])->find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff member not found.',
            ], 404);
        }

        $effectiveRole = $user->role;
        if (empty($effectiveRole) || $effectiveRole === 'student') {
            $effectiveRole = $user->is_admin ? 'super_admin' : 'student';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name ?: ($user->first_name . ' ' . $user->last_name),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $effectiveRole,
                'is_admin' => (bool) $user->is_admin,
                'is_active' => (bool) ($user->is_active ?? true),
                'status' => ($user->is_active ?? true) ? 'active' : 'inactive',
                'created_at' => $user->created_at?->toISOString() ?? $user->created_at,
                'last_active_at' => $user->updated_at?->toISOString() ?? $user->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Update an administrative staff account details (Name, Email, Role, Status, Password).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff member not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'sometimes|required|string|in:super_admin,academic_editor,tutor_moderator,support_moderator,support_agent',
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive',
            'password' => 'sometimes|nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $superAdminsCount = User::where('role', 'super_admin')->where('is_admin', 1)->count();
        $isCurrentSuperAdmin = $user->role === 'super_admin';
        $currentAuthId = auth()->id();

        // 1. Guard against demoting the only super admin
        if ($request->has('role') && $isCurrentSuperAdmin && $request->role !== 'super_admin' && $superAdminsCount <= 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot demote the only remaining Super Admin on the platform.',
            ], 422);
        }

        // Determine requested active state
        $newActiveState = null;
        if ($request->has('is_active')) {
            $newActiveState = $request->boolean('is_active');
        } elseif ($request->has('status')) {
            $newActiveState = $request->status === 'active';
        }

        // 2. Guard against deactivating self
        if ($newActiveState === false && $currentAuthId && (int) $currentAuthId === (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot deactivate your own administrative account.',
            ], 422);
        }

        // 3. Guard against deactivating only super admin
        if ($newActiveState === false && $isCurrentSuperAdmin && $superAdminsCount <= 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot deactivate the only remaining Super Admin.',
            ], 422);
        }

        $oldValues = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) ($user->is_active ?? true),
        ];

        if ($request->has('first_name')) {
            $user->first_name = trim($request->first_name);
        }
        if ($request->has('last_name')) {
            $user->last_name = trim($request->last_name);
        }
        if ($request->has('email')) {
            $user->email = strtolower(trim($request->email));
        }
        if ($request->has('role')) {
            $user->role = $request->role;
            $user->is_admin = 1;
        }
        if ($newActiveState !== null) {
            $user->is_active = $newActiveState;
            if (!$newActiveState) {
                // Invalidate tokens immediately
                try {
                    $user->tokens()->delete();
                } catch (\Throwable $e) {}
            }
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $newValues = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
        ];

        // Audit Log
        if (class_exists(\App\Services\AuditLogService::class)) {
            \App\Services\AuditLogService::log(
                'staff.updated',
                $user,
                $oldValues,
                $newValues,
                (int) ($currentAuthId ?? 1)
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Staff account for {$user->first_name} {$user->last_name} updated successfully.",
            'data' => [
                'id' => (int) $user->id,
                'name' => $user->name ?: ($user->first_name . ' ' . $user->last_name),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => (bool) $user->is_admin,
                'is_active' => (bool) ($user->is_active ?? true),
                'status' => ($user->is_active ?? true) ? 'active' : 'inactive',
                'created_at' => $user->created_at?->toISOString() ?? $user->created_at,
                'last_active_at' => $user->updated_at?->toISOString() ?? $user->created_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Activate or Deactivate a staff account status.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff member not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required_without:status|boolean',
            'status' => 'required_without:is_active|string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $newActiveState = $request->has('is_active')
            ? $request->boolean('is_active')
            : ($request->status === 'active');

        $currentAuthId = auth()->id();

        // 1. Guard against self-deactivation
        if ($newActiveState === false && $currentAuthId && (int) $currentAuthId === (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        // 2. Guard against deactivating the only remaining super admin
        if ($newActiveState === false && $user->role === 'super_admin') {
            $superAdminsCount = User::where('role', 'super_admin')->where('is_admin', 1)->count();
            if ($superAdminsCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot deactivate the only remaining Super Admin on the platform.',
                ], 422);
            }
        }

        $oldStatus = ($user->is_active ?? true) ? 'active' : 'inactive';
        $user->is_active = $newActiveState;
        $user->save();

        if (!$newActiveState) {
            // Revoke active sessions / tokens immediately
            try {
                $user->tokens()->delete();
            } catch (\Throwable $e) {}
        }

        $newStatus = $newActiveState ? 'active' : 'inactive';

        // Record in audit log
        if (class_exists(\App\Services\AuditLogService::class)) {
            \App\Services\AuditLogService::log(
                $newActiveState ? 'staff.activated' : 'staff.deactivated',
                $user,
                ['status' => $oldStatus, 'is_active' => !$newActiveState],
                ['status' => $newStatus, 'is_active' => $newActiveState],
                (int) ($currentAuthId ?? 1)
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Account for {$user->first_name} {$user->last_name} has been set to {$newStatus}.",
            'data' => [
                'id' => (int) $user->id,
                'is_active' => (bool) $user->is_active,
                'status' => $newStatus,
            ],
        ]);
    }

    /**
     * Permanently delete an administrative staff member account.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Staff member not found.',
            ], 404);
        }

        $currentAuthId = auth()->id();

        // 1. Guard against self-deletion
        if ($currentAuthId && (int) $currentAuthId === (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own administrative account.',
            ], 422);
        }

        // 2. Guard against deleting the only super admin
        if ($user->role === 'super_admin') {
            $superAdminsCount = User::where('role', 'super_admin')->where('is_admin', 1)->count();
            if ($superAdminsCount <= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete the only remaining Super Admin on the platform.',
                ], 422);
            }
        }

        $deletedSnapshot = [
            'name' => "{$user->first_name} {$user->last_name}",
            'email' => $user->email,
            'role' => $user->role,
            'is_admin' => $user->is_admin,
            'is_active' => (bool) ($user->is_active ?? true),
        ];

        // Revoke tokens and delete user
        try {
            $user->tokens()->delete();
        } catch (\Throwable $e) {}

        $user->delete();

        // Record in audit log
        if (class_exists(\App\Services\AuditLogService::class)) {
            \App\Services\AuditLogService::log(
                'staff.deleted',
                'App\Models\User',
                $deletedSnapshot,
                null,
                (int) ($currentAuthId ?? 1)
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => "Staff member {$deletedSnapshot['name']} has been permanently removed.",
        ]);
    }
}

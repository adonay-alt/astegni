<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminAuditLogController extends Controller
{
    /**
     * Master catalog of all filterable administrative system activities grouped by module.
     */
    public static array $actionCatalog = [
        'Resources & Pricing' => [
            'resource.uploaded',
            'resource.updated',
            'resource.price_updated',
            'resource.price_removed',
            'resource.deleted',
        ],
        'Chapters & Curriculum' => [
            'chapter.created',
            'chapter.updated',
            'chapter.deleted',
            'chapter.ai_notes_regenerated',
            'stream.created',
            'stream.updated',
            'stream.deleted',
            'subject.created',
            'subject.updated',
            'subject.deleted',
        ],
        'Media & Textbooks' => [
            'textbook.saved',
            'textbook.updated',
            'textbook.deleted',
            'video.created',
            'video.updated',
            'video.deleted',
        ],
        'Notes & AI Synthesis' => [
            'note.created',
            'note.updated',
            'note.deleted',
            'note.activated',
            'note.ai_generated',
            'note.visibility_toggled',
        ],
        'AI Prompts & Settings' => [
            'ai_settings.updated',
            'ai_settings.reset_notes',
        ],
        'Quizzes & Questions' => [
            'quiz.created',
            'quiz.updated',
            'quiz.deleted',
            'quiz.ai_generated',
            'quiz_question.created',
            'quiz_question.updated',
            'quiz_question.deleted',
            'quiz_questions.bulk_deleted',
            'quiz_questions.all_deleted',
            'quiz_questions.batch_created',
        ],
        'National Entrance Exams' => [
            'national_exam.created',
            'national_exam.updated',
            'national_exam.deleted',
            'exam_year.created',
            'exam_year.updated',
            'exam_year.deleted',
            'exam_subject.created',
            'exam_subject.updated',
            'exam_subject.deleted',
            'exam_question.created',
            'exam_question.updated',
            'exam_question.deleted',
            'exam_questions.batch_imported',
        ],
        'CMS Pages & FAQs' => [
            'cms_page.created',
            'cms_page.updated',
            'cms_page.deleted',
            'cms_page.published',
            'cms_page.unpublished',
            'cms_section.created',
            'cms_section.updated',
            'cms_section.deleted',
            'cms_sections.reordered',
            'faq.created',
            'faq.updated',
            'faq.deleted',
            'faq.reordered',
            'faq.status_toggled',
        ],
        'Announcements & Updates' => [
            'latest_update.created',
            'latest_update.updated',
            'latest_update.deleted',
        ],
        'Staff & Access Control' => [
            'staff.created',
            'staff.updated',
            'staff.promoted',
            'staff.role_updated',
            'staff.activated',
            'staff.deactivated',
            'staff.revoked',
            'staff.deleted',
            'user.impersonated',
        ],
        'Tutors & Reviews' => [
            'tutor.approved',
            'tutor.rejected',
            'tutor_review.approved',
            'tutor_review.rejected',
        ],
        'Platform Reviews' => [
            'platform_review.approved',
            'platform_review.rejected',
            'platform_review.pending',
            'platform_review.deleted',
        ],
        'Audit Ledger' => [
            'audit_log.deleted',
            'audit_log.pruned',
        ],
    ];

    /**
     * Retrieve all administrative audit trail records.
     * Immutable write-only ledger displaying staff operations, credential changes, and system mutations.
     *
     * GET /api/admin/audit-logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin privileges required.',
            ], 403);
        }

        $query = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.admin_id', '=', 'users.id')
            ->select(
                'admin_audit_logs.*',
                'users.first_name',
                'users.last_name',
                'users.email as admin_email'
            );

        // Filter by action
        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('admin_audit_logs.action', $request->action);
        }

        // Search by admin name, email, IP, action, or auditable type
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('users.first_name', 'like', "%{$term}%")
                  ->orWhere('users.last_name', 'like', "%{$term}%")
                  ->orWhere('users.email', 'like', "%{$term}%")
                  ->orWhere('admin_audit_logs.ip_address', 'like', "%{$term}%")
                  ->orWhere('admin_audit_logs.action', 'like', "%{$term}%")
                  ->orWhere('admin_audit_logs.auditable_type', 'like', "%{$term}%")
                  ->orWhere('admin_audit_logs.auditable_id', 'like', "%{$term}%");
            });
        }

        $perPage = $request->integer('per_page', 25);
        $logs = $query->orderBy('admin_audit_logs.id', 'desc')->paginate($perPage);

        // Format logs with decoded JSON payloads
        $formatted = collect($logs->items())->map(function ($log) {
            $adminName = trim("{$log->first_name} {$log->last_name}");
            if (empty($adminName)) {
                $adminName = !empty($log->admin_email) ? $log->admin_email : 'Administrator';
            }

            $carbonDate = $log->created_at ? Carbon::parse($log->created_at)->timezone('Africa/Addis_Ababa') : null;

            return [
                'id' => (int) $log->id,
                'admin_id' => (int) $log->admin_id,
                'admin_name' => $adminName,
                'admin_email' => $log->admin_email ?? 'admin@asteghi.com',
                'action' => $log->action,
                'auditable_type' => class_basename($log->auditable_type),
                'auditable_type_full' => $log->auditable_type,
                'auditable_id' => (int) $log->auditable_id,
                'old_values' => $log->old_values ? (is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true)) : null,
                'new_values' => $log->new_values ? (is_array($log->new_values) ? $log->new_values : json_decode($log->new_values, true)) : null,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $carbonDate ? $carbonDate->toIso8601String() : null,
                'time_ago' => $carbonDate ? $carbonDate->diffForHumans() : null,
                'formatted_time' => $carbonDate ? $carbonDate->format('M d, Y, h:i:s A') : null,
            ];
        });

        // Fetch any existing dynamic actions stored in MySQL
        $dbActions = DB::table('admin_audit_logs')
            ->distinct()
            ->pluck('action')
            ->filter()
            ->toArray();

        // Build structured action groups and merge any dynamic DB actions
        $catalogActions = [];
        $actionGroups = [];

        foreach (self::$actionCatalog as $category => $actions) {
            $actionGroups[] = [
                'category' => $category,
                'actions' => array_values(array_unique($actions)),
            ];
            foreach ($actions as $act) {
                $catalogActions[$act] = true;
            }
        }

        // Collect any actions from DB not present in predefined catalog
        $customDbActions = [];
        foreach ($dbActions as $act) {
            if (!isset($catalogActions[$act])) {
                $customDbActions[] = $act;
            }
        }

        if (!empty($customDbActions)) {
            $actionGroups[] = [
                'category' => 'Other Activities',
                'actions' => array_values(array_unique($customDbActions)),
            ];
        }

        // Flat list of all available actions, sorted alphabetically
        $availableActions = collect(array_merge(array_keys($catalogActions), $dbActions))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return response()->json([
            'status' => 'success',
            'data' => $formatted,
            'available_actions' => $availableActions,
            'actions' => $availableActions,
            'action_groups' => $actionGroups,
            'pagination' => [
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
            ],
        ]);
    }

    /**
     * Show a single audit log entry.
     *
     * GET /api/admin/audit-logs/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $log = DB::table('admin_audit_logs')
            ->leftJoin('users', 'admin_audit_logs.admin_id', '=', 'users.id')
            ->where('admin_audit_logs.id', $id)
            ->select(
                'admin_audit_logs.*',
                'users.first_name',
                'users.last_name',
                'users.email as admin_email'
            )
            ->first();

        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => 'Audit log not found.',
            ], 404);
        }

        $adminName = trim("{$log->first_name} {$log->last_name}");
        if (empty($adminName)) {
            $adminName = !empty($log->admin_email) ? $log->admin_email : 'Administrator';
        }

        $carbonDate = $log->created_at ? Carbon::parse($log->created_at)->timezone('Africa/Addis_Ababa') : null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (int) $log->id,
                'admin_id' => (int) $log->admin_id,
                'admin_name' => $adminName,
                'admin_email' => $log->admin_email ?? 'admin@asteghi.com',
                'action' => $log->action,
                'auditable_type' => class_basename($log->auditable_type),
                'auditable_type_full' => $log->auditable_type,
                'auditable_id' => (int) $log->auditable_id,
                'old_values' => $log->old_values ? (is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true)) : null,
                'new_values' => $log->new_values ? (is_array($log->new_values) ? $log->new_values : json_decode($log->new_values, true)) : null,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $carbonDate ? $carbonDate->toIso8601String() : null,
                'time_ago' => $carbonDate ? $carbonDate->diffForHumans() : null,
                'formatted_time' => $carbonDate ? $carbonDate->format('M d, Y, h:i:s A') : null,
            ],
        ]);
    }

    /**
     * Delete a single audit log entry (Super Admin action).
     *
     * DELETE /api/admin/audit-logs/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $log = DB::table('admin_audit_logs')->where('id', $id)->first();

        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => 'Audit log entry not found or already removed.',
            ], 404);
        }

        DB::table('admin_audit_logs')->where('id', $id)->delete();

        AuditLogService::log(
            'audit_log.deleted',
            null,
            "Deleted audit log #{$id} (action: {$log->action})",
            ['deleted_log_id' => (int) $id, 'deleted_action' => $log->action]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Audit log entry removed.',
        ]);
    }

    /**
     * Prune audit logs older than specified days (default 90).
     *
     * POST /api/admin/audit-logs/prune
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function prune(Request $request): JsonResponse
    {
        $days = $request->integer('days', 90);
        $deleted = DB::table('admin_audit_logs')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        AuditLogService::log(
            'audit_log.pruned',
            null,
            "Pruned {$deleted} audit logs older than {$days} days",
            ['days' => $days, 'deleted_count' => $deleted]
        );

        return response()->json([
            'status' => 'success',
            'message' => "Pruned {$deleted} audit logs older than {$days} days.",
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Store an immutable audit log entry (client-side initiated actions like impersonation).
     *
     * POST /api/admin/audit-logs
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin privileges required.',
            ], 403);
        }

        $validated = $request->validate([
            'action' => 'required|string|max:255',
            'auditable_type' => 'required|string|max:255',
            'auditable_id' => 'required|numeric',
            'old_values' => 'nullable|array',
            'new_values' => 'nullable|array',
        ]);

        $log = AdminAuditLog::record(
            adminId: $user->id,
            action: $validated['action'],
            auditableType: $validated['auditable_type'],
            auditableId: $validated['auditable_id'],
            oldValues: $validated['old_values'] ?? null,
            newValues: $validated['new_values'] ?? null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Audit event recorded in write-only ledger.',
            'data' => $log,
        ], 201);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditLogService
{
    /**
     * Log an administrative action safely.
     * Accepts arrays, strings, objects, or null for old/new values.
     */
    public static function log(
        string $action,
        $auditable,
        $oldValues = null,
        $newValues = null,
        ?int $customAdminId = null
    ): void {
        try {
            $adminId = $customAdminId ?? auth()->id() ?? 1;

            // Safely serialize old values
            $serializedOld = null;
            if (is_array($oldValues)) {
                $serializedOld = json_encode($oldValues);
            } elseif (is_string($oldValues)) {
                $serializedOld = json_encode(['value' => $oldValues]);
            } elseif ($oldValues !== null) {
                $serializedOld = json_encode($oldValues);
            }

            // Safely serialize new values
            $serializedNew = null;
            if (is_array($newValues)) {
                $serializedNew = json_encode($newValues);
            } elseif (is_string($newValues)) {
                $serializedNew = json_encode(['value' => $newValues]);
            } elseif ($newValues !== null) {
                $serializedNew = json_encode($newValues);
            }

            $auditableType = is_string($auditable)
                ? $auditable
                : (is_object($auditable) ? get_class($auditable) : 'System');

            $auditableId = is_numeric($auditable)
                ? (int) $auditable
                : ($auditable->id ?? (is_object($auditable) && method_exists($auditable, 'getKey') ? $auditable->getKey() : 0));

            DB::table('admin_audit_logs')->insert([
                'admin_id' => $adminId,
                'action' => $action,
                'auditable_type' => $auditableType,
                'auditable_id' => $auditableId,
                'old_values' => $serializedOld,
                'new_values' => $serializedNew,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'System / Console',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never allow an audit log failure to break user operations
            \Illuminate\Support\Facades\Log::warning("Audit logging failed for {$action}: " . $e->getMessage());
        }
    }
}

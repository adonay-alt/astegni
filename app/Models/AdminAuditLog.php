<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    use HasFactory;

    protected $table = 'admin_audit_logs';

    protected $fillable = [
        'admin_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the admin user that initiated the audit action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Helper to log an administrative action.
     */
    public static function record(
        int $adminId,
        string $action,
        string $auditableType,
        int|string $auditableId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'admin_id' => $adminId,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip() ?? '127.0.0.1',
            'user_agent' => $userAgent ?? request()->userAgent() ?? 'System',
        ]);
    }

    /**
     * Resilient helper to log an administrative action with automatic model resolution.
     * Guaranteed to never throw or break business transactions.
     *
     * @param string $action Standardized action slug (e.g. 'resource.price_updated')
     * @param Model|string $auditable The target model instance or class name string
     * @param int|string|null $auditableId The model ID (optional if model passed)
     * @param array|null $oldValues State before mutation
     * @param array|null $newValues State after mutation
     * @param int|null $adminId Admin user ID (defaults to current authenticated user or 1)
     * @return self|null
     */
    public static function log(
        string $action,
        Model|string $auditable,
        int|string|null $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $adminId = null
    ): ?self {
        try {
            $auditableType = is_object($auditable) ? get_class($auditable) : (string) $auditable;
            $id = $auditableId ?? (is_object($auditable) && method_exists($auditable, 'getKey') ? $auditable->getKey() : 0);
            $userId = $adminId ?? auth()->id() ?? 1;

            return static::record(
                adminId: (int) $userId,
                action: $action,
                auditableType: $auditableType,
                auditableId: $id,
                oldValues: $oldValues,
                newValues: $newValues,
                ipAddress: request()->ip() ?? '127.0.0.1',
                userAgent: request()->userAgent() ?? 'System'
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("AdminAuditLog::log failed: " . $e->getMessage(), [
                'action' => $action,
            ]);
            return null;
        }
    }

    /**
     * Scope query to specific action or prefix.
     */
    public function scopeAction($query, string $action)
    {
        if (str_contains($action, '*')) {
            return $query->where('action', 'like', str_replace('*', '%', $action));
        }
        return $query->where('action', $action);
    }

    /**
     * Scope query to a specific admin.
     */
    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope query to recent records.
     */
    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}

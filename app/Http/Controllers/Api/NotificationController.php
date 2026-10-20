<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends Controller
{
    /**
     * Standard HTTP response headers to bypass all caching layers.
     */
    protected array $noCacheHeaders = [
        'Cache-Control' => 'no-cache, no-store, must-revalidate, private, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ];

    /**
     * Display paginated notifications belonging exclusively to the authenticated user.
     * Supports filtering by type (?type=tutor_application).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Notification::forUser($user->id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $perPage = (int) $request->input('per_page', 15);
        $notifications = $query->latest()->paginate($perPage);

        $unreadCount = Notification::forUser($user->id)
            ->unread()
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => $notifications->items(),
            'notifications' => $notifications->items(),
            'unread_count' => $unreadCount,
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ], 200, $this->noCacheHeaders);
    }

    /**
     * Get the total unread notification count for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadCount = Notification::forUser($user->id)
            ->unread()
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'unread_count' => $unreadCount,
            ],
            'unread_count' => $unreadCount,
        ], 200, $this->noCacheHeaders);
    }

    /**
     * Lightweight polling endpoint to fetch newest notifications since a given ID or timestamp.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function latest(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Notification::forUser($user->id)->latest();

        if ($request->filled('after_id')) {
            $query->where('id', '>', (int) $request->after_id);
        } elseif ($request->filled('since')) {
            $query->where('created_at', '>', $request->since);
        }

        $limit = min((int) $request->input('limit', 10), 50);
        $notifications = $query->take($limit)->get();

        $unreadCount = Notification::forUser($user->id)
            ->unread()
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'server_time' => now()->toISOString(),
        ], 200, $this->noCacheHeaders);
    }

    /**
     * Server-Sent Events (SSE) real-time streaming endpoint for notifications.
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();
        $lastSeenId = (int) $request->header('Last-Event-ID', $request->input('last_id', 0));

        return response()->stream(function () use ($user, $lastSeenId) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Send initial connection handshake
            echo "event: connected\ndata: " . json_encode([
                'user_id' => $user->id,
                'connected' => true,
                'server_time' => now()->toISOString(),
            ]) . "\n\n";
            flush();

            $currentLastId = $lastSeenId;
            $startTime = time();
            $timeout = 25; // 25-second lifecycle before reconnect to work cleanly with reverse proxies

            while ((time() - $startTime) < $timeout) {
                if (connection_aborted()) {
                    break;
                }

                $newNotifications = Notification::forUser($user->id)
                    ->where('id', '>', $currentLastId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($newNotifications as $notification) {
                    $currentLastId = $notification->id;
                    echo "id: {$notification->id}\n";
                    echo "event: notification\n";
                    echo "data: " . json_encode($notification) . "\n\n";
                    flush();
                }

                usleep(500000); // 500ms polling tick for real-time delivery
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Mark a specific notification belonging to the authenticated user as read.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found or access denied.',
            ], 404, $this->noCacheHeaders);
        }

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
            'data' => $notification,
        ], 200, $this->noCacheHeaders);
    }

    /**
     * Mark all unread notifications belonging to the authenticated user as read.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        Notification::forUser($user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read.',
        ], 200, $this->noCacheHeaders);
    }
}

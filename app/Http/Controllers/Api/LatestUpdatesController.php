<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LatestUpdate;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LatestUpdatesController extends Controller
{
    /**
     * Resolve the requested locale with fallback to default 'en'.
     */
    protected function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale') ?? $request->header('X-Locale');

        if (!$locale && $request->hasHeader('Accept-Language')) {
            $accept = strtolower((string) $request->header('Accept-Language'));
            if (str_starts_with($accept, 'am')) {
                $locale = 'am';
            }
        }

        $normalized = strtolower(trim((string) $locale));

        return in_array($normalized, ['am', 'en'], true) ? $normalized : 'en';
    }

    /**
     * Invalidate cached update list and item.
     */
    protected function invalidateUpdateCache(?int $id = null): void
    {
        Cache::forget('cms_latest_updates_list_en');
        Cache::forget('cms_latest_updates_list_am');

        if ($id) {
            Cache::forget("cms_latest_update_{$id}_en");
            Cache::forget("cms_latest_update_{$id}_am");
        }
    }

    /**
     * Display a listing of all latest updates (Admin listing: includes drafts, unfiltered, with all locales).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $updates = LatestUpdate::orderByRaw('COALESCE(published_at, created_at) DESC')->get();

        return response()->json([
            'status' => 'success',
            'data' => $updates,
        ], 200);
    }

    /**
     * Display a listing of active latest updates (Public, Locale-Aware, Cached).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $cacheKey = "cms_latest_updates_list_{$locale}";

        $data = Cache::remember($cacheKey, 3600, function () use ($locale) {
            $updates = LatestUpdate::where('is_active', true)
                ->orderByRaw('COALESCE(published_at, created_at) DESC')
                ->get();

            return $updates->map(function ($update) use ($locale) {
                return [
                    'id' => $update->id,
                    'title' => $update->getLocalizedTitle($locale),
                    'description' => $update->getLocalizedDescription($locale),
                    'image_path' => $update->image_path,
                    'image_url' => $update->image_url,
                    'is_active' => (bool) $update->is_active,
                    'published_at' => $update->published_at ? $update->published_at->toIso8601String() : null,
                    'created_at' => $update->created_at ? $update->created_at->toIso8601String() : null,
                    'updated_at' => $update->updated_at ? $update->updated_at->toIso8601String() : null,
                ];
            })->values()->all();
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    /**
     * Display a single active update (Public, Locale-Aware, Cached).
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $cacheKey = "cms_latest_update_{$id}_{$locale}";

        $data = Cache::remember($cacheKey, 3600, function () use ($id, $locale) {
            $update = LatestUpdate::find($id);

            if (!$update || !$update->is_active) {
                return null;
            }

            return [
                'id' => $update->id,
                'title' => $update->getLocalizedTitle($locale),
                'description' => $update->getLocalizedDescription($locale),
                'image_path' => $update->image_path,
                'image_url' => $update->image_url,
                'is_active' => (bool) $update->is_active,
                'published_at' => $update->published_at ? $update->published_at->toIso8601String() : null,
                'created_at' => $update->created_at ? $update->created_at->toIso8601String() : null,
                'updated_at' => $update->updated_at ? $update->updated_at->toIso8601String() : null,
            ];
        });

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Latest update not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    /**
     * Store a newly created latest update in storage (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required_without:title_en', 'nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'description' => ['required_without:description_en', 'nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'is_active' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $title = $request->input('title_en') ?? $request->input('title');
        $description = $request->input('description_en') ?? $request->input('description');

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('updates', 'public');
        }

        $update = LatestUpdate::create([
            'title' => $title,
            'title_am' => $request->input('title_am'),
            'description' => $description,
            'description_am' => $request->input('description_am'),
            'image_path' => $imagePath,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'published_at' => $request->published_at ?? now(),
        ]);

        $this->invalidateUpdateCache($update->id);

        AuditLogService::log(
            'latest_update.created',
            $update,
            null,
            ['title' => $update->title, 'is_published' => $update->is_published]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Latest update created successfully',
            'data' => $update,
        ], 201);
    }

    /**
     * Update the specified latest update in storage (Admin only).
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $update = LatestUpdate::find($id);

        if (!$update) {
            return response()->json([
                'status' => 'error',
                'message' => 'Latest update not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title_am' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'description_en' => ['sometimes', 'nullable', 'string'],
            'description_am' => ['sometimes', 'nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'remove_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('remove_image')) {
            if ($update->image_path && Storage::disk('public')->exists($update->image_path)) {
                Storage::disk('public')->delete($update->image_path);
            }
            $update->image_path = null;
        }

        if ($request->hasFile('image')) {
            // Delete old image if present
            if ($update->image_path && Storage::disk('public')->exists($update->image_path)) {
                Storage::disk('public')->delete($update->image_path);
            }
            $update->image_path = $request->file('image')->store('updates', 'public');
        }

        if ($request->has('title_en')) {
            $update->title = $request->input('title_en');
        } elseif ($request->has('title')) {
            $update->title = $request->input('title');
        }

        if ($request->has('title_am')) {
            $update->title_am = $request->input('title_am');
        }

        if ($request->has('description_en')) {
            $update->description = $request->input('description_en');
        } elseif ($request->has('description')) {
            $update->description = $request->input('description');
        }

        if ($request->has('description_am')) {
            $update->description_am = $request->input('description_am');
        }

        $oldStatus = (bool) $update->is_active;

        if ($request->has('is_active')) {
            $update->is_active = $request->boolean('is_active');
        }

        if ($request->has('published_at')) {
            $update->published_at = $request->published_at;
        }

        $update->save();

        $this->invalidateUpdateCache($update->id);

        AuditLogService::log(
            'update.status_toggled',
            $update,
            ['is_published' => $oldStatus],
            ['is_published' => $update->is_published]
        );

        return response()->json([
            'status' => 'success',
            'message' => $update->is_published ? 'Update published successfully.' : 'Update hidden successfully.',
            'data' => $update->fresh(),
        ], 200);
    }

    /**
     * Remove the specified latest update from storage (Admin only).
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $update = LatestUpdate::find($id);

        if (!$update) {
            return response()->json([
                'status' => 'error',
                'message' => 'Latest update not found',
            ], 404);
        }

        if ($update->image_path && Storage::disk('public')->exists($update->image_path)) {
            Storage::disk('public')->delete($update->image_path);
        }

        $titleText = $update->title;
        $id = $update->id;
        $update->delete();

        $this->invalidateUpdateCache($id);

        AuditLogService::log(
            'latest_update.deleted',
            $update,
            ['title' => $titleText, 'is_published' => $update->is_published],
            null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Latest update deleted successfully',
        ], 200);
    }
}

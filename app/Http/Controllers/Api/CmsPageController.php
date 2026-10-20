<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class CmsPageController extends Controller
{
    protected array $allowedSlugs = ['faq', 'privacy', 'terms', 'cookies'];

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
     * Targeted cache invalidation for a page across all supported locales.
     */
    protected function invalidatePageCache(string $slug): void
    {
        Cache::forget("cms_page_{$slug}_en");
        Cache::forget("cms_page_{$slug}_am");
    }

    /**
     * Display a published CMS page by slug (Public, Locale-Aware, Cached).
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        if (!in_array($slug, $this->allowedSlugs, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $locale = $this->resolveLocale($request);
        $cacheKey = "cms_page_{$slug}_{$locale}";

        $data = Cache::remember($cacheKey, 3600, function () use ($slug, $locale) {
            $page = Page::where('slug', $slug)
                ->where('is_published', true)
                ->first();

            if (!$page) {
                return null;
            }

            $sections = $page->sections()
                ->where('is_published', true)
                ->get();

            if ($slug === 'faq' || $page->type === 'faq') {
                $formattedItems = $sections->map(function ($section, $index) use ($locale) {
                    return [
                        'id' => $section->id,
                        'question' => $section->getLocalizedQuestion($locale),
                        'answer' => $section->getLocalizedAnswer($locale),
                        'order' => $section->sort_order ?: ($index + 1),
                    ];
                })->values()->all();

                return [
                    'slug' => $page->slug,
                    'title' => $page->getLocalizedTitle($locale),
                    'subtitle' => $page->getLocalizedSubtitle($locale),
                    'locale' => $locale,
                    'updated_at' => $page->updated_at ? $page->updated_at->toIso8601String() : null,
                    'items' => $formattedItems,
                ];
            }

            $formattedSections = $sections->map(function ($section, $index) use ($locale) {
                return [
                    'id' => $section->id,
                    'heading' => $section->getLocalizedHeading($locale),
                    'body' => $section->getLocalizedBody($locale),
                    'order' => $section->sort_order ?: ($index + 1),
                ];
            })->values()->all();

            return [
                'slug' => $page->slug,
                'title' => $page->getLocalizedTitle($locale),
                'subtitle' => $page->getLocalizedSubtitle($locale),
                'locale' => $locale,
                'updated_at' => $page->updated_at ? $page->updated_at->toIso8601String() : null,
                'sections' => $formattedSections,
            ];
        });

        if ($data === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    // ==========================================
    // ADMIN CMS MANAGEMENT ENDPOINTS
    // ==========================================

    /**
     * List all CMS pages (Admin only).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $pages = Page::withCount('sections')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $pages,
        ]);
    }

    /**
     * Show full page details including all draft/published sections (Admin only).
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function adminShow($id): JsonResponse
    {
        $page = Page::with('sections')->find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $page,
        ]);
    }

    /**
     * Create a new CMS page (Admin only).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'slug' => ['required', 'string', 'in:faq,privacy,terms,cookies', 'unique:pages,slug'],
            'title' => ['required_without:title_en', 'nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'subtitle_am' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:faq,legal'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $title = $request->input('title_en') ?? $request->input('title');
        $subtitle = $request->input('subtitle_en') ?? $request->input('subtitle');
        $isPublished = $request->boolean('is_published');

        $page = Page::create([
            'slug' => strtolower($request->input('slug')),
            'title' => $title,
            'title_am' => $request->input('title_am'),
            'subtitle' => $subtitle,
            'subtitle_am' => $request->input('subtitle_am'),
            'type' => $request->input('type'),
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);

        // Record audit trail
        AuditLogService::log('cms_page.created', $page, null, [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
        ]);

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Page created successfully',
            'data' => $page,
        ], 201);
    }

    /**
     * Update an existing CMS page (Admin only).
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $oldValues = [
            'title' => $page->title,
            'slug' => $page->slug,
            'type' => $page->type,
            'is_published' => $page->is_published,
        ];

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title_en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'title_am' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:255'],
            'subtitle_am' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'string', 'in:faq,legal'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('title_en')) {
            $page->title = $request->input('title_en');
        } elseif ($request->has('title')) {
            $page->title = $request->input('title');
        }

        if ($request->has('title_am')) {
            $page->title_am = $request->input('title_am');
        }

        if ($request->has('subtitle_en')) {
            $page->subtitle = $request->input('subtitle_en');
        } elseif ($request->has('subtitle')) {
            $page->subtitle = $request->input('subtitle');
        }

        if ($request->has('subtitle_am')) {
            $page->subtitle_am = $request->input('subtitle_am');
        }

        if ($request->has('type')) {
            $page->type = $request->input('type');
        }

        if ($request->has('is_published')) {
            $page->is_published = $request->boolean('is_published');
            if ($page->is_published && !$page->published_at) {
                $page->published_at = now();
            }
        }

        $page->save();

        // Record audit trail
        AuditLogService::log('cms_page.updated', $page, $oldValues, [
            'slug' => $page->slug,
            'title' => $page->title,
            'is_published' => $page->is_published,
        ]);

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Page updated successfully',
            'data' => $page,
        ]);
    }

    /**
     * Delete a CMS page (Admin only).
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $deletedSnapshot = [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
        ];

        $slug = $page->slug;
        $page->delete();

        // Record audit trail
        AuditLogService::log('cms_page.deleted', 'App\Models\Page', $deletedSnapshot, null);

        $this->invalidatePageCache($slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Page deleted successfully',
        ]);
    }

    /**
     * Publish a CMS page (Admin only).
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function publish($id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $page->is_published = true;
        $page->published_at = now();
        $page->save();

        // Record audit trail
        AuditLogService::log('cms_page.published', $page, ['is_published' => false], ['is_published' => true]);

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Page published successfully',
            'data' => $page,
        ]);
    }

    /**
     * Unpublish a CMS page (Admin only).
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function unpublish($id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $page->is_published = false;
        $page->save();

        // Record audit trail
        AuditLogService::log('cms_page.unpublished', $page, ['is_published' => true], ['is_published' => false]);

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Page unpublished successfully',
            'data' => $page,
        ]);
    }

    /**
     * Add a section or FAQ item to a page (Admin only).
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function storeSection(Request $request, $id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'heading' => ['nullable', 'string', 'max:255'],
            'heading_en' => ['nullable', 'string', 'max:255'],
            'heading_am' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'body_am' => ['nullable', 'string'],
            'question' => ['nullable', 'string'],
            'question_en' => ['nullable', 'string'],
            'question_am' => ['nullable', 'string'],
            'answer' => ['nullable', 'string'],
            'answer_en' => ['nullable', 'string'],
            'answer_am' => ['nullable', 'string'],
            'order' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = $request->input('order') ?? $request->input('sort_order') ?? ($page->sections()->max('sort_order') + 1);

        $heading = $request->input('heading_en') ?? $request->input('heading');
        $body = $request->input('body_en') ?? $request->input('body');
        $question = $request->input('question_en') ?? $request->input('question');
        $answer = $request->input('answer_en') ?? $request->input('answer');

        $section = PageSection::create([
            'page_id' => $page->id,
            'heading' => $heading,
            'heading_am' => $request->input('heading_am'),
            'body' => $body,
            'body_am' => $request->input('body_am'),
            'question' => $question,
            'question_am' => $request->input('question_am'),
            'answer' => $answer,
            'answer_am' => $request->input('answer_am'),
            'sort_order' => (int) $order,
            'is_published' => $request->boolean('is_published', true),
        ]);

        // Record audit trail
        AuditLogService::log('cms_section.created', $section, null, [
            'id' => $section->id,
            'page_id' => $page->id,
            'heading' => $section->heading,
        ]);

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Section created successfully',
            'data' => $section,
        ], 201);
    }

    /**
     * Update an existing section or FAQ item (Admin only).
     *
     * @param Request $request
     * @param int|string $pageId
     * @param int|string $sectionId
     * @return JsonResponse
     */
    public function updateSection(Request $request, $pageId, $sectionId): JsonResponse
    {
        $page = Page::find($pageId);
        $section = PageSection::where('page_id', $pageId)->where('id', $sectionId)->first();

        if (!$page || !$section) {
            return response()->json([
                'status' => 'error',
                'message' => 'Section not found',
            ], 404);
        }

        $oldValues = [
            'heading' => $section->heading,
            'body' => mb_substr($section->body ?? '', 0, 100),
            'page_id' => $page->id,
        ];

        $validator = Validator::make($request->all(), [
            'heading' => ['nullable', 'string', 'max:255'],
            'heading_en' => ['nullable', 'string', 'max:255'],
            'heading_am' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'body_en' => ['nullable', 'string'],
            'body_am' => ['nullable', 'string'],
            'question' => ['nullable', 'string'],
            'question_en' => ['nullable', 'string'],
            'question_am' => ['nullable', 'string'],
            'answer' => ['nullable', 'string'],
            'answer_en' => ['nullable', 'string'],
            'answer_am' => ['nullable', 'string'],
            'order' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('heading_en')) {
            $section->heading = $request->input('heading_en');
        } elseif ($request->has('heading')) {
            $section->heading = $request->input('heading');
        }

        if ($request->has('heading_am')) {
            $section->heading_am = $request->input('heading_am');
        }

        if ($request->has('body_en')) {
            $section->body = $request->input('body_en');
        } elseif ($request->has('body')) {
            $section->body = $request->input('body');
        }

        if ($request->has('body_am')) {
            $section->body_am = $request->input('body_am');
        }

        if ($request->has('question_en')) {
            $section->question = $request->input('question_en');
        } elseif ($request->has('question')) {
            $section->question = $request->input('question');
        }

        if ($request->has('question_am')) {
            $section->question_am = $request->input('question_am');
        }

        if ($request->has('answer_en')) {
            $section->answer = $request->input('answer_en');
        } elseif ($request->has('answer')) {
            $section->answer = $request->input('answer');
        }

        if ($request->has('answer_am')) {
            $section->answer_am = $request->input('answer_am');
        }

        if ($request->has('order') || $request->has('sort_order')) {
            $section->sort_order = (int) ($request->input('order') ?? $request->input('sort_order'));
        }

        if ($request->has('is_published')) {
            $section->is_published = $request->boolean('is_published');
        }

        $section->save();

        // Record audit trail
        AuditLogService::log('cms_section.updated', $section, $oldValues, [
            'heading' => $section->heading,
            'body' => mb_substr($section->body ?? '', 0, 100),
            'page_id' => $page->id,
        ]);

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Section updated successfully',
            'data' => $section,
        ]);
    }

    /**
     * Delete a section or FAQ item (Admin only).
     *
     * @param int|string $pageId
     * @param int|string $sectionId
     * @return JsonResponse
     */
    public function destroySection($pageId, $sectionId): JsonResponse
    {
        $page = Page::find($pageId);
        $section = PageSection::where('page_id', $pageId)->where('id', $sectionId)->first();

        if (!$page || !$section) {
            return response()->json([
                'status' => 'error',
                'message' => 'Section not found',
                'errors' => 'Section not found',
            ], 404);
        }

        $deletedSnapshot = [
            'id' => $section->id,
            'page_id' => $page->id,
            'heading' => $section->heading,
        ];

        $section->delete();

        // Record audit trail
        AuditLogService::log('cms_section.deleted', 'App\Models\PageSection', $deletedSnapshot, null);

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Section deleted successfully',
        ]);
    }

    /**
     * Batch reorder sections or FAQ items for a page (Admin only).
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function reorderSections(Request $request, $id): JsonResponse
    {
        $page = Page::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.order' => ['nullable', 'integer'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->input('items') as $item) {
            $sectionId = $item['id'];
            $newOrder = $item['order'] ?? $item['sort_order'] ?? 0;

            PageSection::where('page_id', $page->id)
                ->where('id', $sectionId)
                ->update(['sort_order' => (int) $newOrder]);
        }

        // Record audit trail
        AuditLogService::log('cms_sections.reordered', $page, null, [
            'page_id' => $page->id,
            'count' => count($request->input('items', [])),
        ]);

        $updatedSections = $page->sections()->get();

        $this->invalidatePageCache($page->slug);

        return response()->json([
            'status' => 'success',
            'message' => 'Sections reordered successfully',
            'data' => $updatedSections,
        ]);
    }

    // ==========================================
    // DEDICATED ADMIN FAQ CRUD METHODS
    // ==========================================

    /**
     * Helper to retrieve or create the canonical FAQ page.
     */
    protected function getOrCreateFaqPage(): Page
    {
        return Page::firstOrCreate(
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
    }

    /**
     * List all FAQ items for admin management.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminFaqIndex(Request $request): JsonResponse
    {
        $faqPage = $this->getOrCreateFaqPage();

        $items = $faqPage->sections()
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'page' => $faqPage,
                'items' => $items,
            ],
        ], 200);
    }

    /**
     * Store a new FAQ item.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminFaqStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => ['required_without:question_en', 'nullable', 'string'],
            'question_en' => ['nullable', 'string'],
            'question_am' => ['nullable', 'string'],
            'answer' => ['required_without:answer_en', 'nullable', 'string'],
            'answer_en' => ['nullable', 'string'],
            'answer_am' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $faqPage = $this->getOrCreateFaqPage();

        $order = $request->input('order') ?? $request->input('sort_order') ?? ($faqPage->sections()->max('sort_order') + 1);
        $question = $request->input('question_en') ?? $request->input('question');
        $answer = $request->input('answer_en') ?? $request->input('answer');

        $faqItem = PageSection::create([
            'page_id' => $faqPage->id,
            'question' => $question,
            'question_am' => $request->input('question_am'),
            'answer' => $answer,
            'answer_am' => $request->input('answer_am'),
            'sort_order' => (int) $order,
            'is_published' => $request->boolean('is_published', true),
        ]);

        $this->invalidatePageCache('faq');

        AuditLogService::log(
            'faq.created',
            $faqItem,
            "Created FAQ item: {$faqItem->question}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ item created successfully',
            'data' => $faqItem,
        ], 201);
    }

    /**
     * Display a single FAQ item.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function adminFaqShow($id): JsonResponse
    {
        $faqItem = PageSection::find($id);

        if (!$faqItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ item not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $faqItem,
        ], 200);
    }

    /**
     * Update an existing FAQ item.
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function adminFaqUpdate(Request $request, $id): JsonResponse
    {
        $faqItem = PageSection::find($id);

        if (!$faqItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ item not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'question' => ['sometimes', 'nullable', 'string'],
            'question_en' => ['sometimes', 'nullable', 'string'],
            'question_am' => ['sometimes', 'nullable', 'string'],
            'answer' => ['sometimes', 'nullable', 'string'],
            'answer_en' => ['sometimes', 'nullable', 'string'],
            'answer_am' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('question_en')) {
            $faqItem->question = $request->input('question_en');
        } elseif ($request->has('question')) {
            $faqItem->question = $request->input('question');
        }

        if ($request->has('question_am')) {
            $faqItem->question_am = $request->input('question_am');
        }

        if ($request->has('answer_en')) {
            $faqItem->answer = $request->input('answer_en');
        } elseif ($request->has('answer')) {
            $faqItem->answer = $request->input('answer');
        }

        if ($request->has('answer_am')) {
            $faqItem->answer_am = $request->input('answer_am');
        }

        if ($request->has('order') || $request->has('sort_order')) {
            $faqItem->sort_order = (int) ($request->input('order') ?? $request->input('sort_order'));
        }

        if ($request->has('is_published')) {
            $faqItem->is_published = $request->boolean('is_published');
        }

        $faqItem->save();

        $this->invalidatePageCache('faq');

        AuditLogService::log(
            'faq.updated',
            $faqItem,
            "Updated FAQ item: {$faqItem->question}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ item updated successfully',
            'data' => $faqItem,
        ], 200);
    }

    /**
     * Delete an FAQ item.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function adminFaqDestroy($id): JsonResponse
    {
        $faqItem = PageSection::find($id);

        if (!$faqItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ item not found',
            ], 404);
        }

        $questionText = $faqItem->question;
        $faqItem->delete();

        $this->invalidatePageCache('faq');

        AuditLogService::log(
            'faq.deleted',
            $faqItem,
            "Deleted FAQ item: {$questionText}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ item deleted successfully',
        ], 200);
    }

    /**
     * Batch reorder FAQ items.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminFaqReorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.order' => ['nullable', 'integer'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $faqPage = $this->getOrCreateFaqPage();

        foreach ($request->input('items') as $item) {
            $itemId = $item['id'];
            $newOrder = $item['order'] ?? $item['sort_order'] ?? 0;

            PageSection::where('page_id', $faqPage->id)
                ->where('id', $itemId)
                ->update(['sort_order' => (int) $newOrder]);
        }

        $this->invalidatePageCache('faq');

        $updatedItems = $faqPage->sections()->orderBy('sort_order', 'asc')->get();

        AuditLogService::log(
            'faq.reordered',
            $faqPage,
            "Reordered FAQ items"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'FAQ items reordered successfully',
            'data' => $updatedItems,
        ], 200);
    }

    /**
     * Toggle active/published status of an FAQ item.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function adminFaqToggleStatus($id): JsonResponse
    {
        $faqItem = PageSection::find($id);

        if (!$faqItem) {
            return response()->json([
                'status' => 'error',
                'message' => 'FAQ item not found',
            ], 404);
        }

        $faqItem->is_published = !$faqItem->is_published;
        $faqItem->save();

        $this->invalidatePageCache('faq');

        $statusText = $faqItem->is_published ? 'published' : 'unpublished';
        AuditLogService::log(
            'faq.status_toggled',
            $faqItem,
            "FAQ item {$statusText}: {$faqItem->question}"
        );

        return response()->json([
            'status' => 'success',
            'message' => $faqItem->is_published ? 'FAQ item published' : 'FAQ item unpublished',
            'data' => $faqItem,
        ], 200);
    }
}

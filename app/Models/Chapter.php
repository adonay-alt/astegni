<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'subject_id',
        'title',
        'chapter_number',
        'description',
        'is_featured',
        'pdf_path',
        'ai_generated_notes',
        'notes_generated_at',
        'ai_premium_notes',
        'premium_notes_generated_at',
    ];

    protected $casts = [
        'notes_generated_at' => 'datetime',
        'premium_notes_generated_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    protected $hidden = ['created_at', 'updated_at'];
    /**
     * Get the grade that owns the chapter.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the subject that owns the chapter.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the videos for the chapter.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get the quizzes for the chapter.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Get the notes for the chapter.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the textbooks for the chapter.
     */
    public function textbooks(): HasMany
    {
        return $this->hasMany(Textbook::class);
    }

    /**
     * Get the resources for the chapter.
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * Get the bookmarks for the chapter.
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Resolve and verify the physical absolute PDF path on the server disk.
     *
     * @return string|null Absolute path if file physically exists on disk, null otherwise.
     */
    public function resolvePdfPath(): ?string
    {
        $candidatePath = null;

        // 1. Check direct pdf_path on chapter
        if (!empty($this->pdf_path)) {
            $candidatePath = $this->pdf_path;
        } else {
            // 2. Check linked textbooks relation
            $textbook = $this->relationLoaded('textbooks')
                ? $this->textbooks->first()
                : $this->textbooks()->first();

            if ($textbook) {
                $candidatePath = $textbook->getRawOriginal('file_url')
                    ?? $textbook->getRawOriginal('file_path')
                    ?? $textbook->getRawOriginal('pdf_path')
                    ?? $textbook->file_url
                    ?? $textbook->file_path
                    ?? $textbook->pdf_path
                    ?? null;
            }
        }

        if (empty($candidatePath) || !is_string($candidatePath)) {
            return null;
        }

        $candidatePath = trim($candidatePath);
        if ($candidatePath === '') {
            return null;
        }

        // Test raw candidate path directly if it is already absolute or directly valid
        if (file_exists($candidatePath) && !is_dir($candidatePath)) {
            return realpath($candidatePath) ?: $candidatePath;
        }

        // Strip URL domain and storage prefixes (e.g. 'https://apiv2.asteghi.com/storage/...')
        $clean = preg_replace('#^https?://[^/]+(/storage)?/#i', '', $candidatePath);
        if ($clean === $candidatePath && filter_var($candidatePath, FILTER_VALIDATE_URL)) {
            $urlPath = parse_url($candidatePath, PHP_URL_PATH) ?: '';
            $clean = preg_replace('#^/storage/#i', '', $urlPath);
        }

        // Strip leading storage prefix and slashes
        $clean = ltrim($clean, '/\\');
        if (str_starts_with(strtolower($clean), 'storage/')) {
            $clean = substr($clean, 8);
        }
        $clean = ltrim($clean, '/\\');

        // Normalize legacy directory names with spaces
        $clean = str_replace(['textbooks/grade 10/', 'textbooks/Grade 10/'], 'textbooks/grade10/', $clean);

        // List possible absolute paths to test for physical existence
        $candidateAbsolutePaths = [];

        // 1. Public storage disk path
        try {
            $candidateAbsolutePaths[] = Storage::disk('public')->path($clean);
        } catch (\Throwable $e) {}

        // 2. Local storage disk path
        try {
            $candidateAbsolutePaths[] = Storage::disk('local')->path($clean);
        } catch (\Throwable $e) {}

        // 3. Fallback storage_path helpers
        $candidateAbsolutePaths[] = storage_path('app/public/' . $clean);
        $candidateAbsolutePaths[] = storage_path('app/' . $clean);

        // 4. Fallback public_path helpers
        $candidateAbsolutePaths[] = public_path('storage/' . $clean);
        $candidateAbsolutePaths[] = public_path($clean);

        // 5. Clean path relative to CWD
        $candidateAbsolutePaths[] = $clean;

        // 6. If candidatePath has no domain, try public disk directly with ltrimmed candidatePath
        try {
            $rawClean = ltrim($candidatePath, '/\\');
            $candidateAbsolutePaths[] = Storage::disk('public')->path($rawClean);
        } catch (\Throwable $e) {}

        foreach ($candidateAbsolutePaths as $path) {
            if (!empty($path) && is_string($path) && file_exists($path) && !is_dir($path)) {
                return realpath($path) ?: $path;
            }
        }

        return null;
    }

    /**
     * Resolve and verify the physical absolute PDF path of a chapter's supplementary resource.
     *
     * @param int|null $resourceId Optional specific resource ID
     * @return string|null Absolute path if file physically exists on disk, null otherwise.
     */
    public function resolveResourcePdfPath(?int $resourceId = null): ?string
    {
        $resource = null;
        if ($resourceId) {
            $resource = $this->relationLoaded('resources')
                ? $this->resources->firstWhere('id', $resourceId)
                : $this->resources()->find($resourceId);
        }

        if (!$resource) {
            if ($this->relationLoaded('resources')) {
                $resource = $this->resources->first(function ($item) {
                    return strtolower((string)$item->type) === 'pdf' || str_ends_with(strtolower((string)($item->getRawOriginal('url') ?? $item->url)), '.pdf');
                }) ?? $this->resources->first();
            } else {
                $resource = $this->resources()
                    ->where(function ($q) {
                        $q->where('type', 'pdf')
                          ->orWhere('url', 'like', '%.pdf');
                    })
                    ->first() ?? $this->resources()->first();
            }
        }

        if (!$resource) {
            return null;
        }

        $candidatePath = $resource->getRawOriginal('url')
            ?? ($resource->getAttributes()['url'] ?? null);

        if (empty($candidatePath)) {
            try {
                $candidatePath = $resource->url;
            } catch (\Throwable $e) {
                $candidatePath = null;
            }
        }

        if (empty($candidatePath) || !is_string($candidatePath)) {
            return null;
        }

        $candidatePath = trim($candidatePath);
        if ($candidatePath === '') {
            return null;
        }

        // Test raw candidate path directly if it is already absolute or directly valid
        if (file_exists($candidatePath) && !is_dir($candidatePath)) {
            return realpath($candidatePath) ?: $candidatePath;
        }

        // Strip URL domain and storage prefixes (e.g. 'https://apiv2.asteghi.com/storage/...')
        $clean = preg_replace('#^https?://[^/]+(/storage)?/#i', '', $candidatePath);
        if ($clean === $candidatePath && filter_var($candidatePath, FILTER_VALIDATE_URL)) {
            $urlPath = parse_url($candidatePath, PHP_URL_PATH) ?: '';
            $clean = preg_replace('#^/storage/#i', '', $urlPath);
        }

        // Strip leading storage prefix and slashes
        $clean = ltrim($clean, '/\\');
        if (str_starts_with(strtolower($clean), 'storage/')) {
            $clean = substr($clean, 8);
        }
        $clean = ltrim($clean, '/\\');

        // List possible absolute paths to test for physical existence
        $candidateAbsolutePaths = [];

        // 1. Public storage disk path
        try {
            $candidateAbsolutePaths[] = Storage::disk('public')->path($clean);
        } catch (\Throwable $e) {}

        // 2. Local storage disk path
        try {
            $candidateAbsolutePaths[] = Storage::disk('local')->path($clean);
        } catch (\Throwable $e) {}

        // 3. Fallback storage_path helpers
        $candidateAbsolutePaths[] = storage_path('app/public/' . $clean);
        $candidateAbsolutePaths[] = storage_path('app/' . $clean);

        // 4. Fallback public_path helpers
        $candidateAbsolutePaths[] = public_path('storage/' . $clean);
        $candidateAbsolutePaths[] = public_path($clean);

        // 5. Clean path relative to CWD
        $candidateAbsolutePaths[] = $clean;

        // 6. Direct candidate path on public disk
        try {
            $rawClean = ltrim($candidatePath, '/\\');
            $candidateAbsolutePaths[] = Storage::disk('public')->path($rawClean);
        } catch (\Throwable $e) {}

        foreach ($candidateAbsolutePaths as $path) {
            if (!empty($path) && is_string($path) && file_exists($path) && !is_dir($path)) {
                return realpath($path) ?: $path;
            }
        }

        return null;
    }
}

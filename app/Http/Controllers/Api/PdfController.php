<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\Textbook;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    /**
     * Directly stream a PDF file by Textbook ID with HTTP Byte-Range, ETag caching, and web server offloading.
     *
     * @param Request $request
     * @param int|string $id
     * @return Response|JsonResponse|BinaryFileResponse
     */
    public function streamPdf(Request $request, $id): Response|JsonResponse|BinaryFileResponse
    {
        // Select only required columns to avoid N+1 and heavy memory overhead
        $textbook = Textbook::select(['id', 'title', 'file_url', 'chapter_id', 'grade_id', 'subject_id'])
            ->find($id);

        if (!$textbook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Textbook PDF not found.',
            ], 404);
        }

        $rawPath = $textbook->getRawOriginal('file_url') ?: $textbook->file_url;
        return $this->servePdfFile($request, $rawPath, $textbook->title);
    }

    /**
     * Stream PDF by Grade -> Subject -> Chapter hierarchy.
     *
     * @param Request $request
     * @param string|int $gradeParam
     * @param string|int $subjectParam
     * @param string|int $chapterNumber
     * @return Response|JsonResponse|BinaryFileResponse
     */
    public function streamByHierarchy(Request $request, $gradeParam, $subjectParam, $chapterNumber): Response|JsonResponse|BinaryFileResponse
    {
        // 1. Resolve Grade ID using index lookup
        $gradeId = null;
        if (is_numeric($gradeParam)) {
            $gradeId = (int) $gradeParam;
        } else {
            $cleanGrade = str_replace('-', ' ', (string) $gradeParam);
            $grade = Grade::select(['id'])
                ->where('name', $gradeParam)
                ->orWhere('name', 'like', "%{$gradeParam}%")
                ->orWhere('name', 'like', "%{$cleanGrade}%")
                ->first();

            if (!$grade) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Grade not found: {$gradeParam}",
                ], 404);
            }
            $gradeId = $grade->id;
        }

        // 2. Resolve Subject ID using index lookup
        $cleanSubject = str_replace('-', ' ', (string) $subjectParam);
        $subjectQuery = Subject::select(['id', 'grade_id']);
        if (is_numeric($subjectParam)) {
            $subjectQuery->where('id', (int) $subjectParam);
        } else {
            $subjectQuery->where(function ($q) use ($subjectParam, $cleanSubject) {
                $q->where('title', 'like', $subjectParam)
                  ->orWhere('title', 'like', "%{$subjectParam}%")
                  ->orWhere('title', 'like', "%{$cleanSubject}%");
            });
        }
        if ($gradeId) {
            $subjectQuery->where('grade_id', $gradeId);
        }
        $subject = $subjectQuery->first();

        if (!$subject) {
            return response()->json([
                'status' => 'error',
                'message' => "Subject not found: {$subjectParam}",
            ], 404);
        }

        // 3. Resolve Chapter selecting only pdf_path & id
        $chapter = Chapter::select(['id', 'grade_id', 'subject_id', 'chapter_number', 'title', 'pdf_path'])
            ->where('subject_id', $subject->id)
            ->where('chapter_number', $chapterNumber)
            ->first();

        if (!$chapter) {
            return response()->json([
                'status' => 'error',
                'message' => "Chapter {$chapterNumber} not found for Subject ID {$subject->id}",
            ], 404);
        }

        // Check chapter pdf_path or associated textbook
        $pdfPath = $chapter->pdf_path;
        if (!$pdfPath) {
            $textbook = Textbook::select(['file_url', 'title'])
                ->where('chapter_id', $chapter->id)
                ->first();

            if ($textbook) {
                $pdfPath = $textbook->getRawOriginal('file_url') ?: $textbook->file_url;
            }
        }

        if (!$pdfPath) {
            return response()->json([
                'status' => 'error',
                'message' => "No PDF file attached to Chapter {$chapterNumber}.",
            ], 404);
        }

        return $this->servePdfFile($request, $pdfPath, $chapter->title);
    }

    /**
     * Helper to build a high-performance streamed BinaryFileResponse supporting:
     * - Accept-Ranges: bytes (Progressive page-by-page rendering in browser PDF viewers)
     * - ETag & Last-Modified caching headers
     * - 304 Not Modified validation for instant re-loads
     * - Web server offloading (Nginx X-Accel-Redirect / Apache X-Sendfile)
     *
     * @param Request $request
     * @param string $path
     * @param string $title
     * @return Response|JsonResponse|BinaryFileResponse
     */
    protected function servePdfFile(Request $request, string $path, string $title): Response|JsonResponse|BinaryFileResponse
    {
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }
        $path = ltrim($path, '/');
        $normalizedPath = str_replace(['textbooks/grade 10/', 'textbooks/Grade 10/'], 'textbooks/grade10/', $path);

        $fullPath = storage_path('app/public/' . $normalizedPath);

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            // Fallback check directly in storage_path
            $altPath = storage_path($normalizedPath);
            if (file_exists($altPath) && is_file($altPath)) {
                $fullPath = $altPath;
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PDF file not found on server disk.',
                ], 404);
            }
        }

        $realPath = realpath($fullPath);
        if (!$realPath) {
            return response()->json(['status' => 'error', 'message' => 'Invalid file path.'], 403);
        }

        // Web Server Offloading (Nginx X-Accel-Redirect / Apache X-Sendfile)
        if (env('X_ACCEL_REDIRECT_PREFIX')) {
            $basePath = realpath(storage_path('app/public'));
            $normRealPath = strtolower(str_replace('\\', '/', $realPath));
            $normBasePath = strtolower(str_replace('\\', '/', $basePath ?: ''));

            if ($basePath && str_starts_with($normRealPath, $normBasePath)) {
                $relativePath = ltrim(substr($normRealPath, strlen($normBasePath)), '/');
                return response('', 200, [
                    'X-Accel-Redirect' => rtrim(env('X_ACCEL_REDIRECT_PREFIX'), '/') . '/' . $relativePath,
                    'Content-Type' => mime_content_type($realPath) ?: 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . basename($realPath) . '"',
                    'Accept-Ranges' => 'bytes',
                    'Cache-Control' => 'public, max-age=31536000, immutable',
                ]);
            }
        }

        $mimeType = mime_content_type($realPath) ?: 'application/pdf';
        $fileMtime = filemtime($realPath);
        $fileSize = filesize($realPath);

        $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $title) . '.pdf';

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Accept-Ranges' => 'bytes',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Range',
            'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ];

        /** @var BinaryFileResponse $response */
        $response = response()->file($realPath, $headers);
        $response->setLastModified(\DateTime::createFromFormat('U', (string) $fileMtime));
        $response->setEtag(sprintf('"%x-%x"', $fileMtime, $fileSize));
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->prepare($request);
        return $response;
    }
}

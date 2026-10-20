<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Astegni API is running',
        'version' => '1.0.0'
    ]);
});

Route::options('/storage/{path}', function () {
    return response('', 204, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
    ]);
})->where('path', '.*');

Route::get('/storage/{path}', function (\Illuminate\Http\Request $request, string $path) {
    $path = urldecode($path);
    $path = ltrim($path, '/');

    // Normalize legacy space path 'textbooks/grade 10/...' -> 'textbooks/grade10/...'
    $normalizedPath = str_replace(['textbooks/grade 10/', 'textbooks/Grade 10/'], 'textbooks/grade10/', $path);

    $fullPath = storage_path('app/public/' . $normalizedPath);

    if (!file_exists($fullPath) || !is_file($fullPath)) {
        abort(404, 'Textbook PDF file not found.');
    }

    $realPath = realpath($fullPath);
    $basePath = realpath(storage_path('app/public'));

    $normRealPath = strtolower(str_replace('\\', '/', $realPath ?: ''));
    $normBasePath = strtolower(str_replace('\\', '/', $basePath ?: ''));

    if (!$realPath || !$basePath || !str_starts_with($normRealPath, $normBasePath)) {
        abort(403, 'Access denied.');
    }

    // Web Server Offloading (Nginx X-Accel-Redirect / Apache X-Sendfile) if enabled in env
    if (env('X_ACCEL_REDIRECT_PREFIX')) {
        $relativePath = ltrim(substr($normRealPath, strlen($normBasePath)), '/');
        return response('', 200, [
            'X-Accel-Redirect' => rtrim(env('X_ACCEL_REDIRECT_PREFIX'), '/') . '/' . $relativePath,
            'Content-Type' => mime_content_type($realPath) ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($realPath) . '"',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    $mimeType = mime_content_type($realPath) ?: 'application/pdf';
    $fileMtime = filemtime($realPath);
    $fileSize = filesize($realPath);

    $headers = [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'inline; filename="' . basename($realPath) . '"',
        'Accept-Ranges' => 'bytes',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Range',
        'Access-Control-Expose-Headers' => 'Content-Range, Content-Length, Accept-Ranges',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ];

    /** @var \Symfony\Component\HttpFoundation\BinaryFileResponse $response */
    $response = response()->file($realPath, $headers);
    $response->setLastModified(\DateTime::createFromFormat('U', (string) $fileMtime));
    $response->setEtag(sprintf('"%x-%x"', $fileMtime, $fileSize));
    $response->setPublic();

    if ($response->isNotModified($request)) {
        return $response;
    }

    $response->prepare($request);
    return $response;
})->where('path', '.*')->name('storage.public');


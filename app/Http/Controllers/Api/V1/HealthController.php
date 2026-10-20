<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

/**
 * HealthController handles basic health check endpoints.
 */
class HealthController extends ApiController
{
    /**
     * Return a simple health check response.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->success(
            ['timestamp' => now()->toIso8601String()],
            'API is healthy and running.'
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Return a simple health check response.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            "status" => "online",
            "server" => "Laravel",
            "api" => "Astegni",
            "version" => "1.0"
        ]);
    }
}

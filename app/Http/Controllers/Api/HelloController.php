<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HelloController extends Controller
{
    /**
     * Return a welcome message for the API.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            "success" => true,
            "application" => "Astegni API",
            "version" => "1.0",
            "message" => "Welcome to the Astegni Backend API"
        ]);
    }
}

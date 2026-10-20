<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LegalController extends Controller
{
    /**
     * Public — no authentication required.
     * Describes cookies, authentication tokens,
     * and browser storage used by the application.
     */
    public function cookiePolicy(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'title' => 'Cookies & Local Storage',
                'last_updated' => '2026-09-14',

                'summary' => 'Asteghi uses authentication tokens to authenticate users and authorize access to protected API endpoints. The API does not use authentication cookies for its Bearer-token authentication mechanism.',

                'items' => [
                    [
                        'name' => 'Authentication Token',
                        'type' => 'Bearer token',
                        'purpose' => 'Authenticates users and authorizes access to protected API endpoints.',
                        'storage' => 'The frontend is responsible for storing the token in the browser.',
                        'expires' => 'The token can be revoked when the user logs out or when the token is otherwise invalidated.',
                    ],
                    [
                        'name' => 'Cookie Consent',
                        'type' => 'Browser storage',
                        'purpose' => 'May be used by the frontend to remember the user’s cookie-consent preference.',
                        'storage' => 'Browser storage.',
                    ],
                ],

                'cookies_used' => [],
            ],
        ]);
    }
}
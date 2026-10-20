<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Allowed staff roles
        $allowedRoles = [
            'super_admin',
            'academic_editor',
            'tutor_moderator',
            'support_moderator',
            'tutor',
            'support_agent',
        ];

        $userRole = strtolower(trim((string)$user->role));

        // Allow if user is marked is_admin OR has an authorized staff role
        if (!empty($user->is_admin) || in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden: You do not have staff permissions to access this administrative resource.'
        ], 403);
    }
}

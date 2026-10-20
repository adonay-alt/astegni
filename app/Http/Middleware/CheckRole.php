<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request and enforce role-based access control.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$roles
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Super admins have unrestricted access across all operational modules
        if ($user->role === 'super_admin' || ($user->is_admin && empty($user->role))) {
            return $next($request);
        }

        // Normalize roles: if comma-separated roles were passed in a single string argument
        $parsedRoles = [];
        foreach ($roles as $roleArg) {
            foreach (explode(',', $roleArg) as $subRole) {
                $trimmed = trim($subRole);
                if ($trimmed !== '') {
                    $parsedRoles[] = $trimmed;
                }
            }
        }

        if (!in_array($user->role, $parsedRoles, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: You do not have permission to access this resource.',
                'required_roles' => $parsedRoles,
                'current_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
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

        if (!$user || !$user->is_admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Administrative privileges required.',
            ], 403);
        }

        // Super admins always have unrestricted system-wide authority
        if ($user->role === 'super_admin' || empty($user->role)) {
            return $next($request);
        }

        // Check if user's role matches any of the permitted roles
        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden. Your administrative role does not have permission for this module.',
                'required_roles' => $roles,
                'current_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}

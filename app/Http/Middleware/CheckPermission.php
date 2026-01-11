<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = $request->user();

        foreach ($permissions as $permission) {
            if (! $user->hasPermissionTo($permission, 'api')) {
                return response()->json([
                    'message' => 'Unauthorized. You do not have the required permission: ' . $permission,
                ], 403);
            }
        }

        return $next($request);
    }
}

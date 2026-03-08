<?php

namespace App\Http\Middleware;

use App\Support\AdminPermission;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    /**
     * Handle an incoming request. Requires admin middleware to have run first.
     * Usage: ->middleware('admin_permission:manage_users')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return new JsonResponse(['message' => 'Forbidden.'], 403);
        }

        if (! $user->hasPermission($permission)) {
            return new JsonResponse(['message' => 'Forbidden. You do not have permission for this action.'], 403);
        }

        return $next($request);
    }
}

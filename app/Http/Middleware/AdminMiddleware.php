<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // If User has relation role and name is 'admin' -> allow
        if ($user->role && strtolower($user->role->name) === 'admin') {
            return $next($request);
        }

        // Otherwise deny
        return response()->json(['message' => 'Forbidden. Admins only.'], 403);
    }
}

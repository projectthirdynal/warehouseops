<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature/permission to check
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->canAccess($feature)) {
            // If it's an API request, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this feature.'
                ], 403);
            }

            // If the user has access to leads, redirect there
            if ($user->canAccess('leads_view')) {
                return redirect()->route('leads.index')->with('error', 'You do not have permission to access that section.');
            }

            // Otherwise redirect to login or show error
            return redirect()->route('login')->with('error', 'You do not have permission to access this system.');
        }

        return $next($request);
    }
}

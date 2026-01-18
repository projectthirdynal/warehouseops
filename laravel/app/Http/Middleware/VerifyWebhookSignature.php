<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * Verify webhook requests have valid API key or signature.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for API key in header
        $apiKey = $request->header('X-Webhook-Key') ?? $request->header('Authorization');

        // Get expected key from config
        $expectedKey = config('services.courier.webhook_key');

        // If no key is configured, log warning but allow (for backward compatibility during transition)
        if (empty($expectedKey)) {
            Log::warning('Webhook received but no COURIER_WEBHOOK_KEY configured', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return $next($request);
        }

        // Strip "Bearer " prefix if present
        if (str_starts_with($apiKey ?? '', 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        // Validate API key
        if (empty($apiKey) || !hash_equals($expectedKey, $apiKey)) {
            Log::warning('Webhook authentication failed', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'provided_key' => $apiKey ? substr($apiKey, 0, 8) . '...' : 'none',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - invalid or missing webhook key'
            ], 401);
        }

        return $next($request);
    }
}

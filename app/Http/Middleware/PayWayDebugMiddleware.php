<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PayWayDebugMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to PayWay related routes
        if (!str_contains($request->path(), 'payment') && !str_contains($request->path(), 'payway')) {
            return $next($request);
        }

        // Log the request
        Log::info('PayWay Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'has_payway_session' => $request->session()->has('payway_payment_data'),
            'request_data' => $request->except(['password', '_token'])
        ]);

        try {
            $response = $next($request);

            // Log successful response
            Log::info('PayWay Response', [
                'status' => $response->getStatusCode(),
                'url' => $request->fullUrl(),
                'session_id' => $request->session()->getId()
            ]);

            return $response;

        } catch (\Exception $e) {
            // Log the error with full context
            Log::error('PayWay Request Exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'request_data' => $request->except(['password', '_token']),
                'session_data' => [
                    'session_id' => $request->session()->getId(),
                    'has_payway_data' => $request->session()->has('payway_payment_data'),
                    'has_order_id' => $request->session()->has('payway_order_id')
                ]
            ]);

            // Re-throw the exception
            throw $e;
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.jsdelivr.net https://cdn.ckeditor.com https://sdk.cashfree.com https://*.razorpay.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https://fonts.gstatic.com",
                "connect-src 'self' https://sandbox.cashfree.com https://api.cashfree.com https://*.cashfree.com https://*.razorpay.com https://cdn.ckeditor.com https://cdn.jsdelivr.net https://api.iconify.design https://api.simplesvg.com https://api.unisvg.com",
                "frame-src 'self' https://www.youtube.com https://sandbox.cashfree.com https://api.cashfree.com https://*.cashfree.com https://*.razorpay.com",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self' https://sandbox.cashfree.com https://api.cashfree.com https://*.cashfree.com https://*.razorpay.com",
            ]));
        }

        return $response;
    }
}

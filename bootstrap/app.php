<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Laravel 11.57's framework defaults still reference the deprecated PDO MySQL
// SSL constant on PHP 8.5 while the application config uses the replacement.
set_error_handler(static function (int $severity, string $message): bool {
    return $severity === E_DEPRECATED
        && str_contains($message, 'PDO::MYSQL_ATTR_SSL_CA');
});

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->redirectTo(function ($request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('signin');
            }
            return route('frontend.login');
        });
        
        $middleware->web(append: [
            \App\Http\Middleware\CheckUserIsActive::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\AdminSessionTimeout::class,
            \App\Http\Middleware\RequireAdminTwoFactor::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payment/cashfree/return',
            'diet-chart/payment/cashfree/return',
            'payment/cashfree/webhook',
            'payment/razorpay/webhook',
            'diet-chart/payment/cashfree/webhook',
            'api/webhooks/cashfree',
            'webhooks/aisensy',
        ]);
        
        $middleware->alias([
            'diet.subscription' => \App\Http\Middleware\CheckDietSubscription::class,
            'admin.timeout' => \App\Http\Middleware\AdminSessionTimeout::class,
            'admin.2fa' => \App\Http\Middleware\RequireAdminTwoFactor::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

return $app;

<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Block destructive database commands (migrate:fresh, db:wipe, etc.)
        // unless explicitly allowed via ALLOW_DB_RESET=true in .env
        if (! app()->environment('testing') && ! filter_var(env('ALLOW_DB_RESET', false), FILTER_VALIDATE_BOOLEAN)) {
            DB::prohibitDestructiveCommands();
        }

        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        \App\Models\OrderItem::observe(\App\Observers\OrderItemObserver::class);

        if (! app()->environment('testing') && ! app()->runningInConsole() && config('services.cashfree.app_id')) {
            try {
                \App\Services\Payments\CashfreePaymentService::bootCheck();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::critical('Cashfree configuration error: ' . $e->getMessage());
                if (config('app.debug')) {
                    throw $e;
                }
            }
        }

        RateLimiter::for('admin-login', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(10)->by('admin-login:ip:' . $request->ip()),
                Limit::perMinute(5)->by('admin-login:email:' . ($email ?: $request->ip())),
            ];
        });

        RateLimiter::for('otp-send', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone'));

            return [
                Limit::perMinute(8)->by('otp-send:ip:' . $request->ip()),
                Limit::perMinute(3)->by('otp-send:phone:' . ($phone ?: $request->ip())),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone'));

            return [
                Limit::perMinute(15)->by('otp-verify:ip:' . $request->ip()),
                Limit::perMinute(8)->by('otp-verify:phone:' . ($phone ?: $request->ip())),
            ];
        });

        RateLimiter::for('form-submit', function (Request $request) {
            return Limit::perMinute(30)->by('form-submit:' . $request->ip());
        });

        RateLimiter::for('cart', function (Request $request) {
            return Limit::perMinute(240)->by('cart:' . ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(120)->by('checkout:' . ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('order-submit', function (Request $request) {
            return Limit::perMinute(12)->by('order-submit:' . ($request->user()?->id ?: $request->ip()));
        });

        View::composer('components.head', function ($view) {
            $data = $view->getData();
            
            $siteName = Setting::get('site_name', 'NutriBuddy');
            $defaultDescription = Setting::get('meta_description', 'Your Health Partner Store');
            $defaultKeywords = Setting::get('meta_keywords', 'nutrition, wellness, health, supplements');
            
            $metaTitle = $data['meta_title'] ?? null;
            $metaDescription = $data['meta_description'] ?? $defaultDescription;
            $metaKeywords = $data['meta_keywords'] ?? $defaultKeywords;

            // Contextual SEO
            if (isset($data['product'])) {
                $p = $data['product'];
                $metaTitle = $p->meta_title ?: $p->name;
                $metaDescription = $p->meta_description ?: Str::limit(strip_tags($p->description), 160);
                $metaKeywords = $p->meta_keywords ?: $p->brand . ', ' . $p->name;
            } elseif (isset($data['category'])) {
                $c = $data['category'];
                $metaTitle = $c->meta_title ?: $c->name;
                $metaDescription = $c->meta_description ?: $c->description;
                $metaKeywords = $c->meta_keywords ?: $c->name;
            } elseif (isset($data['post'])) {
                $post = $data['post'];
                $metaTitle = $post->meta_title ?: $post->title;
                $metaDescription = $post->meta_description ?: $post->excerpt;
                $metaKeywords = $post->meta_keywords ?: '';
            }

            $view->with([
                'seoTitle' => $metaTitle ? "$metaTitle | $siteName" : $siteName,
                'seoDescription' => $metaDescription,
                'seoKeywords' => $metaKeywords,
            ]);
        });

        View::composer('components.navbar', function ($view) {
            $admin = auth('admin')->user();
            $notifications = $admin?->notifications()->latest()->limit(5)->get() ?? collect();
            $unreadCount = $admin?->unreadNotifications()->count() ?? 0;
            $view->with([
                'navbarNotifications' => $notifications,
                'unreadNotificationsCount' => $unreadCount
            ]);
        });
    }

}

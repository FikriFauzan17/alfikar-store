<?php

namespace App\Providers;

use App\Models\User;
use App\Services\InformationalPageService;
use App\Services\PublicSiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Gate::define('access-admin', fn (User $user): bool => (bool) $user->is_admin && (bool) $user->is_active);
        View::composer('layouts.storefront', function ($view): void {
            $publicSettings = app(PublicSiteSettings::class);
            $settings = $publicSettings->values(['seo_title', 'seo_description', 'profile_name', 'site_logo_path']);
            $view->with('siteSeo', $settings);
            $view->with('siteName', $settings['profile_name'] ?? 'Toko Buku Alfikar');
            $view->with(
                'siteLogoUrl',
                filled($settings['site_logo_path'] ?? null)
                    ? Storage::disk((string) config('image-processing.disk', 'public'))->url($settings['site_logo_path'])
                    : null,
            );
            $view->with(
                'informationPages',
                app(InformationalPageService::class)->navigationPages($publicSettings),
            );
        });

        RateLimiter::for('admin-login', function (Request $request): array {
            $email = $request->input('email');
            $email = is_string($email) ? Str::lower($email) : '';

            return [
                Limit::perMinute(5)->by('admin-login:account:'.hash('sha256', $email)),
                Limit::perMinute(20)->by('admin-login:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('admin-two-factor', function (Request $request): array {
            $pendingUserId = $request->session()->get('admin.two_factor.pending_user_id', 'unknown');
            $accountKey = is_numeric($pendingUserId)
                ? (string) $pendingUserId
                : hash('sha256', $request->session()->getId());

            return [
                Limit::perMinute(5)->by('admin-two-factor:account:'.$accountKey),
                Limit::perMinute(20)->by('admin-two-factor:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('product-images', function (Request $request): array {
            $userId = $request->user()?->getAuthIdentifier() ?? 'guest';

            return [
                Limit::perMinute(10)->by('product-images:admin:'.$userId),
                Limit::perMinute(20)->by('product-images:ip:'.$request->ip()),
            ];
        });

        RateLimiter::for(
            'public-catalog',
            fn (Request $request): Limit => Limit::perMinute(60)->by('public-catalog:'.$request->ip())
        );

        RateLimiter::for(
            'public-product',
            fn (Request $request): Limit => Limit::perMinute(120)->by('public-product:'.$request->ip())
        );

        RateLimiter::for(
            'whatsapp-order',
            fn (Request $request): Limit => Limit::perMinute(20)->by('whatsapp-order:'.$request->ip())
        );
    }
}

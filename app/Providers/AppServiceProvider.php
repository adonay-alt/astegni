<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

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
        ResetPassword::createUrlUsing(function (User $user, string $token) {
           $baseUrl = config('auth.frontend_reset_url') ?? env('FRONTEND_RESET_PASSWORD_URL', 'https://example.com/reset-password');
            $separator = str_contains($baseUrl, '?') ? '&' : '?';
            return $baseUrl . $separator . 'token=' . urlencode($token) . '&email=' . urlencode($user->getEmailForPasswordReset());
        });
    }
}

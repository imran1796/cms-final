<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('public-content', function (Request $request) {
            $key = ($request->ip() ?: 'ip') . '|' . ($request->header('X-Space-Id') ?: 'no-space');
            return Limit::perMinute(120)->by($key);
        });

        RateLimiter::for('form-submit', function (Request $request) {
            $space = $request->header('X-Space-Id', 'no-space');
            $ip = $request->ip() ?: 'no-ip';
            $handle = $request->route('handle') ?: 'no-handle';

            return [
                Limit::perMinute(30)->by("form:{$space}:{$handle}:{$ip}"),
                Limit::perHour(300)->by("formh:{$space}:{$handle}:{$ip}"),
            ];
        });

        RateLimiter::for('public-assets', function (Request $request) {
            $key = ($request->ip() ?: 'ip') . '|' . ($request->header('X-Space-Id') ?: 'no-space');
            return Limit::perMinute(180)->by($key);
        });

        RateLimiter::for('auth-login', function (Request $request) {
            $ip = $request->ip() ?: 'ip';
            $email = $request->input('email');
            $key = $email ? "login:{$ip}:{$email}" : "login:{$ip}";
            return [
                Limit::perMinute(5)->by($key),
                Limit::perHour(20)->by("loginh:{$ip}"),
            ];
        });

        RateLimiter::for('auth-forgot-password', function (Request $request) {
            $ip = $request->ip() ?: 'ip';
            $email = $request->input('email', '');
            return Limit::perHour(3)->by("forgot:{$ip}:{$email}");
        });

        RateLimiter::for('auth-reset-password', function (Request $request) {
            $ip = $request->ip() ?: 'ip';
            $email = $request->input('email', '');
            return Limit::perHour(5)->by("reset:{$ip}:{$email}");
        });
    }
}

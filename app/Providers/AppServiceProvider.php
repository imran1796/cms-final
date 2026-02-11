<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use App\Listeners\BroadcastFailedJobAlert;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $base = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost'));
            $email = $notifiable->getEmailForPasswordReset();

            return rtrim($base, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($email);
        });

        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(function ($request) {
                if ($this->app->environment('local')) {
                    return true;
                }
                if (! (bool) env('HORIZON_ENABLED', false)) {
                    return false;
                }
                $user = $request->user();
                return $user && $user->hasRole('Super Admin');
            });
        }

        Event::listen(JobFailed::class, BroadcastFailedJobAlert::class);
    }
}

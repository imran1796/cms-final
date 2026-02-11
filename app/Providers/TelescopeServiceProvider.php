<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal
                || $entry->isReportableException()
                || $entry->isFailedRequest()
                || $entry->isFailedJob()
                || $entry->isScheduledTask()
                || $entry->hasMonitoredTag();
        });
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token', 'password', 'password_confirmation']);
        Telescope::hideRequestHeaders(['cookie', 'x-csrf-token', 'x-xsrf-token', 'authorization']);
    }

    protected function gate(): void
    {
        Gate::define('viewTelescope', function (?User $user) {
            if ($user === null) {
                return false;
            }
            return $user->hasRole('Super Admin')
                || in_array($user->email, [
                    'admin@cms.local',
                ], true);
        });
    }
}

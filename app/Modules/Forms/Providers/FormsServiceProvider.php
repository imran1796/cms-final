<?php

namespace App\Modules\Forms\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Forms\Repositories\Interfaces\FormRepositoryInterface;
use App\Modules\Forms\Repositories\FormRepository;
use App\Modules\Forms\Repositories\Interfaces\FormSubmissionRepositoryInterface;
use App\Modules\Forms\Repositories\FormSubmissionRepository;
use App\Modules\Forms\Services\Interfaces\FormServiceInterface;
use App\Modules\Forms\Services\FormService;
use App\Modules\Forms\Services\Interfaces\FormSubmissionServiceInterface;
use App\Modules\Forms\Services\FormSubmissionService;

final class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FormRepositoryInterface::class, FormRepository::class);
        $this->app->bind(FormSubmissionRepositoryInterface::class, FormSubmissionRepository::class);

        $this->app->bind(FormServiceInterface::class, FormService::class);
        $this->app->bind(FormSubmissionServiceInterface::class, FormSubmissionService::class);
    }

    public function boot(): void
    {
    }
}

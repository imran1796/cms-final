<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DummyController;
use App\Modules\System\Auth\Controllers\AuthController;
use App\Modules\Spaces\Controllers\SpaceController;
use App\Modules\Spaces\Middleware\TenantResolverMiddleware;
use App\Modules\System\System\Controllers\SystemController;
use App\Modules\System\ApiKeys\Controllers\ApiKeyAdminController;
use App\Modules\System\Users\Controllers\UserAdminController;
use App\Modules\System\Settings\Controllers\SettingsController;
use App\Modules\Content\Controllers\CollectionController;
use App\Modules\Content\Controllers\ContentLocaleConfigController;
use App\Modules\Content\Controllers\EntryAdminController;
use App\Modules\Content\Controllers\EntryLockController;
use App\Modules\Content\Controllers\PublishingController;
use App\Modules\Content\Controllers\PreviewController;
use App\Modules\ContentTree\Controllers\TreeController;
use App\Modules\Content\Revisions\Controllers\RevisionController;
use App\Modules\SavedViews\Controllers\SavedViewController;
use App\Modules\ContentDelivery\Controllers\PublicContentController;
use App\Modules\Forms\Controllers\FormAdminController;
use App\Modules\Forms\Controllers\FormSubmissionAdminController;
use App\Modules\Forms\Controllers\FormPublicController;
use App\Modules\Assets\Controllers\AssetAdminController;
use App\Modules\Assets\Controllers\AssetConfigController;
use App\Modules\Assets\Controllers\AssetPublicController;
use App\Modules\Finder\Controllers\FinderController;
use App\Modules\Search\Controllers\SearchController;


Route::get('/v1/dummy/ping', [DummyController::class, 'ok']);

Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    Route::patch('/me', [AuthController::class, 'updateMe'])->middleware('auth:sanctum');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth-forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth-reset-password');
});

Route::prefix('v1/spaces')
    ->middleware(['auth:sanctum', TenantResolverMiddleware::class])
    ->group(function () {
        Route::get('/', [SpaceController::class, 'index']);
        Route::post('/', [SpaceController::class, 'store']);
        Route::delete('/{id}', [SpaceController::class, 'destroy']);
    });

    Route::prefix('v1/system')->group(function () {
        // Health is usually public (no auth) for monitoring
        Route::get('/health', [SystemController::class, 'health']);

        // Info/Stats should be protected
        Route::get('/info', [SystemController::class, 'info'])->middleware('auth:sanctum');
        Route::get('/stats', [SystemController::class, 'stats'])->middleware('auth:sanctum');
    });

    Route::prefix('v1/admin/api-keys')
    ->middleware(['auth:sanctum', TenantResolverMiddleware::class])
    ->group(function () {
        Route::get('/', [ApiKeyAdminController::class, 'index']);
        Route::post('/', [ApiKeyAdminController::class, 'store']);
        Route::put('/{id}', [ApiKeyAdminController::class, 'update']);
        Route::delete('/{id}', [ApiKeyAdminController::class, 'destroy']);
        Route::post('/{id}/regenerate', [ApiKeyAdminController::class, 'regenerate']);
    });

    Route::prefix('v1/admin/users')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [UserAdminController::class, 'index']);
        Route::post('/', [UserAdminController::class, 'store']);
        Route::get('/roles', [UserAdminController::class, 'roles']);
        Route::get('/{id}', [UserAdminController::class, 'show']);
        Route::put('/{id}', [UserAdminController::class, 'update']);
        Route::delete('/{id}', [UserAdminController::class, 'destroy']);
    });

    Route::prefix('v1/admin/settings')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
        Route::patch('/', [SettingsController::class, 'update']);
    });

    Route::prefix('v1/admin/finder')
    ->middleware(['auth:sanctum', TenantResolverMiddleware::class])
    ->group(function () {
        Route::get('/', [FinderController::class, 'index']);
        Route::post('/folders', [FinderController::class, 'storeFolder']);
        Route::post('/move', [FinderController::class, 'move']);
        Route::put('/folders/{id}', [FinderController::class, 'updateFolder'])->whereNumber('id');
        Route::delete('/folders/{id}', [FinderController::class, 'destroyFolder'])->whereNumber('id');
    });

    Route::prefix('v1/admin/collections')
    ->middleware(['auth:sanctum', TenantResolverMiddleware::class])
    ->group(function () {
        Route::get('/', [CollectionController::class, 'index']);
        Route::post('/', [CollectionController::class, 'store']);
        Route::get('/{id}', [CollectionController::class, 'show']);
        Route::put('/{id}', [CollectionController::class, 'update']);
        Route::delete('/{id}', [CollectionController::class, 'destroy']);

        Route::post('/{id}/fields', [CollectionController::class, 'addField']);
        Route::put('/{id}/fields/{fieldId}', [CollectionController::class, 'updateField']);
        Route::delete('/{id}/fields/{fieldId}', [CollectionController::class, 'deleteField']);
    });


    Route::middleware(['auth:sanctum', TenantResolverMiddleware::class])
    ->prefix('v1/admin')
    ->group(function () {
        Route::get('/saved-views', [SavedViewController::class, 'index']);
        Route::post('/saved-views', [SavedViewController::class, 'store']);
        Route::put('/saved-views/{id}', [SavedViewController::class, 'update']);
        Route::delete('/saved-views/{id}', [SavedViewController::class, 'destroy']);

        Route::get('/forms', [FormAdminController::class, 'index']);
        Route::post('/forms', [FormAdminController::class, 'store']);
        Route::get('/forms/{id}', [FormAdminController::class, 'show']);
        Route::put('/forms/{id}', [FormAdminController::class, 'update']);
        Route::delete('/forms/{id}', [FormAdminController::class, 'destroy']);
        Route::get('/forms/{id}/submissions', [FormSubmissionAdminController::class, 'index']);
        Route::get('/forms/{id}/submissions/{submissionId}', [FormSubmissionAdminController::class, 'show']);
        Route::put('/forms/{id}/submissions/{submissionId}', [FormSubmissionAdminController::class, 'update']);

        Route::get('/assets', [AssetAdminController::class, 'index']);
        Route::get('/assets/config', [AssetConfigController::class, 'show']);
        Route::post('/assets', [AssetAdminController::class, 'store']);
        Route::post('/assets/upload/chunk/init', [AssetAdminController::class, 'chunkInit']);
        Route::post('/assets/upload/chunk', [AssetAdminController::class, 'chunk']);
        Route::post('/assets/upload/chunk/complete', [AssetAdminController::class, 'chunkComplete']);
        Route::put('/assets/{id}', [AssetAdminController::class, 'update']);
        Route::delete('/assets/{id}', [AssetAdminController::class, 'destroy']);
        Route::get('/assets/folders', [AssetAdminController::class, 'listFolders']);
        Route::post('/assets/folders', [AssetAdminController::class, 'createFolder']);
        Route::post('/assets/move', [AssetAdminController::class, 'move']);
        Route::get('/content/locales', [ContentLocaleConfigController::class, 'show']);

        Route::get('/{collectionHandle}', [EntryAdminController::class, 'index']);
        Route::post('/{collectionHandle}', [EntryAdminController::class, 'store']);
        Route::get('/{collectionHandle}/{id}', [EntryAdminController::class, 'show'])->whereNumber('id');
        Route::put('/{collectionHandle}/{id}', [EntryAdminController::class, 'update'])->whereNumber('id');
        Route::delete('/{collectionHandle}/{id}', [EntryAdminController::class, 'destroy'])->whereNumber('id');
        Route::post('/{collectionHandle}/{id}/publish', [PublishingController::class, 'publish'])->whereNumber('id');
        Route::post('/{collectionHandle}/{id}/unpublish', [PublishingController::class, 'unpublish'])->whereNumber('id');
        Route::post('/{collectionHandle}/{id}/lock', [EntryLockController::class, 'lock'])->whereNumber('id');
        Route::post('/{collectionHandle}/{id}/unlock', [EntryLockController::class, 'unlock'])->whereNumber('id');


    });

    Route::prefix('v1/spaces/{space_id}/admin')
        ->middleware(['auth:sanctum', TenantResolverMiddleware::class])
        ->group(function () {
            Route::get('/finder', [FinderController::class, 'index']);
            Route::post('/finder/folders', [FinderController::class, 'storeFolder']);
            Route::post('/finder/move', [FinderController::class, 'move']);
            Route::put('/finder/folders/{id}', [FinderController::class, 'updateFolder'])->whereNumber('id');
            Route::delete('/finder/folders/{id}', [FinderController::class, 'destroyFolder'])->whereNumber('id');

            Route::get('/saved-views', [SavedViewController::class, 'index']);
            Route::post('/saved-views', [SavedViewController::class, 'store']);
            Route::put('/saved-views/{id}', [SavedViewController::class, 'update']);
            Route::delete('/saved-views/{id}', [SavedViewController::class, 'destroy']);

            Route::get('/collections', [CollectionController::class, 'index']);
            Route::post('/collections', [CollectionController::class, 'store']);
            Route::get('/collections/{id}', [CollectionController::class, 'show']);
            Route::put('/collections/{id}', [CollectionController::class, 'update']);
            Route::delete('/collections/{id}', [CollectionController::class, 'destroy']);
            Route::post('/collections/{id}/fields', [CollectionController::class, 'addField']);
            Route::put('/collections/{id}/fields/{fieldId}', [CollectionController::class, 'updateField']);
            Route::delete('/collections/{id}/fields/{fieldId}', [CollectionController::class, 'deleteField']);

            Route::get('/forms', [FormAdminController::class, 'index']);
            Route::post('/forms', [FormAdminController::class, 'store']);
            Route::get('/forms/{id}', [FormAdminController::class, 'show']);
            Route::put('/forms/{id}', [FormAdminController::class, 'update']);
            Route::delete('/forms/{id}', [FormAdminController::class, 'destroy']);
            Route::get('/forms/{id}/submissions', [FormSubmissionAdminController::class, 'index']);
            Route::get('/forms/{id}/submissions/{submissionId}', [FormSubmissionAdminController::class, 'show']);
            Route::put('/forms/{id}/submissions/{submissionId}', [FormSubmissionAdminController::class, 'update']);

            Route::get('/assets', [AssetAdminController::class, 'index']);
            Route::get('/assets/config', [AssetConfigController::class, 'show']);
            Route::post('/assets', [AssetAdminController::class, 'store']);
            Route::post('/assets/upload/chunk/init', [AssetAdminController::class, 'chunkInit']);
            Route::post('/assets/upload/chunk', [AssetAdminController::class, 'chunk']);
            Route::post('/assets/upload/chunk/complete', [AssetAdminController::class, 'chunkComplete']);
            Route::put('/assets/{id}', [AssetAdminController::class, 'update']);
            Route::delete('/assets/{id}', [AssetAdminController::class, 'destroy']);
            Route::get('/assets/folders', [AssetAdminController::class, 'listFolders']);
            Route::post('/assets/folders', [AssetAdminController::class, 'createFolder']);
            Route::post('/assets/move', [AssetAdminController::class, 'move']);
            Route::get('/content/locales', [ContentLocaleConfigController::class, 'show']);

            Route::get('/{collectionHandle}/tree', [TreeController::class, 'tree']);
            Route::post('/{collectionHandle}/{id}/move', [TreeController::class, 'move'])->whereNumber('id');
            Route::post('/{collectionHandle}/reorder', [TreeController::class, 'reorder']);
            Route::get('/{collectionHandle}/{id}/revisions', [RevisionController::class, 'index'])->whereNumber('id');
            Route::post('/{collectionHandle}/{id}/restore', [RevisionController::class, 'restore'])->whereNumber('id');

            Route::get('/{collectionHandle}', [EntryAdminController::class, 'index']);
            Route::post('/{collectionHandle}', [EntryAdminController::class, 'store']);
            Route::get('/{collectionHandle}/{id}', [EntryAdminController::class, 'show'])->whereNumber('id');
            Route::put('/{collectionHandle}/{id}', [EntryAdminController::class, 'update'])->whereNumber('id');
            Route::delete('/{collectionHandle}/{id}', [EntryAdminController::class, 'destroy'])->whereNumber('id');
            Route::post('/{collectionHandle}/{id}/publish', [PublishingController::class, 'publish'])->whereNumber('id');
            Route::post('/{collectionHandle}/{id}/unpublish', [PublishingController::class, 'unpublish'])->whereNumber('id');
            Route::post('/{collectionHandle}/{id}/lock', [EntryLockController::class, 'lock'])->whereNumber('id');
            Route::post('/{collectionHandle}/{id}/unlock', [EntryLockController::class, 'unlock'])->whereNumber('id');
        });

    Route::prefix('v1/spaces/{space_id}')
        ->middleware(['auth:sanctum', TenantResolverMiddleware::class])
        ->group(function () {
            Route::get('/preview/{collectionHandle}/{id}', [PreviewController::class, 'preview'])->whereNumber('id');
        });

    Route::prefix('v1/spaces/{space_id}/content')
        ->middleware([TenantResolverMiddleware::class, 'throttle:public-content'])
        ->group(function () {
            Route::get('/search/{collectionHandle}', [SearchController::class, 'search']);
            Route::get('/{collectionHandle}', [PublicContentController::class, 'index']);
            Route::get('/{collectionHandle}/{id}', [PublicContentController::class, 'show']);
        });

    Route::middleware([TenantResolverMiddleware::class])
    ->prefix('content')
    ->group(function () {
        Route::get('/preview/{collectionHandle}/{id}', [PreviewController::class, 'preview'])->whereNumber('id');
    });

    Route::middleware(['auth:sanctum', TenantResolverMiddleware::class])->group(function () {

        Route::get('/v1/admin/{collectionHandle}/tree', [TreeController::class, 'tree']);

        Route::post('/v1/admin/{collectionHandle}/{id}/move', [TreeController::class, 'move']);

        Route::post('/v1/admin/{collectionHandle}/reorder', [TreeController::class, 'reorder']);
    });

    Route::middleware(['auth:sanctum', TenantResolverMiddleware::class])->group(function () {
        Route::get('/v1/admin/{collectionHandle}/{id}/revisions', [RevisionController::class, 'index']);
        Route::post('/v1/admin/{collectionHandle}/{id}/restore', [RevisionController::class, 'restore']);
    });

    Route::middleware([
        \App\Modules\Spaces\Middleware\TenantResolverMiddleware::class,
        'throttle:public-content',
    ])
    ->prefix('content')
    ->group(function () {
        Route::get('/search/{collectionHandle}', [SearchController::class, 'search']);

        Route::get('/{collectionHandle}', [PublicContentController::class, 'index']);
        Route::get('/{collectionHandle}/{id}', [PublicContentController::class, 'show']);
    });

// Public submit (tenant required + throttle)
Route::middleware([\App\Modules\Spaces\Middleware\TenantResolverMiddleware::class, 'throttle:form-submit'])
    ->prefix('forms')
    ->group(function () {
        Route::post('/{handle}/submit', [FormPublicController::class, 'submit']);
    });

// Public assets (tenant-resolved + throttled)
Route::middleware([\App\Modules\Spaces\Middleware\TenantResolverMiddleware::class, 'throttle:public-assets'])
    ->group(function () {
        Route::get('/storage/media/{id}', [AssetPublicController::class, 'original'])->whereNumber('id');
        Route::get('/storage/media/{id}/image', [AssetPublicController::class, 'image'])->whereNumber('id');
        Route::get('/storage/media/{id}/preset/{presetKey}', [AssetPublicController::class, 'preset'])->whereNumber('id');
    });

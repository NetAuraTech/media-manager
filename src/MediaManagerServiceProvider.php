<?php

namespace Netauratech\MediaManager;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Netauratech\CoreCms\Contracts\MediaProviderInterface;
use Netauratech\CoreCms\Events\ContentSaved;
use Netauratech\CoreCms\Events\LangLoaded;
use Netauratech\CoreCms\Form\FormRegistry;
use Netauratech\CoreCms\Services\AssetManager;
use Netauratech\MediaManager\Listeners\UpdateContentMedia;
use Netauratech\MediaManager\Services\MediaProvider;

class MediaManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            MediaProviderInterface::class,
            MediaProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(AssetManager $assetManager, FormRegistry $formRegistry): void
    {
        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('migrations'),
        ], 'media-manager-migrations');

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([
            __DIR__.'/database/seeders/' => database_path('seeders')
        ], 'media-manager-seeders');

        // Load all views
        $this->loadViewsFrom(__DIR__.'/resources/views', 'media-manager');

        // Register Assets
        $packageBasePath = realpath(__DIR__ . '/../');
        $composerJsonPath = $packageBasePath . '/composer.json';

        $assetManager->registerTranslationPath('media-manager', __DIR__.'/lang');

        if (file_exists($composerJsonPath)) {
            $composerJsonContent = json_decode(file_get_contents($composerJsonPath), true);
            if (isset($composerJsonContent['name'])) {
                $packageName = $composerJsonContent['name'];
            }
            $assetManager->registerAppJs("vendor/{$packageName}/src/resources/ts/app.ts");
            $assetManager->registerAdminJs("vendor/{$packageName}/src/resources/ts/admin.ts");
        }

        // Lang
        $this->loadTranslationsFrom(__DIR__.'/lang', 'media-manager');
        LangLoaded::dispatch('media-manager');

        //Route Web
        Route::group([
            'middleware' => ['web'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        });

        //Route api
        Route::group([
            'prefix' => 'api',
            'as' => 'api.',
            'middleware' => ['web', 'auth'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        });

        Event::listen(
            ContentSaved::class,
            UpdateContentMedia::class,
        );

        $mediaKey = "media_id";

        $formRegistry->registerFormFields('content_form', [
            $mediaKey => [
                'template' => 'media-manager::form.media',
                'props' => [
                    'name' => $mediaKey,
                    'label' => __('media-manager::admin.content.image'),
                ]
            ],
        ]);

        $formRegistry->registerValidationRules('content_form', [
            $mediaKey => ['nullable', 'integer'],
        ]);

        $formRegistry->registerFormFields('content_meta', [
            'meta' => [
                'template' => 'media-manager::blog.meta',
            ],
        ]);

        $formRegistry->registerFormFields('blog_media', [
            'media' => [
                'template' => 'media-manager::blog.media',
            ],
        ]);

        $formRegistry->registerFormFields('blog_media_single', [
            'media' => [
                'template' => 'media-manager::blog.media_single',
            ],
        ]);

        $formRegistry->registerFormFields('option_media', [
            'media' => [
                'label' => __('media-manager::admin.media.value'),
                'type' => 'media',
                'template' => 'media-manager::option.media',
                'renderer' => 'media-manager::option.media_renderer',
            ],
        ]);
    }
}
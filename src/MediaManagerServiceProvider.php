<?php

namespace Netauratech\MediaManager;

use Illuminate\Support\Facades\Event;
use Netauratech\CoreCms\Services\AbstractCmsServiceProvider;
use Netauratech\CoreCms\Form\FormRegistry;
use Netauratech\CoreCms\Contracts\MediaProviderInterface;
use Netauratech\MediaManager\Services\MediaProvider;
use Netauratech\CoreCms\Events\ContentSaved;
use Netauratech\MediaManager\Listeners\UpdateContentMedia;

class MediaManagerServiceProvider extends AbstractCmsServiceProvider
{
    protected function getPackageName(): string
    {
        return 'media-manager';
    }

    protected function getBootstrapConfig(): array
    {
        $config = parent::getBootstrapConfig();

        $config['routes']['admin'] = false;
        $config['routes']['auth'] = false;
        $config['publishes']['config'] = false;
        $config['publishes']['assets'] = false;

        return $config;
    }

    public function register(): void
    {
        $this->app->bind(MediaProviderInterface::class, MediaProvider::class);
    }

    public function boot(FormRegistry $formRegistry): void
    {
        $this->bootstrapPackage();

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
            'meta' => ['template' => 'media-manager::blog.meta'],
        ]);

        $formRegistry->registerFormFields('blog_media', [
            'media' => ['template' => 'media-manager::blog.media'],
        ]);

        $formRegistry->registerFormFields('blog_media_single', [
            'media' => ['template' => 'media-manager::blog.media_single'],
        ]);

        $formRegistry->registerFormFields('option_media', [
            'media' => [
                'label' => __('media-manager::admin.media.value'),
                'type' => 'media',
                'template' => 'media-manager::option.media',
                'renderer' => 'media-manager::option.media_renderer',
            ],
        ]);

        Event::listen(ContentSaved::class, UpdateContentMedia::class);
    }
}
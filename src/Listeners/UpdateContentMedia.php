<?php

namespace Netauratech\MediaManager\Listeners;


use Illuminate\Support\Facades\Log;
use Netauratech\CoreCms\Events\ContentSaved;
use Netauratech\MediaManager\Models\Media;

class UpdateContentMedia
{
    /**
     * Creates the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handles the ContentSaved event.
     * This method is called when the ContentSaved event is triggered.
     */
    public function handle(ContentSaved $event): void
    {
        $content = $event->content;

        $request = $event->request;

        $mediaIdFromRequest = $request->input('media_id');

        if ($mediaIdFromRequest) {
            $media = Media::find($mediaIdFromRequest);
            if ($media) {
                $content->media_id = (int)$mediaIdFromRequest;
                $content->save();
            }
        } else {
            if ($content->media_id !== null) {
                $content->media_id = null;
                $content->save();
            }
        }
    }
}
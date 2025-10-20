<?php

namespace Netauratech\MediaManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Netauratech\CoreCms\Http\Controllers\AdminController;
use Netauratech\MediaManager\Http\Requests\StoreMediaAltRequest;
use Netauratech\MediaManager\Http\Requests\UpdateMediaAltRequest;
use Netauratech\MediaManager\Models\Media;
use Netauratech\MediaManager\Models\MediaAlt;

class MediaAltController extends AdminController
{
    protected array $permissions = [
        'media-list'   => ['index'],
        'media-upload' => ['store'],
        'media-delete' => ['destroy'],
    ];

    /**
     * Retrieves all alt texts for a specific media.
     *
     * @param string $mediaId The ID of the media.
     * @return JsonResponse A JSON response containing the list of alt texts.
     */
    public function index(string $mediaId): JsonResponse
    {
        $media = Media::with('alts')->find($mediaId);

        if (!$media) {
            return response()->json(['error' => __('media-manager::admin.media.notfound')], 404);
        }

        return response()->json(
            $media->alts->map(fn(MediaAlt $alt) => [
                'id' => $alt->id,
                'text' => $alt->alt_text,
                'isDefault' => $alt->is_default,
            ])
        );
    }

    /**
     * Stores a new alt text for a specific media.
     *
     * @param StoreMediaAltRequest $request The validated request.
     * @param string $mediaId The ID of the media.
     * @return JsonResponse A JSON response containing the created alt text (status 201) or an error message.
     */
    public function store(StoreMediaAltRequest $request, string $mediaId): JsonResponse
    {
        $media = Media::find($mediaId);

        if (!$media) {
            return response()->json(['error' => __('media-manager::admin.media.notfound')], 404);
        }

        $validated = $request->validated();

        $alt = MediaAlt::create([
            'media_id' => $mediaId,
            'alt_text' => $validated['alt_text'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return response()->json([
            'id' => $alt->id,
            'text' => $alt->alt_text,
            'isDefault' => $alt->is_default,
        ], 201);
    }

    /**
     * Updates an existing alt text.
     *
     * @param UpdateMediaAltRequest $request The validated request.
     * @param string $mediaId The ID of the media.
     * @param string $altId The ID of the alt text to update.
     * @return JsonResponse A JSON response containing the updated alt text or an error message.
     */
    public function update(UpdateMediaAltRequest $request, string $mediaId, string $altId): JsonResponse
    {
        $alt = MediaAlt::where('media_id', $mediaId)->find($altId);

        if (!$alt) {
            return response()->json(['error' => __('media-manager::admin.media.alt.notfound')], 404);
        }

        $validated = $request->validated();

        $alt->update([
            'alt_text' => $validated['alt_text'] ?? $alt->alt_text,
            'is_default' => $validated['is_default'] ?? $alt->is_default,
        ]);

        return response()->json([
            'id' => $alt->id,
            'text' => $alt->alt_text,
            'isDefault' => $alt->is_default,
        ]);
    }

    /**
     * Deletes an alt text.
     *
     * @param string $mediaId The ID of the media.
     * @param string $altId The ID of the alt text to delete.
     * @return JsonResponse An empty JSON response with status 204 if successful, or an error message.
     */
    public function destroy(string $mediaId, string $altId): JsonResponse
    {
        $alt = MediaAlt::where('media_id', $mediaId)->find($altId);

        if (!$alt) {
            return response()->json(['error' => __('media-manager::admin.media.alt.notfound')], 404);
        }

        // Prevent deletion of the default alt if it's the only one
        if ($alt->is_default && MediaAlt::where('media_id', $mediaId)->count() === 1) {
            return response()->json(['error' => __('media-manager::admin.media.alt.cannot_delete_last_default')], 422);
        }

        $alt->delete();

        return response()->json([], 204);
    }
}
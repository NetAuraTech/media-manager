<?php

namespace Netauratech\MediaManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use League\Glide\Filesystem\FilesystemException;
use Netauratech\CoreCms\Http\Controllers\AdminController;
use Netauratech\MediaManager\Http\Requests\UploadMediaRequest;
use Netauratech\MediaManager\Models\Media;
use Netauratech\MediaManager\Services\MediaProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MediaController extends AdminController
{
    protected array $permissions = [
        'media-list'   => ['folders', 'files', 'normalize', ],
        'media-upload' => ['create', 'upload'],
        'media-delete' => ['destroy'],
    ];

    public MediaProvider $mediaProvider;
    public function __construct(MediaProvider $mediaProvider)
    {
        parent::__construct();
        $this->mediaProvider = $mediaProvider;
    }

    /**
     * Resizes an image via the Glide service using the file name.
     *
     * @param string $path The path to the media file (filename) to be resized.
     * @param Request $request The HTTP request containing the Glide resizing parameters (e.g., w, h, fit, q).
     * @return mixed The response from the Glide server (usually an image).
     * @throws HttpException If the media specified by the path is not found (code 404).
     * @throws FilesystemException May be thrown by the Glide server in case of a file system error.
     */
    public function resize(string $path, Request $request): mixed
    {
        $media = Media::where('filename', $path)->first();

        if(!$media) {
            throw new HttpException(404);
        }

        return $this->mediaProvider->resize($media->filename, $request);
    }

    /**
     * Retrieves a list of media folders grouped by month and year,
     * with the number of media files in each folder.
     *
     * @return array An array of arrays, where each array represents a folder
     * with ‘path’ (e.g., “YYYY/MM”) and ‘count’ (number of media files).
     */
    public function folders(): array
    {
        $data = DB::table('medias')
            ->select(DB::raw('EXTRACT(MONTH FROM created_at) as month, EXTRACT(YEAR FROM created_at) as year, COUNT(id) as count'))
            ->groupBy('month', 'year')
            ->orderBy('month', 'DESC')
            ->orderBy('year', 'DESC')
            ->get();

        return array_map(fn(array $row) => [
            'path' => $row['year'] . '/' . str_pad((string)$row['month'], 2, '0', STR_PAD_LEFT),
            'count' => $row['count'],
        ], json_decode($data->toJson(), true));
    }

    /**
     * Retrieves a collection of media files based on the query parameters.
     *
     * Supports string search (‘q’), path filtering (‘path’),
     * or retrieval of the latest files if no parameters are specified.
     * Each media file is then normalized using the `normalize` method.
     *
     * @param Request $request The HTTP request.
     * @return Collection A collection of normalized media objects.
     */
    public function files(Request $request): Collection
    {
        $params = $request->all();
        if (array_key_exists('q', $params)) {
            return $this->search($params['q'])->map([$this, 'normalize']);
        } elseif (array_key_exists('path', $params)) {
            return $this->findForPath($params['path'])->map([$this, 'normalize']);
        } else {
            return $this->findLatest()->map(function ($item) {
                return $this->normalize($item);
            });
        }
    }

    /**
     * Performs a media search by file name.
     *
     * @param string $q The search string.
     * @return Collection A collection of Media objects matching the search.
     */
    public function search(string $q): Collection
    {
        return Media::where('filename', 'LIKE', '%' . $q . '%')
            ->orderBy('created_at', 'DESC')
            ->limit(25)
            ->get();
    }

    /**
     * Finds media for a given path (year/month).
     *
     * @param string $path The path in “YYYY/MM” format (e.g., “2023/07”).
     * @return Collection A collection of Media objects created in the specified period.
     */
    public function findForPath(string $path): Collection
    {
        $parts = explode('/', $path);
        $start = new \DateTimeImmutable("{$parts[0]}-{$parts[1]}-01");
        $end = $start->modify('+1 month -1 second');

        return Media::whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get();
    }

    /**
     * Retrieves the latest uploaded media.
     *
     * @return Collection A collection of Media objects, sorted by creation date in descending order.
     */
    public function findLatest(): Collection
    {
        return Media::orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Normalizes a Media object into an associative array for a consistent API response.
     * Includes original URLs and thumbnails generated by MediaProvider.
     *
     * @param Media $media The Media model instance to normalize.
     * @return array An array containing the normalized media information.
     */
    public function normalize(Media $media): array
    {
        $info = pathinfo($media->filename);
        $filename = $info['filename'];
        $extension = $info['extension'] ?? '';

        $url = $this->mediaProvider->url($media->id);

        return [
            'id' => $media->id,
            'createdAt' => $media->created_at,
            'name' => "{$filename}.{$extension}",
            'width' => $media->width,
            'height' => $media->height,
            'size' => $media->filesize,
            'url' => $url,
            'thumbnail' => $this->mediaProvider->url($media->id, ['w' => 250, 'h' => 100]),
        ];
    }

    /**
     * Handles the upload of a new media file.
     *
     * Validates the request, stores the file via MediaProvider,
     * then returns the normalized media information in JSON.
     *
     * @param UploadMediaRequest $request The validated upload request.
     * @return JsonResponse A JSON response containing the uploaded media information
     * (status 203) or an error message (status 400 or 500).
     */
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (!$validated) {
                return response()->json(['error' => __('media-manager::admin.media.invalid')], 400);
            }

            $media = $this->mediaProvider->store($request->file('file'));

            return response()->json($this->normalize($media), 203);
        } catch (\Exception $e) {
            return response()->json(['error' => __('media-manager::admin.media.error')], 500);
        }
    }

    /**
     * Deletes a media item by its ID.
     *
     * Delegates the deletion to MediaProvider, which handles the deletion
     * of the physical file and the database entry.
     *
     * @param string $id The ID of the media to be deleted.
     * @return JsonResponse An empty JSON response with a 204 (No Content) status if successful,
     * or an error message with a 404 status if the media is not found.
     */
    public function destroy(string $id): JsonResponse
    {
        if(!$this->mediaProvider->delete($id)) {
            return response()->json(['error' => __('media-manager::admin.media.notfound')], 404);
        }

        return response()->json([], 204);
    }
}
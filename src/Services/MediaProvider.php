<?php

namespace Netauratech\MediaManager\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Filesystem\FilesystemException;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\Server;
use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Urls\UrlBuilder;
use Netauratech\CoreCms\Contracts\MediaProviderInterface;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;
use Netauratech\MediaManager\Models\Media;
use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MediaProvider implements MediaProviderInterface
{
    protected UrlBuilder $urlBuilder;

    protected string $path = "medias";

    public function __construct()
    {
        $this->urlBuilder = UrlBuilderFactory::create('/media/resize/', config('app.key'));
    }

    /**
     * Stores an uploaded file and creates a media entry in the database.
     * This method is an internal extension of MediaProvider, not required by the CoreCMS interface.
     *
     * @param UploadedFile $file The uploaded file.
     * @param string|null $disk The name of the storage disk (by default the one configured in config/filesystems.php).
     * @return Media The instance of the Media model created.
     */
    public function store(UploadedFile $file, ?string $disk = null): Media
    {
        $disk = $disk ?? 'public';

        $mimeType = $file->getMimeType();
        $mediaType = Media::determineMediaType($mimeType);

        $filePath = $file->store($this->path, 'public');

        $filename = str_replace($this->path . '/', '', $filePath);


        $width = null;
        $height = null;

        if ($mediaType === 'image') {
            try {
                $image = Image::make($file->getRealPath());
                $width = $image->width();
                $height = $image->height();
            } catch (\Exception $e) {
            }
        }

        return Media::create([
            'filename' => $filename,
            'disk' => $disk,
            'mime_type' => $mimeType,
            'type' => $mediaType,
            'filesize' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);
    }

    /**
     * Retrieves a Media instance by its ID.
     * This method is an internal extension of MediaProvider.
     *
     * @param int $id The media ID.
     * @return Media|null The Media model instance or null if not found.
     */
    public function get(int $id): ?Media
    {
        return Media::find($id);
    }

    /**
     * Deletes media by its ID, which will trigger the deletion of the physical file.
     * This method is an internal extension of MediaProvider.
     *
     * @param int $id The ID of the media to be deleted.
     * @return bool True if the media was successfully deleted, false otherwise.
     */
    public function delete(int $id): bool
    {
        $media = $this->get($id);
        if ($media) {
            return $media->delete();
        }
        return false;
    }

    /**
     * Retrieves the complete URL of a media item without any transformation.
     * This method is an internal extension of MediaProvider.
     *
     * @param int $id The ID of the media item.
     * @return string The complete URL of the media item.
     */
    public function originalUrl(int $id): string
    {
        $media = $this->get($id);

        if (!$media) {
            return "";
        }

        return Storage::disk($media->disk)->url($this->path . '/' . $media->filename);
    }

    /**
     * Generates a URL for a media file, with optional parameters for image transformation (e.g., resizing).
     * This method is an internal extension of MediaProvider.
     *
     * @param int $id The media ID.
     * @param array $params Transformation parameters for images (e.g., [‘w’ => 200, ‘h’ => 200, ‘fit’ => ‘crop’]).
     * @return string The complete URL for the media.
     */
    public function url(int $id, array $params = []): string
    {
        $media = $this->get($id);

        if (!$media) {
            return "";
        }

        if (!$media->isImage()) {
            return $this->originalUrl($id);
        }

        return $this->urlBuilder->getUrl($media->filename, [...$params]);
    }

    /**
     * Generates a file, with optional resizing.
     * This method is an internal extension of MediaProvider.
     *
     * @param string $filename
     * @param Request $request
     * @return mixed The media.
     * @throws FilesystemException
     */
    public function resize(string $filename, Request $request): mixed
    {
        try {
            SignatureFactory::create(config('app.key'))->validateRequest($request->path(), $request->all());

            if (ob_get_level() > 0) {
                ob_clean();
            }

            $glideServer = ServerFactory::create([
                'source' => Storage::disk('public')->getDriver(),
                'cache' => Storage::disk('local')->getDriver(),
                'driver' => 'imagick',
                'response' => new SymfonyResponseFactory(app('request')),
                'defaults' => [
                    'q' => 85,
                    'fm' => 'webp',
                    'fit' => 'crop',
                ],
            ]);

            return $glideServer->getImageResponse($this->path . '/' . $filename, $request->all());
        } catch (SignatureException) {
            throw new HttpException(403, __('cms.asset.signature.invalid'));
        } catch (FileNotFoundException $e) {
            throw new HttpException(404, $e);
        }
    }

    /**
     * Generates a URL for an image, potentially resized.
     *
     * @param string|int $id The media ID.
     * @param int|null $width The desired width for the image.
     * @param int|null $height The desired height for the image.
     * @return string The URL of the image.
     */
    public function getImageUrl(string|int $id, ?int $width = null, ?int $height = null): string
    {
        $params = [];
        if ($width) {
            $params['w'] = $width;
        }
        if ($height) {
            $params['h'] = $height;
        }

        if ($width && $height) {
            $params['fit'] = $params['fit'] ?? 'crop';
        }

        return $this->url($id, $params);
    }

    /**
     * Generates an HTML <img> tag to display an image.
     *
     * This function takes an image entity or path and constructs an <img> tag
     * with options for alternative text, height, CSS transitions,
     * additional classes, and preloading.
     *
     * @param int $id The image id.
     * @param string|null $alt The alternative text for the image, for accessibility. Defaults to null.
     * @param int|null $width The width of the image in pixels. Defaults to null.
     * @param string|null $transitionName A CSS transition name (e.g., for frontend animations). Defaults to null.
     * @param string|null $class Additional CSS classes to apply to the <img> tag. Defaults to null.
     * @return string|null The generated HTML <img> tag as a string, or null if the image cannot be generated.
     */
    public function image_tag(int $id, ?string $alt = null, ?int $width = null, ?string $transitionName = null, ?string $class = null): ?string
    {
        $media = $this->get($id);

        if($media === null) {
            return null;
        }

        $style = $transitionName ? " style=\"view-transition-name: $transitionName\"" : '';

        if($media->isSvgImage()) {
            if(!$width) {
                $width = $media->width ?: 512;
            }

            $targetHeight = ($media->height ?? 512) * ($width /( $media->width ?? 512));

            $svgContent = Storage::disk('public')->get("medias/{$media->filename}");

            if ($svgContent !== false) {
                preg_match('/<svg[^>]*viewBox="([^"]*)"/', $svgContent, $matches);
                $viewBox = $matches[1] ?? null;

                preg_match('/<svg[^>]*fill="([^"]*)"/', $svgContent, $fillMatch);
                preg_match('/<svg[^>]*stroke="([^"]*)"/', $svgContent, $strokeMatch);
                preg_match('/<svg[^>]*stroke-width="([^"]*)"/', $svgContent, $strokeWidthMatch);

                $fill = $fillMatch[1] ?? null;
                $stroke = $strokeMatch[1] ?? null;
                $strokeWidth = $strokeWidthMatch[1] ?? null;

                $svgContent = preg_replace('/<svg[^>]*>|<\/svg>/', '', $svgContent);

                $svgTag = "<svg {$style} class=\"$class\" width=\"$width\" height=\"$targetHeight\" aria-label=\"$alt\"";

                if ($viewBox) {
                    $svgTag .= " viewBox=\"$viewBox\"";
                }
                if ($fill) {
                    $svgTag .= " fill=\"$fill\"";
                }
                if ($stroke) {
                    $svgTag .= " stroke=\"$stroke\"";
                }
                if ($strokeWidth) {
                    $svgTag .= " stroke-width=\"$strokeWidth\"";
                }

                $svgTag .= ">$svgContent</svg>";

                return $svgTag;
            }
        }

        if(!$width) {
            $width = $media->width;
        }

        $targetHeight = $media->height * ($width / $media->width);

        $url = $this->url($media->id, ['w' => $width, 'h' => $targetHeight]);

        if ('' !== $url) {
            if (!$alt) {
                $alt = $media->filename;
            }

            return "<img {$style} class=\"$class\" src=\"$url\" width=\"$width\" height=\"$targetHeight\" alt=\"$alt\"/>";
        }

        return null;
    }
}

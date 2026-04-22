<?php

namespace Netauratech\MediaManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'medias';

    protected $fillable = [
        'filename',
        'disk',
        'mime_type',
        'type',
        'filesize',
        'width',
        'height'
    ];

    protected $casts = [
        'filesize' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     * Registers an event listener to delete the physical file
     * when the media entry is deleted from the database.
     */
    protected static function booted(): void
    {
        static::deleted(function (Media $media) {
            Storage::disk($media->disk)->delete('medias/' . $media->filename);
        });
    }

    /**
     * Get all alt texts for this media.
     */
    public function alts(): HasMany
    {
        return $this->hasMany(MediaAlt::class);
    }

    /**
     * Get the default alt text for this media.
     *
     * @return string|null
     */
    public function getDefaultAlt(): ?string
    {
        $defaultAlt = $this->alts->firstWhere('is_default', true);

        if ($defaultAlt) {
            return $defaultAlt->alt_text;
        }

        // Fallback on filename without extension
        $info = pathinfo($this->filename);
        return $info['filename'] ?? $this->filename;
    }

    /**
     * Determines and returns the generic type of media based on its MIME type.
     * This method can be used internally or as an accessor (mutator).
     *
     * @param string $mimeType The MIME type of the file.
     * @return string The generic type ('image', 'document', 'video', 'audio', 'other').
     */
    public static function determineMediaType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        if ($mimeType === 'application/pdf') {
            return 'document';
        }

        if (str_starts_with($mimeType, 'application/')) {
            return 'document';
        }
        return 'other';
    }

    /**
     * Checks if the media is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->type === 'image' && !$this->isSvgImage();
    }

    /**
     * Checks if the media is a svg.
     *
     * @return bool
     */
    public function isSvgImage(): bool
    {
        return $this->mime_type === 'image/svg+xml';
    }

    /**
     * Checks whether the media is a document.
     *
     * @return bool
     */
    public function isDocument(): bool
    {
        return $this->type === 'document';
    }
}
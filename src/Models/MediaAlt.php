<?php

namespace Netauratech\MediaManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAlt extends Model
{
    use HasFactory;

    protected $table = 'media_alts';

    protected $fillable = [
        'media_id',
        'alt_text',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the media that owns this alt text.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * The "booted" method of the model.
     * Ensures only one default alt per media.
     */
    protected static function booted(): void
    {
        static::creating(function (MediaAlt $mediaAlt) {
            if ($mediaAlt->is_default) {
                static::where('media_id', $mediaAlt->media_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function (MediaAlt $mediaAlt) {
            if ($mediaAlt->is_default && $mediaAlt->isDirty('is_default')) {
                static::where('media_id', $mediaAlt->media_id)
                    ->where('id', '!=', $mediaAlt->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
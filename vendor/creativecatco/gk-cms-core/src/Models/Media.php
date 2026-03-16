<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'filename',
        'path',
        'alt_text',
        'mime_type',
        'size',
        'folder',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the full URL of the media file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk(config('cms.media_disk', 'public'))->url($this->path);
    }

    /**
     * Get the human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the media is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Scope a query to filter by folder.
     */
    public function scopeInFolder(Builder $query, ?string $folder): Builder
    {
        return $query->where('folder', $folder);
    }

    /**
     * Scope a query to only include images.
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope a query to only include documents.
     */
    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where('mime_type', 'not like', 'image/%');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'user_id', 'disk', 'path', 'original_name', 'mime_type', 'size',
    ];

    protected $appends = ['human_size', 'is_image'];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes < 1024) {
            return $bytes.' o';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024).' Ko';
        }

        return round($bytes / 1048576, 1).' Mo';
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    protected static function booted(): void
    {
        static::deleting(function (Document $document) {
            Storage::disk($document->disk)->delete($document->path);
        });
    }
}

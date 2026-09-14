<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    public const MENTIONS = 'mentions-legales';

    public const CONFIDENTIALITE = 'confidentialite';

    protected $fillable = ['slug', 'title', 'content', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    public const MENTIONS = 'mentions-legales';

    public const CONFIDENTIALITE = 'confidentialite';

    protected $fillable = ['slug', 'title', 'meta_description', 'content', 'is_published', 'position'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * Les URL publiques utilisent le slug. Attention : cette clé s'applique
     * aussi aux routes d'administration, qui doivent donc declarer {page:id}
     * explicitement si elles envoient l'identifiant numerique.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}

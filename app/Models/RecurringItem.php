<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringItem extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'name', 'description', 'amount',
        'type', 'day_of_month', 'starts_on', 'ends_on', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActiveOn(Builder $query, $date): Builder
    {
        return $query->where('is_active', true)
            ->whereDate('starts_on', '<=', $date)
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date);
            });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'recurring_item_id', 'name', 'description',
        'amount', 'type', 'occurred_on', 'is_adjustment',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
            'is_adjustment' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function recurringItem(): BelongsTo
    {
        return $this->belongsTo(RecurringItem::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->whereBetween('occurred_on', [
            $month.'-01',
            date('Y-m-t', strtotime($month.'-01')),
        ]);
    }

    public function scopeRecurring(Builder $query): Builder
    {
        return $query->whereNotNull('recurring_item_id');
    }

    public function scopeOneOff(Builder $query): Builder
    {
        return $query->whereNull('recurring_item_id');
    }
}

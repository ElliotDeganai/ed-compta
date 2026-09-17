<?php

namespace App\Models;

use App\Support\BudgetPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationItem extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'name', 'description',
        'amount', 'type', 'occurred_on', 'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_on' => 'date',
            'is_enabled' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForPeriod(Builder $query, BudgetPeriod $period): Builder
    {
        return $query->whereBetween('occurred_on', [$period->startsAt(), $period->endsAt()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdjustment(): array
    {
        return [
            'amount' => (float) $this->amount,
            'type' => $this->type,
            'occurred_on' => $this->occurred_on->toDateString(),
        ];
    }
}

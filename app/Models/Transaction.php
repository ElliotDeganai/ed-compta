<?php

namespace App\Models;

use App\Support\BudgetPeriod;
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

    /**
     * Mouvements d'une periode budgetaire. Remplace l'ancien scope par mois
     * calendaire : avec un cycle demarrant le 25, une periode chevauche deux
     * mois et un filtre sur le mois donnerait des totaux faux.
     */
    public function scopeForPeriod(Builder $query, BudgetPeriod $period): Builder
    {
        return $query->whereBetween('occurred_on', [$period->startsAt(), $period->endsAt()]);
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

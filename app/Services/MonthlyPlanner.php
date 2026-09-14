<?php

namespace App\Services;

use App\Models\RecurringItem;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

/**
 * Materialise les lignes recurrentes du mois demande sous forme de transactions.
 *
 * Une fois materialisee, une occurrence devient une transaction comme une autre :
 * elle peut etre modifiee (facture d'electricite plus elevee ce mois-ci) sans
 * toucher au modele recurrent, et l'historique des mois passes reste fige.
 */
class MonthlyPlanner
{
    public function ensureMonth(int $userId, string $month): void
    {
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $items = RecurringItem::query()
            ->where('user_id', $userId)
            ->activeOn($end->toDateString())
            ->get();

        foreach ($items as $item) {
            $day = min((int) $item->day_of_month, $end->day);
            $date = $start->copy()->setDay($day);

            if ($item->starts_on->gt($date)) {
                continue;
            }

            if ($item->ends_on && $item->ends_on->lt($date)) {
                continue;
            }

            Transaction::firstOrCreate(
                [
                    'recurring_item_id' => $item->id,
                    'occurred_on' => $date->toDateString(),
                ],
                [
                    'user_id' => $item->user_id,
                    'category_id' => $item->category_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'type' => $item->type,
                ]
            );
        }
    }
}

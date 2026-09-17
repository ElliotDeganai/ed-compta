<?php

namespace App\Services;

use App\Models\RecurringItem;
use App\Models\Transaction;
use App\Support\BudgetPeriod;
use App\Support\BusinessDay;
use Illuminate\Support\Carbon;

/**
 * Materialise les lignes recurrentes d'une periode sous forme de transactions.
 *
 * Une periode pouvant chevaucher deux mois — cycle du 25 au 24 — chaque ligne
 * est cherchee dans le mois de debut puis dans celui de fin. Le loyer du 30
 * tombe dans le premier, l'assurance du 5 dans le second, et les deux
 * appartiennent bien au meme cycle.
 *
 * Les lignes marquees shift_to_business_day glissent au jour ouvre suivant :
 * un salaire attendu le 25 un dimanche est date du mardi 27.
 */
class MonthlyPlanner
{
    public function ensurePeriod(int $userId, BudgetPeriod $period): void
    {
        $items = RecurringItem::query()
            ->where('user_id', $userId)
            ->activeOn($period->endsAt())
            ->get();

        foreach ($items as $item) {
            $date = $this->dateInPeriod($period, $item);

            if (! $date) {
                continue;
            }

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

    /**
     * Date effective de la ligne a l'interieur de la periode.
     *
     * Le jour est ramene au dernier jour du mois s'il le depasse — un
     * prelevement au 31 tombe le 30 en avril — puis decale au jour ouvre
     * suivant si la ligne le demande.
     */
    private function dateInPeriod(BudgetPeriod $period, RecurringItem $item): ?Carbon
    {
        $day = (int) $item->day_of_month;

        foreach ([$period->start, $period->end] as $reference) {
            $candidate = $reference->copy()
                ->setDay(min($day, $reference->daysInMonth))
                ->startOfDay();

            if ($item->shift_to_business_day) {
                $candidate = BusinessDay::next($candidate);
            }

            if ($period->contains($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

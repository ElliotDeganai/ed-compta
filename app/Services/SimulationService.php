<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SimulationItem;
use App\Models\Transaction;
use App\Support\BudgetPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SimulationService
{
    public function __construct(private BudgetService $budget)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function compare(int $userId, BudgetPeriod $period): array
    {
        $items = SimulationItem::query()
            ->where('user_id', $userId)
            ->forPeriod($period)
            ->with('category:id,name,icon,color')
            ->orderBy('occurred_on')
            ->get();

        $adjustments = $items->where('is_enabled', true)
            ->map(fn (SimulationItem $item) => $item->toAdjustment())
            ->values()
            ->all();

        $before = $this->budget->summary($userId, $period);
        $after = $this->budget->summary($userId, $period, $adjustments);

        return [
            'items' => $items,
            'before' => $before,
            'after' => $after,
            'totals' => [
                'income' => round((float) $items->where('is_enabled', true)->where('type', 'income')->sum('amount'), 2),
                'expense' => round((float) $items->where('is_enabled', true)->where('type', 'expense')->sum('amount'), 2),
                'enabled' => $items->where('is_enabled', true)->count(),
            ],
            'weeks' => $this->weeks($items, $before, $after),
            'projection' => $this->projection($userId, $period, $items),
        ];
    }

    /**
     * Fusionne les deux decoupages produits par BudgetService::weeksFrom().
     * Aucun budget n'est recalcule ici : l'ecran de simulation et le tableau
     * de bord partagent donc exactement la meme regle de repartition.
     *
     * @param  Collection<int, SimulationItem>  $items
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<int, array<string, mixed>>
     */
    private function weeks(Collection $items, array $before, array $after): array
    {
        $avant = $this->budget->weeksFrom($before);
        $apres = $this->budget->weeksFrom($after);

        if (! $apres) {
            return [];
        }

        $enabled = $items->where('is_enabled', true);

        return array_map(function (array $week, int $index) use ($avant, $enabled) {
            $reference = $avant[$index] ?? $week;

            $inWeek = $enabled->filter(
                fn (SimulationItem $item) => $item->occurred_on->toDateString() >= $week['start']
                    && $item->occurred_on->toDateString() <= $week['end']
            );

            return $week + [
                'budget_before' => $reference['budget'],
                'delta' => round($week['budget'] - $reference['budget'], 2),
                'labels' => $inWeek->map(fn (SimulationItem $item) => [
                    'name' => $item->name,
                    'amount' => (float) $item->amount,
                    'type' => $item->type,
                ])->values()->all(),
            ];
        }, $apres, array_keys($apres));
    }

    /**
     * Projection du solde d'aujourd'hui a la fin de la periode.
     *
     * Ne compte que les mouvements dates et connus. Les depenses courantes ne
     * sont pas extrapolees : les inventer donnerait une courbe fausse avec
     * l'apparence de la precision.
     *
     * @param  Collection<int, SimulationItem>  $items
     * @return array<string, mixed>
     */
    private function projection(int $userId, BudgetPeriod $period, Collection $items): array
    {
        $today = Carbon::today();
        $from = $today->greaterThan($period->start) ? $today->copy() : $period->start->copy();

        if ($from->gt($period->end)) {
            return ['points' => [], 'low' => null, 'threshold' => 0.0];
        }

        $future = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('occurred_on', [$from->copy()->addDay()->toDateString(), $period->endsAt()])
            ->get(['amount', 'type', 'occurred_on', 'name']);

        $enabled = $items->where('is_enabled', true);

        $balance = $this->budget->balance($userId);
        $simulated = $balance;
        $points = [];
        $low = null;

        for ($day = $from->copy(); $day->lte($period->end); $day->addDay()) {
            $date = $day->toDateString();

            $realDelta = $future
                ->filter(fn ($movement) => $movement->occurred_on->toDateString() === $date)
                ->sum(fn ($movement) => $movement->type === 'income' ? (float) $movement->amount : -(float) $movement->amount);

            $sameDay = $enabled->filter(fn (SimulationItem $item) => $item->occurred_on->toDateString() === $date);

            $simDelta = $sameDay->sum(
                fn (SimulationItem $item) => $item->type === 'income' ? (float) $item->amount : -(float) $item->amount
            );

            $balance = round($balance + $realDelta, 2);
            $simulated = round($simulated + $realDelta + $simDelta, 2);

            $points[] = [
                'date' => $date,
                'day' => (int) $day->day,
                'baseline' => $balance,
                'simulated' => $simulated,
                'events' => $sameDay->map(fn (SimulationItem $item) => [
                    'name' => $item->name,
                    'amount' => (float) $item->amount,
                    'type' => $item->type,
                ])->values()->all(),
            ];

            if ($low === null || $simulated < $low['amount']) {
                $low = ['date' => $date, 'amount' => $simulated];
            }
        }

        return [
            'points' => $points,
            'low' => $low,
            'threshold' => (float) Setting::get('low_balance_threshold', 300),
        ];
    }
}

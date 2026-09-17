<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use App\Support\BudgetPeriod;
use Illuminate\Support\Carbon;

class BudgetService
{
    public function __construct(private MonthlyPlanner $planner)
    {
    }

    /**
     * Solde du compte : solde d'ouverture + tous les mouvements deja passes.
     * Independant du cycle budgetaire — un solde n'a pas de periode.
     */
    public function balance(int $userId): float
    {
        $opening = (float) Setting::get('opening_balance', 0);
        $openingDate = Setting::get('opening_balance_date');

        $query = Transaction::query()
            ->where('user_id', $userId)
            ->whereDate('occurred_on', '<=', Carbon::today()->toDateString());

        if ($openingDate) {
            $query->whereDate('occurred_on', '>=', $openingDate);
        }

        $movements = (float) $query
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as total")
            ->value('total');

        return round($opening + $movements, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $adjustments  Mouvements hypothetiques
     * @return array<string, mixed>
     */
    public function summary(int $userId, BudgetPeriod $period, array $adjustments = []): array
    {
        $this->planner->ensurePeriod($userId, $period);

        $today = Carbon::today();
        $isCurrent = $period->isCurrent();

        $adjustments = $this->keepPeriod($adjustments, $period);

        $income = $this->sum($userId, $period, 'income') + $this->sumAdjustments($adjustments, 'income');
        $expenses = $this->sum($userId, $period, 'expense') + $this->sumAdjustments($adjustments, 'expense');
        $recurringExpenses = $this->sum($userId, $period, 'expense', recurringOnly: true);
        $pendingExpenses = $this->pendingExpenses($userId, $period, $today, $adjustments);

        $available = round($income - $expenses, 2);
        $planned = round($income - $recurringExpenses, 2);

        $weekly = $isCurrent
            ? $this->weeklyBudget($userId, $period, $today, $income, $expenses, $adjustments)
            : null;

        return [
            'period' => $period->toArray(),
            'month' => $period->key,
            'label' => $period->label(),
            'is_current_month' => $isCurrent,
            'balance' => $this->balance($userId),
            'income_total' => round($income, 2),
            'expense_total' => round($expenses, 2),
            'recurring_expense_total' => $recurringExpenses,
            'pending_expense_total' => $pendingExpenses,
            'planned' => $planned,
            'available' => $available,
            'days_remaining' => $isCurrent ? (int) $today->diffInDays($period->end) + 1 : 0,
            'weekly' => $weekly,
        ];
    }

    /**
     * Decoupe les semaines restantes de la periode et donne le budget de
     * chacune. Fonction pure du resume : aucune requete, donc le tableau de
     * bord et la simulation ne peuvent pas diverger.
     *
     * @param  array<string, mixed>  $summary
     * @return array<int, array<string, mixed>>
     */
    public function weeksFrom(array $summary): array
    {
        $weekly = $summary['weekly'] ?? null;

        if (! $weekly) {
            return [];
        }

        $end = Carbon::parse($summary['period']['end'])->startOfDay();
        $cursor = Carbon::parse($weekly['week_start'])->startOfDay();
        $weeks = [];
        $index = 0;

        while ($cursor->lte($end)) {
            $weekEnd = $cursor->copy()->endOfWeek()->startOfDay();

            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }

            $days = (int) $cursor->diffInDays($weekEnd) + 1;
            $isCurrent = $index === 0;

            $budget = $isCurrent
                ? (float) $weekly['allowance']
                : round((float) ($weekly['next_allowance'] ?? 0) * ($days / 7), 2);

            $weeks[] = [
                'start' => $cursor->toDateString(),
                'end' => $weekEnd->toDateString(),
                'days' => $days,
                'is_current' => $isCurrent,
                'is_partial' => $days < 7,
                'budget' => $budget,
                'spent' => $isCurrent ? (float) $weekly['spent'] : 0.0,
                'remaining' => $isCurrent ? (float) $weekly['remaining'] : $budget,
                'overspent' => $isCurrent ? (float) $weekly['overspent'] : 0.0,
            ];

            $cursor = $weekEnd->copy()->addDay()->startOfDay();
            $index++;
        }

        return $weeks;
    }

    /**
     * Budget de la semaine en cours, et budget des semaines suivantes.
     *
     * Le budget d'une semaine pleine est le disponible au debut de la semaine
     * en cours, divise par le nombre de semaines restantes de la periode. Une
     * semaine incomplete — au bord de la periode — recoit sa part au prorata
     * de ses jours.
     *
     * Ce qui est depense au-dela du budget d'une semaine n'est pas efface : il
     * sort du disponible, donc le budget des semaines suivantes baisse d'autant.
     *
     * @param  array<int, array<string, mixed>>  $adjustments
     * @return array<string, float|int|string|null>
     */
    private function weeklyBudget(
        int $userId,
        BudgetPeriod $period,
        Carbon $today,
        float $income,
        float $expenses,
        array $adjustments
    ): array {
        $weekStart = $today->copy()->startOfWeek();

        if ($weekStart->lt($period->start)) {
            $weekStart = $period->start->copy();
        }

        $weekEnd = $today->copy()->endOfWeek()->startOfDay();

        if ($weekEnd->gt($period->end)) {
            $weekEnd = $period->end->copy();
        }

        // Borne haute a aujourd'hui : sans elle, une depense datee dans le
        // futur serait soustraite du disponible puis rajoutee ici, et n'aurait
        // aucun effet sur le budget.
        $spent = (float) Transaction::query()
            ->where('user_id', $userId)
            ->forPeriod($period)
            ->oneOff()
            ->where('type', 'expense')
            ->whereBetween('occurred_on', [$weekStart->toDateString(), $today->toDateString()])
            ->sum('amount');

        $adjustedThisWeek = $this->between($adjustments, $weekStart, $today);
        $spent += $this->sumAdjustments($adjustedThisWeek, 'expense');
        $spent -= $this->sumAdjustments($adjustedThisWeek, 'income');
        $spent = round($spent, 2);

        $daysRemaining = (int) $weekStart->diffInDays($period->end) + 1;
        $currentWeekDays = (int) $weekStart->diffInDays($weekEnd) + 1;
        $daysAfter = max($daysRemaining - $currentWeekDays, 0);

        $availableAtWeekStart = round($income - $expenses + $spent, 2);
        $fullWeek = round($availableAtWeekStart / max($daysRemaining / 7, 0.01), 2);

        $allowance = round($fullWeek * ($currentWeekDays / 7), 2);
        $remaining = round($allowance - $spent, 2);

        $availableAfter = round($income - $expenses, 2);
        $nextFullWeek = $daysAfter > 0 ? round($availableAfter / ($daysAfter / 7), 2) : null;

        return [
            'allowance' => $allowance,
            'full_week' => $fullWeek,
            'spent' => $spent,
            'remaining' => max($remaining, 0.0),
            'overspent' => max(-$remaining, 0.0),
            'days_in_week' => $currentWeekDays,
            'days_remaining' => $daysRemaining,
            'days_after' => $daysAfter,
            'weeks_remaining' => round($daysRemaining / 7, 2),
            'next_allowance' => $nextFullWeek,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
        ];
    }

    private function sum(int $userId, BudgetPeriod $period, string $type, bool $recurringOnly = false): float
    {
        $query = Transaction::query()
            ->where('user_id', $userId)
            ->forPeriod($period)
            ->where('type', $type);

        if ($recurringOnly) {
            $query->recurring();
        }

        return round((float) $query->sum('amount'), 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $adjustments
     */
    private function pendingExpenses(int $userId, BudgetPeriod $period, Carbon $today, array $adjustments): float
    {
        $real = (float) Transaction::query()
            ->where('user_id', $userId)
            ->forPeriod($period)
            ->where('type', 'expense')
            ->whereDate('occurred_on', '>', $today->toDateString())
            ->sum('amount');

        $simulated = $this->sumAdjustments(
            $this->fromDate($adjustments, $today->copy()->addDay()),
            'expense'
        );

        return round($real + $simulated, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $adjustments
     * @return array<int, array<string, mixed>>
     */
    private function keepPeriod(array $adjustments, BudgetPeriod $period): array
    {
        return array_values(array_filter(
            $adjustments,
            fn (array $item) => $item['occurred_on'] >= $period->startsAt()
                && $item['occurred_on'] <= $period->endsAt()
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $adjustments
     * @return array<int, array<string, mixed>>
     */
    private function between(array $adjustments, Carbon $from, Carbon $to): array
    {
        return array_values(array_filter(
            $adjustments,
            fn (array $item) => $item['occurred_on'] >= $from->toDateString()
                && $item['occurred_on'] <= $to->toDateString()
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $adjustments
     * @return array<int, array<string, mixed>>
     */
    private function fromDate(array $adjustments, Carbon $date): array
    {
        return array_values(array_filter(
            $adjustments,
            fn (array $item) => $item['occurred_on'] >= $date->toDateString()
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $adjustments
     */
    private function sumAdjustments(array $adjustments, string $type): float
    {
        return round(array_sum(array_map(
            fn (array $item) => $item['type'] === $type ? (float) $item['amount'] : 0.0,
            $adjustments
        )), 2);
    }
}

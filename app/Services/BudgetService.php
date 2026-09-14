<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class BudgetService
{
    public function __construct(private MonthlyPlanner $planner)
    {
    }

    /**
     * Solde du compte : solde d'ouverture + tous les mouvements deja passes.
     * Les lignes datees dans le futur (loyer du 30) ne sont pas comptees ici :
     * l'argent est encore sur le compte.
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
     * @return array<string, mixed>
     */
    public function summary(int $userId, string $month): array
    {
        $this->planner->ensureMonth($userId, $month);

        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $today = Carbon::today();
        $isCurrentMonth = $today->betweenIncluded($start, $end);

        $income = $this->sum($userId, $month, 'income');
        $expenses = $this->sum($userId, $month, 'expense');
        $recurringExpenses = $this->sum($userId, $month, 'expense', recurringOnly: true);
        $pendingExpenses = $this->pendingExpenses($userId, $month, $today);

        $available = round($income - $expenses, 2);
        $planned = round($income - $recurringExpenses, 2);

        $weekly = $isCurrentMonth
            ? $this->weeklyBudget($userId, $month, $start, $end, $today, $income, $expenses)
            : null;

        return [
            'month' => $month,
            'label' => $start->locale('fr')->isoFormat('MMMM YYYY'),
            'is_current_month' => $isCurrentMonth,
            'balance' => $this->balance($userId),
            'income_total' => $income,
            'expense_total' => $expenses,
            'recurring_expense_total' => $recurringExpenses,
            'pending_expense_total' => $pendingExpenses,
            'planned' => $planned,
            'available' => $available,
            'days_remaining' => $isCurrentMonth ? (int) $today->diffInDays($end->copy()->startOfDay()) + 1 : 0,
            'weekly' => $weekly,
        ];
    }

    /**
     * Budget hebdomadaire : ce qui restait disponible au debut de la semaine,
     * divise par le nombre de semaines restantes a cette date. Les charges
     * recurrentes non encore prelevees restent reservees.
     *
     * @return array<string, float>
     */
    private function weeklyBudget(
        int $userId,
        string $month,
        Carbon $start,
        Carbon $end,
        Carbon $today,
        float $income,
        float $expenses
    ): array {
        $weekStart = $today->copy()->startOfWeek();

        if ($weekStart->lt($start)) {
            $weekStart = $start->copy();
        }

        $discretionarySinceWeekStart = (float) Transaction::query()
            ->where('user_id', $userId)
            ->forMonth($month)
            ->oneOff()
            ->where('type', 'expense')
            ->whereDate('occurred_on', '>=', $weekStart->toDateString())
            ->sum('amount');

        $availableAtWeekStart = $income - $expenses + $discretionarySinceWeekStart;
        $daysRemaining = (int) $weekStart->diffInDays($end->copy()->startOfDay()) + 1;
        $weeksRemaining = max($daysRemaining / 7, 0.5);

        $allowance = round($availableAtWeekStart / $weeksRemaining, 2);

        $spent = (float) Transaction::query()
            ->where('user_id', $userId)
            ->forMonth($month)
            ->oneOff()
            ->where('type', 'expense')
            ->whereBetween('occurred_on', [$weekStart->toDateString(), $today->toDateString()])
            ->sum('amount');

        return [
            'allowance' => $allowance,
            'spent' => round($spent, 2),
            'remaining' => round($allowance - $spent, 2),
            'weeks_remaining' => round($weeksRemaining, 1),
            'week_start' => $weekStart->toDateString(),
        ];
    }

    private function sum(int $userId, string $month, string $type, bool $recurringOnly = false): float
    {
        $query = Transaction::query()
            ->where('user_id', $userId)
            ->forMonth($month)
            ->where('type', $type);

        if ($recurringOnly) {
            $query->recurring();
        }

        return round((float) $query->sum('amount'), 2);
    }

    private function pendingExpenses(int $userId, string $month, Carbon $today): float
    {
        return round((float) Transaction::query()
            ->where('user_id', $userId)
            ->forMonth($month)
            ->where('type', 'expense')
            ->whereDate('occurred_on', '>', $today->toDateString())
            ->sum('amount'), 2);
    }
}

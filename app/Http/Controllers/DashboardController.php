<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private BudgetService $budget)
    {
    }

    public function index(Request $request): Response
    {
        $month = $this->resolveMonth($request);
        $userId = $request->user()->id;

        $summary = $this->budget->summary($userId, $month);

        $recurring = Transaction::query()
            ->where('user_id', $userId)
            ->forMonth($month)
            ->recurring()
            ->with('category:id,name,icon,color')
            ->orderBy('occurred_on')
            ->get();

        $oneOff = Transaction::query()
            ->where('user_id', $userId)
            ->forMonth($month)
            ->oneOff()
            ->with(['category:id,name,icon,color', 'documents:id,documentable_id,documentable_type,original_name'])
            ->orderByDesc('occurred_on')
            ->limit(8)
            ->get();

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'recurringLines' => $recurring,
            'latestMovements' => $oneOff,
            'categories' => Category::orderBy('position')->orderBy('name')->get(['id', 'name', 'type', 'icon', 'color']),
            'today' => Carbon::today()->toDateString(),
            'months' => $this->availableMonths($userId),
        ]);
    }

    /**
     * Rapprochement bancaire : on saisit le solde reel, l'outil cree la ligne
     * d'ecart pour que le calcul reparte juste.
     */
    public function reconcile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'real_balance' => ['required', 'numeric'],
        ]);

        $userId = $request->user()->id;
        $difference = round((float) $data['real_balance'] - $this->budget->balance($userId), 2);

        if (abs($difference) < 0.01) {
            return back()->with('success', 'Le solde calcule correspond deja a votre releve.');
        }

        Transaction::create([
            'user_id' => $userId,
            'name' => 'Ecart de rapprochement',
            'description' => 'Ligne generee automatiquement pour aligner le solde calcule sur le solde reel.',
            'amount' => abs($difference),
            'type' => $difference > 0 ? 'income' : 'expense',
            'occurred_on' => Carbon::today()->toDateString(),
            'is_adjustment' => true,
        ]);

        return back()->with('success', 'Solde rapproche, ecart de '.number_format($difference, 2, '.', ' ').' enregistre.');
    }

    private function resolveMonth(Request $request): string
    {
        $month = (string) $request->query('month', Carbon::today()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1
            ? $month
            : Carbon::today()->format('Y-m');
    }

    /**
     * @return array<int, string>
     */
    private function availableMonths(int $userId): array
    {
        $first = Transaction::where('user_id', $userId)->min('occurred_on');
        $cursor = $first ? Carbon::parse($first)->startOfMonth() : Carbon::today()->startOfMonth();
        $last = Carbon::today()->startOfMonth();
        $months = [];

        while ($cursor->lte($last)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return array_reverse($months);
    }
}

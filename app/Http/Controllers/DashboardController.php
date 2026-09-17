<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\BudgetService;
use App\Support\BudgetPeriod;
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
        $period = $this->resolvePeriod($request);
        $userId = $request->user()->id;

        $summary = $this->budget->summary($userId, $period);

        $recurring = Transaction::query()
            ->where('user_id', $userId)
            ->forPeriod($period)
            ->recurring()
            ->with('category:id,name,icon,color')
            ->orderBy('occurred_on')
            ->get();

        $oneOff = Transaction::query()
            ->where('user_id', $userId)
            ->forPeriod($period)
            ->oneOff()
            ->with(['category:id,name,icon,color', 'documents:id,documentable_id,documentable_type,original_name'])
            ->orderByDesc('occurred_on')
            ->limit(8)
            ->get();

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'weeks' => $this->budget->weeksFrom($summary),
            'recurringLines' => $recurring,
            'latestMovements' => $oneOff,
            'categories' => Category::orderBy('position')->orderBy('name')->get(['id', 'name', 'type', 'icon', 'color']),
            'today' => Carbon::today()->toDateString(),
            'periods' => $this->availablePeriods($userId, $period),
        ]);
    }

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

    private function resolvePeriod(Request $request): BudgetPeriod
    {
        $key = (string) $request->query('month', '');

        return preg_match('/^\d{4}-\d{2}$/', $key) === 1
            ? BudgetPeriod::fromKey($key)
            : BudgetPeriod::current();
    }

    /**
     * Periodes proposees au selecteur : de la plus ancienne donnee connue
     * jusqu'a douze cycles a venir.
     *
     * Les cycles futurs servent a anticiper — saisir une facture connue
     * d'avance — et les passes a corriger apres coup.
     *
     * @return array<int, array<string, mixed>>
     */
    private function availablePeriods(int $userId, BudgetPeriod $selected): array
    {
        $first = Transaction::where('user_id', $userId)->min('occurred_on');
        $anchor = $first ? Carbon::parse($first) : Carbon::today();

        $openingDate = Setting::get('opening_balance_date');

        if ($openingDate && Carbon::parse($openingDate)->lt($anchor)) {
            $anchor = Carbon::parse($openingDate);
        }

        $cursor = BudgetPeriod::containing($anchor);
        $last = BudgetPeriod::current();

        // Douze cycles d'avance, plus la periode consultee si elle sort de
        // cette fenetre — sinon le selecteur afficherait une valeur absente
        // de sa propre liste.
        for ($i = 0; $i < 12; $i++) {
            $last = $last->next();
        }

        $periods = [];

        while ($cursor->key <= $last->key) {
            $periods[] = $cursor->toArray();
            $cursor = $cursor->next();
        }

        if (! collect($periods)->contains(fn (array $period) => $period['key'] === $selected->key)) {
            $periods[] = $selected->toArray();
            usort($periods, fn (array $a, array $b) => strcmp($a['key'], $b['key']));
        }

        return $periods;
    }
}

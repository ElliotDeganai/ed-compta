<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecurringItemRequest;
use App\Models\Category;
use App\Models\RecurringItem;
use App\Models\Transaction;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RecurringItemController extends Controller
{
    public function __construct(private BudgetService $budget)
    {
    }

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $month = Carbon::today()->format('Y-m');

        $items = RecurringItem::query()
            ->where('user_id', $userId)
            ->with('category:id,name,icon,color')
            ->orderBy('type')
            ->orderBy('day_of_month')
            ->get();

        $settledIds = Transaction::query()
            ->where('user_id', $userId)
            ->forMonth($month)
            ->recurring()
            ->whereDate('occurred_on', '<=', Carbon::today()->toDateString())
            ->pluck('recurring_item_id')
            ->all();

        return Inertia::render('Recurring/Index', [
            'items' => $items,
            'settledIds' => $settledIds,
            'categories' => Category::orderBy('position')->orderBy('name')->get(['id', 'name', 'type', 'icon', 'color']),
            'summary' => $this->budget->summary($userId, $month),
        ]);
    }

    public function store(RecurringItemRequest $request): RedirectResponse
    {
        RecurringItem::create($request->validated() + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Ligne recurrente ajoutee.');
    }

    /**
     * La modification ne touche que les occurrences futures : les transactions
     * deja materialisees gardent le montant qui a reellement ete preleve.
     */
    public function update(RecurringItemRequest $request, RecurringItem $recurring): RedirectResponse
    {
        abort_unless($recurring->user_id === $request->user()->id, 403);

        $recurring->update($request->validated());

        $recurring->transactions()
            ->whereDate('occurred_on', '>', Carbon::today()->toDateString())
            ->delete();

        return back()->with('success', 'Ligne recurrente mise a jour.');
    }

    public function destroy(Request $request, RecurringItem $recurring): RedirectResponse
    {
        abort_unless($recurring->user_id === $request->user()->id, 403);
        abort_unless($request->user()->can('manage recurring'), 403);

        $recurring->transactions()
            ->whereDate('occurred_on', '>', Carbon::today()->toDateString())
            ->delete();

        $recurring->delete();

        return back()->with('success', 'Ligne recurrente supprimee.');
    }
}

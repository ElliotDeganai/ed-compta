<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulationItemRequest;
use App\Models\Category;
use App\Models\SimulationItem;
use App\Models\Transaction;
use App\Services\SimulationService;
use App\Support\BudgetPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SimulationController extends Controller
{
    public function __construct(private SimulationService $simulation)
    {
    }

    public function index(Request $request): Response
    {
        $period = $this->resolvePeriod($request);
        $userId = $request->user()->id;

        return Inertia::render('Simulation/Index', array_merge(
            $this->simulation->compare($userId, $period),
            [
                'categories' => Category::orderBy('position')->orderBy('name')->get(['id', 'name', 'type', 'icon', 'color']),
                'today' => Carbon::today()->toDateString(),
                'month' => $period->key,
                'period' => $period->toArray(),
            ]
        ));
    }

    public function store(SimulationItemRequest $request): RedirectResponse
    {
        SimulationItem::create($request->validated() + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Hypothèse ajoutée.');
    }

    public function update(SimulationItemRequest $request, SimulationItem $simulation): RedirectResponse
    {
        abort_unless($simulation->user_id === $request->user()->id, 403);

        $simulation->update($request->validated());

        return back()->with('success', 'Hypothèse mise à jour.');
    }

    public function toggle(Request $request, SimulationItem $simulation): RedirectResponse
    {
        abort_unless($simulation->user_id === $request->user()->id, 403);

        $simulation->update(['is_enabled' => ! $simulation->is_enabled]);

        return back();
    }

    public function destroy(Request $request, SimulationItem $simulation): RedirectResponse
    {
        abort_unless($simulation->user_id === $request->user()->id, 403);

        $simulation->delete();

        return back()->with('success', 'Hypothèse supprimée.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $period = $this->resolvePeriod($request);

        SimulationItem::where('user_id', $request->user()->id)->forPeriod($period)->delete();

        return back()->with('success', 'Simulation vidée.');
    }

    public function convert(Request $request): RedirectResponse
    {
        $period = $this->resolvePeriod($request);
        $userId = $request->user()->id;

        $items = SimulationItem::where('user_id', $userId)->forPeriod($period)->enabled()->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Aucune hypothèse active à convertir.');
        }

        foreach ($items as $item) {
            Transaction::create([
                'user_id' => $userId,
                'category_id' => $item->category_id,
                'name' => $item->name,
                'description' => $item->description,
                'amount' => $item->amount,
                'type' => $item->type,
                'occurred_on' => $item->occurred_on->toDateString(),
            ]);

            $item->delete();
        }

        return back()->with('success', $items->count().' hypothèse(s) converties en mouvements réels.');
    }

    private function resolvePeriod(Request $request): BudgetPeriod
    {
        $key = (string) $request->input('month', '');

        return preg_match('/^\d{4}-\d{2}$/', $key) === 1
            ? BudgetPeriod::fromKey($key)
            : BudgetPeriod::current();
    }
}

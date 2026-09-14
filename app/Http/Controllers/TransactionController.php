<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(private DocumentService $documents)
    {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'category_id', 'month']);

        $transactions = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with(['category:id,name,icon,color', 'documents'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->forMonth($month))
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories' => Category::orderBy('position')->orderBy('name')->get(['id', 'name', 'type', 'icon', 'color']),
            'filters' => $filters,
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $transaction = Transaction::create(
            $request->safe()->except('documents') + ['user_id' => $request->user()->id]
        );

        if ($request->hasFile('documents')) {
            $this->documents->attachMany($transaction, $request->file('documents'), $request->user()->id);
        }

        return back()->with('success', 'Mouvement enregistre.');
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $transaction->update($request->safe()->except('documents'));

        if ($request->hasFile('documents')) {
            $this->documents->attachMany($transaction, $request->file('documents'), $request->user()->id);
        }

        return back()->with('success', 'Mouvement mis a jour.');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);
        abort_unless($request->user()->can('manage transactions'), 403);

        $transaction->documents->each->delete();
        $transaction->delete();

        return back()->with('success', 'Mouvement supprime.');
    }
}

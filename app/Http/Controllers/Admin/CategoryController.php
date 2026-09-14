<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories', [
            'categories' => Category::withCount('transactions')
                ->orderBy('position')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return back()->with('success', 'Categorie creee.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('success', 'Categorie mise a jour.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->transactions()->exists() || $category->recurringItems()->exists()) {
            return back()->with('error', 'Cette categorie est utilisee, detachez-la d\'abord.');
        }

        $category->delete();

        return back()->with('success', 'Categorie supprimee.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'icon' => ['required', 'string', 'max:60'],
            'color' => ['required', 'string', 'max:20'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}

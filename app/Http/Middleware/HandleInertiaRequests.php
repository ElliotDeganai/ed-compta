<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\Setting;
use App\Services\BrandingService;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'initials' => $this->initials($user->name),
                ] : null,
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'roles' => $user ? $user->getRoleNames() : [],
            ],
            'app' => [
                'name' => Setting::get('site_name', 'Compte perso'),
                'tagline' => Setting::get('site_tagline', 'Savoir combien vous pouvez dépenser cette semaine.'),
                'currency' => Setting::get('currency', 'CHF'),
                'low_balance_threshold' => (float) Setting::get('low_balance_threshold', 300),
            ],
            'branding' => fn () => app(BrandingService::class)->all(),
            'footerPages' => fn () => Page::published()
                ->orderBy('position')
                ->orderBy('id')
                ->get(['slug', 'title'])
                ->toArray(),
            'balance' => fn () => $user ? app(BudgetService::class)->balance($user->id) : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials ?: 'U';
    }
}

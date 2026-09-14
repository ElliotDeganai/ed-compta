<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BrandingService;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const KEYS = [
        'site_name',
        'site_tagline',
        'currency',
        'opening_balance',
        'opening_balance_date',
        'low_balance_threshold',
    ];

    public function __construct(private BrandingService $branding)
    {
    }

    public function edit(Request $request, BudgetService $budget): Response
    {
        $settings = [];

        foreach (self::KEYS as $key) {
            $settings[$key] = Setting::get($key);
        }

        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
            'branding' => $this->branding->all(),
            'computedBalance' => $budget->balance($request->user()->id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'site_tagline' => ['required', 'string', 'max:160'],
            'currency' => ['required', 'string', 'max:6'],
            'opening_balance' => ['required', 'numeric'],
            'opening_balance_date' => ['required', 'date'],
            'low_balance_threshold' => ['required', 'numeric', 'min:0'],

            // Le logo accepte le SVG : c'est un fichier de marque, televerse
            // par un administrateur, et il doit rester net a toute taille.
            'logo' => ['nullable', 'file', 'max:1024', 'mimes:svg,png,jpg,jpeg,webp'],
            'favicon' => ['nullable', 'file', 'max:512', 'mimes:svg,png,ico'],
            'og_image' => ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg'],
        ]);

        foreach (self::KEYS as $key) {
            Setting::put($key, $data[$key]);
        }

        $this->storeAsset($request->file('logo'), 'logo_path');
        $this->storeAsset($request->file('favicon'), 'favicon_path');
        $this->storeAsset($request->file('og_image'), 'og_image_path');

        return back()->with('success', 'Paramètres enregistrés.');
    }

    /**
     * Remet le visuel concerne sur son fichier de repli et supprime le
     * televersement precedent.
     */
    public function resetAsset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'in:logo_path,favicon_path,og_image_path'],
        ]);

        $this->forgetPrevious($data['key']);
        Setting::put($data['key'], null);

        return back()->with('success', 'Visuel réinitialisé.');
    }

    private function storeAsset(?UploadedFile $file, string $key): void
    {
        if (! $file) {
            return;
        }

        $this->forgetPrevious($key);

        $path = $file->store(BrandingService::DIRECTORY, BrandingService::DISK);

        Setting::put($key, $path);
    }

    /**
     * Sans cette suppression, chaque changement de logo laisserait un fichier
     * orphelin dans storage/app/public/branding.
     */
    private function forgetPrevious(string $key): void
    {
        $previous = Setting::get($key);

        if (filled($previous) && Storage::disk(BrandingService::DISK)->exists($previous)) {
            Storage::disk(BrandingService::DISK)->delete($previous);
        }
    }
}

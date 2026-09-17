<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BrandingService;
use App\Services\BudgetService;
use App\Support\BudgetPeriod;
use App\Support\BusinessDay;
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
        'cycle_start_day',
        'cycle_shift_to_business_day',
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

        $settings['business_days'] = BusinessDay::days();
        $settings['holidays_fixed'] = BusinessDay::fixedHolidays();
        $settings['holidays_movable'] = BusinessDay::movableHolidays();

        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
            'currentPeriod' => BudgetPeriod::current()->toArray(),
            'upcomingPeriods' => $this->upcomingPeriods(),
            'weekdayOptions' => BusinessDay::WEEKDAYS,
            'movableOptions' => collect(BusinessDay::MOVABLE)
                ->map(fn (array $entry, string $key) => ['key' => $key, 'label' => $entry['label']])
                ->values()
                ->all(),
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

            // Plafonne a 28 : un cycle demarrant le 30 n'existerait pas en
            // fevrier et deviendrait irregulier d'un mois sur l'autre.
            'cycle_start_day' => ['required', 'integer', 'min:1', 'max:'.BudgetPeriod::MAX_START_DAY],

            // Decale l'ouverture du cycle au jour ouvre suivant quand la date
            // theorique tombe un samedi, un dimanche, un lundi ou un ferie.
            'cycle_shift_to_business_day' => ['boolean'],

            'business_days' => ['required', 'array', 'min:1'],
            'business_days.*' => ['integer', 'min:1', 'max:7'],

            'holidays_fixed' => ['present', 'array', 'max:40'],
            'holidays_fixed.*.label' => ['required', 'string', 'max:60'],
            'holidays_fixed.*.date' => ['required', 'string', 'regex:/^\\d{2}-\\d{2}$/'],

            'holidays_movable' => ['present', 'array'],
            'holidays_movable.*' => ['string', 'in:'.implode(',', array_keys(BusinessDay::MOVABLE))],
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

        // Stockes en JSON : la colonne value est du texte, et ces trois
        // reglages sont des listes.
        Setting::put('business_days', json_encode(array_values(array_unique(array_map('intval', $data['business_days'])))));
        Setting::put('holidays_fixed', json_encode(array_values($data['holidays_fixed'])));
        Setting::put('holidays_movable', json_encode(array_values($data['holidays_movable'])));

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

    /**
     * Les six prochains cycles, pour montrer l'effet du parametrage sans
     * attendre le mois suivant. Un reglage de dates ne se verifie qu'en
     * regardant les dates qu'il produit.
     *
     * @return array<int, array<string, mixed>>
     */
    private function upcomingPeriods(): array
    {
        $period = BudgetPeriod::current();
        $periods = [];

        for ($i = 0; $i < 6; $i++) {
            $periods[] = $period->toArray() + [
                'weekday' => $period->start->locale('fr')->isoFormat('ddd'),
            ];

            $period = $period->next();
        }

        return $periods;
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

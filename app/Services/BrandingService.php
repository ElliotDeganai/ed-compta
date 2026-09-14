<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Resout les visuels de marque : logo, favicon et image de partage.
 *
 * Chaque visuel a un fichier de repli livre dans public/. Tant que rien n'a ete
 * televerse depuis l'administration, c'est ce fichier qui est servi — le site
 * n'affiche donc jamais d'image cassee.
 */
class BrandingService
{
    public const DISK = 'public';

    public const DIRECTORY = 'branding';

    /**
     * Cle de reglage => chemin de repli dans public/.
     */
    public const ASSETS = [
        'logo_path' => '/images/ed-web-factory.svg',
        'favicon_path' => '/favicon.svg',
        'og_image_path' => '/images/og-default.png',
    ];

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $branding = [];

        foreach (array_keys(self::ASSETS) as $key) {
            $branding[str_replace('_path', '', $key)] = $this->url($key);
        }

        return $branding;
    }

    public function url(string $key): string
    {
        $stored = Setting::get($key);

        if (filled($stored) && Storage::disk(self::DISK)->exists($stored)) {
            // asset() plutot que Storage::url() pour respecter APP_URL et le
            // sous-domaine, et garder l'URL absolue attendue par Open Graph.
            return asset('storage/'.ltrim($stored, '/'));
        }

        return asset(ltrim(self::ASSETS[$key] ?? '', '/'));
    }
}

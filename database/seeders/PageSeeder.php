<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => Page::MENTIONS,
                'title' => 'Mentions légales',
                'meta_description' => 'Éditeur, hébergeur et conditions d\'utilisation du site.',
                'position' => 1,
            ],
            [
                'slug' => Page::CONFIDENTIALITE,
                'title' => 'Politique de confidentialité',
                'meta_description' => 'Données collectées, durée de conservation et droits d\'accès.',
                'position' => 2,
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(
                ['slug' => $page['slug']],
                $page + ['content' => '<p>Contenu à rédiger depuis la zone d\'administration.</p>']
            );
        }
    }
}

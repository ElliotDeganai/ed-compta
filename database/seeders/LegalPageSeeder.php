<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        LegalPage::firstOrCreate(
            ['slug' => LegalPage::MENTIONS],
            [
                'title' => 'Mentions légales',
                'content' => '<p>Contenu à compléter depuis la zone d\'administration.</p>',
            ]
        );

        LegalPage::firstOrCreate(
            ['slug' => LegalPage::CONFIDENTIALITE],
            [
                'title' => 'Politique de confidentialité',
                'content' => '<p>Contenu à compléter depuis la zone d\'administration.</p>',
            ]
        );
    }
}

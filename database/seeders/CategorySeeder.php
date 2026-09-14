<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salaire', 'type' => 'income', 'icon' => 'briefcase', 'color' => '#0f766e'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => 'code', 'color' => '#0891b2'],
            ['name' => 'Logement', 'type' => 'expense', 'icon' => 'home', 'color' => '#0ea5e9'],
            ['name' => 'Assurances', 'type' => 'expense', 'icon' => 'shield', 'color' => '#6366f1'],
            ['name' => 'Telecom', 'type' => 'expense', 'icon' => 'device-mobile', 'color' => '#8b5cf6'],
            ['name' => 'Vehicule', 'type' => 'expense', 'icon' => 'car', 'color' => '#f59e0b'],
            ['name' => 'Alimentation', 'type' => 'expense', 'icon' => 'shopping-cart', 'color' => '#84cc16'],
            ['name' => 'Loisirs', 'type' => 'expense', 'icon' => 'sparkles', 'color' => '#ec4899'],
            ['name' => 'Enfant', 'type' => 'expense', 'icon' => 'heart', 'color' => '#f43f5e'],
            ['name' => 'Divers', 'type' => 'expense', 'icon' => 'tag', 'color' => '#64748b'],
        ];

        foreach ($categories as $index => $category) {
            Category::updateOrCreate(
                ['slug' => str($category['name'])->slug()->value()],
                $category + ['position' => $index]
            );
        }
    }
}

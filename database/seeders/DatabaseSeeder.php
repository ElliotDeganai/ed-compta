<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            CategorySeeder::class,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'elliot@compte-perso.test'],
            [
                'name' => 'Elliot',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['admin']);

        Setting::put('site_name', 'Compte perso');
        Setting::put('currency', 'CHF');
        Setting::put('opening_balance', '0');
        Setting::put('opening_balance_date', now()->startOfMonth()->toDateString());
        Setting::put('low_balance_threshold', '300');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProductSourceSeeder::class,
            CategorySeeder::class,
            MultiMarketProductSeeder::class,
        ]);

        if (User::where('email', 'admin@ai-commerce.local')->doesntExist()) {
            User::factory()->admin()->create([
                'name' => 'Demo Researcher',
                'email' => 'admin@ai-commerce.local',
            ]);
        }
    }
}

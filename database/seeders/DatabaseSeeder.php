<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\System\RolesAndPermissionsSeeder;
use Database\Seeders\System\CollectionPermissionsSeeder;
use Database\Seeders\System\SuperAdminSeeder;
use Database\Seeders\BlogSpaceSeeder;
use Database\Seeders\ProductSpaceSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(\Database\Seeders\System\RolesAndPermissionsSeeder::class);
        $this->call(\Database\Seeders\System\CollectionPermissionsSeeder::class);
        $this->call(\Database\Seeders\System\SuperAdminSeeder::class);
        
        // Seed spaces with collections and entries
        $this->call(\Database\Seeders\BlogSpaceSeeder::class);
        $this->call(\Database\Seeders\ProductSpaceSeeder::class);

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}

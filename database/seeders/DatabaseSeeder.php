<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed users for each role
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'user_type' => 'admin',
            'username' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Charity User',
            'email' => 'charity@example.com',
            'user_type' => 'charity',
            'username' => 'charity',
        ]);
        User::factory()->create([
            'name' => 'Insurance User',
            'email' => 'insurance@example.com',
            'user_type' => 'insurance',
            'username' => 'insurance',
        ]);

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AssignRolePermissionSeeder::class,
        ]);
    }
} 
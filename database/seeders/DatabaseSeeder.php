<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Administrateur MIACI',
            'email' => 'admin@miaci.ci',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole(Role::Admin->value);

        $gestionnaire = User::factory()->create([
            'name' => 'Gestionnaire MIACI',
            'email' => 'gestionnaire@miaci.ci',
            'password' => Hash::make('password'),
        ]);
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->call(DemoDataSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use App\Enums\Permission as AppPermission;
use App\Enums\Role as AppRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed les rôles et permissions de l'application.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AppPermission::values() as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate(AppRole::Admin->value);
        $admin->syncPermissions(AppPermission::values());

        $gestionnaire = Role::findOrCreate(AppRole::Gestionnaire->value);
        $gestionnaire->syncPermissions([
            AppPermission::GererAdherents->value,
            AppPermission::GererCotisations->value,
            AppPermission::GererSinistres->value,
            AppPermission::ExporterDonnees->value,
        ]);

        Role::findOrCreate(AppRole::Adherent->value);
    }
}

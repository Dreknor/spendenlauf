<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSION = 'system update';

    public function up(): void
    {
        Permission::findOrCreate(self::PERMISSION, 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Administratoren (Rechteverwaltung) dürfen Updates einspielen
        foreach (User::permission('edit permission')->get() as $admin) {
            $admin->givePermissionTo(self::PERMISSION);
        }
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

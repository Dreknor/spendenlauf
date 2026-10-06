<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'edit event',
        'manage abrechnung',
        'schnellerfassung',
        'edit runden',
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Administratoren (Rechteverwaltung) erhalten die neuen Rechte
        $admins = User::permission('edit permission')->get();
        foreach ($admins as $admin) {
            $admin->givePermissionTo(self::PERMISSIONS);
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

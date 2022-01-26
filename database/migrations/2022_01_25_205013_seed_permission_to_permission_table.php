<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class SeedPermissionToPermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Permission::insert([
            ['id' => '1',
                'name' => 'edit user',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '3',
                'name' => 'edit permission',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '5',
                'name' => 'edit projekt',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '6',
                'name' => 'edit laeufer',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '7',
                'name' => 'edit teams',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '8',
                'name' => 'edit sponsoren',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '9',
                'name' => 'edit sponsorings',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '10',
                'name' => 'import export',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '11',
                'name' => 'send mail',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL],
            ['id' => '12',
                'name' => 'show auswertung',
                'guard_name' => 'web',
                'created_at' => NULL,
                'updated_at' => NULL]
        ]);


        \App\Model\User::query()->first()->givePermissionTo(Permission::all());
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permission', function (Blueprint $table) {
            //
        });
    }
}

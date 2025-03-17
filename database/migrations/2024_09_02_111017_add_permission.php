<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Spatie\Permission\Models\Permission::create([
            'name' => 'edit startnummer',
            'guard_name' => 'web',
        ]);

        \Illuminate\Support\Facades\Cache::clear();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

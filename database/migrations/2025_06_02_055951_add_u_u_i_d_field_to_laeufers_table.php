<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laeufers', function (Blueprint $table) {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
                $table->uuid('uuid')->after('id')->default(DB::raw('UUID()'))->unique();
            } else {
                $table->uuid('uuid')->nullable()->unique();
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laeufers', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};

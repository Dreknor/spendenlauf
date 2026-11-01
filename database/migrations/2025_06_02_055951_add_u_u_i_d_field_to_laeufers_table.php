<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kein "DEFAULT UUID()": das versteht nur MariaDB, MySQL 8 bricht mit einem Syntaxfehler ab.
     * Neue Läufer bekommen ihre UUID im Model, bestehende werden hier nachgetragen.
     */
    public function up(): void
    {
        Schema::table('laeufers', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        foreach (DB::table('laeufers')->whereNull('uuid')->pluck('id') as $id) {
            DB::table('laeufers')->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
        }
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

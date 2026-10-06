<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Am Lauftag werden Sponsoren oft ohne vollständige Anschrift erfasst. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('strasse')->nullable()->change();
            $table->string('plz')->nullable()->change();
            $table->string('ort')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Nicht umkehrbar ohne Datenverlust (Sponsoren ohne Anschrift)
    }
};

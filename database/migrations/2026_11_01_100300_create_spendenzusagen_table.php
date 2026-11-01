<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zusagen über den öffentlichen Sponsor-Link warten hier auf die Bestätigung per E-Mail (Double-Opt-in).
     * Erst danach entstehen Sponsor und Spende; unbestätigte Angaben werden nach einigen Tagen gelöscht.
     */
    public function up(): void
    {
        Schema::create('spendenzusagen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('laeufer_id')->constrained('laeufers')->cascadeOnDelete();
            // Anschrift und Beträge, verschlüsselt
            $table->text('daten');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spendenzusagen');
    }
};

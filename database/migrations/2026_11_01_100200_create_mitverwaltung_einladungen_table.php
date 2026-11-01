<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eltern können weitere Konten (z. B. den anderen Elternteil) nur einladen – die eingeladene Person
     * muss zustimmen. Die Einladung hängt an der E-Mail-Adresse, damit sie auch vor der Registrierung gilt.
     */
    public function up(): void
    {
        Schema::create('mitverwaltung_einladungen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laeufer_id')->constrained('laeufers')->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('eingeladen_von')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('offen');
            $table->timestamp('entschieden_am')->nullable();
            $table->timestamps();
            $table->index(['email', 'status']);
            $table->index(['laeufer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mitverwaltung_einladungen');
    }
};

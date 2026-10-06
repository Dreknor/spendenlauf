<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Weitere Benutzer (z. B. zweiter Elternteil), die einen Läufer mitverwalten
        Schema::create('laeufer_user', function (Blueprint $table) {
            $table->foreignId('laeufer_id')->constrained('laeufers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['laeufer_id', 'user_id']);
        });

        Schema::create('mitverwaltung_anfragen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laeufer_id')->constrained('laeufers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('offen');
            $table->foreignId('entschieden_von')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entschieden_am')->nullable();
            $table->timestamps();
            $table->index(['laeufer_id', 'status']);
        });

        // Paare, die das Orga-Team als „keine Dublette“ markiert hat
        Schema::create('dubletten_ignoriert', function (Blueprint $table) {
            $table->id();
            $table->string('typ', 20);
            $table->unsignedBigInteger('id_a');
            $table->unsignedBigInteger('id_b');
            $table->timestamps();
            $table->unique(['typ', 'id_a', 'id_b']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dubletten_ignoriert');
        Schema::dropIfExists('mitverwaltung_anfragen');
        Schema::dropIfExists('laeufer_user');
    }
};

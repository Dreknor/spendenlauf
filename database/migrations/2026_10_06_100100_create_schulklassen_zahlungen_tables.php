<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schulklassen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name', 50);
            $table->unsignedTinyInteger('stufe')->nullable();
            $table->string('klassenleitung')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });

        Schema::create('event_projects', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('projects_id')->constrained('projects')->cascadeOnDelete();
            $table->primary(['event_id', 'projects_id']);
        });

        Schema::create('zahlungen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->restrictOnDelete();
            $table->foreignId('sponsor_id')->constrained('sponsors')->restrictOnDelete();
            $table->decimal('betrag', 10, 2);
            $table->date('bezahlt_am');
            $table->string('art', 20);
            $table->string('notiz')->nullable();
            $table->foreignId('erfasst_von')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sponsor_mails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained('sponsors')->cascadeOnDelete();
            $table->string('typ', 20);
            $table->decimal('betrag', 10, 2)->nullable();
            $table->timestamp('gesendet_am');
            $table->timestamps();

            $table->index(['event_id', 'sponsor_id', 'typ']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_mails');
        Schema::dropIfExists('zahlungen');
        Schema::dropIfExists('event_projects');
        Schema::dropIfExists('schulklassen');
    }
};

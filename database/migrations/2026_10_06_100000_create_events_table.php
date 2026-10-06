<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('nummer')->nullable();
            $table->date('datum');
            $table->date('anmeldeschluss')->nullable();
            $table->boolean('aktiv')->default(false);

            // Lauf
            $table->unsignedSmallInteger('runden_laenge_m')->default(400);
            $table->unsignedSmallInteger('dauer_minuten')->default(120);
            $table->json('altersgruppen')->nullable();

            // Verteilung (jeweils vom Bruttobetrag)
            $table->decimal('soziale_abgabe_prozent', 5, 2)->default(0);
            $table->string('soziale_abgabe_empfaenger')->nullable();
            $table->text('soziale_abgabe_beschreibung')->nullable();
            $table->decimal('klassenkasse_prozent', 5, 2)->default(10);

            // Zahlung
            $table->string('kontoinhaber')->nullable();
            $table->string('iban', 40)->nullable();
            $table->string('bic', 20)->nullable();
            $table->string('bank')->nullable();
            $table->string('paypal_me_name')->nullable();
            $table->string('verwendungszweck_prefix')->default('Spendenlauf');
            $table->unsignedSmallInteger('zahlungsziel_tage')->default(14);

            // Verein (für vereinfachten Spendennachweis)
            $table->string('verein_name')->nullable();
            $table->string('verein_anschrift')->nullable();
            $table->string('verein_steuernummer')->nullable();
            $table->string('verein_finanzamt')->nullable();
            $table->date('verein_freistellung_datum')->nullable();
            $table->string('verein_zweck')->nullable();

            // Kommunikation
            $table->string('mail_absender_name')->nullable();
            $table->string('mail_absender_adresse')->nullable();
            $table->string('info_url')->nullable();

            // Zeitmessung
            $table->text('import_url')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

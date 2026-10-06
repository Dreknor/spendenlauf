<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Macht die bestehenden Tabellen mehrjahresfähig und übernimmt vorhandene Daten
 * in eine Veranstaltung „7. Radebeuler Spendenlauf“.
 */
return new class extends Migration
{
    private const MORPH_MAP = [
        'Laeufer' => 'laeufer',
        'Teams' => 'team',
        'Projects' => 'projekt',
        'User' => 'user',
    ];

    public function up(): void
    {
        Schema::table('laeufers', function (Blueprint $table) {
            $table->dropUnique('laeufers_startnummer_unique');
        });

        Schema::table('laeufers', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->restrictOnDelete();
            $table->string('kategorie', 20)->nullable()->after('geschlecht');
            $table->foreignId('schulklasse_id')->nullable()->after('kategorie')->constrained('schulklassen')->nullOnDelete();
            $table->integer('startnummer')->nullable()->change();
            $table->boolean('runden_manuell')->default(false)->after('runden');
            $table->timestamp('runden_aktualisiert_am')->nullable()->after('runden_manuell');
            $table->unique(['event_id', 'startnummer']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->restrictOnDelete();
        });

        Schema::table('sponsorings', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->restrictOnDelete();
        });

        Schema::table('sponsorings', function (Blueprint $table) {
            $table->dropForeign(['verwaltet_von']);
        });

        Schema::table('sponsorings', function (Blueprint $table) {
            $table->unsignedBigInteger('verwaltet_von')->nullable()->change();
            $table->foreign('verwaltet_von')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('startnummern', function (Blueprint $table) {
            $table->dropUnique('startnummern_startnummer_unique');
        });

        Schema::table('startnummern', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->cascadeOnDelete();
            $table->unique(['event_id', 'startnummer']);
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        $this->datenUebernehmen();

        // Team-Zuordnungen auf nicht mehr existierende Teams bereinigen, dann FK setzen
        DB::table('laeufers')
            ->whereNotNull('team_id')
            ->whereNotIn('team_id', DB::table('teams')->select('id'))
            ->update(['team_id' => null]);

        Schema::table('laeufers', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn('mail_send');
        });
    }

    private function datenUebernehmen(): void
    {
        foreach (DB::table('sponsors')->whereNull('uuid')->pluck('id') as $id) {
            DB::table('sponsors')->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
        }

        foreach (DB::table('laeufers')->whereNull('uuid')->pluck('id') as $id) {
            DB::table('laeufers')->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
        }

        foreach (self::MORPH_MAP as $klasse => $alias) {
            foreach (["App\\Model\\$klasse", "App\\Models\\$klasse"] as $alt) {
                DB::table('sponsorings')->where('sponsorable_type', $alt)->update(['sponsorable_type' => $alias]);
                DB::table('media')->where('model_type', $alt)->update(['model_type' => $alias]);
                DB::table('model_has_permissions')->where('model_type', $alt)->update(['model_type' => $alias]);
                DB::table('model_has_roles')->where('model_type', $alt)->update(['model_type' => $alias]);
            }
        }

        // maxBetrag = 0 bedeutete „kein Maximum“ (Default des alten Models)
        DB::table('sponsorings')->where('maxBetrag', 0)->update(['maxBetrag' => null]);

        $hatDaten = DB::table('laeufers')->exists()
            || DB::table('sponsorings')->exists()
            || DB::table('teams')->exists()
            || DB::table('projects')->exists();

        if (! $hatDaten) {
            return;
        }

        $datum = env('DATE') ?: '2025-06-25';
        $abgabeName = env('help_project_name') ?: 'Sozialdienst katholischer Frauen Radebeul';

        $eventId = DB::table('events')->insertGetId([
            'name' => '7. Radebeuler Spendenlauf',
            'nummer' => 7,
            'datum' => $datum,
            'aktiv' => true,
            'runden_laenge_m' => 400,
            'dauer_minuten' => 120,
            'soziale_abgabe_prozent' => (float) (env('help_project_percent') ?: 10),
            'soziale_abgabe_empfaenger' => $abgabeName,
            // 2025 gab es noch keine Klassenkasse
            'klassenkasse_prozent' => 0,
            'kontoinhaber' => 'Freunde und Förderer des ESZ Radebeul e.V.',
            'iban' => 'DE40850550000500027960',
            'bic' => 'SOLADES1MEI',
            'bank' => 'Sparkasse Meißen',
            'verwendungszweck_prefix' => 'Spendenlauf',
            'zahlungsziel_tage' => 14,
            'verein_name' => 'Freunde und Förderer des ESZ Radebeul e.V.',
            'mail_absender_name' => 'Evangelisches Schulzentrum Radebeul',
            'mail_absender_adresse' => 'info@esz-radebeul.de',
            'info_url' => 'https://www.radebeuler-spendenlauf.de',
            'import_url' => env('IMPORT_URL'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['laeufers', 'teams', 'sponsorings', 'startnummern'] as $tabelle) {
            DB::table($tabelle)->whereNull('event_id')->update(['event_id' => $eventId]);
        }

        DB::table('event_projects')->insert(
            DB::table('projects')->pluck('id')->map(fn ($id) => ['event_id' => $eventId, 'projects_id' => $id])->all()
        );

        $versandt = DB::table('sponsors')->whereNotNull('mail_send')->get(['id', 'mail_send']);
        foreach ($versandt as $sponsor) {
            DB::table('sponsor_mails')->insert([
                'event_id' => $eventId,
                'sponsor_id' => $sponsor->id,
                'typ' => 'aufforderung',
                'gesendet_am' => $sponsor->mail_send,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->timestamp('mail_send')->nullable();
            $table->dropColumn('uuid');
        });

        Schema::table('laeufers', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropUnique(['event_id', 'startnummer']);
            $table->dropConstrainedForeignId('schulklasse_id');
            $table->dropConstrainedForeignId('event_id');
            $table->dropColumn(['kategorie', 'runden_manuell', 'runden_aktualisiert_am']);
            $table->unique('startnummer');
        });

        Schema::table('startnummern', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'startnummer']);
            $table->dropConstrainedForeignId('event_id');
            $table->unique('startnummer');
        });

        foreach (['teams', 'sponsorings'] as $tabelle) {
            Schema::table($tabelle, function (Blueprint $table) {
                $table->dropConstrainedForeignId('event_id');
            });
        }

        foreach (self::MORPH_MAP as $klasse => $alias) {
            DB::table('sponsorings')->where('sponsorable_type', $alias)->update(['sponsorable_type' => "App\\Models\\$klasse"]);
            DB::table('media')->where('model_type', $alias)->update(['model_type' => "App\\Models\\$klasse"]);
            DB::table('model_has_permissions')->where('model_type', $alias)->update(['model_type' => "App\\Models\\$klasse"]);
        }
    }
};

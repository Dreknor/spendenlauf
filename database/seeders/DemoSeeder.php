<?php

namespace Database\Seeders;

use App\Enums\Kategorie;
use App\Enums\ZahlungsArt;
use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Schulklasse;
use App\Models\Sponsor;
use App\Models\Sponsoring;
use App\Models\Teams;
use App\Models\User;
use App\Models\Zahlung;
use App\Services\SpendenRechner;
use App\Support\CurrentEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Demo-Daten für die lokale Entwicklung: ein vergangener Lauf mit Klassen, Teams, Sponsoren und Zahlungen.
 *
 * Demo-Admin: demo-admin@example.org / password
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Eine Transaktion statt mehrerer hundert Einzel-Commits (auf MySQL mit sync_binlog sonst > 1 Minute)
        DB::transaction(fn () => $this->anlegen());
    }

    private function anlegen(): void
    {
        $admin = User::firstOrCreate(['email' => 'demo-admin@example.org'], [
            'vorname' => 'Demo', 'nachname' => 'Admin', 'password' => 'password', 'email_verified_at' => now(),
        ]);
        $admin->givePermissionTo(Permission::pluck('name'));

        Event::query()->update(['aktiv' => false]);
        $event = Event::factory()->create([
            'name' => 'Demo-Spendenlauf',
            'datum' => now()->subDays(3)->toDateString(),
            'paypal_me_name' => 'DemoVerein',
            'verein_name' => 'Freunde und Förderer des ESZ Radebeul e.V.',
        ]);
        app(CurrentEvent::class)->set($event);

        $projekte = Projects::factory()->count(3)->create();
        $event->projects()->sync($projekte->pluck('id'));

        $klassen = collect(['1a', '1b', '2a', '3a', '4a', '5a', '5b', '6a', '7a', '8a', '9a', '10a'])
            ->map(fn ($name) => Schulklasse::factory()->create(['event_id' => $event->id, 'name' => $name, 'stufe' => (int) $name]));

        $eltern = User::factory()->count(25)->create();
        $teams = Teams::factory()->count(5)->sequence(fn ($s) => ['name' => ['Die Flitzer', 'Lehrerzimmer', 'Laufwunder', 'Team Hort', 'Familie Sonnenschein'][$s->index]])
            ->create(['event_id' => $event->id, 'verwaltet_von' => $admin->id]);

        $nummer = 1;
        $laeufer = collect();
        foreach ($eltern as $user) {
            foreach (range(1, fake()->numberBetween(1, 3)) as $i) {
                $kategorie = fake()->randomElement([Kategorie::Schueler, Kategorie::Schueler, Kategorie::Schueler, Kategorie::Eltern, Kategorie::Lehrer, Kategorie::Freunde, Kategorie::Extern]);
                $klasse = $kategorie === Kategorie::Schueler ? $klassen->random() : null;
                $laeufer->push(Laeufer::factory()->create([
                    'event_id' => $event->id,
                    'verwaltet_von' => $user->id,
                    'kategorie' => $kategorie,
                    'schulklasse_id' => $klasse?->id,
                    'geburtsdatum' => $klasse
                        ? now()->subYears(6 + $klasse->stufe)->subDays(fake()->numberBetween(0, 364))
                        : fake()->dateTimeBetween('-70 years', '-19 years'),
                    'startnummer' => $nummer++,
                    'runden' => fake()->numberBetween(3, 45),
                    'team_id' => fake()->boolean(25) ? $teams->random()->id : null,
                ]));
            }
        }

        $sponsoren = Sponsor::factory()->count(60)->create();
        foreach ($laeufer as $l) {
            foreach ($sponsoren->random(fake()->numberBetween(0, 3)) as $sponsor) {
                Sponsoring::factory()->fuer($l)->create([
                    'sponsor_id' => $sponsor->id,
                    'rundenBetrag' => fake()->randomElement([0, 0.5, 1, 1, 2]),
                    'festBetrag' => fake()->randomElement([0, 0, 5, 10, 20]) ?: 5,
                    'maxBetrag' => fake()->boolean(20) ? 30 : null,
                    'verwaltet_von' => $l->verwaltet_von,
                ]);
                $sponsor->users()->syncWithoutDetaching([$l->verwaltet_von]);
            }
        }
        foreach ($teams as $team) {
            Sponsoring::factory()->fuer($team)->create(['sponsor_id' => $sponsoren->random()->id, 'rundenBetrag' => 0.2, 'festBetrag' => 0]);
        }

        // Etwa die Hälfte hat bezahlt
        $bilanz = SpendenRechner::fuer($event)->berechne();
        foreach ($bilanz->sponsoren as $sponsorId => $werte) {
            if (fake()->boolean(50)) {
                Zahlung::factory()->create([
                    'event_id' => $event->id,
                    'sponsor_id' => $sponsorId,
                    'betrag' => $werte['soll'] / 100,
                    'art' => fake()->randomElement(ZahlungsArt::cases()),
                ]);
            }
        }

        SpendenRechner::vergessen();
        $event->update(['aktiv' => true]);
    }
}

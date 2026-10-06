<?php

use App\Models\Event;
use App\Models\Laeufer;
use App\Models\Projects;
use App\Models\Schulklasse;
use App\Models\Sponsoring;
use App\Models\Teams;
use App\Models\Zahlung;
use App\Services\SpendenRechner;

beforeEach(function () {
    $this->event = aktivesEvent(['soziale_abgabe_prozent' => 10, 'klassenkasse_prozent' => 10]);
    $this->projekte = Projects::factory()->count(2)->create();
    $this->event->projects()->sync($this->projekte->pluck('id'));
    $this->klasse = Schulklasse::factory()->create(['event_id' => $this->event->id, 'name' => '5a']);
});

it('verteilt eine Schüler-Spende auf Abgabe, Klassenkasse und Projekte (alles vom Brutto)', function () {
    $schueler = Laeufer::factory()->schueler($this->klasse)->runden(20)->create(['event_id' => $this->event->id]);
    Sponsoring::factory()->fuer($schueler)->create(['rundenBetrag' => 5, 'festBetrag' => 0]);

    $bilanz = SpendenRechner::fuer($this->event)->berechne();

    expect($bilanz->bruttoCent)->toBe(10000)
        ->and($bilanz->abgabeCent)->toBe(1000)
        ->and($bilanz->klassenkasseCent)->toBe(1000)
        ->and($bilanz->klassen[$this->klasse->id])->toBe(['brutto' => 10000, 'kasse' => 1000])
        ->and($bilanz->projekteCent)->toBe(8000)
        ->and(array_values($bilanz->projekte))->toBe([4000, 4000])
        ->and($bilanz->abgabeCent + $bilanz->klassenkasseCent + $bilanz->projekteCent)->toBe($bilanz->bruttoCent);
});

it('gibt Spenden von Nicht-Schülern keine Klassenkasse', function () {
    $mama = Laeufer::factory()->runden(10)->create(['event_id' => $this->event->id]);
    Sponsoring::factory()->fuer($mama)->create(['rundenBetrag' => 1]);

    $bilanz = SpendenRechner::fuer($this->event)->berechne();

    expect($bilanz->klassenkasseCent)->toBe(0)
        ->and($bilanz->abgabeCent)->toBe(100)
        ->and($bilanz->projekteCent)->toBe(900);
});

it('verteilt Team-Spenden nach Runden auf die Mitglieder und deren Klassen', function () {
    $team = Teams::factory()->create(['event_id' => $this->event->id]);
    $klasse2 = Schulklasse::factory()->create(['event_id' => $this->event->id, 'name' => '7b']);
    $a = Laeufer::factory()->schueler($this->klasse)->runden(30)->create(['event_id' => $this->event->id, 'team_id' => $team->id]);
    $b = Laeufer::factory()->schueler($klasse2)->runden(10)->create(['event_id' => $this->event->id, 'team_id' => $team->id]);
    $c = Laeufer::factory()->runden(0)->create(['event_id' => $this->event->id, 'team_id' => $team->id]);
    Sponsoring::factory()->fuer($team)->create(['rundenBetrag' => 1, 'festBetrag' => 0]);

    $bilanz = SpendenRechner::fuer($this->event)->berechne();

    // 40 Runden × 1 € = 40 €; A 30/40, B 10/40, C 0
    expect($bilanz->bruttoCent)->toBe(4000)
        ->and($bilanz->laeufer[$a->id]['teamanteil'])->toBe(3000)
        ->and($bilanz->laeufer[$b->id]['teamanteil'])->toBe(1000)
        ->and($bilanz->laeufer[$c->id]['teamanteil'])->toBe(0)
        ->and($bilanz->klassen[$this->klasse->id]['kasse'])->toBe(300)
        ->and($bilanz->klassen[$klasse2->id]['kasse'])->toBe(100)
        ->and($bilanz->teams[$team->id]['spende'])->toBe(4000);
});

it('verteilt bei fehlender Projektauswahl auf alle Projekte des Events', function () {
    $l = Laeufer::factory()->runden(1)->create(['event_id' => $this->event->id]);
    $s = Sponsoring::factory()->fuer($l)->create(['rundenBetrag' => 0, 'festBetrag' => 10]);
    $s->projects()->sync([$this->projekte->first()->id]);
    $l2 = Laeufer::factory()->create(['event_id' => $this->event->id]);
    Sponsoring::factory()->fuer($l2)->create(['rundenBetrag' => 0, 'festBetrag' => 10]);

    $bilanz = SpendenRechner::fuer($this->event)->berechne();

    // je 10 € brutto, 9 € nach Abgabe; erstes Projekt: 9 € + 4,50 €, zweites 4,50 €
    expect($bilanz->projekte[$this->projekte[0]->id])->toBe(1350)
        ->and($bilanz->projekte[$this->projekte[1]->id])->toBe(450);
});

it('berücksichtigt nur Daten der eigenen Veranstaltung', function () {
    $anderes = Event::factory()->create(['aktiv' => false]);
    $l = Laeufer::factory()->runden(10)->create(['event_id' => $anderes->id]);
    Sponsoring::factory()->fuer($l)->create(['rundenBetrag' => 1]);

    expect(SpendenRechner::fuer($this->event)->berechne()->bruttoCent)->toBe(0)
        ->and(SpendenRechner::fuer($anderes)->berechne()->bruttoCent)->toBe(1000);
});

it('summiert Zahlungen je Sponsor und berechnet offene Beträge', function () {
    $l = Laeufer::factory()->runden(10)->create(['event_id' => $this->event->id]);
    $s = Sponsoring::factory()->fuer($l)->create(['rundenBetrag' => 2]);
    Zahlung::factory()->create(['event_id' => $this->event->id, 'sponsor_id' => $s->sponsor_id, 'betrag' => 5]);

    $bilanz = SpendenRechner::fuer($this->event)->berechne();

    expect($bilanz->sponsorSoll($s->sponsor_id))->toBe(2000)
        ->and($bilanz->sponsorIst($s->sponsor_id))->toBe(500)
        ->and($bilanz->offenCent())->toBe(1500);
});

it('ignoriert Sponsorings gelöschter Läufer', function () {
    $l = Laeufer::factory()->runden(10)->create(['event_id' => $this->event->id]);
    Sponsoring::factory()->fuer($l)->create(['rundenBetrag' => 1]);
    $l->delete();

    expect(SpendenRechner::fuer($this->event)->berechne()->bruttoCent)->toBe(0);
});

it('kann die Bilanz in einem persistenten Cache ablegen und wieder lesen', function () {
    config(['cache.default' => 'file']);
    $l = Laeufer::factory()->runden(3)->create(['event_id' => $this->event->id]);
    Sponsoring::factory()->fuer($l)->create(['rundenBetrag' => 1]);
    SpendenRechner::vergessen();

    SpendenRechner::fuer($this->event)->bilanz();
    expect(SpendenRechner::fuer($this->event)->bilanz()->bruttoCent)->toBe(300);

    SpendenRechner::vergessen();
});

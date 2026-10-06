<?php

use App\Models\Laeufer;
use App\Models\Schulklasse;
use App\Models\Sponsoring;
use App\Services\Statistik;

beforeEach(function () {
    $this->event = aktivesEvent(['datum' => '2027-06-20']);
});

it('ordnet Läufer nach ihrem Alter am Lauftag den Altersgruppen zu', function () {
    Laeufer::factory()->create(['event_id' => $this->event->id, 'geburtsdatum' => '2017-06-20', 'nachname' => 'Zehn']);   // genau 10
    Laeufer::factory()->create(['event_id' => $this->event->id, 'geburtsdatum' => '2016-06-21', 'nachname' => 'NochZehn']); // 10, wird morgen 11
    Laeufer::factory()->create(['event_id' => $this->event->id, 'geburtsdatum' => '2016-06-20', 'nachname' => 'Elf']);
    Laeufer::factory()->create(['event_id' => $this->event->id, 'geburtsdatum' => null, 'nachname' => 'Ohne']);

    $zeilen = Statistik::fuer($this->event)->laeuferZeilen()->keyBy('nachname');

    expect($zeilen['Zehn']['altersgruppe'])->toBe('7–10 Jahre')
        ->and($zeilen['NochZehn']['altersgruppe'])->toBe('7–10 Jahre')
        ->and($zeilen['Elf']['altersgruppe'])->toBe('11–14 Jahre')
        ->and($zeilen['Ohne']['altersgruppe'])->toBe('unbekannt');
});

it('verwendet am Event eingestellte Altersgruppen', function () {
    $this->event->update(['altersgruppen' => [['von' => null, 'bis' => 9], ['von' => 10, 'bis' => null]]]);
    Laeufer::factory()->create(['event_id' => $this->event->id, 'geburtsdatum' => '2010-01-01']);

    expect(Statistik::fuer($this->event)->laeuferZeilen()->first()['altersgruppe'])->toBe('ab 10 Jahre');
});

it('liefert konsistente Summen über alle Dimensionen', function () {
    $klasse = Schulklasse::factory()->create(['event_id' => $this->event->id]);
    foreach (range(1, 12) as $i) {
        $l = Laeufer::factory()->runden($i * 3)->create([
            'event_id' => $this->event->id,
            'kategorie' => $i % 2 ? 'schueler' : 'eltern',
            'schulklasse_id' => $i % 2 ? $klasse->id : null,
        ]);
        Sponsoring::factory()->fuer($l)->create(['rundenBetrag' => 0.5, 'festBetrag' => 3]);
    }

    $s = Statistik::fuer($this->event);
    $gesamt = $s->uebersicht();

    foreach (array_keys(Statistik::DIMENSIONEN) as $dim) {
        $gruppen = $s->nachDimension($dim);
        expect($gruppen->sum('spende'))->toBe($gesamt['brutto'], "Spenden je $dim")
            ->and($gruppen->sum('runden'))->toBe($gesamt['runden'], "Runden je $dim")
            ->and($gruppen->sum('anzahl'))->toBe(12);
    }

    expect($s->klassen()->first()['schueler'])->toBe(6);
});

it('berechnet Ranglisten je Altersgruppe', function () {
    foreach ([10, 30, 20, 5] as $runden) {
        Laeufer::factory()->runden($runden)->create(['event_id' => $this->event->id, 'geburtsdatum' => '2018-01-01']);
    }

    $top = Statistik::fuer($this->event)->topJeAltersgruppe(3);

    expect($top)->toHaveCount(1)
        ->and($top->first()['zeilen']->pluck('runden')->all())->toBe([30, 20, 10]);
});

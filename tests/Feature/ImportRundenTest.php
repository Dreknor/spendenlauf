<?php

use App\Actions\ImportRunden;
use App\Models\Laeufer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->event = aktivesEvent(['import_url' => 'https://www.berlin-timing.de/Sponsorenlauf/Ergebnis']);
    $this->max = Laeufer::factory()->create(['event_id' => $this->event->id, 'startnummer' => 101]);
    $this->erika = Laeufer::factory()->create(['event_id' => $this->event->id, 'startnummer' => 102]);
    $this->moritz = Laeufer::factory()->create(['event_id' => $this->event->id, 'startnummer' => 103, 'runden' => 15, 'runden_manuell' => true]);
});

it('liest die Zeitmessungs-CSV mit Kopfzeile weiter unten', function () {
    $ergebnis = (new ImportRunden($this->event))->ausDatei(__DIR__.'/../fixtures/berlin-timing.csv');

    expect($this->max->fresh()->runden)->toBe(35)
        ->and($this->erika->fresh()->runden)->toBe(28)
        ->and($ergebnis['aktualisiert'])->toBe(2)
        ->and($ergebnis['unbekannt'])->toBe([999])
        ->and($ergebnis['uebersprungen'])->toBe([103]);
});

it('überschreibt manuelle Runden nur auf ausdrücklichen Wunsch', function () {
    (new ImportRunden($this->event))->ausDatei(__DIR__.'/../fixtures/berlin-timing.csv');
    expect($this->moritz->fresh()->runden)->toBe(15);

    (new ImportRunden($this->event, manuelleUeberschreiben: true))->ausDatei(__DIR__.'/../fixtures/berlin-timing.csv');
    expect($this->moritz->fresh())->runden->toBe(12)->runden_manuell->toBeFalse();
});

it('lädt die CSV über die Ergebnisseite der Zeitmessung', function () {
    Http::fake([
        'www.berlin-timing.de/Sponsorenlauf/Ergebnis' => Http::response('<a href="Liste/abc123.csv">CSV</a>'),
        'www.berlin-timing.de/Liste/abc123.csv' => Http::response(file_get_contents(__DIR__.'/../fixtures/berlin-timing.csv')),
    ]);

    $ergebnis = (new ImportRunden($this->event))->vonUrl();

    expect($ergebnis['aktualisiert'])->toBe(2)->and($this->max->fresh()->runden)->toBe(35);
});

it('importiert per Upload im Browser (Komma-getrennt)', function () {
    $datei = UploadedFile::fake()->createWithContent('runden.csv', "Startnummer,Runden\n101,20\n102,\n");

    $this->actingAs(admin())->post(route('import.datei'), ['datei' => $datei])->assertRedirect(route('import.create'));

    expect($this->max->fresh()->runden)->toBe(20)->and($this->erika->fresh()->runden)->toBe(0);
});

it('meldet fehlende Spalten verständlich', function () {
    $datei = UploadedFile::fake()->createWithContent('falsch.csv', "Name;Zeit\nMax;12:00\n");

    $this->actingAs(admin())->post(route('import.datei'), ['datei' => $datei])->assertSessionHas('type', 'danger');
});

it('importiert per Artisan-Befehl nur am Lauftag', function () {
    Http::fake();

    $this->artisan('spendenlauf:import-runden')->expectsOutputToContain('kein Lauftag')->assertSuccessful();
    Http::assertNothingSent();
});

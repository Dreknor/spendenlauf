# Radebeuler Spendenlauf

Verwaltung des Spendenlaufs des Evangelischen Schulzentrums Radebeul: Anmeldung von Läufern, Sponsoren und Spendenzusagen, Rundenerfassung am Lauftag, Abrechnung mit den Sponsoren sowie Statistiken und Klassenauswertung.

**Technik:** Laravel 13 (PHP ≥ 8.3), Blade + Alpine.js, Tailwind CSS 4 (Vite), Chart.js, MariaDB/MySQL, Laravel Fortify (Login/Registrierung), spatie/laravel-permission, DomPDF, Laravel Excel.

## Einrichtung (lokal)

```bash
composer install
npm install
cp .env.example .env   # Datenbank und Mail eintragen
php artisan key:generate
php artisan migrate
php artisan db:seed     # nur lokal: Demo-Lauf mit Testdaten, Login demo-admin@example.org / password
npm run build           # oder: npm run dev
php artisan test
```

Für den automatischen Runden-Import am Lauftag muss der Scheduler laufen (Cronjob):

```
* * * * * cd /pfad/zum/projekt && php artisan schedule:run >> /dev/null 2>&1
```

Deployment auf dem Server: `./deploy.sh` (Wartungsmodus, `git pull`, Composer, Frontend-Build, Migrationen, Caches).

## Ablauf eines Spendenlaufs

1. **Vorbereiten** – *Verwaltung → Veranstaltungen → Neuen Lauf vorbereiten*. Einstellungen werden vom letzten Lauf übernommen, Klassen werden auf Wunsch hochgestuft (5a → 7a bei zwei Jahren Abstand). Projekte auswählen, Bankdaten, PayPal.me-Name, Prozentsätze für soziale Abgabe und Klassenkasse prüfen und den Lauf **aktivieren**.
2. **Anmeldung** – Eltern registrieren sich, melden sich und ihre Kinder an (Kategorie, bei Schülern die Klasse). Läufer vom letzten Lauf können mit einem Klick übernommen werden. Jeder Läufer hat einen persönlichen **Sponsor-Link**, über den Sponsoren selbst Spenden zusagen.
3. **Lauftag** –
   - *Schnellerfassung*: Läufer, Startnummer, Sponsoren, Spenden und Barzahlungen in einem Schritt.
   - *Runden eingeben*: manuelle Eingabe/Korrektur per Startnummer.
   - *Runden importieren*: automatisch alle 5 Minuten von der Ergebnisseite der Zeitmessung (Import-URL am Event) oder per CSV/Excel-Upload. Manuell eingegebene Runden werden dabei nicht überschrieben.
   - *Startnummern*: Pool der vorgedruckten Nummern.
4. **Abrechnung** – Zahlungsaufforderungen per Mail (Aufstellung, Bankverbindung, Verwendungszweck mit Sponsor-Nr., PayPal.me-Link, Link zur persönlichen Sponsor-Seite), Zahlungseingänge erfassen, Erinnerungen an offene Posten, vereinfachter Spendennachweis (≤ 300 €) als PDF, Export der Spenden über 300 € für Zuwendungsbestätigungen.
5. **Auswertung** – Statistiken nach Läufern, Teams, Altersgruppen, Geschlecht, Kategorie, Klassen/Stufen, Sponsoren, Verteilung und Jahresvergleich; Excel- und PDF-Export; Klassenauswertung (PDF je Klasse für die Klassenleitungen); öffentliche Live-Statistik unter `/stats` (einbettbar, `/stats/embed` ohne Kopf/Fuß).

## Doppelte Einträge

- **Läufer:** Bei jeder Anmeldung (Eltern, Schnellerfassung, Übernahme aus dem Vorjahr) wird nach bereits angemeldeten Läufern gesucht – unabhängig von Groß-/Kleinschreibung, Umlaut-Schreibweise (ä/ae, ß/ss), Leerzeichen, Abkürzungen (Max/Maximilian) und kleinen Tippfehlern (`app/Services/Dubletten.php`). Gleicher Name und gleiches Geburtsdatum blockieren die Anmeldung (nur das Orga-Team kann übergehen), bei ähnlichen Namen kann bestätigt werden, dass es eine andere Person ist.
- **Mitverwaltung:** Ist ein Kind schon vom anderen Elternteil angemeldet, wird statt einer zweiten Anmeldung die Mitverwaltung angefragt. Der anmeldende Elternteil (oder das Orga-Team) bestätigt auf der Startseite; das Orga-Team kann weitere Konten auch direkt zuordnen.
- **Sponsoren** sind gemeinsame Datensätze: Gibt es die E-Mail-Adresse (oder Name + PLZ) schon, wird der vorhandene Sponsor verwendet; bei ähnlichen Namen wird nachgefragt. Eltern sehen bei gemeinsamen Sponsoren nur die Spenden für ihre eigenen Läufer.
- **Bereinigung:** *Verwaltung → Doppelte Einträge* listet mögliche Dubletten. Beim Zusammenführen werden Spenden, Zahlungen, Mails, Verwalter und fehlende Angaben übernommen; Paare können als „keine Dublette“ markiert werden.

## Berechnung

Alle Beträge werden zentral in `app/Services/SpendenRechner.php` berechnet (intern in Cent):

- Spende = Festbetrag + Runden × Betrag je Runde, gedeckelt durch den Höchstbetrag (falls angegeben). Bei Team-Spenden zählen die Runden aller Teammitglieder.
- **Soziale Abgabe** und **Klassenkasse** werden beide vom Bruttobetrag berechnet. Die Klassenkasse erhält den eingestellten Anteil der Spenden für Schüler-Läufer; Team-Spenden werden dafür nach Runden auf die Mitglieder verteilt.
- Der Rest wird gleichmäßig auf die gewählten Projekte verteilt (ohne Auswahl: alle Projekte des Laufs). Rundungsreste werden so verteilt, dass alle Summen centgenau aufgehen.

## Rechte

| Recht | Bedeutung |
|---|---|
| `edit user`, `edit permission` | Benutzer verwalten, Rechte vergeben |
| `edit event` | Veranstaltungen, Klassen, Einstellungen |
| `edit projekt` | Projekte verwalten |
| `edit laeufer`, `edit teams`, `edit sponsoren`, `edit sponsorings` | alle Datensätze sehen und bearbeiten (sonst nur eigene) |
| `edit startnummer` | Startnummern-Pool, Startnummern ändern |
| `schnellerfassung` | Anmeldetisch am Lauftag |
| `edit runden` | Runden manuell eingeben |
| `import export` | Runden-Import, Excel-Exporte |
| `show auswertung` | Statistiken und Klassenauswertung |
| `manage abrechnung` | Zahlungen, Zahlungsaufforderungen, Nachweise |

## Wichtige Dateien

- `app/Services/SpendenRechner.php`, `Statistik.php`, `SponsorAbrechnung.php` – Berechnungen
- `app/Actions/ImportRunden.php`, `Startnummernvergabe.php`, `LaeuferUebernehmen.php`
- `app/Models/Concerns/BelongsToEvent.php` – beschränkt Läufer, Teams, Spenden usw. auf die aktuelle Veranstaltung
- `resources/css/app.css` – Tailwind-Theme mit den ESZ-Farben (`esz-*` = #cc224d, `navy-*` = #002561)

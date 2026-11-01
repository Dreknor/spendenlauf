# Radebeuler Spendenlauf

Verwaltung des Spendenlaufs des Evangelischen Schulzentrums Radebeul: Anmeldung von Läufern, Sponsoren und Spendenzusagen, Rundenerfassung am Lauftag, Abrechnung mit den Sponsoren sowie Statistiken und Klassenauswertung.

**Technik:** Laravel 13 (PHP ≥ 8.3), Blade + Alpine.js, Tailwind CSS 4 (Vite), Chart.js, MariaDB/MySQL, Laravel Fortify (Login/Registrierung), spatie/laravel-permission, DomPDF, Laravel Excel.

## Einrichtung (lokal)

```bash
composer install
npm install
cp .env.example .env   # Datenbank und Mail eintragen; lokal: APP_ENV=local, APP_DEBUG=true, SESSION_SECURE_COOKIE=false
php artisan key:generate
php artisan migrate
php artisan db:seed     # nur lokal: Demo-Lauf mit Testdaten, Login demo-admin@example.org / password
npm run build           # oder: npm run dev
php artisan test
```

**Mails lokal:** Mit Laragon läuft [Mailpit](http://localhost:8025) – in der `.env` `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025` setzen, dann landen alle Mails (Zahlungsaufforderungen, Bestätigungen, …) dort statt bei echten Empfängern.

Auf dem Server muss der Scheduler laufen (Cronjob) – für den automatischen Runden-Import am Lauftag, den Mailversand, das Löschen unbestätigter Spendenzusagen und die tägliche Update-Prüfung:

```
* * * * * cd /pfad/zum/projekt && php artisan schedule:run >> /dev/null 2>&1
```

**Mails und Warteschlange:** Mit `QUEUE_CONNECTION=database` (Standard in `.env.example`) werden Mails in die Warteschlange gestellt und vom Scheduler jede Minute versandt – ein dauerhaft laufender Queue-Worker ist nicht nötig, kann aber zusätzlich laufen (`php artisan queue:work`). Fehlgeschlagene Mails werden zweimal wiederholt und landen danach in `failed_jobs` (`php artisan queue:failed`). Mit `QUEUE_CONNECTION=sync` gehen Mails sofort beim Absenden raus; das ist nur lokal sinnvoll, denn beim Massenversand an viele Sponsoren kann das Zeitlimit des Webservers greifen.

Deployment auf dem Server: `./deploy.sh` (Wartungsmodus, `git pull`, Composer, Frontend-Build, Migrationen, Caches) – oder bequem über den Online-Updater.

## Online-Updater

*Verwaltung → System-Update* (Recht `system update`, erhalten alle Konten mit `edit permission`) zeigt die installierte Version, prüft per `git fetch`, ob es neue Commits gibt, und listet die Änderungen. „Update jetzt installieren" startet im Hintergrund `php artisan spendenlauf:update`, das dieselben Schritte wie `deploy.sh` ausführt; der Fortschritt erscheint live auf der Seite.

- Abbruch vor dem Wartungsmodus, wenn auf dem Server Dateien geändert wurden oder die Installation Commits enthält, die im Repository fehlen.
- Schlägt nach dem `git merge` ein Schritt fehl, wird der vorherige Stand automatisch wiederhergestellt (Code, Composer-Pakete, Frontend, Caches). **Datenbank-Migrationen werden nicht zurückgerollt** – vor größeren Updates ein Backup anlegen.
- Status und Protokoll: `storage/app/updater/` (`status.json`, `update.log`). Auf der Konsole: `php artisan spendenlauf:update` (`--check` nur prüfen, `--force` Schritte auch ohne neue Version ausführen).
- Der Scheduler prüft täglich um 4:15 Uhr auf neue Versionen; im Menü erscheint dann „neu". Installiert wird nur auf Klick.

Voraussetzungen auf dem Server: Der Webserver-Benutzer muss das Projektverzeichnis schreiben und `git`, `composer` (und optional `npm`) ausführen dürfen. Gehört das Verzeichnis einem anderen Benutzer, meldet git „dubious ownership" – Abhilfe: `git config --global --add safe.directory /pfad/zum/projekt`. Unter *Server-Umgebung* zeigt die Seite die gefundenen Versionen von PHP, git, Composer und npm. Einstellungen in der `.env`:

| Variable | Bedeutung |
|---|---|
| `UPDATER_ENABLED` | `false` blendet den Updater aus |
| `UPDATER_BRANCH`, `UPDATER_REMOTE` | Quelle der Updates (Standard: aktueller Branch von `origin`) |
| `UPDATER_PHP` | PHP-CLI, falls mehrere Versionen installiert sind (z. B. `/usr/bin/php8.3`) |
| `UPDATER_COMPOSER` | z. B. `composer` oder `/usr/bin/php8.3 /opt/composer.phar` |
| `UPDATER_NPM` | `auto` (bauen, wenn npm vorhanden) oder `false` – dann muss `public/build` anders aktualisiert werden |
| `UPDATER_TIMEOUT` | maximale Dauer eines Schritts in Sekunden (Standard 900) |

## Ablauf eines Spendenlaufs

1. **Vorbereiten** – *Verwaltung → Veranstaltungen → Neuen Lauf vorbereiten*. Einstellungen werden vom letzten Lauf übernommen, Klassen werden auf Wunsch hochgestuft (5a → 7a bei zwei Jahren Abstand). Projekte auswählen, Bankdaten, PayPal.me-Name, Prozentsätze für soziale Abgabe und Klassenkasse prüfen und den Lauf **aktivieren**.
2. **Anmeldung** – Eltern registrieren sich, melden sich und ihre Kinder an (Kategorie, bei Schülern die Klasse). Läufer vom letzten Lauf können mit einem Klick übernommen werden. Jeder Läufer hat einen persönlichen **Sponsor-Link**, über den Sponsoren selbst Spenden zusagen. Die Zusage zählt erst, wenn der Sponsor sie über den Link in einer Bestätigungsmail bestätigt (Double-Opt-in, 7 Tage gültig; unbestätigte Angaben werden danach gelöscht). Über den Link sind höchstens 1.000 € fest bzw. 25 € je Runde möglich (`SPENDENLAUF_OEFFENTLICH_MAX_BETRAG`, `SPENDENLAUF_OEFFENTLICH_MAX_RUNDE`).
3. **Lauftag** –
   - *Schnellerfassung*: Läufer, Startnummer, Sponsoren, Spenden und Barzahlungen in einem Schritt.
   - *Runden eingeben*: manuelle Eingabe/Korrektur per Startnummer.
   - *Runden importieren*: automatisch alle 5 Minuten von der Ergebnisseite der Zeitmessung (Import-URL am Event) oder per CSV/Excel-Upload. Manuell eingegebene Runden werden dabei nicht überschrieben.
   - *Startnummern*: Pool der vorgedruckten Nummern.
4. **Abrechnung** – Zahlungsaufforderungen per Mail (über die Warteschlange; wer eine Aufforderung schon erhalten hat bzw. heute schon erinnert wurde, wird beim Massenversand übersprungen) (Aufstellung, Bankverbindung, Verwendungszweck mit Sponsor-Nr., PayPal.me-Link, Link zur persönlichen Sponsor-Seite), Zahlungseingänge erfassen, Erinnerungen an offene Posten, vereinfachter Spendennachweis (≤ 300 €) als PDF, Export der Spenden über 300 € für Zuwendungsbestätigungen.
5. **Auswertung** – Statistiken nach Läufern, Teams, Altersgruppen, Geschlecht, Kategorie, Klassen/Stufen, Sponsoren, Verteilung und Jahresvergleich; Excel- und PDF-Export; Klassenauswertung (PDF je Klasse für die Klassenleitungen); öffentliche Live-Statistik unter `/stats` (einbettbar, `/stats/embed` ohne Kopf/Fuß).

## Doppelte Einträge

- **Läufer:** Bei jeder Anmeldung (Eltern, Schnellerfassung, Übernahme aus dem Vorjahr) wird nach bereits angemeldeten Läufern gesucht – unabhängig von Groß-/Kleinschreibung, Umlaut-Schreibweise (ä/ae, ß/ss), Leerzeichen, Abkürzungen (Max/Maximilian) und kleinen Tippfehlern (`app/Services/Dubletten.php`). Gleicher Name und gleiches Geburtsdatum blockieren die Anmeldung (nur das Orga-Team kann übergehen), bei ähnlichen Namen kann bestätigt werden, dass es eine andere Person ist.
- **Mitverwaltung:** Ist ein Kind schon vom anderen Elternteil angemeldet, wird statt einer zweiten Anmeldung die Mitverwaltung angefragt – nur für Läufer, auf die die eigene Anmeldung gestoßen ist. Der anmeldende Elternteil (oder das Orga-Team) bestätigt auf der Startseite. Klasse, Team und Jahrgang fremder Kinder werden dabei nicht angezeigt. Eltern können weitere Konten per E-Mail einladen; die eingeladene Person muss die Einladung mit einem Konto zu dieser Adresse annehmen (30 Tage gültig). Nur das Orga-Team ordnet Konten direkt zu.
- **Sponsoren** sind gemeinsame Datensätze: Gibt es die E-Mail-Adresse (oder Name mit gleicher Anschrift – „Str.“ und „Straße“ gelten als gleich) schon, wird der vorhandene Sponsor verwendet. Bei gleichem Namen mit anderer Straße oder ähnlichen Namen wird nachgefragt; Sponsor-Link und Schnellerfassung legen dann einen neuen Sponsor an, den das Orga-Team bei Bedarf zusammenführt. Eltern sehen bei gemeinsamen Sponsoren nur die Spenden für ihre eigenen Läufer. Anschrift, E-Mail und Telefon sieht und ändert nur, wer den Sponsor zuerst erfasst hat (bzw. das Orga-Team) – sonst könnte jeder mit einer bekannten E-Mail-Adresse die Kontaktdaten auslesen oder Zahlungs-Mails umleiten.
- **Bereinigung:** *Verwaltung → Doppelte Einträge* listet mögliche Dubletten. Beim Zusammenführen werden Spenden, Zahlungen, Mails, Verwalter und fehlende Angaben übernommen; Paare können als „keine Dublette“ markiert werden. Bei Läufern gehören Startnummer und Runden zusammen (die Zeitmessung zählt pro Nummer): Haben beide eine Startnummer, wählt das Orga-Team, welche bleibt (Vorschlag: die mit den meisten Runden). Die andere Nummer wird wieder frei – außer darauf wurden schon Runden gezählt, dann bleibt sie gesperrt, damit kein neuer Läufer diese Runden erbt.

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
| `system update` | Online-Updater (neue Versionen einspielen) |

Konten mit Rechten, die man selbst nicht hat, kann nur bearbeiten, wer `edit permission` hat. Nach dem Lauf können Eltern weder das Team wechseln noch Spenden löschen (das würde bereits abgerechnete Beträge verändern).

## Wichtige Dateien

- `app/Services/SpendenRechner.php`, `Statistik.php`, `SponsorAbrechnung.php` – Berechnungen
- `app/Actions/ImportRunden.php`, `Startnummernvergabe.php`, `LaeuferUebernehmen.php`
- `app/Models/Concerns/BelongsToEvent.php` – beschränkt Läufer, Teams, Spenden usw. auf die aktuelle Veranstaltung
- `resources/css/app.css` – Tailwind-Theme mit den ESZ-Farben (`esz-*` = #cc224d, `navy-*` = #002561)

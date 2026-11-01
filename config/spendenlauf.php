<?php

/*
|--------------------------------------------------------------------------
| Spendenlauf
|--------------------------------------------------------------------------
|
| Installationsweite Einstellungen. Alles, was sich von Lauf zu Lauf ändert
| (Datum, Prozentsätze, Bankdaten, Import-URL, ...), wird in der
| Veranstaltungsverwaltung gepflegt.
|
*/

return [

    'schule' => env('SPENDENLAUF_SCHULE', 'Evangelisches Schulzentrum Radebeul'),

    'logo' => env('LOGO') ?: 'img/logo.png',

    'logo_link' => env('LOGO_LINK') ?: 'https://esz-radebeul.de',

    'datenschutz_link' => env('DATENSCHUTZ', 'https://www.spendenlauf-radebeul.de/index.php/datenschutz/'),

    'impressum_link' => env('IMPRESSUM', 'https://esz-radebeul.de/impressum/'),

    // Optionale Adresse, die über Änderungen an Teams informiert wird (z. B. Organisationsteam)
    'admin_mail' => env('SPENDENLAUF_ADMIN_MAIL'),

    // Einbettung der öffentlichen Statistik (/stats) in andere Seiten
    'stats' => [
        'content_security_policy' => env('Content_Security_Policy', "frame-ancestors 'self'"),
        'x_frame_options' => env('X_Frame_Options', 'SAMEORIGIN'),
        'reload_sekunden' => (int) env('STATS_RELOAD', 60),
    ],

    // Grenze für den vereinfachten Spendennachweis (§ 50 Abs. 4 EStDV)
    'nachweis_grenze_cent' => 30000,

    // Online-Updater (Verwaltung → System-Update): holt neue Versionen per git und führt die Schritte aus deploy.sh aus
    'updater' => [
        'enabled' => (bool) env('UPDATER_ENABLED', true),
        'remote' => env('UPDATER_REMOTE', 'origin'),
        // leer = aktuell ausgecheckter Branch
        'branch' => env('UPDATER_BRANCH'),
        // leer = automatisch ermitteln; auf Servern mit mehreren PHP-Versionen z. B. /usr/bin/php8.3
        'php' => env('UPDATER_PHP'),
        // z. B. "composer" oder "/usr/bin/php8.3 /opt/composer.phar"
        'composer' => env('UPDATER_COMPOSER', 'composer'),
        // "auto" = Frontend bauen, wenn npm vorhanden ist; "false" = nie (public/build muss dann anders aktualisiert werden)
        'npm' => env('UPDATER_NPM', 'auto'),
        // maximale Laufzeit eines einzelnen Schritts in Sekunden
        'timeout' => (int) env('UPDATER_TIMEOUT', 900),
        'verzeichnis' => storage_path('app/updater'),
    ],

];

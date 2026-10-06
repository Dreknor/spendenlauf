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

];

<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Services\SponsorAbrechnung;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Persönliche Seite eines Sponsors: Aufstellung, Zahlungsinformationen, Nachweis.
 */
class SponsorPortalController extends Controller
{
    public function show(string $uuid)
    {
        $sponsor = Sponsor::where('uuid', $uuid)->firstOrFail();

        return view('public.sponsor', ['a' => SponsorAbrechnung::fuer($sponsor, $this->event())]);
    }

    public function nachweis(string $uuid)
    {
        $sponsor = Sponsor::where('uuid', $uuid)->firstOrFail();
        $abrechnung = SponsorAbrechnung::fuer($sponsor, $this->event());

        abort_unless($abrechnung->nachweisMoeglich(), 404);

        return Pdf::loadView('pdf.nachweis', ['a' => $abrechnung])->download('Spendennachweis.pdf');
    }
}

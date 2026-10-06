<?php

namespace App\Http\Controllers;

use App\Enums\ZahlungsArt;
use App\Models\Sponsor;
use App\Models\Zahlung;
use App\Services\SpendenRechner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ZahlungController extends Controller
{
    public function store(Request $request, Sponsor $sponsor)
    {
        $daten = $request->validate([
            'betrag' => ['required', 'numeric', 'not_in:0', 'min:-100000', 'max:100000'],
            'bezahlt_am' => ['required', 'date', 'before_or_equal:today'],
            'art' => ['required', Rule::enum(ZahlungsArt::class)],
            'notiz' => ['nullable', 'string', 'max:190'],
        ]);

        $zahlung = new Zahlung($daten + ['sponsor_id' => $sponsor->id]);
        $zahlung->erfasst_von = $request->user()->id;
        $zahlung->save();

        SpendenRechner::vergessen();

        return $this->erfolg('Zahlung über '.euro((float) $daten['betrag'], false).' wurde erfasst.');
    }

    public function destroy(Zahlung $zahlung)
    {
        $zahlung->delete();
        SpendenRechner::vergessen();

        return $this->erfolg('Zahlung wurde gelöscht.');
    }
}

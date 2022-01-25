<?php

namespace App\Exports;

use App\Model\Sponsor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class SponsorExport implements FromCollection, WithStrictNullComparison, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return [
            'id',
            'Anrede',
            'Nachname',
            'Vorname',
            'Firmenname',
            'Email',
            'Anschrift',
            'Plz',
            'Ort',
            'Telefon',
            'Spendensumme',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $Sponsoren = Sponsor::all();
        $berechneteSponsoren = [];

        foreach ($Sponsoren as $sponsor) {
            $spendensumme = $sponsor->spendensumme;
            $sponsor = $sponsor->toArray();
            $sponsor['spendensumme'] = $spendensumme;

            $berechneteSponsoren[] = $sponsor;
        }

        return collect($berechneteSponsoren);
    }
}

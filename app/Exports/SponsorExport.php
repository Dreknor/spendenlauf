<?php

namespace App\Exports;

use App\Model\Projects;
use App\Model\Sponsor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class SponsorExport implements FromCollection, WithStrictNullComparison, ShouldAutoSize, WithHeadings
{
    private $projects;

    public function __construct()
    {
        $this->projects = Projects::all();
    }

    public function headings(): array
    {
        $arr = [
            'id',
            'Anrede',
            'Nachname',
            'Vorname',
            'Firmenname',
            'Email',
            'Mail versandt',
            'Anschrift',
            'Plz',
            'Ort',
            'Telefon',
            'Spendensumme (gesamt)',
        ];

        foreach ($this->projects as $project){
            array_push($arr, $project->name);
        }

        return $arr;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $Sponsoren = Sponsor::all();
        $berechneteSponsoren = [];

        foreach ($Sponsoren as $sponsor) {
            $projekte = $sponsor->Spendenprojects;

            $spendensumme = $sponsor->spendensumme;
            $sponsor = $sponsor->toArray();
            $sponsor['spendensumme'] = $spendensumme;

            foreach ($this->projects as $name) {
               if (array_key_exists($name->name, $projekte)){
                   $sponsor[$name->name] = $projekte[$name->name];
               } else {
                   $sponsor[$name->name] = 0;
               }
            }



            $berechneteSponsoren[] = $sponsor;
        }

        return collect($berechneteSponsoren);
    }
}

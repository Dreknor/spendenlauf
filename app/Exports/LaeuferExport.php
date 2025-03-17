<?php

namespace App\Exports;

use App\Model\Laeufer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class LaeuferExport implements FromCollection, WithStrictNullComparison, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return [
            'Startnummer',
            'Nachname',
            'Vorname',
            'Runden',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Laeufer::query()->orderBy('nachname')->get(['startnummer', 'nachname', 'vorname', 'runden']);
    }
}

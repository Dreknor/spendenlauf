<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProjectExport implements FromCollection, WithHeadings
{
    private $Liste;

    public function __construct(array $Liste)
    {
        $this->Liste = collect($Liste);
    }

    public function headings(): array
    {
        return [
            'Projektname',
            'Spendenbetrag',
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->Liste;
    }
}

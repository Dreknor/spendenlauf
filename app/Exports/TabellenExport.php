<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Einfache Tabelle (Überschriften + Zeilen) als Excel-Datei.
 */
class TabellenExport implements FromArray, ShouldAutoSize, WithHeadings, WithStrictNullComparison, WithStyles, WithTitle
{
    /**
     * @param  list<string>  $ueberschriften
     * @param  list<list<mixed>>  $zeilen
     */
    public function __construct(
        private readonly string $titel,
        private readonly array $ueberschriften,
        private readonly array $zeilen,
    ) {}

    public function array(): array
    {
        return $this->zeilen;
    }

    public function headings(): array
    {
        return $this->ueberschriften;
    }

    public function title(): string
    {
        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $this->titel), 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}

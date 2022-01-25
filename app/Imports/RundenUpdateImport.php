<?php

namespace App\Imports;

use App\Model\Laeufer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RundenUpdateImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $row = $row->toArray();
            array_change_key_case($row);
            if (isset($row['startnummer']) and isset($row['runden'])) {
                Laeufer::query()->where('startnummer', $row['startnummer'])->update([
                    'runden'    => $row['runden'],
                ]);
            } elseif (isset($row['startnummer']) and is_null($row['runden'])) {
                Laeufer::query()->where('startnummer', $row['startnummer'])->update([
                    'runden'    => 0,
                ]);
            }
        }
    }
}

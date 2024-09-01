<?php

namespace App\Imports;

use App\Model\Laeufer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RundenUpdate implements  ToModel, WithHeadingRow, WithBatchInserts
{

    public function model(array $row)
    {
        $laeufer = Laeufer::where('startnummer', $row['startnr'])->first();

        if ($laeufer != null) {
            $laeufer->runden = $row['anzahl_runden'];
            $laeufer->save();
        }

        return null;

    }

    public function headingRow(): int
    {
        return 4;
    }

    public function batchSize(): int
    {
        return 20;
    }


}

<?php

namespace App\Imports;

use App\Model\Laeufer;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RundenUpdate implements  ToModel, WithHeadingRow, WithBatchInserts
{

    public function model(array $row)
    {

        Log::info('Import von RundenUpdate');

        Log::info(collect($row));

        $laeufer = Laeufer::where('startnummer', $row['startnr'])->first();


        if ($laeufer != null) {
            $laeufer->runden = $row['anzahl_runden'];

            Log::info('Runden: '.$row['anzahl_runden']);

            $laeufer->save();
        } else {
            Log::info('Laeufer nicht gefunden'. $row['startnr']);
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

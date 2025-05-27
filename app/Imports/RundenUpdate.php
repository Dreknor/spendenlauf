<?php

namespace App\Imports;

use App\Model\Laeufer;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RundenUpdate implements  ToModel, WithHeadingRow, WithBatchInserts, WithCustomCsvSettings

{

    public function model(array $row)
    {

        Log::info('Import von RundenUpdate');
        Log::info('Import von RundenUpdate', [
            'row' => $row,
        ]);


        $laeufer = Laeufer::where('startnummer', $row['startnr'])->first();


        if ($laeufer != null) {
            $laeufer->runden = $row['runden'];

            Log::info('Runden: '.$row['runden']);

            $laeufer->save();
        } else {
            Log::info('Laeufer nicht gefunden',
            [
                'row' => $row,

                ]);
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

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ";"

        ];
    }

}

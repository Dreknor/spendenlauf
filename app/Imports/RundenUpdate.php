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


        $laeufer = Laeufer::where('startnummer', $row[0])->first();


        if ($laeufer != null) {
            $laeufer->runden = $row[5];

            Log::info('Runden: '.$row[5]);

            $laeufer->save();
        } else {
            Log::info('Laeufer nicht gefunden',
            [
                'startnummer' => $row[0],
                'vorname' => $row[1],
                'nachname' => $row[2],
                'team' => $row[3],
                'runden' => $row[5],

                ]);
        }

        return null;

    }

    public function headingRow(): int
    {
        return 5;
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

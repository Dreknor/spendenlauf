{{-- Tabelle: Kennzahlen je Gruppe ($gruppen aus Statistik::nachDimension), $spalte = Überschrift --}}
<div class="table-wrap">
    <table class="table">
        <thead><tr>
            <th>{{ $spalte }}</th><th class="num">Läufer</th><th class="num">Teilnehmer</th><th class="num">Runden</th><th class="num">Ø Runden</th>
            <th class="num">max.</th><th class="num">km</th><th class="num">Spenden</th><th class="num">Ø je Läufer</th><th class="num">je Runde</th>
        </tr></thead>
        <tbody>
        @foreach ($gruppen as $g)
            <tr>
                <td class="font-medium">{{ $g['label'] }}</td>
                <td class="num">{{ $g['anzahl'] }}</td>
                <td class="num">{{ $g['teilnehmer'] }}</td>
                <td class="num">{{ zahl($g['runden']) }}</td>
                <td class="num">{{ zahl($g['runden_schnitt'], 1) }}</td>
                <td class="num">{{ $g['runden_max'] }}</td>
                <td class="num">{{ zahl($g['km'], 1) }}</td>
                <td class="num font-semibold">{{ euro($g['spende']) }}</td>
                <td class="num">{{ euro($g['spende_schnitt']) }}</td>
                <td class="num">{{ euro($g['spende_pro_runde']) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <td>Gesamt</td>
            <td class="num">{{ $gruppen->sum('anzahl') }}</td>
            <td class="num">{{ $gruppen->sum('teilnehmer') }}</td>
            <td class="num">{{ zahl($gruppen->sum('runden')) }}</td>
            <td></td><td></td>
            <td class="num">{{ zahl($gruppen->sum('km'), 1) }}</td>
            <td class="num">{{ euro($gruppen->sum('spende')) }}</td>
            <td></td><td></td>
        </tr></tfoot>
    </table>
</div>

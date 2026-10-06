<table class="table">
    <thead><tr><th>für</th><th class="num">Runden</th><th class="num">je Runde</th><th class="num">Festbetrag</th><th class="num">max.</th><th class="num">Betrag</th></tr></thead>
    <tbody>
    @foreach ($a->positionen as $p)
        <tr>
            <td>{{ $p['ziel'] }}</td>
            <td class="num">{{ $p['runden'] }}</td>
            <td class="num">{{ $p['rundenBetrag'] ? euro($p['rundenBetrag']) : '–' }}</td>
            <td class="num">{{ $p['festBetrag'] ? euro($p['festBetrag']) : '–' }}</td>
            <td class="num">{{ $p['maxBetrag'] ? euro($p['maxBetrag']) : '–' }}</td>
            <td class="num font-semibold">{{ euro($p['betrag']) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot><tr><td colspan="5">Summe</td><td class="num">{{ euro($a->konto->sollCent) }}</td></tr></tfoot>
</table>

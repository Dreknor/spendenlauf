{{-- Tabelle von Sponsorings. Erwartet $sponsorings, $bilanz; optional $zeigeZiel, $zeigeSponsor --}}
@php $zeigeZiel ??= true; $zeigeSponsor ??= true; @endphp
<div class="overflow-x-auto">
    <table class="table">
        <thead>
        <tr>
            @if ($zeigeSponsor)<th>Sponsor</th>@endif
            @if ($zeigeZiel)<th>für</th>@endif
            <th class="num">Festbetrag</th>
            <th class="num">je Runde</th>
            <th class="num">max.</th>
            <th class="num">Betrag</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($sponsorings as $s)
            <tr>
                @if ($zeigeSponsor)
                    <td>
                        @can('view', $s->sponsor)
                            <a href="{{ route('sponsoren.edit', $s->sponsor) }}">{{ $s->sponsor?->name }}</a>
                        @else
                            {{ $s->sponsor?->name }}
                        @endcan
                    </td>
                @endif
                @if ($zeigeZiel)
                    <td>
                        @if ($s->istTeamSponsoring())
                            <x-badge color="esz">Team</x-badge> {{ $s->sponsorable?->name ?? '(gelöscht)' }}
                        @else
                            {{ $s->sponsorable?->name ?? '(gelöscht)' }}
                        @endif
                    </td>
                @endif
                <td class="num">{{ (float) $s->festBetrag > 0 ? euro($s->festBetrag, false) : '–' }}</td>
                <td class="num">{{ (float) $s->rundenBetrag > 0 ? euro($s->rundenBetrag, false) : '–' }}</td>
                <td class="num">{{ $s->maxBetrag !== null && (float) $s->maxBetrag > 0 ? euro($s->maxBetrag, false) : '–' }}</td>
                <td class="num font-semibold">{{ euro($bilanz->sponsorings[$s->id] ?? 0) }}</td>
                <td class="text-right">
                    @can('delete', $s)
                        <x-delete-button :action="route('sponsorings.destroy', $s)" label="" confirm="Diese Spende wirklich löschen?" />
                    @endcan
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

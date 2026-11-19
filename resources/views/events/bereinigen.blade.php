<x-layouts.app :title="'Bereinigen: '.$event->name">
    <x-page-header :title="'Lauf bereinigen: '.$event->name"
                   subtitle="Setzt den Lauf auf den Stand direkt nach dem Anlegen zurück. Benutzerkonten bleiben erhalten." />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Wird entfernt">
            <ul class="space-y-1 text-sm">
                <li><strong class="tabular-nums">{{ zahl($anzahl['laeufer']) }}</strong> Läufer (inkl. Mitverwaltungen und Einladungen)</li>
                <li><strong class="tabular-nums">{{ zahl($anzahl['teams']) }}</strong> Teams</li>
                <li><strong class="tabular-nums">{{ zahl($anzahl['sponsorings']) }}</strong> Spenden (Sponsorings)</li>
                <li><strong class="tabular-nums">{{ zahl($anzahl['zahlungen']) }}</strong> Zahlungen</li>
                <li><strong class="tabular-nums">{{ zahl($anzahl['startnummern']) }}</strong> freie Startnummern im Pool</li>
                <li><strong class="tabular-nums">{{ zahl($anzahl['sponsoren']) }}</strong> Sponsoren, die nur in diesem Lauf vorkommen</li>
            </ul>
            @if ($anzahl['zahlungen'] > 0)
                <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Achtung: Für diesen Lauf sind bereits Zahlungen erfasst. Sie werden mit entfernt.</p>
            @endif
        </x-card>

        <x-card title="Bleibt erhalten">
            <ul class="space-y-1 text-sm">
                <li>alle Benutzerkonten mit ihren Rechten</li>
                <li>Einstellungen, Projekte und Klassen dieses Laufs</li>
                <li>alle Daten anderer Läufe – auch Sponsoren, die dort gespendet haben</li>
            </ul>
        </x-card>
    </div>

    <x-card title="Bestätigen" class="mt-6">
        <form method="POST" action="{{ route('events.bereinigen.ausfuehren', $event) }}" class="space-y-4">
            @csrf
            <p class="rounded-lg bg-red-50 p-3 text-sm text-red-800">Das Bereinigen lässt sich nicht rückgängig machen.</p>
            <x-input name="bestaetigung" :label="'Zur Bestätigung den Namen des Laufs eingeben: '.$event->name" autocomplete="off" required />
            <div class="flex gap-2">
                <button type="submit" class="btn-danger">Lauf bereinigen</button>
                <a href="{{ route('events.index') }}" class="btn-light">Abbrechen</a>
            </div>
        </form>
    </x-card>
</x-layouts.app>

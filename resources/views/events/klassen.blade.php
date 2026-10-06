<x-layouts.app :title="'Klassen – '.$event->name">
    <x-page-header title="Klassen" :subtitle="$event->name">
        <a href="{{ route('events.index') }}" class="btn-light">Zurück</a>
    </x-page-header>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Klassen hinzufügen">
            <form method="POST" action="{{ route('klassen.store', $event) }}" class="space-y-3">
                @csrf
                <x-textarea name="klassen" label="Klassen" rows="6" required hint="Mehrere Klassen durch Komma oder Zeilenumbruch trennen, z. B. „1a, 1b, 2a, 2b“. Die Stufe wird aus der Zahl übernommen." />
                <button type="submit" class="btn-primary w-full">Hinzufügen</button>
            </form>
        </x-card>
        <x-card title="{{ $klassen->count() }} Klassen" class="lg:col-span-2" :padding="false">
            @if ($klassen->isEmpty())
                <div class="p-6"><x-empty message="Noch keine Klassen. Beim Anlegen eines neuen Laufs können die Klassen des vorherigen Laufs hochgestuft übernommen werden." /></div>
            @else
                <table class="table">
                    <thead><tr><th>Klasse</th><th>Stufe</th><th>Klassenleitung</th><th class="num">Läufer</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($klassen as $k)
                        <tr x-data="{ edit: false }">
                            <td colspan="5" class="p-0!">
                                <form method="POST" action="{{ route('klassen.update', $k) }}" class="grid grid-cols-12 items-center gap-2 px-3 py-2">
                                    @csrf @method('PUT')
                                    <div class="col-span-3"><span x-show="!edit" class="font-medium">{{ $k->name }}</span><input x-show="edit" x-cloak type="text" name="name" value="{{ $k->name }}" class="mt-0!"></div>
                                    <div class="col-span-2"><span x-show="!edit">{{ $k->stufe ?? '–' }}</span><input x-show="edit" x-cloak type="number" name="stufe" value="{{ $k->stufe }}" class="mt-0!"></div>
                                    <div class="col-span-4"><span x-show="!edit" class="text-slate-600">{{ $k->klassenleitung ?? '–' }}</span><input x-show="edit" x-cloak type="text" name="klassenleitung" value="{{ $k->klassenleitung }}" class="mt-0!"></div>
                                    <div class="col-span-1 text-right tabular-nums">{{ $anzahl[$k->id] ?? 0 }}</div>
                                    <div class="col-span-2 flex justify-end gap-1">
                                        <button type="button" x-show="!edit" @click="edit = true" class="btn-light btn-sm" aria-label="Bearbeiten"><x-icon name="pencil" class="size-4" /></button>
                                        <button type="submit" x-show="edit" x-cloak class="btn-primary btn-sm" aria-label="Speichern"><x-icon name="check" class="size-4" /></button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="border-t border-slate-100 p-4 text-xs text-slate-500">
                    Klassen ohne Läufer können gelöscht werden:
                    @foreach ($klassen->filter(fn ($k) => ! isset($anzahl[$k->id])) as $k)
                        <form method="POST" action="{{ route('klassen.destroy', $k) }}" class="inline">@csrf @method('DELETE')<button class="ml-1 underline hover:text-red-600">{{ $k->name }}</button></form>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>

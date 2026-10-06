<x-layouts.app :title="$project->exists ? 'Projekt bearbeiten' : 'Projekt anlegen'">
    <x-page-header :title="$project->exists ? 'Projekt bearbeiten' : 'Projekt anlegen'" />
    <form method="POST" enctype="multipart/form-data" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($project->exists) @method('PUT') @endif
        <x-card>
            <div class="space-y-4">
                <x-input name="name" label="Name" :value="$project->name" required />
                <x-textarea name="description" label="Beschreibung" :value="$project->beschreibungText()" rows="10" required
                            hint="Absätze durch Leerzeilen trennen. **fett**, *kursiv* und Links [Text](https://…) sind möglich." />
                <div>
                    <label for="bild">Bild</label>
                    @if ($url = $project->bildUrl())
                        <img src="{{ $url }}" alt="" class="my-2 h-32 rounded-lg object-cover">
                    @endif
                    <input type="file" name="bild" id="bild" accept="image/*">
                    @error('bild')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                @if ($aktuellesEvent)
                    <input type="hidden" name="im_aktuellen_lauf" value="0">
                    <x-checkbox name="im_aktuellen_lauf" :label="'Wird beim '.$aktuellesEvent->name.' unterstützt'"
                                :checked="! $project->exists || $aktuellesEvent->projects()->whereKey($project->id)->exists()" />
                @endif
            </div>
        </x-card>
        <div class="flex flex-wrap justify-between gap-2">
            <button type="submit" class="btn-primary">Speichern</button>
        </div>
    </form>
    @if ($project->exists)
        <div class="mt-6"><x-delete-button :action="route('projects.destroy', $project)" label="Projekt löschen" /></div>
    @endif
</x-layouts.app>

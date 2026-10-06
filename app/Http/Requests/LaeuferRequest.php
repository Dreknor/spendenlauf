<?php

namespace App\Http\Requests;

use App\Enums\Kategorie;
use App\Support\CurrentEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LaeuferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $eventId = app(CurrentEvent::class)->id();
        $neu = $this->isMethod('POST');
        $darfVerwalten = $this->user()->can('edit laeufer');

        return [
            'vorname' => ['required', 'string', 'max:100'],
            'nachname' => ['required', 'string', 'max:100'],
            'geburtsdatum' => [$darfVerwalten ? 'nullable' : 'required', 'date', 'before:today', 'after:1900-01-01'],
            'geschlecht' => ['nullable', Rule::in(['weiblich', 'maennlich', 'divers'])],
            'email' => ['nullable', 'email', 'max:190'],
            'kategorie' => ['required', Rule::enum(Kategorie::class)],
            'schulklasse_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('kategorie') === Kategorie::Schueler->value),
                Rule::exists('schulklassen', 'id')->where('event_id', $eventId),
            ],
            'team_id' => ['nullable', Rule::exists('teams', 'id')->where('event_id', $eventId)],
            'teamname' => ['nullable', 'string', 'max:100', Rule::unique('teams', 'name')->where('event_id', $eventId)],
            'datenschutz' => [$neu ? 'accepted' : 'nullable'],
            'bilder' => [$neu ? 'accepted' : 'nullable'],
            'startnummer' => ['nullable', 'integer', 'min:1'],
            'runden' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'schulklasse_id' => 'Klasse',
            'teamname' => 'Teamname',
            'datenschutz' => 'Datenschutzerklärung',
            'bilder' => 'Einwilligung zu Fotos',
        ];
    }

    public function messages(): array
    {
        return [
            'schulklasse_id.required' => 'Bitte die Klasse des Schülers / der Schülerin auswählen.',
        ];
    }

    /** Felder, die in den Läufer übernommen werden. */
    public function laeuferDaten(): array
    {
        $daten = $this->safe()->only(['vorname', 'nachname', 'geburtsdatum', 'geschlecht', 'email', 'kategorie', 'schulklasse_id']);

        if (($daten['kategorie'] ?? null) !== Kategorie::Schueler->value) {
            $daten['schulklasse_id'] = null;
        }

        return $daten;
    }
}

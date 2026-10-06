<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit event') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('iban')) {
            $this->merge(['iban' => strtoupper(str_replace(' ', '', $this->input('iban')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'nummer' => ['nullable', 'integer', 'min:1', 'max:999'],
            'datum' => ['required', 'date'],
            'anmeldeschluss' => ['nullable', 'date', 'before_or_equal:datum'],
            'runden_laenge_m' => ['required', 'integer', 'min:1', 'max:10000'],
            'dauer_minuten' => ['required', 'integer', 'min:1', 'max:1440'],
            'altersgruppen_text' => ['nullable', 'string', 'max:500', 'regex:/^[\d\s,\-+]*$/'],
            'soziale_abgabe_prozent' => ['required', 'numeric', 'min:0', 'max:100'],
            'soziale_abgabe_empfaenger' => ['nullable', 'required_unless:soziale_abgabe_prozent,0', 'string', 'max:190'],
            'soziale_abgabe_beschreibung' => ['nullable', 'string', 'max:2000'],
            'klassenkasse_prozent' => ['required', 'numeric', 'min:0', 'max:100'],
            'kontoinhaber' => ['nullable', 'string', 'max:190'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/'],
            'bic' => ['nullable', 'string', 'max:11'],
            'bank' => ['nullable', 'string', 'max:190'],
            'paypal_me_name' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'verwendungszweck_prefix' => ['required', 'string', 'max:60'],
            'zahlungsziel_tage' => ['required', 'integer', 'min:1', 'max:365'],
            'verein_name' => ['nullable', 'string', 'max:190'],
            'verein_anschrift' => ['nullable', 'string', 'max:190'],
            'verein_steuernummer' => ['nullable', 'string', 'max:60'],
            'verein_finanzamt' => ['nullable', 'string', 'max:190'],
            'verein_freistellung_datum' => ['nullable', 'date'],
            'verein_zweck' => ['nullable', 'string', 'max:190'],
            'mail_absender_name' => ['nullable', 'string', 'max:190'],
            'mail_absender_adresse' => ['nullable', 'email', 'max:190'],
            'info_url' => ['nullable', 'url', 'max:190'],
            'import_url' => ['nullable', 'url', 'max:2000'],
            'projects' => ['nullable', 'array'],
            'projects.*' => ['integer', 'exists:projects,id'],
            'vorlage_event_id' => ['nullable', 'integer', 'exists:events,id'],
            'klassen_uebernehmen' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'altersgruppen_text' => 'Altersgruppen',
            'soziale_abgabe_empfaenger' => 'Empfänger der sozialen Abgabe',
            'paypal_me_name' => 'PayPal.me-Name',
            'iban' => 'IBAN',
        ];
    }

    /**
     * „6, 10, 14, 18, 39, 59“ → Gruppen bis 6, 7–10, 11–14, …, ab 60.
     *
     * @return list<array{von: ?int, bis: ?int}>|null
     */
    public function altersgruppen(): ?array
    {
        $grenzen = collect(preg_split('/[\s,;]+/', (string) $this->input('altersgruppen_text'), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($g) => (int) $g)->filter(fn ($g) => $g > 0)->unique()->sort()->values();

        if ($grenzen->isEmpty()) {
            return null;
        }

        $gruppen = [];
        $von = null;
        foreach ($grenzen as $bis) {
            $gruppen[] = ['von' => $von, 'bis' => $bis];
            $von = $bis + 1;
        }
        $gruppen[] = ['von' => $von, 'bis' => null];

        return $gruppen;
    }

    public function eventDaten(): array
    {
        $daten = collect($this->validated())->except(['altersgruppen_text', 'projects', 'vorlage_event_id', 'klassen_uebernehmen'])->all();
        $daten['altersgruppen'] = $this->altersgruppen();

        return $daten;
    }
}

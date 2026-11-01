<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PublicSponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $istFirma = $this->input('anrede') === 'Firma';
        $maxBetrag = config('spendenlauf.oeffentlich.max_betrag');

        return array_merge(SponsoringRequest::betragsRegeln(), [
            // Niedrigere Grenzen als für angemeldete Benutzer: keine absurden Zusagen in der Live-Statistik
            'festBetrag' => ['nullable', 'numeric', 'min:0', 'max:'.$maxBetrag],
            'rundenBetrag' => ['nullable', 'numeric', 'min:0', 'max:'.config('spendenlauf.oeffentlich.max_runde')],
            'maxBetrag' => ['nullable', 'numeric', 'min:0', 'max:'.$maxBetrag],
            'anrede' => ['required', Rule::in(['Herr', 'Frau', 'Firma'])],
            'vorname' => ['nullable', Rule::requiredIf(! $istFirma), 'string', 'max:190'],
            'nachname' => ['nullable', Rule::requiredIf(! $istFirma), 'string', 'max:190'],
            'firmenname' => ['nullable', Rule::requiredIf($istFirma), 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190'],
            'strasse' => ['required', 'string', 'max:190'],
            'plz' => ['required', 'string', 'max:10'],
            'ort' => ['required', 'string', 'max:190'],
            'telefon' => ['nullable', 'string', 'max:50'],
            'datenschutz' => ['accepted'],
            // Ohne dieses Feld überspringt der Honeypot seine Prüfung – Bots könnten ihn so einfach umgehen
            config('honeypot.valid_from_field_name', 'valid_from') => config('honeypot.enabled') && config('honeypot.valid_from_timestamp') ? ['required'] : [],
        ]);
    }

    public function messages(): array
    {
        $hinweis = 'Über dieses Formular sind höchstens :max € möglich. Für eine höhere Spende wenden Sie sich bitte direkt an das Orga-Team.';

        return ['festBetrag.max' => $hinweis, 'rundenBetrag.max' => $hinweis, 'maxBetrag.max' => $hinweis];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $v) => SponsoringRequest::pruefeBetrag($v, $this->all()));
    }

    public function attributes(): array
    {
        return [
            'festBetrag' => 'Festbetrag', 'rundenBetrag' => 'Betrag je Runde', 'maxBetrag' => 'Maximalbetrag',
            'firmenname' => 'Firmenname', 'plz' => 'PLZ', 'strasse' => 'Straße', 'datenschutz' => 'Datenschutzerklärung',
        ];
    }
}

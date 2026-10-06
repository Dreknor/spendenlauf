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

        return array_merge([
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
        ], SponsoringRequest::betragsRegeln());
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

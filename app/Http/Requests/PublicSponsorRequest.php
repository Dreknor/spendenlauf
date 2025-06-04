<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicSponsorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'anrede' => 'required|in:Herr,Frau',
            'vorname' => 'required|string|max:255',
            'nachname' => 'required|string|max:255',
            'firmenname' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'strasse' => 'required|string|max:255',
            'plz' => 'required|string|max:10',
            'ort' => 'required|string|max:255',
            'telefon' => 'nullable|string|max:255',
            'maxBetrag' => 'nullable|numeric|min:0.5',
            'rundenBetrag' => 'nullable|numeric|min:0.5',
            'festBetrag' => 'nullable|numeric|min:0.5',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'Das Feld :attribute ist erforderlich.',
            'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'numeric' => 'Der Spendenbetrag muss eine Zahl sein.',
            'min' => 'Der Mindestspendenbetrag beträgt 0,01 €.',
        ];
    }
}

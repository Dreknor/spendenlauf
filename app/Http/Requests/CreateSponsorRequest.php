<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSponsorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'anrede'    => 'required|in:Herr,Frau,Firma',
            'firmenname'    => 'nullable|required_if:anrede,Firma|string',
            'vorname'   => 'required|string',
            'nachname'   => 'required|string',
            'telefon'   => 'nullable|string',
            'email'   => 'nullable|email',
            'plz'   => 'required|string',
            'strasse'   => 'required|string',
            'ort'   => 'required|string',
        ];
    }
}

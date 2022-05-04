<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'firmenname'    => [
                'nullable',
                'required_if:anrede,Firma',
                'string',
                'max:190'
                ],
            'vorname'   =>
                [
                    Rule::requiredIf(request()->anrede != 'Firma'),
                    'string',
                    'max:190',
                    'nullable'
                ],
            'nachname'   => [
                Rule::requiredIf(request()->anrede != 'Firma'),
                'string',
                'nullable',
                'max:190'
            ],
            'telefon'   => 'nullable|string',
            'email'   => [
                'nullable',
                'email',
                'unique:App\Model\Sponsor,email'
            ],
            'plz'   => 'required|string|size:5',
            'strasse'   => [
                'required',
                'string',
                'max:190'
            ],
            'ort'   => [
                'required',
                'string',
                'max:190'
            ],
        ];
    }
}

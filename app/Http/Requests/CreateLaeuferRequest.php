<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLaeuferRequest extends FormRequest
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
            'vorname'   => 'required|string',
            'nachname'   => 'required|string',
            'geburtsdatum'   => 'required|date',
            'geschlecht'   => 'nullable|string',
            'datenschutz'   => 'required',
            'bilder'   => 'required',
            'team_id'   => 'sometimes|nullable|exists:teams,id',
            'teamname'   => 'sometimes|nullable|string|unique:teams,name',
        ];
    }
}

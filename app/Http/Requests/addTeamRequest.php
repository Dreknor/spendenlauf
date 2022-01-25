<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class addTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (auth()->user()->can('edit laeufer') or $this->laeufer->verwaltet_von == auth()->user()->id) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'team_id'   => 'required|integer',
        ];
    }
}

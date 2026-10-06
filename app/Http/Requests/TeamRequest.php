<?php

namespace App\Http\Requests;

use App\Support\CurrentEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('teams', 'name')->where('event_id', app(CurrentEvent::class)->id())->ignore($this->route('team')?->id),
            ],
            'open' => ['nullable', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit projekt') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string'],
            'bild' => ['nullable', 'image', 'max:5120'],
            'im_aktuellen_lauf' => ['nullable', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SponsorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return self::regeln($this->route('sponsor')?->id, $this->input('anrede'));
    }

    public static function regeln(?int $ignoriereId = null, ?string $anrede = null, string $praefix = ''): array
    {
        $istFirma = $anrede === 'Firma';

        return [
            $praefix.'anrede' => ['required', Rule::in(['Herr', 'Frau', 'Firma'])],
            $praefix.'firmenname' => ['nullable', Rule::requiredIf($istFirma), 'string', 'max:190'],
            $praefix.'vorname' => ['nullable', Rule::requiredIf(! $istFirma), 'string', 'max:190'],
            $praefix.'nachname' => ['nullable', Rule::requiredIf(! $istFirma), 'string', 'max:190'],
            $praefix.'email' => ['nullable', 'email', 'max:190', Rule::unique('sponsors', 'email')->ignore($ignoriereId)],
            $praefix.'telefon' => ['nullable', 'string', 'max:50'],
            $praefix.'strasse' => ['nullable', 'string', 'max:190'],
            $praefix.'plz' => ['nullable', 'string', 'max:10'],
            $praefix.'ort' => ['nullable', 'string', 'max:190'],
        ];
    }

    public function attributes(): array
    {
        return ['firmenname' => 'Firmenname', 'plz' => 'PLZ', 'strasse' => 'Straße'];
    }
}

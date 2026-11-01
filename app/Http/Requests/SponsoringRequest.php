<?php

namespace App\Http\Requests;

use App\Support\CurrentEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SponsoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return array_merge([
            'sponsor_id' => ['required', 'integer', 'exists:sponsors,id'],
            'type' => ['required', 'in:laeufer,team'],
            'laeufer_id' => ['required_if:type,laeufer', 'nullable', 'integer'],
            'team_id' => ['required_if:type,team', 'nullable', 'integer'],
        ], self::betragsRegeln());
    }

    /** Regeln für die Beträge (auch für öffentliches Formular und Schnellerfassung). */
    public static function betragsRegeln(string $praefix = ''): array
    {
        return [
            $praefix.'festBetrag' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            $praefix.'rundenBetrag' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            $praefix.'maxBetrag' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            $praefix.'projects' => ['nullable', 'array'],
            // nur Projekte der aktuellen Veranstaltung
            $praefix.'projects.*' => ['integer', Rule::exists('event_projects', 'projects_id')->where('event_id', app(CurrentEvent::class)->get()?->id)],
        ];
    }

    /** Mindestens Fest- oder Rundenbetrag muss angegeben sein. */
    public static function pruefeBetrag(Validator $validator, array $daten, string $praefix = ''): void
    {
        if ((float) ($daten['festBetrag'] ?? 0) <= 0 && (float) ($daten['rundenBetrag'] ?? 0) <= 0) {
            $validator->errors()->add($praefix.'festBetrag', 'Bitte einen Festbetrag oder einen Betrag je Runde angeben.');
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $v) => self::pruefeBetrag($v, $this->all()));
    }

    public function attributes(): array
    {
        return ['festBetrag' => 'Festbetrag', 'rundenBetrag' => 'Betrag je Runde', 'maxBetrag' => 'Maximalbetrag'];
    }
}

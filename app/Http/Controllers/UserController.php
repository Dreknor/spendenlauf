<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public const RECHTE = [
        'edit user' => 'Benutzer verwalten',
        'edit permission' => 'Rechte vergeben',
        'edit event' => 'Veranstaltungen & Klassen verwalten',
        'edit projekt' => 'Projekte verwalten',
        'edit laeufer' => 'alle Läufer bearbeiten',
        'edit teams' => 'alle Teams bearbeiten',
        'edit sponsoren' => 'alle Sponsoren bearbeiten',
        'edit sponsorings' => 'alle Spenden bearbeiten',
        'edit startnummer' => 'Startnummern verwalten',
        'schnellerfassung' => 'Schnellerfassung am Lauftag',
        'edit runden' => 'Runden eingeben',
        'import export' => 'Import & Export',
        'show auswertung' => 'Statistiken & Auswertungen',
        'manage abrechnung' => 'Abrechnung (Zahlungen, Mails)',
        'send mail' => 'Mails an Sponsoren (alt)',
    ];

    public function index(Request $request)
    {
        $users = User::query()
            ->with('permissions')
            ->withCount('laeufer')
            ->when($request->filled('q'), function ($q) use ($request) {
                $suche = '%'.$request->input('q').'%';
                $q->where(fn ($q) => $q->where('vorname', 'like', $suche)->orWhere('nachname', 'like', $suche)->orWhere('email', 'like', $suche));
            })
            ->when($request->input('filter') === 'team', fn ($q) => $q->has('permissions'))
            ->orderBy('nachname')
            ->paginate(50)
            ->withQueryString();

        return view('users.index', ['users' => $users]);
    }

    public function show(User $user)
    {
        return view('users.show', [
            'user' => $user->load('permissions'),
            'rechte' => collect(self::RECHTE)->only(Permission::pluck('name')),
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Konten mit Rechten, die man selbst nicht hat, nur mit "edit permission" bearbeiten –
        // sonst ließe sich über eine geänderte E-Mail-Adresse ein Admin-Konto übernehmen
        $bearbeiter = $request->user();
        $fehlendeRechte = $user->getAllPermissions()->pluck('name')->diff($bearbeiter->getAllPermissions()->pluck('name'));
        if ($fehlendeRechte->isNotEmpty() && ! $bearbeiter->can('edit permission')) {
            return $this->fehler('Dieses Konto hat Rechte, die Sie selbst nicht haben, und kann daher nur mit dem Recht „Rechte vergeben“ bearbeitet werden.');
        }

        $daten = $request->validate([
            'vorname' => ['required', 'string', 'max:255'],
            'nachname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->fill(collect($daten)->only(['vorname', 'nachname', 'email'])->all());

        // Neue Adresse muss erneut bestätigt werden
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        if ($bearbeiter->can('edit permission')) {
            $user->syncPermissions($daten['permissions'] ?? []);
        }

        return $this->erfolg('Benutzer gespeichert.');
    }

    public function sendVerification(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return $this->fehler('Die E-Mail-Adresse ist bereits bestätigt.');
        }

        $user->sendEmailVerificationNotification();

        return $this->erfolg('Bestätigungs-Mail wurde versandt.');
    }
}

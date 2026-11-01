<?php

namespace App\Policies;

use App\Models\Sponsor;
use App\Models\User;

class SponsorPolicy
{
    public function view(User $user, Sponsor $sponsor): bool
    {
        return $user->can('edit sponsoren') || $user->can('manage abrechnung') || $sponsor->wirdVerwaltetVon($user);
    }

    /** Kontaktdaten (Anschrift, E-Mail, Telefon) sehen und ändern. */
    public function update(User $user, Sponsor $sponsor): bool
    {
        return $user->can('edit sponsoren') || $sponsor->istErfasstVon($user);
    }
}

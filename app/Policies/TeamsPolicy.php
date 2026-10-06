<?php

namespace App\Policies;

use App\Models\Teams;
use App\Models\User;

class TeamsPolicy
{
    public function view(User $user, Teams $team): bool
    {
        return $user->can('edit teams') || $team->istVerwaltetVon($user);
    }

    public function update(User $user, Teams $team): bool
    {
        return $this->view($user, $team);
    }

    public function delete(User $user, Teams $team): bool
    {
        return $this->view($user, $team);
    }

    /** Läufer in dieses Team aufnehmen */
    public function join(User $user, Teams $team): bool
    {
        return $team->open || $this->view($user, $team);
    }
}

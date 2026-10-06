<?php

namespace App\Policies;

use App\Models\Laeufer;
use App\Models\User;

class LaeuferPolicy
{
    public function view(User $user, Laeufer $laeufer): bool
    {
        return $user->can('edit laeufer') || $laeufer->istVerwaltetVon($user);
    }

    public function update(User $user, Laeufer $laeufer): bool
    {
        return $this->view($user, $laeufer);
    }

    public function delete(User $user, Laeufer $laeufer): bool
    {
        return $user->can('edit laeufer')
            || ($laeufer->istVerwaltetVon($user) && $laeufer->sponsorings()->doesntExist() && (int) $laeufer->runden === 0);
    }
}

<?php

namespace App\Policies;

use App\Models\Sponsoring;
use App\Models\User;

class SponsoringPolicy
{
    public function delete(User $user, Sponsoring $sponsoring): bool
    {
        return $user->can('edit sponsorings') || (int) $sponsoring->verwaltet_von === (int) $user->id;
    }
}

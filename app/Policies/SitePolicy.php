<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Site;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function view(User $user, Site $site): bool
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function update(User $user, Site $site): bool
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function delete(User $user, Site $site): bool
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }
}

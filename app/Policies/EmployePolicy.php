<?php

namespace App\Policies;
use App\Models\Employe;
use App\Models\User;

class EmployePolicy
{
    // Admin IT et Admin peuvent voir tous les employés
    public function viewAny(User $user)
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function view(User $user)
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function create(User $user)
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function update(User $user)
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }

    public function delete(User $user)
    {
        return in_array($user->role, ['administrateur_it', 'administrateur']);
    }
}

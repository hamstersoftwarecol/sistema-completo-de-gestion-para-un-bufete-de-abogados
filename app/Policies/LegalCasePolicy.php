<?php

namespace App\Policies;

use App\Models\LegalCase;
use App\Models\User;

class LegalCasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LegalCase $case): bool
    {
        return $case->isMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LegalCase $case): bool
    {
        return $case->isMember($user);
    }

    /** Sólo el abogado senior responsable (o el administrador) puede eliminar. */
    public function delete(User $user, LegalCase $case): bool
    {
        return $user->isSenior() && $case->lawyer_id === $user->id;
    }
}

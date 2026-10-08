<?php

namespace App\Policies;

use App\Models\Hearing;
use App\Models\User;

class HearingPolicy
{
    public function view(User $user, Hearing $hearing): bool
    {
        return $hearing->user_id === $user->id || (bool) $hearing->legalCase?->isMember($user);
    }

    public function update(User $user, Hearing $hearing): bool
    {
        return $this->view($user, $hearing);
    }

    public function delete(User $user, Hearing $hearing): bool
    {
        return $hearing->user_id === $user->id
            || ($user->isSenior() && $hearing->legalCase?->lawyer_id === $user->id);
    }
}

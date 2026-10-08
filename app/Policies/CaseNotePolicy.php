<?php

namespace App\Policies;

use App\Models\CaseNote;
use App\Models\User;

class CaseNotePolicy
{
    public function update(User $user, CaseNote $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, CaseNote $note): bool
    {
        return $note->user_id === $user->id || $note->legalCase?->lawyer_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return (bool) $document->legalCase?->isMember($user);
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->user_id === $user->id || $document->legalCase?->lawyer_id === $user->id;
    }
}

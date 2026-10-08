<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * El superadministrador tiene acceso total (ver Gate::before en AppServiceProvider).
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return $client->user_id === $user->id
            || $client->cases()->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Client $client): bool
    {
        return $this->view($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isSenior() && $client->user_id === $user->id;
    }
}

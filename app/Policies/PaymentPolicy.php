<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return Payment::query()->visibleTo($user)->whereKey($payment->id)->exists();
    }

    /** Los abogados junior registran pagos pero no los modifican ni eliminan. */
    public function update(User $user, Payment $payment): bool
    {
        return $user->isSenior() && $this->view($user, $payment);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }
}

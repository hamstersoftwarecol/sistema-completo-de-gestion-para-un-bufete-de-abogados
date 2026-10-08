<?php

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function view(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id || (bool) $expense->legalCase?->isMember($user);
    }

    /** Sólo quien lo registró puede editarlo mientras siga pendiente. */
    public function update(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id && $expense->status === ExpenseStatus::Pending;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }

    /** Aprueba/rechaza: el abogado senior responsable del caso (no sus propios gastos). */
    public function approve(User $user, Expense $expense): bool
    {
        return $expense->status === ExpenseStatus::Pending
            && $user->isSenior()
            && $expense->legalCase?->lawyer_id === $user->id
            && $expense->user_id !== $user->id;
    }

    /** El reembolso lo marca únicamente el superadministrador. */
    public function reimburse(User $user, Expense $expense): bool
    {
        return false;
    }
}

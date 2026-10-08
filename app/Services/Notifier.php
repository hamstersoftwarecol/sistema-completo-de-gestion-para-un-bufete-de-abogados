<?php

namespace App\Services;

use App\Enums\HearingStatus;
use App\Enums\Role;
use App\Models\Expense;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Collection;

/**
 * Centraliza las notificaciones internas del bufete.
 */
class Notifier
{
    public static function caseAssigned(LegalCase $case, ?User $actor = null): void
    {
        $recipients = collect([$case->lawyer, $case->assistant])->filter()
            ->reject(fn (User $u) => $actor && $u->id === $actor->id)
            ->unique('id');

        foreach ($recipients as $user) {
            $user->notify(new AppNotification(
                'Caso asignado',
                "Se le asignó el caso {$case->case_number}: {$case->title}",
                route('cases.show', $case, false),
                'briefcase',
                'indigo',
            ));
        }
    }

    public static function hearingScheduled(Hearing $hearing, ?User $actor = null): void
    {
        $case = $hearing->legalCase;
        $recipients = collect([$hearing->user, $case?->lawyer, $case?->assistant])->filter()
            ->reject(fn (User $u) => $actor && $u->id === $actor->id)
            ->unique('id');

        foreach ($recipients as $user) {
            $user->notify(new AppNotification(
                'Audiencia programada',
                "{$hearing->title} — {$hearing->scheduled_at->format('d/m/Y h:i a')} (caso {$case?->case_number})",
                route('hearings.edit', $hearing, false),
                'scale',
                'rose',
            ));
        }
    }

    public static function expenseSubmitted(Expense $expense): void
    {
        $case = $expense->legalCase;

        self::expenseApprovers($expense)->each(fn (User $user) => $user->notify(new AppNotification(
            'Gasto pendiente de aprobación',
            "{$expense->user?->name} registró un gasto de ".money($expense->amount)." en el caso {$case?->case_number}",
            route('expenses.show', $expense, false),
            'receipt',
            'amber',
        )));
    }

    public static function expenseReviewed(Expense $expense): void
    {
        if (! $expense->user) {
            return;
        }

        $expense->user->notify(new AppNotification(
            'Gasto '.mb_strtolower($expense->status->label()),
            "Su gasto «{$expense->description}» por ".money($expense->amount).' fue '.mb_strtolower($expense->status->label()).'.',
            route('expenses.show', $expense, false),
            'receipt',
            $expense->status->color(),
        ));
    }

    /** Superadministradores activos + el abogado senior responsable del caso. */
    public static function expenseApprovers(Expense $expense): Collection
    {
        $admins = User::query()->active()->where('role', Role::Superadmin->value)->get();
        $lead = $expense->legalCase?->lawyer;

        return $admins
            ->when($lead && $lead->isSenior() && $lead->is_active, fn ($c) => $c->push($lead))
            ->reject(fn (User $u) => $u->id === $expense->user_id)
            ->unique('id')
            ->values();
    }

    /**
     * Envía recordatorios de audiencias que ocurren en las próximas 24 horas.
     */
    public static function sendHearingReminders(): int
    {
        $hearings = Hearing::query()
            ->with(['legalCase.lawyer', 'legalCase.assistant', 'user'])
            ->where('status', HearingStatus::Scheduled->value)
            ->whereNull('reminder_sent_at')
            ->whereBetween('scheduled_at', [now(), now()->addDay()])
            ->get();

        foreach ($hearings as $hearing) {
            $case = $hearing->legalCase;
            collect([$hearing->user, $case?->lawyer, $case?->assistant])->filter()->unique('id')
                ->each(fn (User $user) => $user->notify(new AppNotification(
                    'Recordatorio de audiencia',
                    "Mañana o en las próximas horas: {$hearing->title} — {$hearing->scheduled_at->format('d/m/Y h:i a')} (caso {$case?->case_number})",
                    route('hearings.edit', $hearing, false),
                    'clock',
                    'rose',
                )));

            $hearing->forceFill(['reminder_sent_at' => now()])->saveQuietly();
        }

        return $hearings->count();
    }
}

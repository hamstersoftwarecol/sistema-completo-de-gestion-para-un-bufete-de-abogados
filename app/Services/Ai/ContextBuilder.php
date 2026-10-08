<?php

namespace App\Services\Ai;

use App\Enums\AppointmentStatus;
use App\Enums\ExpenseStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\Payment;
use App\Models\User;

/**
 * Construye un resumen de los datos visibles para el usuario, que se envía a
 * la IA como contexto para responder preguntas en lenguaje natural.
 */
class ContextBuilder
{
    public static function build(User $user): string
    {
        $now = now();
        $out = [];

        $out[] = 'Fecha y hora actual: '.$now->translatedFormat('l d \d\e F \d\e Y, h:i a');
        $out[] = "Usuario: {$user->name} — {$user->role->label()}";
        $out[] = 'Bufete: '.setting('firm_name', config('app.name'));
        $out[] = $user->isSuperadmin()
            ? 'Alcance de los datos: todo el bufete.'
            : 'Alcance de los datos: sólo los asuntos asignados a este usuario.';

        // Casos
        $casesQuery = LegalCase::query()->visibleTo($user);
        $total = (clone $casesQuery)->count();
        $cases = (clone $casesQuery)
            ->with(['client', 'status', 'type', 'court', 'lawyer', 'assistant'])
            ->withSum('payments', 'amount')
            ->latest('updated_at')
            ->limit(40)
            ->get();

        $out[] = "\n## Casos ({$cases->count()} de {$total})";
        foreach ($cases as $c) {
            $paid = (float) ($c->payments_sum_amount ?? 0);
            $out[] = sprintf(
                '- [%s] %s | Cliente: %s | Tipo: %s | Estado: %s | Juzgado: %s | Juez: %s | Responsable: %s%s | Prioridad: %s | Radicado: %s | Honorarios: %s | Pagado: %s | Saldo: %s',
                $c->case_number,
                $c->title,
                $c->client?->name ?? '—',
                $c->type?->name ?? '—',
                $c->status?->name ?? '—',
                $c->court?->name ?? '—',
                $c->judge ?: '—',
                $c->lawyer?->name ?? '—',
                $c->assistant ? ' (asistente: '.$c->assistant->name.')' : '',
                $c->priority?->label() ?? '—',
                fdate($c->filing_date),
                money($c->fee_amount),
                money($paid),
                money(max(0, (float) $c->fee_amount - $paid)),
            );
        }

        // Audiencias
        $hearings = Hearing::query()->visibleTo($user)
            ->with('legalCase.client')
            ->whereBetween('scheduled_at', [$now->copy()->subDays(15), $now->copy()->addDays(90)])
            ->orderBy('scheduled_at')
            ->limit(40)
            ->get();

        $out[] = "\n## Audiencias (últimos 15 días y próximos 90 días)";
        foreach ($hearings as $h) {
            $out[] = sprintf(
                '- %s | %s | %s | Caso %s (%s) | Lugar: %s | Estado: %s',
                $h->scheduled_at->translatedFormat('D d/m/Y h:i a'),
                $h->title,
                $h->hearing_type ?? '',
                $h->legalCase?->case_number,
                $h->legalCase?->client?->name,
                $h->location ?: '—',
                $h->status->label(),
            );
        }
        if ($hearings->isEmpty()) {
            $out[] = '- (sin audiencias en el periodo)';
        }

        // Citas
        $appointments = Appointment::query()->visibleTo($user)
            ->with(['client', 'user'])
            ->whereBetween('starts_at', [$now->copy()->startOfDay(), $now->copy()->addDays(30)])
            ->whereNot('status', AppointmentStatus::Cancelled->value)
            ->orderBy('starts_at')
            ->limit(30)
            ->get();

        $out[] = "\n## Citas próximas (30 días)";
        foreach ($appointments as $a) {
            $out[] = sprintf(
                '- %s | %s | Con: %s | Abogado: %s | Modalidad: %s | Estado: %s',
                $a->starts_at->translatedFormat('D d/m/Y h:i a'),
                $a->title,
                $a->who,
                $a->user?->name ?? '—',
                $a->mode->label(),
                $a->status->label(),
            );
        }
        if ($appointments->isEmpty()) {
            $out[] = '- (sin citas próximas)';
        }

        // Pagos
        $payments = Payment::query()->visibleTo($user);
        $out[] = "\n## Finanzas";
        $out[] = 'Ingresos del mes actual: '.money((clone $payments)->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->sum('amount'));
        $out[] = 'Ingresos del año: '.money((clone $payments)->whereYear('paid_at', $now->year)->sum('amount'));
        $out[] = 'Últimos pagos:';
        foreach ((clone $payments)->with(['client', 'legalCase'])->latest('paid_at')->limit(10)->get() as $p) {
            $out[] = sprintf('- %s | Recibo %s | %s | %s | %s', fdate($p->paid_at), $p->receipt_number, $p->client?->name, money($p->amount), $p->concept);
        }

        $pendingExpenses = Expense::query()->visibleTo($user)->with(['legalCase', 'user'])
            ->where('status', ExpenseStatus::Pending->value)->latest()->limit(15)->get();
        $out[] = "\n## Gastos pendientes de aprobación ({$pendingExpenses->count()})";
        foreach ($pendingExpenses as $e) {
            $out[] = sprintf('- %s | %s | %s | Caso %s | Registró: %s', fdate($e->expense_date), $e->description, money($e->amount), $e->legalCase?->case_number, $e->user?->name);
        }

        // Clientes
        $clients = Client::query()->visibleTo($user)->withCount('cases')->orderBy('name')->limit(40)->get();
        $out[] = "\n## Clientes ({$clients->count()})";
        foreach ($clients as $cl) {
            $out[] = sprintf('- %s | %s | Tel: %s | Email: %s | Casos: %d', $cl->name, $cl->document_label ?: '—', $cl->phone ?: '—', $cl->email ?: '—', $cl->cases_count);
        }

        return implode("\n", $out);
    }
}

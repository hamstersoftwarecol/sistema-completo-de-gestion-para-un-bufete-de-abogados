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
use Illuminate\Support\Str;

/**
 * Asistente sin conexión: responde consultas frecuentes en lenguaje natural
 * usando los datos del bufete cuando no hay una clave de API configurada.
 */
class LocalAssistant
{
    public function reply(User $user, string $message): string
    {
        $q = Str::lower(Str::ascii($message));

        // ¿Menciona un número de caso concreto?
        $case = LegalCase::query()->visibleTo($user)->get(['id', 'case_number'])
            ->first(fn ($c) => Str::contains($q, Str::lower(Str::ascii($c->case_number))));
        if ($case) {
            return $this->caseSummary(LegalCase::find($case->id));
        }

        return match (true) {
            Str::contains($q, ['audiencia', 'diligencia', 'juicio']) => $this->hearings($user, $q),
            Str::contains($q, ['cita', 'agenda', 'reunion', 'consulta']) => $this->appointments($user),
            Str::contains($q, ['gasto', 'reembolso', 'viatico']) => $this->expenses($user),
            Str::contains($q, ['pago', 'saldo', 'deuda', 'debe', 'cobrar', 'honorario', 'ingreso', 'factur', 'cartera']) => $this->finances($user),
            Str::contains($q, ['cliente']) => $this->clients($user),
            Str::contains($q, ['caso', 'expediente', 'proceso', 'asunto']) => $this->cases($user),
            default => $this->help($user),
        };
    }

    private function hearings(User $user, string $q): string
    {
        [$from, $to, $label] = match (true) {
            Str::contains($q, 'hoy') => [now()->startOfDay(), now()->endOfDay(), 'hoy'],
            Str::contains($q, ['manana']) => [now()->addDay()->startOfDay(), now()->addDay()->endOfDay(), 'mañana'],
            Str::contains($q, ['semana']) => [now(), now()->addDays(7), 'los próximos 7 días'],
            Str::contains($q, ['mes']) => [now(), now()->addDays(30), 'los próximos 30 días'],
            default => [now(), now()->addDays(30), 'los próximos 30 días'],
        };

        $items = Hearing::query()->visibleTo($user)->with('legalCase.client')
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at')->limit(15)->get();

        if ($items->isEmpty()) {
            return "No tiene audiencias programadas para {$label}. ✅";
        }

        $lines = $items->map(fn (Hearing $h) => sprintf(
            '- **%s** — %s · Caso `%s` (%s) · %s · _%s_',
            $h->scheduled_at->translatedFormat('D d/m h:i a'),
            $h->title,
            $h->legalCase?->case_number,
            $h->legalCase?->client?->name,
            $h->location ?: 'Lugar por definir',
            $h->status->label(),
        ));

        return "### Audiencias para {$label} ({$items->count()})\n\n".$lines->implode("\n");
    }

    private function appointments(User $user): string
    {
        $items = Appointment::query()->visibleTo($user)->with('client')
            ->where('starts_at', '>=', now()->startOfDay())
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value, AppointmentStatus::Completed->value])
            ->orderBy('starts_at')->limit(10)->get();

        if ($items->isEmpty()) {
            return 'No tiene citas próximas agendadas.';
        }

        return "### Próximas citas\n\n".$items->map(fn (Appointment $a) => sprintf(
            '- **%s** — %s con %s (%s, %s)',
            $a->starts_at->translatedFormat('D d/m h:i a'),
            $a->title,
            $a->who,
            $a->mode->label(),
            $a->status->label(),
        ))->implode("\n");
    }

    private function finances(User $user): string
    {
        $payments = Payment::query()->visibleTo($user);
        $month = (clone $payments)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $year = (clone $payments)->whereYear('paid_at', now()->year)->sum('amount');

        $cases = LegalCase::query()->visibleTo($user)->open()->with('client')->withSum('payments', 'amount')->get()
            ->map(fn ($c) => [$c, max(0, (float) $c->fee_amount - (float) $c->payments_sum_amount)])
            ->filter(fn ($row) => $row[1] > 0)
            ->sortByDesc(fn ($row) => $row[1])
            ->take(8);

        $text = "### Resumen financiero\n\n- Ingresos de este mes: **".money($month)."**\n- Ingresos del año: **".money($year)."**\n";
        $text .= '- Saldo pendiente por cobrar en casos activos: **'.money($cases->sum(fn ($r) => $r[1]))."**\n";

        if ($cases->isNotEmpty()) {
            $text .= "\n#### Casos con mayor saldo pendiente\n\n";
            $text .= $cases->map(fn ($r) => sprintf('- `%s` %s (%s): %s', $r[0]->case_number, $r[0]->title, $r[0]->client?->name, money($r[1])))->implode("\n");
        }

        return $text;
    }

    private function expenses(User $user): string
    {
        $items = Expense::query()->visibleTo($user)->with(['legalCase', 'user'])
            ->where('status', ExpenseStatus::Pending->value)->latest()->limit(10)->get();

        if ($items->isEmpty()) {
            return 'No hay gastos pendientes de aprobación. ✅';
        }

        return "### Gastos pendientes de aprobación\n\n".$items->map(fn (Expense $e) => sprintf(
            '- %s — %s · %s · Caso `%s` · registrado por %s',
            fdate($e->expense_date),
            $e->description,
            money($e->amount),
            $e->legalCase?->case_number,
            $e->user?->name,
        ))->implode("\n");
    }

    private function clients(User $user): string
    {
        $count = Client::query()->visibleTo($user)->count();
        $recent = Client::query()->visibleTo($user)->withCount('cases')->latest()->limit(8)->get();

        return "Tiene **{$count} clientes**. Los más recientes:\n\n".$recent
            ->map(fn ($c) => "- {$c->name} — {$c->cases_count} caso(s)".($c->phone ? " · ☎ {$c->phone}" : ''))
            ->implode("\n");
    }

    private function cases(User $user): string
    {
        $cases = LegalCase::query()->visibleTo($user)->with(['status', 'client'])->latest('updated_at')->get();
        $open = $cases->filter(fn ($c) => ! $c->isClosed());

        $byStatus = $cases->groupBy(fn ($c) => $c->status?->name ?? 'Sin estado')
            ->map(fn ($g, $name) => "- {$name}: {$g->count()}")->implode("\n");

        return "Tiene **{$cases->count()} casos** ({$open->count()} activos).\n\n#### Por estado\n{$byStatus}\n\n#### Actualizados recientemente\n"
            .$open->take(6)->map(fn ($c) => "- `{$c->case_number}` {$c->title} — {$c->client?->name}")->implode("\n");
    }

    private function caseSummary(LegalCase $case): string
    {
        $case->load(['client', 'status', 'type', 'court', 'lawyer', 'hearings', 'parties']);
        $next = $case->hearings->where('scheduled_at', '>=', now())->sortBy('scheduled_at')->first();

        return "### Caso {$case->case_number}\n\n"
            ."**{$case->title}**\n\n"
            ."- Cliente: {$case->client?->name}\n"
            .'- Tipo: '.($case->type?->name ?? '—')."\n"
            .'- Estado: '.($case->status?->name ?? '—')."\n"
            .'- Juzgado: '.($case->court?->name ?? '—').' · Juez: '.($case->judge ?: '—')."\n"
            .'- Responsable: '.($case->lawyer?->name ?? '—')."\n"
            .'- Partes: '.($case->parties->map(fn ($p) => "{$p->name} ({$p->role_label})")->implode(', ') ?: '—')."\n"
            .'- Honorarios: '.money($case->fee_amount).' · Saldo: '.money($case->balance())."\n"
            .'- Próxima audiencia: '.($next ? $next->scheduled_at->translatedFormat('D d/m/Y h:i a').' — '.$next->title : 'ninguna programada');
    }

    private function help(User $user): string
    {
        $first = Str::before($user->name, ' ');

        return "¡Hola, {$first}! Estoy funcionando en **modo sin conexión** porque no hay una clave de API de Gemini u OpenAI configurada "
            ."(el administrador puede añadirla en *Configuración → Inteligencia artificial*).\n\n"
            ."Aun así puedo responder consultas sobre sus datos, por ejemplo:\n\n"
            ."- «¿Qué audiencias tengo esta semana?»\n"
            ."- «¿Tengo citas pendientes?»\n"
            ."- «¿Cuánto me deben los clientes?»\n"
            ."- «Gastos pendientes de aprobación»\n"
            .'- «Resumen de mis casos» o escribiendo un número de caso.';
    }
}

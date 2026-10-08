<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\ExpenseStatus;
use App\Models\Appointment;
use App\Models\CaseStatus;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\Payment;
use App\Services\Notifier;
use App\Support\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Recordatorios de audiencias (funciona aunque no haya cron configurado en XAMPP).
        Notifier::sendHearingReminders();

        $totalCases = LegalCase::query()->visibleTo($user)->count();
        $openCases = LegalCase::query()->visibleTo($user)->open()->count();

        $payments = Payment::query()->visibleTo($user);
        $expenses = Expense::query()->visibleTo($user);

        $receivable = LegalCase::query()->visibleTo($user)->open()
            ->withSum('payments', 'amount')
            ->get(['id', 'fee_amount'])
            ->sum(fn ($c) => max(0, (float) $c->fee_amount - (float) $c->payments_sum_amount));

        $stats = [
            'clients' => Client::query()->visibleTo($user)->count(),
            'open_cases' => $openCases,
            'closed_cases' => $totalCases - $openCases,
            'hearings_week' => Hearing::query()->visibleTo($user)->upcoming()->where('scheduled_at', '<=', now()->addDays(7))->count(),
            'appointments_today' => Appointment::query()->visibleTo($user)->whereDate('starts_at', today())
                ->whereNot('status', AppointmentStatus::Cancelled->value)->count(),
            'income_month' => (clone $payments)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'income_year' => (clone $payments)->whereYear('paid_at', now()->year)->sum('amount'),
            'expenses_month' => (clone $expenses)->whereIn('status', [ExpenseStatus::Approved->value, ExpenseStatus::Reimbursed->value])
                ->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'pending_expenses' => (clone $expenses)->where('status', ExpenseStatus::Pending->value)->count(),
            'receivable' => $receivable,
        ];

        // Ingresos y gastos de los últimos 12 meses.
        $start = now()->subMonths(11)->startOfMonth();
        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonths($i));

        $incomeRows = (clone $payments)->where('paid_at', '>=', $start)->get(['amount', 'paid_at'])
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'))->map->sum('amount');
        $expenseRows = (clone $expenses)->where('expense_date', '>=', $start)
            ->whereIn('status', [ExpenseStatus::Approved->value, ExpenseStatus::Reimbursed->value])
            ->get(['amount', 'expense_date'])
            ->groupBy(fn ($e) => $e->expense_date->format('Y-m'))->map->sum('amount');

        $charts = [
            'months' => $months->map(fn ($m) => ucfirst($m->translatedFormat('M y')))->all(),
            'income' => $months->map(fn ($m) => round((float) ($incomeRows[$m->format('Y-m')] ?? 0), 2))->all(),
            'expenses' => $months->map(fn ($m) => round((float) ($expenseRows[$m->format('Y-m')] ?? 0), 2))->all(),
            'status' => CaseStatus::query()->ordered()
                ->withCount(['cases' => fn ($q) => $q->visibleTo($user)])->get()
                ->filter(fn ($s) => $s->cases_count > 0)
                ->map(fn ($s) => ['label' => $s->name, 'value' => $s->cases_count, 'color' => Badge::hex($s->color)])
                ->values()->all(),
            'types' => CaseType::query()
                ->withCount(['cases' => fn ($q) => $q->visibleTo($user)])->get()
                ->filter(fn ($t) => $t->cases_count > 0)
                ->sortByDesc('cases_count')
                ->map(fn ($t) => ['label' => $t->name, 'value' => $t->cases_count])
                ->values()->all(),
        ];

        $upcomingHearings = Hearing::query()->visibleTo($user)->upcoming()
            ->with(['legalCase.client', 'court'])->limit(6)->get();

        $todayAppointments = Appointment::query()->visibleTo($user)
            ->with(['client', 'user'])
            ->whereDate('starts_at', today())
            ->orderBy('starts_at')->get();

        $recentCases = LegalCase::query()->visibleTo($user)
            ->with(['client', 'status', 'lawyer'])
            ->latest()->limit(6)->get();

        $approvals = Expense::query()->with(['legalCase', 'user'])
            ->where('status', ExpenseStatus::Pending->value)
            ->latest()->limit(20)->get()
            ->filter(fn ($e) => Gate::allows('approve', $e))
            ->take(5);

        return view('dashboard', compact('stats', 'charts', 'upcomingHearings', 'todayAppointments', 'recentCases', 'approvals'));
    }
}

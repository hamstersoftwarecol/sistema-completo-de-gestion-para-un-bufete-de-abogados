<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Gastos del caso con flujo de aprobación y reembolso:
 * pendiente → aprobado / rechazado → reembolsado.
 */
class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Expense::query()->visibleTo($user)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('lawyer'), fn ($q) => $q->where('user_id', $request->integer('lawyer')))
            ->when($request->input('view') === 'approvals', fn ($q) => $q->where('status', ExpenseStatus::Pending->value))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('description', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('legalCase', fn ($c) => $c->search($request->input('q')))));

        $totals = (clone $query)->selectRaw('status, SUM(amount) as total, COUNT(*) as n')->groupBy('status')->get()->keyBy(fn ($r) => $r->status->value);

        $expenses = $query->with(['legalCase.client', 'user', 'reviewer'])
            ->latest('expense_date')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'totals' => $totals,
            'lawyers' => User::query()->lawyers()->get(),
        ]);
    }

    public function create(Request $request)
    {
        $expense = new Expense([
            'legal_case_id' => $request->integer('case_id') ?: null,
            'expense_date' => today(),
            'billable' => true,
        ]);

        return view('expenses.create', $this->formData($request) + ['expense' => $expense]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['status'] = ExpenseStatus::Pending;

        $expense = Expense::create($data);
        $this->storeReceipt($request, $expense);

        Notifier::expenseSubmitted($expense->load(['legalCase.lawyer', 'user']));

        return redirect()->route('expenses.show', $expense)->with('success', 'Gasto registrado y enviado para aprobación.');
    }

    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);
        $expense->load(['legalCase.client', 'user', 'reviewer', 'reimburser']);

        return view('expenses.show', compact('expense'));
    }

    public function edit(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);
        abort_unless($expense->isPending(), 403, 'Sólo se pueden editar gastos pendientes.');

        return view('expenses.edit', $this->formData($request) + ['expense' => $expense]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);
        abort_unless($expense->isPending(), 403, 'Sólo se pueden editar gastos pendientes.');

        $expense->update($this->validated($request));
        $this->storeReceipt($request, $expense);

        return redirect()->route('expenses.show', $expense)->with('success', 'Gasto actualizado.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        if ($expense->status === ExpenseStatus::Reimbursed) {
            return back()->with('error', 'No se puede eliminar un gasto ya reembolsado.');
        }

        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Gasto eliminado.');
    }

    public function approve(Request $request, Expense $expense)
    {
        return $this->review($request, $expense, ExpenseStatus::Approved);
    }

    public function reject(Request $request, Expense $expense)
    {
        $request->validate(['review_notes' => ['required', 'string', 'max:2000']], [
            'review_notes.required' => 'Indique el motivo del rechazo.',
        ]);

        return $this->review($request, $expense, ExpenseStatus::Rejected);
    }

    public function reimburse(Request $request, Expense $expense)
    {
        $this->authorize('reimburse', $expense);

        if ($expense->status !== ExpenseStatus::Approved) {
            return back()->with('error', 'Sólo se pueden reembolsar gastos aprobados.');
        }

        $expense->update([
            'status' => ExpenseStatus::Reimbursed,
            'reimbursed_by' => $request->user()->id,
            'reimbursed_at' => now(),
        ]);

        Notifier::expenseReviewed($expense->load('user'));

        return back()->with('success', 'Gasto marcado como reembolsado.');
    }

    public function receipt(Expense $expense)
    {
        $this->authorize('view', $expense);
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        $path = Storage::disk('local')->path($expense->receipt_path);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="'.Str::ascii($expense->receipt_original_name ?? 'soporte').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function review(Request $request, Expense $expense, ExpenseStatus $status)
    {
        $this->authorize('approve', $expense);

        if (! $expense->isPending()) {
            return back()->with('error', 'Este gasto ya fue revisado.');
        }

        $expense->update([
            'status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        Notifier::expenseReviewed($expense->load('user'));

        return back()->with('success', 'Gasto '.mb_strtolower($status->label()).'.');
    }

    private function storeReceipt(Request $request, Expense $expense): void
    {
        if (! $request->hasFile('receipt')) {
            return;
        }

        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }

        $file = $request->file('receipt');
        $path = $file->storeAs('expenses', Str::uuid().'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension()), 'local');

        $expense->update([
            'receipt_path' => $path,
            'receipt_original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
        ]);
    }

    private function formData(Request $request): array
    {
        return [
            'cases' => LegalCase::query()->visibleTo($request->user())->with('client:id,name')->orderBy('case_number')
                ->get(['id', 'case_number', 'title', 'client_id']),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'legal_case_id' => ['required', 'exists:legal_cases,id'],
            'category' => ['required', Rule::in(array_keys(config('bufete.expense_categories')))],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'billable' => ['nullable', 'boolean'],
            'receipt' => ['nullable', 'file', 'max:10240', 'mimes:'.config('bufete.receipt_mimes')],
        ]);

        $case = LegalCase::findOrFail($data['legal_case_id']);
        abort_unless(Gate::allows('view', $case), 403, 'No tiene acceso a este caso.');

        $data['billable'] = $request->boolean('billable');

        return collect($data)->except('receipt')->all();
    }
}

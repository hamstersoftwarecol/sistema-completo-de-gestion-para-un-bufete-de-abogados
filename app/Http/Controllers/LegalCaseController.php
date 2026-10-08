<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Models\CaseStatus;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Court;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LegalCaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $cases = LegalCase::query()->visibleTo($user)
            ->search($request->input('q'))
            ->when($request->filled('status'), fn ($q) => $q->where('case_status_id', $request->integer('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('case_type_id', $request->integer('type')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->input('priority')))
            ->when($request->filled('lawyer'), fn ($q) => $q->where(fn ($w) => $w
                ->where('lawyer_id', $request->integer('lawyer'))->orWhere('assistant_id', $request->integer('lawyer'))))
            ->when($request->input('scope', 'open') === 'open', fn ($q) => $q->open())
            ->when($request->input('scope') === 'closed', fn ($q) => $q->whereHas('status', fn ($s) => $s->where('is_closed', true)))
            ->with(['client', 'status', 'type', 'lawyer', 'assistant', 'court'])
            ->withSum('payments', 'amount')
            ->withCount(['hearings as upcoming_hearings_count' => fn ($q) => $q->where('scheduled_at', '>=', now())])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('cases.index', [
            'cases' => $cases,
            'statuses' => CaseStatus::query()->ordered()->get(),
            'types' => CaseType::query()->orderBy('name')->get(),
            'lawyers' => User::query()->lawyers()->get(),
        ]);
    }

    public function create(Request $request)
    {
        $case = new LegalCase([
            'client_id' => $request->integer('client_id') ?: null,
            'lawyer_id' => $request->user()->isJunior() ? null : $request->user()->id,
            'assistant_id' => $request->user()->isJunior() ? $request->user()->id : null,
            'priority' => Priority::Medium,
            'filing_date' => today(),
            'case_status_id' => CaseStatus::query()->ordered()->value('id'),
        ]);

        return view('cases.create', $this->formData($request) + ['case' => $case]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $case = DB::transaction(function () use ($request, $data) {
            $case = LegalCase::create($data);
            DocumentController::storeUploads($request, $case);

            return $case;
        });

        Notifier::caseAssigned($case->load(['lawyer', 'assistant']), $request->user());

        return redirect()->route('cases.show', $case)->with('success', "Caso {$case->case_number} creado correctamente.");
    }

    /**
     * Expediente del caso.
     */
    public function show(LegalCase $case)
    {
        $this->authorize('view', $case);

        $case->load([
            'client', 'type', 'status', 'court', 'lawyer', 'assistant',
            'parties',
            'notes' => fn ($q) => $q->with('user')->orderByDesc('is_pinned')->latest(),
            'documents' => fn ($q) => $q->with('user')->latest(),
            'hearings' => fn ($q) => $q->with(['user', 'court'])->orderByDesc('scheduled_at'),
            'appointments' => fn ($q) => $q->with('user')->latest('starts_at'),
            'payments' => fn ($q) => $q->with('user')->latest('paid_at'),
            'expenses' => fn ($q) => $q->with(['user', 'reviewer'])->latest('expense_date'),
        ]);

        $paid = (float) $case->payments->sum('amount');
        $finance = [
            'fee' => (float) $case->fee_amount,
            'paid' => $paid,
            'balance' => max(0, (float) $case->fee_amount - $paid),
            'expenses' => (float) $case->expenses->whereIn('status.value', ['aprobado', 'reembolsado'])->sum('amount'),
            'expenses_pending' => (float) $case->expenses->where('status.value', 'pendiente')->sum('amount'),
            'progress' => $case->fee_amount > 0 ? min(100, round($paid / (float) $case->fee_amount * 100)) : 0,
        ];

        return view('cases.show', compact('case', 'finance'));
    }

    public function edit(Request $request, LegalCase $case)
    {
        $this->authorize('update', $case);

        return view('cases.edit', $this->formData($request) + ['case' => $case]);
    }

    public function update(Request $request, LegalCase $case)
    {
        $this->authorize('update', $case);

        $before = [$case->lawyer_id, $case->assistant_id];
        $data = $this->validated($request, $case);
        $case->update($data);

        if ($before !== [$case->lawyer_id, $case->assistant_id]) {
            Notifier::caseAssigned($case->load(['lawyer', 'assistant']), $request->user());
        }

        return redirect()->route('cases.show', $case)->with('success', 'Caso actualizado.');
    }

    /**
     * Protección de eliminación: no se borra un caso con registros vinculados.
     */
    public function destroy(LegalCase $case)
    {
        $this->authorize('delete', $case);

        $linked = $case->linkedRecords();

        if ($linked) {
            return back()->with('error', "No se puede eliminar el caso {$case->case_number} porque tiene registros vinculados: "
                .collect($linked)->map(fn ($n, $k) => "{$n} {$k}")->implode(', ')
                .'. Elimínelos primero o cambie el estado del caso a uno de cierre/archivo.');
        }

        $case->delete();

        return redirect()->route('cases.index')->with('success', 'Caso eliminado.');
    }

    /**
     * Verificación en vivo de número de caso duplicado (AJAX).
     */
    public function checkNumber(Request $request)
    {
        $number = trim((string) $request->input('number'));

        if ($number === '') {
            return response()->json(['exists' => false]);
        }

        $existing = LegalCase::query()
            ->with('client')
            ->whereRaw('LOWER(case_number) = ?', [mb_strtolower($number)])
            ->when($request->filled('ignore'), fn ($q) => $q->whereKeyNot($request->integer('ignore')))
            ->first();

        if (! $existing) {
            return response()->json(['exists' => false]);
        }

        $canView = $request->user()->can('view', $existing);

        return response()->json([
            'exists' => true,
            'message' => $canView
                ? "El número {$existing->case_number} ya está registrado: «{$existing->title}» ({$existing->client?->name})."
                : "El número {$existing->case_number} ya está registrado en el bufete por otro abogado.",
            'url' => $canView ? route('cases.show', $existing) : null,
        ]);
    }

    /**
     * Informe imprimible del caso.
     */
    public function print(LegalCase $case)
    {
        $this->authorize('view', $case);

        $case->load([
            'client', 'type', 'status', 'court', 'lawyer', 'assistant', 'parties',
            'notes' => fn ($q) => $q->with('user')->latest(),
            'documents', 'hearings' => fn ($q) => $q->orderBy('scheduled_at'),
            'payments' => fn ($q) => $q->orderBy('paid_at'),
            'expenses' => fn ($q) => $q->orderBy('expense_date'),
        ]);

        return view('cases.print', compact('case'));
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            'clients' => Client::query()->visibleTo($user)->orderBy('name')->get(['id', 'name', 'document_number']),
            'types' => CaseType::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => CaseStatus::query()->ordered()->get(),
            'courts' => Court::query()->orderBy('name')->get(),
            'lawyers' => User::query()->lawyers()->get(),
        ];
    }

    private function validated(Request $request, ?LegalCase $case = null): array
    {
        $user = $request->user();
        $request->merge(['case_number' => trim((string) $request->input('case_number'))]);

        $validator = validator($request->all(), [
            'case_number' => ['required', 'string', 'max:80', Rule::unique('legal_cases', 'case_number')->ignore($case?->id)],
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'exists:clients,id'],
            'case_type_id' => ['nullable', 'exists:case_types,id'],
            'case_status_id' => ['required', 'exists:case_statuses,id'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'judge' => ['nullable', 'string', 'max:255'],
            'lawyer_id' => ['nullable', 'exists:users,id'],
            'assistant_id' => ['nullable', 'exists:users,id', 'different:lawyer_id'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'filing_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:10000'],
            'fee_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'fee_notes' => ['nullable', 'string', 'max:255'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'max:'.config('bufete.upload_max_kb'), 'mimes:'.config('bufete.document_mimes')],
        ], [
            'case_number.unique' => 'Este número de caso ya está registrado en el sistema (verificación de duplicados).',
            'assistant_id.different' => 'El asistente debe ser distinto del abogado responsable.',
        ]);

        $validator->after(function (Validator $v) use ($request, $user) {
            if (! $user->isSuperadmin() && ! in_array($user->id, [(int) $request->input('lawyer_id'), (int) $request->input('assistant_id')], true)) {
                $v->errors()->add('lawyer_id', 'Debe quedar asignado al caso como responsable o asistente.');
            }

            if ($request->filled('client_id') && ! Client::query()->visibleTo($user)->whereKey($request->input('client_id'))->exists()) {
                $v->errors()->add('client_id', 'No tiene acceso a este cliente.');
            }
        });

        $data = collect($validator->validate())->except('documents')->all();
        $data['fee_amount'] = $data['fee_amount'] ?? 0;

        $status = CaseStatus::find($data['case_status_id']);
        if ($status?->is_closed) {
            $data['closed_at'] = $case?->closed_at ?? today();
        } else {
            $data['closed_at'] = null;
        }

        return $data;
    }
}

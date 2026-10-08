<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Payment;
use App\Support\NumberToWords;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Payment::query()->visibleTo($user)
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('receipt_number', 'like', '%'.$request->input('q').'%')
                ->orWhere('concept', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%'.$request->input('q').'%'))))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->input('method')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('paid_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('paid_at', '<=', $request->date('to')));

        $total = (clone $query)->sum('amount');

        $payments = $query->with(['client', 'legalCase', 'user'])
            ->latest('paid_at')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', compact('payments', 'total'));
    }

    public function create(Request $request)
    {
        $appointment = $request->filled('appointment_id')
            ? Appointment::query()->visibleTo($request->user())->find($request->integer('appointment_id'))
            : null;
        $case = $request->filled('case_id')
            ? LegalCase::query()->visibleTo($request->user())->find($request->integer('case_id'))
            : null;

        $payment = new Payment([
            'client_id' => $case?->client_id ?? $appointment?->client_id ?? ($request->integer('client_id') ?: null),
            'legal_case_id' => $case?->id ?? $appointment?->legal_case_id,
            'appointment_id' => $appointment?->id,
            'amount' => $appointment ? max(0, (float) $appointment->fee - $appointment->amountPaid()) : ($case ? $case->balance() : null),
            'paid_at' => today(),
            'method' => PaymentMethod::Cash,
            'concept' => $appointment ? 'Consulta: '.$appointment->title : ($case ? 'Abono honorarios caso '.$case->case_number : ''),
        ]);

        return view('payments.create', $this->formData($request) + ['payment' => $payment]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;

        // Reintenta si dos usuarios generan el mismo número de recibo a la vez.
        $payment = retry(3, function () use ($data) {
            return Payment::create($data + ['receipt_number' => Payment::nextReceiptNumber()]);
        }, 50, fn ($e) => $e instanceof QueryException);

        return redirect()->route('payments.show', $payment)
            ->with('success', "Pago registrado. Recibo {$payment->receipt_number}.");
    }

    /**
     * Recibo imprimible.
     */
    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);
        $payment->load(['client', 'legalCase', 'user', 'appointment']);

        $caseTotals = null;
        if ($payment->legalCase) {
            $paidToDate = $payment->legalCase->payments()
                ->where(fn ($q) => $q->where('paid_at', '<', $payment->paid_at)
                    ->orWhere(fn ($w) => $w->where('paid_at', $payment->paid_at)->where('id', '<=', $payment->id)))
                ->sum('amount');
            $caseTotals = [
                'fee' => (float) $payment->legalCase->fee_amount,
                'paid' => (float) $paidToDate,
                'balance' => max(0, (float) $payment->legalCase->fee_amount - (float) $paidToDate),
            ];
        }

        $amountInWords = NumberToWords::convert((float) $payment->amount, setting('currency_words', 'PESOS'), setting('currency_suffix', 'M/CTE'));

        return view('payments.receipt', compact('payment', 'caseTotals', 'amountInWords'));
    }

    public function edit(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        return view('payments.edit', $this->formData($request) + ['payment' => $payment]);
    }

    public function update(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);
        $payment->update($this->validated($request));

        return redirect()->route('payments.show', $payment)->with('success', 'Pago actualizado.');
    }

    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);
        $payment->delete();

        return redirect()->route('payments.index')->with('success', "Pago {$payment->receipt_number} eliminado.");
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            'clients' => Client::query()->visibleTo($user)->orderBy('name')->get(['id', 'name']),
            'cases' => LegalCase::query()->visibleTo($user)->withSum('payments', 'amount')->orderBy('case_number')
                ->get(['id', 'case_number', 'title', 'client_id', 'fee_amount']),
            'appointments' => Appointment::query()->visibleTo($user)->whereNotNull('client_id')->where('fee', '>', 0)
                ->latest('starts_at')->limit(100)->get(['id', 'title', 'client_id', 'starts_at', 'fee']),
        ];
    }

    private function validated(Request $request): array
    {
        $user = $request->user();

        $validator = validator($request->all(), [
            'client_id' => ['required', 'exists:clients,id'],
            'legal_case_id' => ['nullable', 'exists:legal_cases,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'concept' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function (Validator $v) use ($request, $user) {
            if (! Client::query()->visibleTo($user)->whereKey($request->input('client_id'))->exists()) {
                $v->errors()->add('client_id', 'No tiene acceso a este cliente.');
            }
            if ($request->filled('legal_case_id')) {
                $case = LegalCase::find($request->input('legal_case_id'));
                if ($case && (int) $case->client_id !== (int) $request->input('client_id')) {
                    $v->errors()->add('legal_case_id', 'El caso seleccionado no pertenece al cliente.');
                }
                if ($case && ! $user->can('view', $case)) {
                    $v->errors()->add('legal_case_id', 'No tiene acceso a este caso.');
                }
            }
            if ($request->filled('appointment_id')) {
                $appointment = Appointment::find($request->input('appointment_id'));
                if ($appointment && (int) $appointment->client_id !== (int) $request->input('client_id')) {
                    $v->errors()->add('appointment_id', 'La cita seleccionada no pertenece al cliente.');
                }
            }
        });

        return $validator->validate();
    }
}

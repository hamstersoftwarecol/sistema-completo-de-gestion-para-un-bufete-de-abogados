<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentMode;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $range = $request->input('range', 'upcoming');

        $appointments = Appointment::query()->visibleTo($user)
            ->with(['client', 'legalCase', 'user'])
            ->withSum('payments', 'amount')
            ->when($range === 'today', fn ($q) => $q->whereDate('starts_at', today())->orderBy('starts_at'))
            ->when($range === 'upcoming', fn ($q) => $q->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at'))
            ->when($range === 'past', fn ($q) => $q->where('starts_at', '<', now()->startOfDay())->orderByDesc('starts_at'))
            ->when($range === 'all', fn ($q) => $q->orderByDesc('starts_at'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('lawyer'), fn ($q) => $q->where('user_id', $request->integer('lawyer')))
            ->paginate(20)
            ->withQueryString();

        return view('appointments.index', [
            'appointments' => $appointments,
            'range' => $range,
            'lawyers' => User::query()->lawyers()->get(),
        ]);
    }

    public function create(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : now()->addDay()->setTime(10, 0);
        if ($date->format('H:i') === '00:00') {
            $date->setTime(10, 0);
        }

        $appointment = new Appointment([
            'client_id' => $request->integer('client_id') ?: null,
            'legal_case_id' => $request->integer('case_id') ?: null,
            'user_id' => $request->user()->id,
            'starts_at' => $date,
            'duration_minutes' => 30,
            'mode' => AppointmentMode::InPerson,
            'status' => AppointmentStatus::Pending,
            'fee' => 0,
        ]);

        return view('appointments.create', $this->formData($request) + ['appointment' => $appointment]);
    }

    public function store(Request $request)
    {
        $appointment = Appointment::create($this->validated($request));

        return $this->redirectAfterSave($request, $appointment, 'Cita reservada correctamente.');
    }

    public function edit(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        return view('appointments.edit', $this->formData($request) + [
            'appointment' => $appointment->load(['payments', 'client']),
        ]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $appointment->update($this->validated($request, $appointment));

        return $this->redirectAfterSave($request, $appointment, 'Cita actualizada.');
    }

    public function status(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);
        $data = $request->validate(['status' => ['required', Rule::enum(AppointmentStatus::class)]]);
        $appointment->update($data);

        return back()->with('success', 'Estado de la cita: '.$appointment->status->label().'.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        if ($appointment->payments()->exists()) {
            return back()->with('error', 'No se puede eliminar la cita porque tiene pagos registrados. Puede marcarla como cancelada.');
        }

        $appointment->delete();

        return redirect()->route('appointments.index')->with('success', 'Cita eliminada.');
    }

    private function redirectAfterSave(Request $request, Appointment $appointment, string $message)
    {
        $target = match ($request->input('redirect')) {
            'calendar' => route('calendar.index'),
            default => route('appointments.index'),
        };

        return redirect()->to($target)->with('success', $message);
    }

    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            'clients' => Client::query()->visibleTo($user)->orderBy('name')->get(['id', 'name']),
            'cases' => LegalCase::query()->visibleTo($user)->with('client:id,name')->orderBy('case_number')->get(['id', 'case_number', 'title', 'client_id']),
            'lawyers' => User::query()->lawyers()->get(),
        ];
    }

    private function validated(Request $request, ?Appointment $appointment = null): array
    {
        $user = $request->user();

        $validator = validator($request->all(), [
            'client_id' => ['nullable', 'exists:clients,id'],
            'legal_case_id' => ['nullable', 'exists:legal_cases,id'],
            'contact_name' => ['nullable', 'required_without:client_id', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'mode' => ['required', Rule::enum(AppointmentMode::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['required', 'exists:users,id'],
        ], [
            'contact_name.required_without' => 'Seleccione un cliente o escriba el nombre de la persona (prospecto).',
        ]);

        $validator->after(function (Validator $v) use ($request, $user, $appointment) {
            if ($request->filled('client_id') && ! Client::query()->visibleTo($user)->whereKey($request->input('client_id'))->exists()) {
                $v->errors()->add('client_id', 'No tiene acceso a este cliente.');
            }

            if ($request->filled('legal_case_id')) {
                $case = LegalCase::find($request->input('legal_case_id'));
                if ($case && $request->filled('client_id') && (int) $case->client_id !== (int) $request->input('client_id')) {
                    $v->errors()->add('legal_case_id', 'El caso seleccionado no pertenece al cliente.');
                }
                if ($case && ! $user->can('view', $case)) {
                    $v->errors()->add('legal_case_id', 'No tiene acceso a este caso.');
                }
            }

            // Evita reservar dos citas solapadas para el mismo abogado.
            if ($request->filled('starts_at') && $request->filled('user_id') && $request->input('status') !== AppointmentStatus::Cancelled->value) {
                $start = Carbon::parse($request->input('starts_at'));
                $end = $start->copy()->addMinutes((int) $request->input('duration_minutes', 30));

                $overlap = Appointment::query()
                    ->where('user_id', $request->integer('user_id'))
                    ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
                    ->when($appointment, fn ($q) => $q->whereKeyNot($appointment->id))
                    ->whereBetween('starts_at', [$start->copy()->subHours(10), $end])
                    ->get()
                    ->first(fn (Appointment $a) => $a->starts_at < $end && $a->ends_at > $start);

                if ($overlap) {
                    $v->errors()->add('starts_at', 'El abogado ya tiene una cita en ese horario: «'.$overlap->title.'» a las '.$overlap->starts_at->format('h:i a').'.');
                }
            }
        });

        $data = $validator->validate();
        $data['fee'] = $data['fee'] ?? 0;

        if (! empty($data['legal_case_id']) && empty($data['client_id'])) {
            $data['client_id'] = LegalCase::find($data['legal_case_id'])?->client_id;
        }

        return $data;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\HearingStatus;
use App\Models\Court;
use App\Models\Hearing;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class HearingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $range = $request->input('range', 'upcoming');

        $hearings = Hearing::query()->visibleTo($user)
            ->with(['legalCase.client', 'court', 'user'])
            ->when($range === 'upcoming', fn ($q) => $q->where('scheduled_at', '>=', now()->startOfDay())->orderBy('scheduled_at'))
            ->when($range === 'past', fn ($q) => $q->where('scheduled_at', '<', now()->startOfDay())->orderByDesc('scheduled_at'))
            ->when($range === 'all', fn ($q) => $q->orderByDesc('scheduled_at'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('lawyer'), fn ($q) => $q->where('user_id', $request->integer('lawyer')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->input('q').'%')
                ->orWhereHas('legalCase', fn ($c) => $c->search($request->input('q')))))
            ->paginate(20)
            ->withQueryString();

        return view('hearings.index', [
            'hearings' => $hearings,
            'range' => $range,
            'lawyers' => User::query()->lawyers()->get(),
        ]);
    }

    public function create(Request $request)
    {
        $case = $request->filled('case_id')
            ? LegalCase::query()->visibleTo($request->user())->find($request->integer('case_id'))
            : null;

        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : now()->addDay()->setTime(9, 0);
        if ($date->format('H:i') === '00:00') {
            $date->setTime(9, 0);
        }

        $hearing = new Hearing([
            'legal_case_id' => $case?->id,
            'court_id' => $case?->court_id,
            'judge' => $case?->judge,
            'user_id' => $case?->lawyer_id ?? $request->user()->id,
            'scheduled_at' => $date,
            'duration_minutes' => 60,
            'status' => HearingStatus::Scheduled,
        ]);

        return view('hearings.create', $this->formData($request) + ['hearing' => $hearing]);
    }

    public function store(Request $request)
    {
        $hearing = Hearing::create($this->validated($request));
        Notifier::hearingScheduled($hearing->load(['legalCase.lawyer', 'legalCase.assistant', 'user']), $request->user());

        return $this->redirectAfterSave($request, $hearing, 'Audiencia programada correctamente.');
    }

    public function edit(Request $request, Hearing $hearing)
    {
        $this->authorize('update', $hearing);

        return view('hearings.edit', $this->formData($request, $hearing) + ['hearing' => $hearing->load('legalCase.client')]);
    }

    public function update(Request $request, Hearing $hearing)
    {
        $this->authorize('update', $hearing);

        $oldDate = $hearing->scheduled_at;
        $hearing->update($this->validated($request, $hearing));

        if (! $hearing->scheduled_at->equalTo($oldDate)) {
            $hearing->forceFill(['reminder_sent_at' => null])->save();
            Notifier::hearingScheduled($hearing->load(['legalCase.lawyer', 'legalCase.assistant', 'user']), $request->user());
        }

        return $this->redirectAfterSave($request, $hearing, 'Audiencia actualizada.');
    }

    public function destroy(Hearing $hearing)
    {
        $this->authorize('delete', $hearing);
        $hearing->delete();

        return redirect()->route('hearings.index')->with('success', 'Audiencia eliminada.');
    }

    private function redirectAfterSave(Request $request, Hearing $hearing, string $message)
    {
        $target = match ($request->input('redirect')) {
            'calendar' => route('calendar.index'),
            'case' => route('cases.show', $hearing->legal_case_id).'#audiencias',
            default => route('hearings.index'),
        };

        return redirect()->to($target)->with('success', $message);
    }

    private function formData(Request $request, ?Hearing $hearing = null): array
    {
        $cases = LegalCase::query()->visibleTo($request->user())->open()->with('client')->orderBy('case_number')->get();

        // Si la audiencia pertenece a un caso cerrado, se mantiene en la lista.
        if ($hearing && ! $cases->contains('id', $hearing->legal_case_id)) {
            $cases->push($hearing->legalCase()->with('client')->first());
        }

        return [
            'cases' => $cases->filter(),
            'courts' => Court::query()->orderBy('name')->get(),
            'lawyers' => User::query()->lawyers()->get(),
        ];
    }

    private function validated(Request $request, ?Hearing $hearing = null): array
    {
        $data = $request->validate([
            'legal_case_id' => ['required', 'exists:legal_cases,id'],
            'title' => ['required', 'string', 'max:255'],
            'hearing_type' => ['nullable', 'string', 'max:60'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'court_id' => ['nullable', 'exists:courts,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'judge' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::enum(HearingStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'outcome' => ['nullable', 'string', 'max:5000'],
        ]);

        $case = LegalCase::findOrFail($data['legal_case_id']);
        $this->authorize('update', $case);

        return $data;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ClientType;
use App\Models\Client;
use App\Models\Document;
use App\Models\Hearing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $clients = Client::query()->visibleTo($user)
            ->search($request->input('q'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($user->isSuperadmin() && $request->filled('lawyer'), fn ($q) => $q->where('user_id', $request->integer('lawyer')))
            ->with('lawyer')
            ->withCount('cases')
            ->withSum('payments', 'amount')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $lawyers = User::query()->lawyers()->get();

        return view('clients.index', compact('clients', 'lawyers'));
    }

    public function create()
    {
        return view('clients.create', [
            'client' => new Client(['type' => ClientType::Person, 'user_id' => auth()->id()]),
            'lawyers' => User::query()->lawyers()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $client = Client::create($data);

        return redirect()->route('clients.show', $client)->with('success', 'Cliente registrado correctamente.');
    }

    /**
     * Vista 360 del cliente: casos, pagos, audiencias, documentos, citas y comunicaciones.
     */
    public function show(Request $request, Client $client)
    {
        $this->authorize('view', $client);
        $user = $request->user();

        $cases = $client->cases()->visibleTo($user)
            ->with(['status', 'type', 'lawyer', 'court'])
            ->withSum('payments', 'amount')
            ->latest()->get();

        $caseIds = $cases->pluck('id');

        $payments = $client->payments()->with(['legalCase', 'user'])
            ->where(fn ($q) => $q->whereIn('legal_case_id', $caseIds)->orWhereNull('legal_case_id'))
            ->latest('paid_at')->get();

        $hearings = Hearing::query()->whereIn('legal_case_id', $caseIds)
            ->with('legalCase')->orderByDesc('scheduled_at')->get();

        $documents = Document::query()->whereIn('legal_case_id', $caseIds)
            ->with(['legalCase', 'user'])->latest()->get();

        $appointments = $client->appointments()->visibleTo($user)->with('user')->latest('starts_at')->get();

        $communications = $client->communications()->with(['user', 'legalCase'])->latest()->limit(50)->get();

        $totals = [
            'fees' => $cases->sum('fee_amount'),
            'paid' => $payments->sum('amount'),
            'balance' => $cases->sum(fn ($c) => max(0, (float) $c->fee_amount - (float) $c->payments_sum_amount)),
            'open_cases' => $cases->filter(fn ($c) => ! $c->isClosed())->count(),
            'next_hearing' => $hearings->filter(fn ($h) => $h->scheduled_at->isFuture())->sortBy('scheduled_at')->first(),
        ];

        return view('clients.show', compact('client', 'cases', 'payments', 'hearings', 'documents', 'appointments', 'communications', 'totals'));
    }

    public function edit(Client $client)
    {
        $this->authorize('update', $client);

        return view('clients.edit', [
            'client' => $client,
            'lawyers' => User::query()->lawyers()->get(),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);
        $client->update($this->validated($request, $client));

        return redirect()->route('clients.show', $client)->with('success', 'Cliente actualizado.');
    }

    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        $linked = array_filter([
            'casos' => $client->cases()->count(),
            'pagos' => $client->payments()->count(),
            'citas' => $client->appointments()->count(),
        ]);

        if ($linked) {
            return back()->with('error', 'No se puede eliminar el cliente porque tiene registros vinculados: '
                .collect($linked)->map(fn ($n, $k) => "{$n} {$k}")->implode(', ').'.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente eliminado.');
    }

    private function validated(Request $request, ?Client $client = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(ClientType::class)],
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', Rule::in(array_keys(config('bufete.document_types')))],
            'document_number' => [
                'nullable', 'string', 'max:40',
                Rule::unique('clients', 'document_number')->ignore($client?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'alt_phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['nullable', 'exists:users,id'],
        ], [
            'document_number.unique' => 'Ya existe un cliente registrado con ese número de documento.',
        ]);

        // Sólo el administrador puede asignar el cliente a otro abogado.
        if (! $request->user()->isSuperadmin()) {
            $data['user_id'] = $client?->user_id ?? $request->user()->id;
        }

        return $data;
    }
}

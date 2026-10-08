<?php

namespace App\Http\Controllers;

use App\Enums\CommunicationType;
use App\Mail\ClientMessage;
use App\Models\Client;
use App\Models\Communication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Registro de llamadas / WhatsApp / reuniones y envío de correos al cliente.
 */
class ClientCommunicationController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $this->authorize('view', $client);

        $data = $request->validate([
            'type' => ['required', Rule::enum(CommunicationType::class)],
            'legal_case_id' => ['nullable', Rule::exists('legal_cases', 'id')->where('client_id', $client->id)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $client->communications()->create($data + ['user_id' => $request->user()->id]);

        return back()->with('success', 'Comunicación registrada.');
    }

    public function email(Request $request, Client $client)
    {
        $this->authorize('view', $client);

        if (blank($client->email)) {
            return back()->with('error', 'El cliente no tiene correo electrónico registrado.');
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'legal_case_id' => ['nullable', Rule::exists('legal_cases', 'id')->where('client_id', $client->id)],
        ]);

        try {
            Mail::to($client->email, $client->name)->send(new ClientMessage($data['subject'], $data['body'], $request->user()));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'No se pudo enviar el correo: revise la configuración SMTP (MAIL_*) del archivo .env.');
        }

        $client->communications()->create([
            'type' => CommunicationType::Email,
            'legal_case_id' => $data['legal_case_id'] ?? null,
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'body' => $data['body'],
        ]);

        return back()->with('success', "Correo enviado a {$client->email}.");
    }

    public function destroy(Communication $communication)
    {
        abort_unless($communication->user_id === auth()->id() || auth()->user()->isSuperadmin(), 403);
        $communication->delete();

        return back()->with('success', 'Registro eliminado.');
    }
}

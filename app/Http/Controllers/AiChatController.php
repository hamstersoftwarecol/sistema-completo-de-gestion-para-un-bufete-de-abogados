<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\Ai\AiAssistant;
use App\Services\Ai\AiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Asistente legal con IA (Gemini / ChatGPT) en lenguaje natural.
 * Las conversaciones son privadas de cada usuario.
 */
class AiChatController extends Controller
{
    public function index(Request $request)
    {
        $conversation = $request->user()->aiConversations()->latest('updated_at')->first();

        return $conversation
            ? redirect()->route('ai.show', $conversation)
            : $this->render($request, null);
    }

    public function show(Request $request, int $conversation)
    {
        return $this->render($request, $this->find($request, $conversation));
    }

    public function store(Request $request)
    {
        $conversation = $request->user()->aiConversations()->create(['title' => 'Nueva conversación']);

        return redirect()->route('ai.show', $conversation);
    }

    public function message(Request $request, AiAssistant $assistant)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:8000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $conversation = ! empty($data['conversation_id'])
            ? $this->find($request, $data['conversation_id'])
            : $user->aiConversations()->create(['title' => Str::limit($data['message'], 60)]);

        if ($conversation->messages()->doesntExist()) {
            $conversation->update(['title' => Str::limit(preg_replace('/\s+/', ' ', $data['message']), 60)]);
        }

        $userMessage = $conversation->messages()->create(['role' => 'user', 'content' => $data['message']]);

        try {
            $reply = $assistant->reply($user, $conversation);
        } catch (AiException $e) {
            return response()->json(['error' => $e->getMessage(), 'user_message' => $userMessage->toChatArray()], 422);
        } catch (ConnectionException $e) {
            return response()->json(['error' => 'No se pudo conectar con el proveedor de IA. Verifique la conexión a internet.', 'user_message' => $userMessage->toChatArray()], 422);
        }

        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply['content'],
            'provider' => $reply['provider'],
        ]);
        $conversation->touch();

        return response()->json([
            'conversation' => ['id' => $conversation->id, 'title' => $conversation->title, 'url' => route('ai.show', $conversation)],
            'user_message' => $userMessage->toChatArray(),
            'reply' => $assistantMessage->toChatArray(),
        ]);
    }

    public function rename(Request $request, int $conversation)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:120']]);
        $this->find($request, $conversation)->update($data);

        return back();
    }

    public function destroy(Request $request, int $conversation)
    {
        $this->find($request, $conversation)->delete();

        return redirect()->route('ai.index')->with('success', 'Conversación eliminada.');
    }

    public function instructions(Request $request)
    {
        $data = $request->validate(['ai_instructions' => ['nullable', 'string', 'max:4000']]);
        $request->user()->update($data);

        return back()->with('success', 'Instrucciones personalizadas guardadas.');
    }

    private function find(Request $request, int $id): AiConversation
    {
        return $request->user()->aiConversations()->findOrFail($id);
    }

    private function render(Request $request, ?AiConversation $conversation)
    {
        return view('ai.index', [
            'conversation' => $conversation?->load('messages'),
            'conversations' => $request->user()->aiConversations()->latest('updated_at')->get(),
            'provider' => AiAssistant::provider(),
            'providerLabel' => AiAssistant::providerLabel(),
        ]);
    }
}

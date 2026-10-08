<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Chat interno entre abogados del bufete (actualización por sondeo AJAX).
 */
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $contacts = User::query()->active()->whereKeyNot($me->id)->orderBy('name')->get();

        $unread = Message::query()->where('receiver_id', $me->id)->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as n')->groupBy('sender_id')->pluck('n', 'sender_id');

        $last = Message::query()
            ->where(fn ($q) => $q->where('sender_id', $me->id)->orWhere('receiver_id', $me->id))
            ->latest('id')->get()
            ->unique(fn ($m) => $m->sender_id === $me->id ? $m->receiver_id : $m->sender_id)
            ->keyBy(fn ($m) => $m->sender_id === $me->id ? $m->receiver_id : $m->sender_id);

        // Contactos con conversación reciente primero.
        $contacts = $contacts->sortByDesc(fn ($u) => $last[$u->id]?->id ?? 0)->values();

        $selected = $request->filled('user') ? $contacts->firstWhere('id', $request->integer('user')) : null;

        return view('chat.index', compact('contacts', 'unread', 'last', 'selected'));
    }

    public function messages(Request $request, User $user)
    {
        $me = $request->user();
        $after = $request->integer('after');

        $messages = Message::query()->between($me->id, $user->id)
            ->when($after, fn ($q) => $q->where('id', '>', $after))
            ->orderByDesc('id')
            ->limit($after ? 200 : 100)
            ->get()
            ->reverse()
            ->values();

        Message::query()->where('sender_id', $user->id)->where('receiver_id', $me->id)
            ->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => $m->toChatArray($me->id)),
        ]);
    }

    public function send(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'No puede enviarse mensajes a sí mismo.');
        abort_unless($user->is_active, 422, 'El usuario está desactivado.');

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $user->id,
            'body' => $data['body'],
        ]);

        return response()->json(['message' => $message->toChatArray($request->user()->id)], 201);
    }

    /** Conteo de mensajes no leídos (para el indicador del menú). */
    public function unread(Request $request)
    {
        $me = $request->user();

        return response()->json([
            'total' => Message::query()->where('receiver_id', $me->id)->whereNull('read_at')->count(),
            'by_user' => Message::query()->where('receiver_id', $me->id)->whereNull('read_at')
                ->selectRaw('sender_id, COUNT(*) as n')->groupBy('sender_id')->pluck('n', 'sender_id'),
            'notifications' => $me->unreadNotifications()->count(),
        ]);
    }
}

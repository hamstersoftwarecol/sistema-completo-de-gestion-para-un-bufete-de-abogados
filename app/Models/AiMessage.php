<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AiMessage extends Model
{
    protected $fillable = ['ai_conversation_id', 'role', 'content', 'provider'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function html(): string
    {
        return Str::markdown($this->content, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }

    public function toChatArray(): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'html' => $this->role === 'user' ? nl2br(e($this->content)) : $this->html(),
            'provider' => $this->provider,
            'time' => $this->created_at?->format('d/m H:i'),
        ];
    }
}

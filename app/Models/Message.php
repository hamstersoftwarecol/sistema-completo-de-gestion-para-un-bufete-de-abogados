<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /** Conversación entre dos usuarios (en ambos sentidos). */
    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(function (Builder $q) use ($a, $b) {
            $q->where(fn ($w) => $w->where('sender_id', $a)->where('receiver_id', $b))
                ->orWhere(fn ($w) => $w->where('sender_id', $b)->where('receiver_id', $a));
        });
    }

    public function toChatArray(int $me): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'mine' => $this->sender_id === $me,
            'time' => $this->created_at->format('d/m H:i'),
            'read' => $this->read_at !== null,
        ];
    }
}

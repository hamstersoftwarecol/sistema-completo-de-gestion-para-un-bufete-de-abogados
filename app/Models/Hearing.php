<?php

namespace App\Models;

use App\Enums\HearingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hearing extends Model
{
    protected $fillable = [
        'legal_case_id', 'user_id', 'court_id', 'title', 'hearing_type', 'scheduled_at', 'duration_minutes',
        'location', 'judge', 'status', 'notes', 'outcome', 'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'status' => HearingStatus::class,
            'duration_minutes' => 'integer',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('legalCase', fn (Builder $c) => $c->visibleTo($user));
        });
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>=', now())
            ->where('status', HearingStatus::Scheduled->value)
            ->orderBy('scheduled_at');
    }

    public function getEndsAtAttribute()
    {
        return $this->scheduled_at?->copy()->addMinutes($this->duration_minutes ?: 60);
    }
}

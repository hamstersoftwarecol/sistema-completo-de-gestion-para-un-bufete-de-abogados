<?php

namespace App\Models;

use App\Enums\AppointmentMode;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = [
        'client_id', 'legal_case_id', 'user_id', 'contact_name', 'contact_phone', 'title', 'starts_at',
        'duration_minutes', 'mode', 'location', 'status', 'fee', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'mode' => AppointmentMode::class,
            'fee' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

    public function getWhoAttribute(): string
    {
        return $this->client?->name ?? $this->contact_name ?? 'Sin cliente';
    }

    public function getEndsAtAttribute()
    {
        return $this->starts_at?->copy()->addMinutes($this->duration_minutes ?: 30);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }
}

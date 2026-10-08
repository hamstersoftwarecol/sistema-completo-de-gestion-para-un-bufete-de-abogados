<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'receipt_number', 'client_id', 'legal_case_id', 'appointment_id', 'user_id', 'amount', 'method',
        'paid_at', 'concept', 'reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'method' => PaymentMethod::class,
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('legalCase', fn (Builder $c) => $c->visibleTo($user))
                ->orWhere(fn (Builder $w) => $w->whereNull('legal_case_id')
                    ->whereHas('client', fn (Builder $c) => $c->where('user_id', $user->id)));
        });
    }

    /**
     * Genera el siguiente número de recibo: PREFIJO-AÑO-00001.
     */
    public static function nextReceiptNumber(): string
    {
        $prefix = setting('receipt_prefix', 'REC').'-'.now()->format('Y').'-';

        $last = static::query()
            ->where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

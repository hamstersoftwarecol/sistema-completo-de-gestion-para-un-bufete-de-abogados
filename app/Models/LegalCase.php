<?php

namespace App\Models;

use App\Enums\Priority;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    protected $fillable = [
        'case_number', 'title', 'client_id', 'case_type_id', 'case_status_id', 'court_id', 'judge',
        'lawyer_id', 'assistant_id', 'priority', 'filing_date', 'closed_at', 'description',
        'fee_amount', 'fee_notes',
    ];

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'filing_date' => 'date',
            'closed_at' => 'date',
            'fee_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CaseType::class, 'case_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(CaseStatus::class, 'case_status_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_id');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(CaseParty::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Casos visibles para el usuario: el administrador ve todo el bufete y
     * cada abogado sólo los casos donde es responsable o asistente.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('lawyer_id', $user->id)->orWhere('assistant_id', $user->id);
        });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('case_status_id')
                ->orWhereHas('status', fn (Builder $s) => $s->where('is_closed', false));
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('case_number', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('judge', 'like', "%{$term}%")
                ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"))
                ->orWhereHas('parties', fn (Builder $p) => $p->where('name', 'like', "%{$term}%"));
        });
    }

    public function isClosed(): bool
    {
        return (bool) $this->status?->is_closed;
    }

    public function isMember(User $user): bool
    {
        return $this->lawyer_id === $user->id || $this->assistant_id === $user->id;
    }

    public function totalPaid(): float
    {
        return (float) ($this->payments_sum_amount ?? $this->payments()->sum('amount'));
    }

    public function balance(): float
    {
        return max(0, (float) $this->fee_amount - $this->totalPaid());
    }

    /**
     * Registros vinculados que impiden eliminar el caso.
     *
     * @return array<string,int>
     */
    public function linkedRecords(): array
    {
        return array_filter([
            'audiencias' => $this->hearings()->count(),
            'pagos' => $this->payments()->count(),
            'gastos' => $this->expenses()->count(),
            'documentos' => $this->documents()->count(),
            'citas' => $this->appointments()->count(),
        ]);
    }
}

<?php

namespace App\Models;

use App\Enums\ClientType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    protected $fillable = [
        'type', 'name', 'document_type', 'document_number', 'email', 'phone', 'alt_phone',
        'address', 'city', 'occupation', 'contact_person', 'notes', 'user_id',
    ];

    protected function casts(): array
    {
        return ['type' => ClientType::class];
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function hearings(): HasManyThrough
    {
        return $this->hasManyThrough(Hearing::class, LegalCase::class);
    }

    public function documents(): HasManyThrough
    {
        return $this->hasManyThrough(Document::class, LegalCase::class);
    }

    public function expenses(): HasManyThrough
    {
        return $this->hasManyThrough(Expense::class, LegalCase::class);
    }

    /**
     * Clientes visibles para el usuario: el administrador ve todos; cada
     * abogado ve los clientes que tiene a cargo o con casos asignados.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('cases', fn (Builder $c) => $c->visibleTo($user));
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('document_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function getDocumentLabelAttribute(): string
    {
        return trim(($this->document_type ? $this->document_type.' ' : '').($this->document_number ?? ''));
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}

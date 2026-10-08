<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'professional_id',
        'specialty',
        'is_active',
        'ai_instructions',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function isSuperadmin(): bool
    {
        return $this->role === Role::Superadmin;
    }

    public function isSenior(): bool
    {
        return $this->role === Role::Senior;
    }

    public function isJunior(): bool
    {
        return $this->role === Role::Junior;
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function casesAsLawyer(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'lawyer_id');
    }

    public function casesAsAssistant(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'assistant_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Usuarios que pueden ser responsables de un caso. */
    public function scopeLawyers($query)
    {
        return $query->active()->orderBy('name');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    protected $fillable = ['name', 'city', 'address', 'phone', 'email'];

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->city ? "{$this->name} ({$this->city})" : $this->name;
    }
}

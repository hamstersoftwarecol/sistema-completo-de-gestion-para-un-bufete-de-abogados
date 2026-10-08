<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseStatus extends Model
{
    protected $fillable = ['name', 'color', 'is_closed', 'sort_order'];

    protected function casts(): array
    {
        return ['is_closed' => 'boolean', 'sort_order' => 'integer'];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParty extends Model
{
    protected $fillable = [
        'legal_case_id', 'name', 'role', 'document_number', 'phone', 'email', 'address', 'lawyer_name', 'notes',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function getRoleLabelAttribute(): string
    {
        return config('bufete.party_roles')[$this->role] ?? ucfirst($this->role);
    }
}

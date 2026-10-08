<?php

namespace App\Models;

use App\Enums\CommunicationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Communication extends Model
{
    protected $fillable = ['client_id', 'legal_case_id', 'user_id', 'type', 'subject', 'body'];

    protected function casts(): array
    {
        return ['type' => CommunicationType::class];
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
}

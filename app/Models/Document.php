<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'legal_case_id', 'user_id', 'title', 'category', 'original_name', 'path', 'mime_type', 'size',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return config('bufete.document_categories')[$this->category] ?? 'Otro';
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mime_type, 'video/');
    }

    public function isAudio(): bool
    {
        return str_starts_with((string) $this->mime_type, 'audio/');
    }

    public function isPreviewable(): bool
    {
        return $this->isPdf() || $this->isImage() || $this->isVideo() || $this->isAudio();
    }

    public function getKindAttribute(): string
    {
        return match (true) {
            $this->isPdf() => 'pdf',
            $this->isImage() => 'imagen',
            $this->isVideo() => 'video',
            $this->isAudio() => 'audio',
            default => 'archivo',
        };
    }
}

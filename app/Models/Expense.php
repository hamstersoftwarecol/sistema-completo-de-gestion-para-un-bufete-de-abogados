<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'legal_case_id', 'user_id', 'category', 'description', 'amount', 'expense_date', 'receipt_path',
        'receipt_original_name', 'billable', 'status', 'reviewed_by', 'reviewed_at', 'review_notes',
        'reimbursed_by', 'reimbursed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'reviewed_at' => 'datetime',
            'reimbursed_at' => 'datetime',
            'billable' => 'boolean',
            'status' => ExpenseStatus::class,
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reimburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reimbursed_by');
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

    public function getCategoryLabelAttribute(): string
    {
        return config('bufete.expense_categories')[$this->category] ?? 'Otro';
    }

    public function isPending(): bool
    {
        return $this->status === ExpenseStatus::Pending;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugReportComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bug_report_id',
        'user_id',
        'content',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the bug report this comment belongs to
     */
    public function bugReport(): BelongsTo
    {
        return $this->belongsTo(BugReport::class);
    }

    /**
     * Get the user who created this comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope for public comments
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope for internal comments
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }
}

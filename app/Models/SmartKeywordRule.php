<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SmartKeywordRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'keyword',
        'keyword_normalized',
        'match_field',
        'match_type',
        'target_vehicle_type',
        'target_brand',
        'score_bonus',
        'is_active',
        'priority',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'score_bonus' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'created_by' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $model) {
            $model->keyword_normalized = mb_strtolower(trim($model->keyword));
        });

        static::saved(function () {
            Cache::forget('smart_keyword_rules_active');
        });

        static::deleted(function () {
            Cache::forget('smart_keyword_rules_active');
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByField($query, string $field)
    {
        return $query->where(function ($q) use ($field) {
            $q->where('match_field', $field)->orWhere('match_field', 'any');
        });
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}

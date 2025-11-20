<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Import Template Model
 *
 * Stores reusable column mapping configurations for XLSX imports.
 * Supports template sharing and usage tracking.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property array $mapping_config JSON: {"A": "sku", "B": "name", ...}
 * @property bool $is_shared
 * @property int $usage_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ImportTemplate extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'import_templates';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'mapping_config',
        'is_shared',
        'usage_count',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'user_id' => 'integer',
        'mapping_config' => 'array',
        'is_shared' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Template owner
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Shared templates (visible to all users)
     */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }

    /**
     * Scope: Private templates for specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Templates available for user (owned + shared)
     */
    public function scopeAvailableFor($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_shared', true);
        });
    }

    /**
     * Scope: Popular templates (min usage count)
     */
    public function scopePopular($query, int $minUsage = 5)
    {
        return $query->where('usage_count', '>=', $minUsage)
                     ->orderBy('usage_count', 'desc');
    }

    /**
     * Scope: Order by usage count
     */
    public function scopeOrderByUsage($query, string $direction = 'desc')
    {
        return $query->orderBy('usage_count', $direction);
    }

    /**
     * Scope: Order by name
     */
    public function scopeOrderByName($query, string $direction = 'asc')
    {
        return $query->orderBy('name', $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | INSTANCE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get mapping for specific column
     *
     * @param string $column Column letter (e.g., 'A', 'B', 'C')
     * @return string|null Field name (e.g., 'sku', 'name') or null if not mapped
     */
    public function getMappingFor(string $column): ?string
    {
        return $this->mapping_config[$column] ?? null;
    }

    /**
     * Check if template maps specific field
     *
     * @param string $field Field name (e.g., 'sku', 'name')
     * @return bool
     */
    public function hasFieldMapping(string $field): bool
    {
        return in_array($field, $this->mapping_config);
    }

    /**
     * Get all mapped fields
     *
     * @return array Field names
     */
    public function getMappedFields(): array
    {
        return array_values($this->mapping_config);
    }

    /**
     * Get all mapped columns
     *
     * @return array Column letters
     */
    public function getMappedColumns(): array
    {
        return array_keys($this->mapping_config);
    }

    /**
     * Validate mapping configuration
     *
     * @return bool
     */
    public function hasValidMapping(): bool
    {
        // Must have at least SKU mapping
        return $this->hasFieldMapping('sku');
    }

    /**
     * Check if template is owned by user
     */
    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    /**
     * Check if template is accessible by user (owned or shared)
     */
    public function isAccessibleBy(int $userId): bool
    {
        return $this->isOwnedBy($userId) || $this->is_shared;
    }
}

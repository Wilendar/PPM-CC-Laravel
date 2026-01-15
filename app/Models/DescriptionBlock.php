<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Visual Description Editor - Block Definition Model
 *
 * Reprezentuje definicje bloku dostepnego w edytorze opisow wizualnych.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $category
 * @property string|null $icon
 * @property array|null $default_settings
 * @property array|null $schema
 * @property bool $is_active
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DescriptionBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'category',
        'icon',
        'default_settings',
        'schema',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_settings' => 'array',
        'schema' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Category constants
     */
    public const CATEGORY_LAYOUT = 'layout';
    public const CATEGORY_CONTENT = 'content';
    public const CATEGORY_MEDIA = 'media';
    public const CATEGORY_INTERACTIVE = 'interactive';

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_LAYOUT => 'Uklad',
            self::CATEGORY_CONTENT => 'Tresc',
            self::CATEGORY_MEDIA => 'Media',
            self::CATEGORY_INTERACTIVE => 'Interaktywne',
        ];
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: Only active blocks
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Ordered by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Get default settings with fallback to empty array
     */
    public function getParsedSettingsAttribute(): array
    {
        return $this->default_settings ?? [];
    }

    /**
     * Get schema with fallback to empty array
     */
    public function getParsedSchemaAttribute(): array
    {
        return $this->schema ?? [];
    }

    /**
     * Get human-readable category name
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    // =====================
    // METHODS
    // =====================

    /**
     * Create a new block instance with default values
     */
    public function createInstance(): array
    {
        return [
            'type' => $this->type,
            'content' => [],
            'settings' => $this->default_settings ?? [],
        ];
    }

    /**
     * Validate block data against schema
     */
    public function validateData(array $data): bool
    {
        // Basic validation - can be extended with JSON Schema validation
        if (!isset($data['type']) || $data['type'] !== $this->type) {
            return false;
        }

        return true;
    }
}

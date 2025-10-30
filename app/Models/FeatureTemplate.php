<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Feature Template Model
 *
 * Szablon zestawu cech do grupowego przypisywania produktom
 *
 * @property int $id
 * @property string $name Nazwa szablonu (np. "Pojazdy Elektryczne")
 * @property string|null $description Opis szablonu
 * @property array $features JSON array definicji cech
 * @property bool $is_predefined Czy szablon predefiniowany (ID 1, 2 - nie można usunąć)
 * @property bool $is_active Czy szablon aktywny
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class FeatureTemplate extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'feature_templates';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'name',
        'description',
        'features',
        'is_predefined',
        'is_active',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'features' => 'array', // Automatically decode/encode JSON
        'is_predefined' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only predefined templates (ID 1, 2)
     */
    public function scopePredefined($query)
    {
        return $query->where('is_predefined', true);
    }

    /**
     * Scope: Only custom templates (user-created)
     */
    public function scopeCustom($query)
    {
        return $query->where('is_predefined', false);
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if template can be deleted
     */
    public function isDeletable(): bool
    {
        return !$this->is_predefined;
    }

    /**
     * Get template features count
     */
    public function getFeaturesCount(): int
    {
        return is_array($this->features) ? count($this->features) : 0;
    }

    /**
     * Get template features formatted for display
     *
     * @return array Array of feature names
     */
    public function getFeatureNames(): array
    {
        if (!is_array($this->features)) {
            return [];
        }

        return array_column($this->features, 'name');
    }
}

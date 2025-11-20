<?php

namespace App\Casts;

use App\Services\CategoryMappingsValidator;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * CategoryMappingsCast - Custom Eloquent Cast for category_mappings JSON field
 *
 * Implements Option A architecture from CATEGORY_MAPPINGS_ARCHITECTURE.md
 *
 * Architecture:
 * ```json
 * {
 *   "ui": {
 *     "selected": [100, 103, 42],
 *     "primary": 100
 *   },
 *   "mappings": {
 *     "100": 9,
 *     "103": 15,
 *     "42": 800
 *   },
 *   "metadata": {
 *     "last_updated": "2025-11-18T10:30:00Z",
 *     "source": "manual"
 *   }
 * }
 * ```
 *
 * Features:
 * - Backward compatibility with old formats (UI-only, PrestaShop-only)
 * - Automatic conversion to canonical format
 * - Validation using CategoryMappingsValidator
 * - Extensive logging for debugging
 *
 * @package App\Casts
 * @version 2.0
 * @since 2025-11-18 (Category Mappings Architecture Refactoring)
 */
class CategoryMappingsCast implements CastsAttributes
{
    /**
     * @var CategoryMappingsValidator
     */
    private CategoryMappingsValidator $validator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->validator = app(CategoryMappingsValidator::class);
    }

    /**
     * Cast the given value (deserialize from database)
     *
     * Handles backward compatibility:
     * - Format 1 (UI): {"selected": [1,2], "primary": 1}
     * - Format 2 (PrestaShop): {"9": 9, "15": 15}
     * - Format 3 (Mixed/Canonical): Full Option A structure
     *
     * @param Model $model The model instance
     * @param string $key The attribute name
     * @param mixed $value The raw JSON value from database
     * @param array $attributes All model attributes
     * @return array The deserialized and validated array
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        // Handle NULL or empty string
        if ($value === null || $value === '') {
            return $this->getEmptyStructure();
        }

        // Decode JSON
        try {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('CategoryMappingsCast: JSON decode error', [
                    'model' => get_class($model),
                    'model_id' => $model->id ?? null,
                    'key' => $key,
                    'value' => $value,
                    'error' => json_last_error_msg(),
                ]);

                return $this->getEmptyStructure();
            }
        } catch (\Exception $e) {
            Log::error('CategoryMappingsCast: Exception during JSON decode', [
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'key' => $key,
                'exception' => $e->getMessage(),
            ]);

            return $this->getEmptyStructure();
        }

        // Handle empty array
        if (empty($decoded)) {
            return $this->getEmptyStructure();
        }

        // Detect format and convert to canonical
        try {
            $canonical = $this->validator->convertLegacyFormat($decoded);

            // Log conversion if format changed
            $originalFormat = $this->validator->detectFormat($decoded);
            if ($originalFormat !== 'option_a') {
                Log::info('CategoryMappingsCast: Converted legacy format on read', [
                    'model' => get_class($model),
                    'model_id' => $model->id ?? null,
                    'original_format' => $originalFormat,
                    'converted' => true,
                ]);
            }

            return $canonical;
        } catch (InvalidArgumentException $e) {
            Log::error('CategoryMappingsCast: Validation failed on read', [
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'key' => $key,
                'error' => $e->getMessage(),
                'data' => $decoded,
            ]);

            return $this->getEmptyStructure();
        }
    }

    /**
     * Prepare the given value for storage (serialize to database)
     *
     * Validates the array structure before saving to ensure data integrity
     *
     * @param Model $model The model instance
     * @param string $key The attribute name
     * @param mixed $value The array to be serialized
     * @param array $attributes All model attributes
     * @return string The JSON-encoded string
     * @throws InvalidArgumentException If validation fails
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        // Handle NULL
        if ($value === null) {
            return json_encode($this->getEmptyStructure());
        }

        // Ensure it's an array
        if (!is_array($value)) {
            Log::error('CategoryMappingsCast: Value is not an array', [
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'key' => $key,
                'type' => gettype($value),
            ]);

            throw new InvalidArgumentException('CategoryMappingsCast: Value must be an array');
        }

        // Handle empty array
        if (empty($value)) {
            return json_encode($this->getEmptyStructure());
        }

        // Convert legacy format if needed
        try {
            $canonical = $this->validator->convertLegacyFormat($value);

            // Log conversion if format changed
            $originalFormat = $this->validator->detectFormat($value);
            if ($originalFormat !== 'option_a') {
                Log::info('CategoryMappingsCast: Converted legacy format on write', [
                    'model' => get_class($model),
                    'model_id' => $model->id ?? null,
                    'original_format' => $originalFormat,
                    'converted' => true,
                ]);
            }
        } catch (InvalidArgumentException $e) {
            Log::error('CategoryMappingsCast: Conversion failed on write', [
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'error' => $e->getMessage(),
                'data' => $value,
            ]);

            throw $e;
        }

        // Validate canonical structure
        try {
            $validated = $this->validator->validate($canonical);
        } catch (InvalidArgumentException $e) {
            Log::error('CategoryMappingsCast: Validation failed on write', [
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'key' => $key,
                'error' => $e->getMessage(),
                'data' => $canonical,
            ]);

            throw $e;
        }

        // Encode to JSON
        $json = json_encode($validated);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('CategoryMappingsCast: JSON encode error', [
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'key' => $key,
                'error' => json_last_error_msg(),
            ]);

            throw new InvalidArgumentException('JSON encode error: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Get empty canonical structure
     *
     * Used as default when value is NULL or invalid
     *
     * @return array Empty canonical structure
     */
    private function getEmptyStructure(): array
    {
        return [
            'ui' => [
                'selected' => [],
                'primary' => null,
            ],
            'mappings' => [],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'empty',
            ],
        ];
    }
}

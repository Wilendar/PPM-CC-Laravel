<?php

namespace App\Http\Livewire\Products\Import\Modals;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PendingProduct;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\Log;

/**
 * VariantModal - ETAP_06 FAZA 5.4
 *
 * Modal do tworzenia wariantow dla pending products.
 * Warianty przechowywane jako JSON w kolumnie variant_data.
 *
 * Structure: variant_data = [
 *   'variants' => [
 *     ['sku_suffix' => '-RED-XL', 'name' => 'Czerwony XL', 'attributes' => [
 *       ['attribute_type_id' => 1, 'value_id' => 5, 'value' => 'Czerwony', 'color_hex' => '#FF0000'],
 *       ['attribute_type_id' => 2, 'value_id' => 12, 'value' => 'XL'],
 *     ]],
 *   ],
 *   'attribute_types_used' => [1, 2], // IDs of attribute types
 * ]
 *
 * @package App\Http\Livewire\Products\Import\Modals
 * @since 2025-12-09
 */
class VariantModal extends Component
{
    /**
     * Whether modal is visible
     */
    public bool $showModal = false;

    /**
     * Currently editing pending product ID
     */
    public ?int $pendingProductId = null;

    /**
     * Pending product model
     */
    public ?PendingProduct $pendingProduct = null;

    /**
     * Selected attribute types for generating variants
     * Format: [attribute_type_id => true/false]
     */
    public array $selectedAttributeTypes = [];

    /**
     * Selected values for each attribute type
     * Format: [attribute_type_id => [value_id, value_id, ...]]
     */
    public array $selectedValues = [];

    /**
     * Generated variant combinations
     * Format: [['sku_suffix' => '...', 'name' => '...', 'attributes' => [...]]]
     */
    public array $generatedVariants = [];

    /**
     * SKU prefix/suffix mode: 'suffix' | 'prefix'
     */
    public string $skuMode = 'suffix';

    /**
     * Custom SKU separator
     */
    public string $skuSeparator = '-';

    /**
     * Processing flag
     */
    public bool $isProcessing = false;

    /**
     * Use auto_suffix/auto_prefix from DB config
     */
    public bool $useDbSuffixPrefix = true;

    /**
     * Per-variant price overrides
     * Format: [index => float|null]
     */
    public array $variantPrices = [];

    /**
     * Per-variant active/inactive states
     * Format: [index => bool]
     */
    public array $variantActiveStates = [];

    /**
     * Listeners
     */
    protected $listeners = [
        'openVariantModal' => 'openModal',
    ];

    /**
     * Open modal for a pending product
     */
    #[On('openVariantModal')]
    public function openModal(int $productId): void
    {
        $this->reset(['generatedVariants', 'selectedAttributeTypes', 'selectedValues', 'useDbSuffixPrefix', 'variantPrices', 'variantActiveStates']);
        $this->useDbSuffixPrefix = true; // Default to DB config

        $this->pendingProductId = $productId;
        $this->pendingProduct = PendingProduct::find($productId);

        if (!$this->pendingProduct) {
            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Nie znaleziono produktu',
            ]);
            return;
        }

        // Load existing variant data
        $existingData = $this->pendingProduct->variant_data ?? [];

        if (!empty($existingData['variants'])) {
            $this->generatedVariants = $existingData['variants'];

            // Restore selected attribute types
            if (!empty($existingData['attribute_types_used'])) {
                foreach ($existingData['attribute_types_used'] as $typeId) {
                    $this->selectedAttributeTypes[$typeId] = true;
                }

                // Restore selected values from variants
                foreach ($existingData['variants'] as $variant) {
                    foreach ($variant['attributes'] ?? [] as $attr) {
                        $typeId = $attr['attribute_type_id'] ?? null;
                        $valueId = $attr['value_id'] ?? null;
                        if ($typeId && $valueId) {
                            $this->selectedValues[$typeId][$valueId] = true;
                        }
                    }
                }
            }

            // Initialize tracking arrays from existing variants
            foreach ($this->generatedVariants as $index => $variant) {
                $this->variantPrices[$index] = $variant['price'] ?? null;
                $this->variantActiveStates[$index] = $variant['is_active'] ?? true;
            }
        }

        $this->showModal = true;
    }

    /**
     * Close modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['pendingProductId', 'pendingProduct', 'generatedVariants', 'selectedAttributeTypes', 'selectedValues', 'variantPrices', 'variantActiveStates']);
    }

    /**
     * Toggle attribute type selection
     */
    public function toggleAttributeType(int $typeId): void
    {
        if (isset($this->selectedAttributeTypes[$typeId]) && $this->selectedAttributeTypes[$typeId]) {
            unset($this->selectedAttributeTypes[$typeId]);
            unset($this->selectedValues[$typeId]);
        } else {
            $this->selectedAttributeTypes[$typeId] = true;
            $this->selectedValues[$typeId] = [];
        }

        $this->generatedVariants = [];
    }

    /**
     * Toggle value selection
     */
    public function toggleValue(int $typeId, int $valueId): void
    {
        if (isset($this->selectedValues[$typeId][$valueId]) && $this->selectedValues[$typeId][$valueId]) {
            unset($this->selectedValues[$typeId][$valueId]);
        } else {
            $this->selectedValues[$typeId][$valueId] = true;
        }

        $this->generatedVariants = [];
    }

    /**
     * Generate variant combinations from selected attribute values
     */
    public function generateVariants(): void
    {
        $this->generatedVariants = [];

        // Get active attribute types with values
        $attributeCombinations = [];

        foreach ($this->selectedAttributeTypes as $typeId => $selected) {
            if (!$selected) continue;

            $selectedValueIds = array_keys(array_filter($this->selectedValues[$typeId] ?? []));
            if (empty($selectedValueIds)) continue;

            $type = AttributeType::with(['values' => fn($q) => $q->whereIn('id', $selectedValueIds)])
                ->find($typeId);

            if (!$type) continue;

            $attributeCombinations[$typeId] = $type->values->map(fn($v) => [
                'attribute_type_id' => $typeId,
                'attribute_type_name' => $type->name,
                'attribute_type_code' => $type->code,
                'value_id' => $v->id,
                'value' => $v->label,  // FIX: Correct column name (was $v->value which doesn't exist)
                'color_hex' => $v->color_hex,
            ])->toArray();
        }

        if (empty($attributeCombinations)) {
            $this->dispatch('flash-message', [
                'type' => 'warning',
                'message' => 'Wybierz przynajmniej jeden atrybut i jego wartosci',
            ]);
            return;
        }

        // Generate all combinations (cartesian product)
        $combinations = $this->cartesianProduct(array_values($attributeCombinations));

        // Build variants
        $baseSku = $this->pendingProduct->sku ?? 'PRODUCT';
        $baseName = $this->pendingProduct->name ?? 'Produkt';

        foreach ($combinations as $index => $combo) {
            $suffixParts = [];
            $prefixParts = [];
            $nameParts = [];

            foreach ($combo as $attr) {
                $nameParts[] = $attr['value'];

                if ($this->useDbSuffixPrefix) {
                    // Use auto_suffix/auto_prefix from DB
                    $attrValue = AttributeValue::find($attr['value_id']);
                    if ($attrValue) {
                        if ($attrValue->auto_suffix_enabled && $attrValue->auto_suffix) {
                            $suffixParts[] = $attrValue->auto_suffix;
                        }
                        if ($attrValue->auto_prefix_enabled && $attrValue->auto_prefix) {
                            $prefixParts[] = $attrValue->auto_prefix;
                        }
                    }
                } else {
                    // Use manual config - first 3 chars of value
                    $code = strtoupper(substr($attr['value'], 0, 3));
                    $suffixParts[] = $code;
                }
            }

            // Build SKU with proper separators
            if ($this->useDbSuffixPrefix) {
                $prefix = !empty($prefixParts) ? implode('', $prefixParts) : '';
                $suffix = !empty($suffixParts) ? implode('', $suffixParts) : '';
                // FIX: Add separator between base SKU and suffix/prefix
                $variantSku = ($prefix ? $prefix . $this->skuSeparator : '')
                            . $baseSku
                            . ($suffix ? $this->skuSeparator . $suffix : '');
                $skuSuffix = $suffix ? ($this->skuSeparator . $suffix) : ($prefix ? ($this->skuSeparator . $prefix) : '');
            } else {
                $suffix = $this->skuSeparator . implode($this->skuSeparator, $suffixParts);
                $variantSku = $this->skuMode === 'prefix'
                    ? $suffix . $baseSku
                    : $baseSku . $suffix;
                $skuSuffix = $suffix;
            }

            $this->generatedVariants[] = [
                'sku_suffix' => $skuSuffix,
                'full_sku' => $variantSku,
                'name' => implode(', ', $nameParts),  // PrestaShop: variant name = attribute values only (e.g. "Czerwony", not "Produkt - Czerwony")
                'attributes' => $combo,
                'is_active' => true,
            ];
        }

        // Initialize tracking arrays
        $this->variantPrices = [];
        $this->variantActiveStates = [];
        foreach ($this->generatedVariants as $index => $variant) {
            $this->variantPrices[$index] = $variant['price'] ?? null;
            $this->variantActiveStates[$index] = $variant['is_active'] ?? true;
        }

        Log::info('[VariantModal] Generated variants', [
            'pending_product_id' => $this->pendingProductId,
            'count' => count($this->generatedVariants),
        ]);
    }

    /**
     * Generate cartesian product of arrays
     */
    protected function cartesianProduct(array $arrays): array
    {
        if (empty($arrays)) {
            return [];
        }

        $result = [[]];

        foreach ($arrays as $array) {
            $newResult = [];
            foreach ($result as $product) {
                foreach ($array as $item) {
                    $newResult[] = array_merge($product, [$item]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    /**
     * Remove a generated variant
     */
    public function removeVariant(int $index): void
    {
        if (isset($this->generatedVariants[$index])) {
            unset($this->generatedVariants[$index]);
            $this->generatedVariants = array_values($this->generatedVariants);

            // Re-index tracking arrays
            unset($this->variantPrices[$index]);
            unset($this->variantActiveStates[$index]);
            $this->variantPrices = array_values($this->variantPrices);
            $this->variantActiveStates = array_values($this->variantActiveStates);
        }
    }

    /**
     * Save variants to pending product
     */
    public function saveVariants(): void
    {
        if (!$this->pendingProduct) {
            return;
        }

        $this->isProcessing = true;

        try {
            // Collect used attribute types
            $usedTypeIds = array_keys(array_filter($this->selectedAttributeTypes));

            // Sync prices and active states into generatedVariants before save
            foreach ($this->generatedVariants as $index => &$variant) {
                $variant['price'] = $this->variantPrices[$index] ?? null;
                $variant['is_active'] = $this->variantActiveStates[$index] ?? true;
            }
            unset($variant);

            // Build variant_data structure
            $variantData = [
                'variants' => $this->generatedVariants,
                'attribute_types_used' => $usedTypeIds,
                'sku_mode' => $this->skuMode,
                'sku_separator' => $this->skuSeparator,
                'generated_at' => now()->toIso8601String(),
            ];

            $this->pendingProduct->update([
                'variant_data' => $variantData,
            ]);

            Log::info('[VariantModal] Saved variants', [
                'pending_product_id' => $this->pendingProductId,
                'variant_count' => count($this->generatedVariants),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'success',
                'message' => 'Zapisano ' . count($this->generatedVariants) . ' wariantow',
            ]);

            $this->dispatch('refreshPendingProducts');
            $this->closeModal();

        } catch (\Exception $e) {
            Log::error('[VariantModal] Save failed', [
                'pending_product_id' => $this->pendingProductId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('flash-message', [
                'type' => 'error',
                'message' => 'Blad zapisu: ' . $e->getMessage(),
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Clear all variants
     */
    public function clearVariants(): void
    {
        $this->generatedVariants = [];
        $this->selectedValues = [];
        $this->variantPrices = [];
        $this->variantActiveStates = [];
    }

    /**
     * Toggle variant active/inactive state
     */
    public function toggleVariantActive(int $index): void
    {
        $states = $this->variantActiveStates;
        $states[$index] = !($states[$index] ?? true);
        $this->variantActiveStates = $states;

        // Sync to generatedVariants
        $variants = $this->generatedVariants;
        if (isset($variants[$index])) {
            $variants[$index]['is_active'] = $states[$index];
            $this->generatedVariants = $variants;
        }
    }

    /**
     * Set price for a specific variant
     */
    public function setVariantPrice(int $index, $price): void
    {
        $prices = $this->variantPrices;
        $prices[$index] = $price !== '' && $price !== null ? (float) $price : null;
        $this->variantPrices = $prices;

        // Sync to generatedVariants
        $variants = $this->generatedVariants;
        if (isset($variants[$index])) {
            $variants[$index]['price'] = $prices[$index];
            $this->generatedVariants = $variants;
        }
    }

    /**
     * Get count of variants using a specific attribute value
     */
    public function getValueUsageCount(int $typeId, int $valueId): int
    {
        if (empty($this->generatedVariants)) {
            return 0;
        }

        $count = 0;
        foreach ($this->generatedVariants as $variant) {
            foreach ($variant['attributes'] ?? [] as $attr) {
                if (($attr['attribute_type_id'] ?? 0) == $typeId && ($attr['value_id'] ?? 0) == $valueId) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get available attribute types
     */
    public function getAttributeTypesProperty()
    {
        return AttributeType::where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get values for attribute type
     */
    public function getValuesForType(int $typeId)
    {
        return AttributeValue::where('attribute_type_id', $typeId)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('label')
            ->get();
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.products.import.modals.variant-modal');
    }
}

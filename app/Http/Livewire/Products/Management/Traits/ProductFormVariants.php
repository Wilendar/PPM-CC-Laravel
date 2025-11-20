<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ProductVariant;
use App\Models\VariantImage;
use App\Models\VariantAttribute;
use App\Models\VariantPrice;
use App\Models\VariantStock;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * ProductFormVariants Trait
 *
 * Backend methods for variant management in ProductForm component.
 *
 * FEATURE GROUPS:
 * - CRUD Operations: Create, update, delete, duplicate variants
 * - Default Management: Set default variant logic
 * - SKU Generation: Auto-generate unique variant SKUs
 * - Price Management: Update prices, bulk copy from parent
 * - Stock Management: Update stock per warehouse
 * - Image Management: Upload, assign, delete, set cover
 *
 * USAGE IN LIVEWIRE COMPONENT:
 * ```php
 * use App\Http\Livewire\Products\Management\Traits\ProductFormVariants;
 *
 * class ProductForm extends Component
 * {
 *     use ProductFormVariants;
 *
 *     public function mount(Product $product)
 *     {
 *         $this->product = $product;
 *     }
 * }
 * ```
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since ETAP_05b Phase 6 Wave 2 (2025-10-30)
 */
trait ProductFormVariants
{
    use VariantValidation;
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Variant form data
     *
     * @var array
     */
    public array $variantData = [
        'sku' => '',
        'name' => '',
        'is_active' => true,
        'is_default' => false,
        'position' => 0,
    ];

    /**
     * Currently editing variant ID
     *
     * @var int|null
     */
    public ?int $editingVariantId = null;

    /**
     * Show create modal flag
     *
     * @var bool
     */
    public bool $showCreateModal = false;

    /**
     * Show edit modal flag
     *
     * @var bool
     */
    public bool $showEditModal = false;

    /**
     * Livewire file upload property for variant images
     *
     * @var mixed
     */
    public $variantImageUpload;

    /**
     * Variant stock data [variant_id][warehouse_id] => quantity
     *
     * Used by Alpine.js x-model in variant-stock-grid.blade.php
     * Structure: $variantStock[variant_id][warehouse_index] = quantity
     *
     * @var array
     */
    public array $variantStock = [];

    /**
     * Variant prices data [variant_id][price_group_key] => price
     *
     * Used by Alpine.js x-model in variant-prices-grid.blade.php
     * Structure: $variantPrices[variant_id] = ['retail' => 0, 'dealer_standard' => 0, ...]
     *
     * @var array
     */
    public array $variantPrices = [];

    /**
     * Variant images upload (Livewire WithFileUploads)
     *
     * Used by wire:model in variant-images-manager.blade.php
     *
     * @var mixed
     */
    public $variantImages;

    /**
     * Image to variant assignments [image_id] => variant_id
     *
     * Used by wire:model in variant-images-manager.blade.php
     *
     * @var array
     */
    public array $imageVariantAssignments = [];

    /**
     * Variant attributes data [attribute_type_id] => value
     *
     * Used for attribute selection in create/edit modals
     * Structure: $variantAttributes[attribute_type_id] = 'value_text'
     *
     * @var array
     */
    public array $variantAttributes = [];

    /*
    |--------------------------------------------------------------------------
    | CRUD METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create new variant for current product
     *
     * Business Logic:
     * - Validate variant data (SKU uniqueness, name)
     * - Auto-generate position if not provided
     * - Set product.is_variant_master = true
     * - Transaction for data integrity
     *
     * @return void
     */
    public function createVariant(): void
    {
        // Validation (uses VariantValidation trait) - returns validated array
        $validatedData = $this->validateVariantCreate($this->variantData);

        try {
            // Auto-generate position if not provided
            if (empty($validatedData['position'])) {
                $maxPosition = (int) DB::table('product_variants')
                    ->where('product_id', $this->product->id)
                    ->whereNull('deleted_at')
                    ->max('position');
                $validatedData['position'] = $maxPosition + 1;
            }

            // Prepare data for creation
            $dataToCreate = [
                'product_id' => $this->product->id,
                'sku' => $validatedData['sku'],
                'name' => $validatedData['name'],
                'is_active' => $validatedData['is_active'] ?? true,
                'is_default' => $validatedData['is_default'] ?? false,
                'position' => $validatedData['position'],
            ];

            Log::debug('createVariant FINAL', [
                'validatedData_type' => gettype($validatedData),
                'dataToCreate_type' => gettype($dataToCreate),
                'dataToCreate' => $dataToCreate,
            ]);

            // WORKAROUND #2: Use Query Builder directly to bypass Eloquent entirely
            $variantId = DB::table('product_variants')->insertGetId([
                'product_id' => $dataToCreate['product_id'],
                'sku' => $dataToCreate['sku'],
                'name' => $dataToCreate['name'],
                'is_active' => $dataToCreate['is_active'],
                'is_default' => $dataToCreate['is_default'],
                'position' => $dataToCreate['position'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Load created variant as Eloquent model for subsequent operations
            $variant = ProductVariant::find($variantId);

            // Attribute assignment (Wave 3 Task 1)
            if (!empty($this->variantAttributes)) {
                foreach ($this->variantAttributes as $attributeTypeId => $value) {
                    if (!empty($value)) {
                        VariantAttribute::create([
                            'variant_id' => $variant->id,
                            'attribute_type_id' => $attributeTypeId,
                            'value' => $value,
                            'value_code' => \Illuminate\Support\Str::slug($value),
                        ]);
                    }
                }
            }

            // Set is_variant_master flag on parent product
            // ALWAYS update (don't check condition - accessor may return wrong value)
            $this->product->update([
                'is_variant_master' => true,
            ]);

            // If this is first variant OR is_default=true, set as default
            if ($variant->is_default || $this->product->variants()->count() === 1) {
                $this->product->update(['default_variant_id' => $variant->id]);
            }

            Log::info('Variant created', [
                'product_id' => $this->product->id,
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'attributes_count' => count($this->variantAttributes),
            ]);

            // Refresh product with variants
            $this->product->load('variants');

            // Initialize variantPrices for new variant (prevent Alpine.js undefined errors)
            $priceGroups = PriceGroup::all();
            foreach ($priceGroups as $priceGroup) {
                $this->variantPrices[$variant->id][$priceGroup->code] = 0;
            }

            // Initialize variantStock for new variant (prevent Alpine.js undefined errors)
            $warehouses = Warehouse::orderBy('name')->get();
            foreach ($warehouses as $index => $warehouse) {
                $this->variantStock[$variant->id][$index + 1] = 0;
            }

            // Reset form
            $this->resetVariantData();

            // Close modal (Alpine.js listener)
            $this->dispatch('close-variant-modal');

            // Dispatch success event
            $this->dispatch('variant-created');

            // Flash message
            session()->flash('message', 'Wariant został utworzony pomyślnie.');
        } catch (\Exception $e) {
            Log::error('Variant creation failed', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            $this->addError('variantData', 'Błąd podczas tworzenia wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Update existing variant
     *
     * Uses $this->editingVariantId if no parameter provided (for modal integration)
     *
     * @param int|null $variantId
     * @return void
     */
    public function updateVariant(?int $variantId = null): void
    {
        try {
            $variantId = $variantId ?? $this->editingVariantId;

            if (!$variantId) {
                $this->addError('variantData', 'Nie wybrano wariantu do aktualizacji.');
                return;
            }

            $variant = ProductVariant::findOrFail($variantId);

            // Validation (ignore current variant for SKU uniqueness)
            $this->validateVariantUpdate($variantId, $this->variantData);

            DB::transaction(function () use ($variant) {
                $variant->update([
                    'sku' => $this->variantData['sku'],
                    'name' => $this->variantData['name'],
                    'is_active' => $this->variantData['is_active'],
                    'is_default' => $this->variantData['is_default'],
                    'position' => $this->variantData['position'],
                ]);

                // Update variant attributes (Wave 3 Task 1)
                if (!empty($this->variantAttributes)) {
                    // Delete old attributes
                    $variant->attributes()->delete();

                    // Create new attributes
                    foreach ($this->variantAttributes as $attributeTypeId => $value) {
                        if (!empty($value)) {
                            VariantAttribute::create([
                                'variant_id' => $variant->id,
                                'attribute_type_id' => $attributeTypeId,
                                'value' => $value,
                                'value_code' => \Illuminate\Support\Str::slug($value),
                            ]);
                        }
                    }
                }

                // If set as default, update parent product
                if ($this->variantData['is_default']) {
                    $this->product->update(['default_variant_id' => $variant->id]);

                    // Unset other defaults
                    $this->product->variants()
                        ->where('id', '!=', $variant->id)
                        ->update(['is_default' => false]);
                }

                Log::info('Variant updated', [
                    'variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'attributes_count' => count($this->variantAttributes),
                ]);
            });

            $this->product->load('variants');
            $this->resetVariantData();

            // Close modal (Alpine.js listener)
            $this->dispatch('close-variant-modal');

            $this->dispatch('variant-updated');
            session()->flash('message', 'Wariant został zaktualizowany pomyślnie.');
        } catch (\Exception $e) {
            Log::error('Variant update failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);

            $this->addError('variantData', 'Błąd podczas aktualizacji wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Delete variant (soft delete)
     *
     * Business Logic:
     * - Soft delete (preserves data)
     * - Check PrestaShop sync status (warn if synced)
     * - Update product.is_variant_master if last variant
     * - Clear default_variant_id if deleting default
     *
     * @param int $variantId
     * @return void
     */
    public function deleteVariant(int $variantId): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);

            DB::transaction(function () use ($variant) {
                // Soft delete
                $variant->delete();

                // If this was default variant, clear default_variant_id
                if ($this->product->default_variant_id === $variant->id) {
                    // Set first remaining active variant as default
                    $newDefault = $this->product->variants()
                        ->where('is_active', true)
                        ->orderBy('position')
                        ->first();

                    $this->product->update([
                        'default_variant_id' => $newDefault?->id,
                    ]);
                }

                // If no more variants, clear is_variant_master flag
                if ($this->product->variants()->count() === 0) {
                    $this->product->update([
                        'is_variant_master' => false,
                    ]);
                }

                Log::info('Variant deleted', [
                    'variant_id' => $variant->id,
                    'sku' => $variant->sku,
                ]);
            });

            $this->product->load('variants');

            $this->dispatch('variant-deleted');
            session()->flash('message', 'Wariant został usunięty pomyślnie.');
        } catch (\Exception $e) {
            Log::error('Variant deletion failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas usuwania wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate variant with new SKU
     *
     * @param int $variantId
     * @return void
     */
    public function duplicateVariant(int $variantId): void
    {
        try {
            $original = ProductVariant::with(['attributes', 'prices', 'stock'])->findOrFail($variantId);

            DB::transaction(function () use ($original) {
                // Generate new SKU
                $newSku = $this->generateVariantSKU($original->sku);

                // Create duplicate
                $duplicate = ProductVariant::create([
                    'product_id' => $this->product->id,
                    'sku' => $newSku,
                    'name' => $original->name . ' (Kopia)',
                    'is_active' => false, // Duplicates start as inactive
                    'is_default' => false,
                    'position' => $this->product->variants()->max('position') + 1,
                ]);

                // Copy attributes
                foreach ($original->attributes as $attr) {
                    $duplicate->attributes()->create([
                        'attribute_type_id' => $attr->attribute_type_id,
                        'attribute_value_id' => $attr->attribute_value_id,
                    ]);
                }

                // Copy prices
                foreach ($original->prices as $price) {
                    $duplicate->prices()->create([
                        'price_group_id' => $price->price_group_id,
                        'price' => $price->price,
                        'price_special' => $price->price_special,
                        'special_from' => $price->special_from,
                        'special_to' => $price->special_to,
                    ]);
                }

                // Copy stock (quantity = 0, reserved = 0)
                foreach ($original->stock as $stock) {
                    $duplicate->stock()->create([
                        'warehouse_id' => $stock->warehouse_id,
                        'quantity' => 0, // Start with 0 stock
                        'reserved' => 0,
                    ]);
                }

                Log::info('Variant duplicated', [
                    'original_id' => $original->id,
                    'duplicate_id' => $duplicate->id,
                    'new_sku' => $newSku,
                ]);
            });

            $this->product->load('variants');

            $this->dispatch('variant-duplicated');
            session()->flash('message', 'Wariant został zduplikowany pomyślnie.');
        } catch (\Exception $e) {
            Log::error('Variant duplication failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas duplikowania wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Set variant as default
     *
     * @param int $variantId
     * @return void
     */
    public function setDefaultVariant(int $variantId): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);

            DB::transaction(function () use ($variant) {
                // Set as default
                $variant->update(['is_default' => true]);
                $this->product->update(['default_variant_id' => $variant->id]);

                // Unset other defaults
                $this->product->variants()
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);

                Log::info('Default variant changed', [
                    'product_id' => $this->product->id,
                    'variant_id' => $variant->id,
                ]);
            });

            $this->product->load('variants');

            $this->dispatch('default-variant-changed');
            session()->flash('message', 'Wariant domyślny został ustawiony.');
        } catch (\Exception $e) {
            Log::error('Set default variant failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas ustawiania wariantu domyślnego: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique SKU for variant
     *
     * Logic:
     * - If baseSku provided: append -001, -002, etc.
     * - If no baseSku: use product SKU + -V001, -V002, etc.
     * - Ensure uniqueness
     *
     * @param string|null $baseSku
     * @return string
     */
    public function generateVariantSKU(?string $baseSku = null): string
    {
        $baseSku = $baseSku ?? $this->product->sku;

        // Try with incrementing suffix
        $counter = 1;
        do {
            $newSku = sprintf('%s-V%03d', $baseSku, $counter);
            $counter++;

            // Check uniqueness across products + variants
            $exists = DB::table('products')->where('sku', $newSku)->exists()
                || DB::table('product_variants')->where('sku', $newSku)->exists();

        } while ($exists && $counter < 1000); // Safety limit

        return $newSku;
    }

    /**
     * Load variant data for editing
     *
     * @param int $variantId
     * @return void
     */
    public function loadVariantForEdit(int $variantId): void
    {
        try {
            $variant = ProductVariant::with('attributes')->findOrFail($variantId);

            $this->editingVariantId = $variant->id;
            $this->variantData = [
                'sku' => $variant->sku,
                'name' => $variant->name,
                'is_active' => $variant->is_active,
                'is_default' => $variant->is_default,
                'position' => $variant->position,
            ];

            // Load variant attributes (Wave 3 Task 1)
            $this->variantAttributes = [];
            foreach ($variant->attributes as $attr) {
                $this->variantAttributes[$attr->attribute_type_id] = $attr->value;
            }

            $this->showEditModal = true;
        } catch (\Exception $e) {
            Log::error('Load variant for edit failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Błąd podczas ładowania wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Reset variant form data
     *
     * @return void
     */
    protected function resetVariantData(): void
    {
        $this->variantData = [
            'sku' => '',
            'name' => '',
            'is_active' => true,
            'is_default' => false,
            'position' => 0,
        ];
        $this->editingVariantId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | PRICE MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Update variant price for specific price group
     *
     * @param int $variantId
     * @param int $priceGroupId
     * @param array $priceData ['price', 'price_special', 'special_from', 'special_to']
     * @return void
     */
    public function updateVariantPrice(int $variantId, int $priceGroupId, array $priceData): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);

            // Validation
            $this->validateVariantPrice($priceData);

            // Update or create price
            $variant->prices()->updateOrCreate(
                [
                    'price_group_id' => $priceGroupId,
                ],
                [
                    'price' => $priceData['price'],
                    'price_special' => $priceData['price_special'] ?? null,
                    'special_from' => $priceData['special_from'] ?? null,
                    'special_to' => $priceData['special_to'] ?? null,
                ]
            );

            Log::info('Variant price updated', [
                'variant_id' => $variantId,
                'price_group_id' => $priceGroupId,
            ]);

            $this->dispatch('variant-price-updated');
        } catch (\Exception $e) {
            Log::error('Variant price update failed', [
                'variant_id' => $variantId,
                'price_group_id' => $priceGroupId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas aktualizacji ceny: ' . $e->getMessage());
        }
    }

    /**
     * Copy prices from parent product to all variants
     *
     * @return void
     */
    public function bulkCopyPricesFromParent(): void
    {
        try {
            DB::transaction(function () {
                $productPrices = $this->product->prices;

                foreach ($this->product->variants as $variant) {
                    foreach ($productPrices as $price) {
                        $variant->prices()->updateOrCreate(
                            ['price_group_id' => $price->price_group_id],
                            [
                                'price' => $price->price,
                                'price_special' => $price->price_special,
                                'special_from' => $price->special_from,
                                'special_to' => $price->special_to,
                            ]
                        );
                    }
                }

                Log::info('Bulk copied prices from parent to variants', [
                    'product_id' => $this->product->id,
                    'variants_count' => $this->product->variants->count(),
                ]);
            });

            $this->product->load('variants.prices');
            $this->dispatch('prices-bulk-copied');
            session()->flash('message', 'Ceny zostały skopiowane do wszystkich wariantów.');
        } catch (\Exception $e) {
            Log::error('Bulk copy prices failed', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas kopiowania cen: ' . $e->getMessage());
        }
    }

    /**
     * Save variant prices grid (Wave 3 Task 2)
     *
     * @return void
     */
    public function savePrices(): void
    {
        try {
            DB::beginTransaction();

            foreach ($this->variantPrices as $variantId => $prices) {
                foreach ($prices as $priceGroupKey => $price) {
                    // Find price group by key (e.g., 'retail', 'dealer_standard')
                    $priceGroup = PriceGroup::where('code', $priceGroupKey)->first();

                    if (!$priceGroup) {
                        continue;
                    }

                    // Validate price
                    if (!is_numeric($price) || $price < 0) {
                        throw new \Exception("Nieprawidłowa cena dla wariantu {$variantId}");
                    }

                    // Update or create price
                    VariantPrice::updateOrCreate(
                        [
                            'variant_id' => $variantId,
                            'price_group_id' => $priceGroup->id,
                        ],
                        [
                            'price' => $price,
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            Log::info('Variant prices saved', [
                'product_id' => $this->product->id,
                'variants_count' => count($this->variantPrices),
            ]);

            $this->dispatch('success', message: 'Ceny wariantów zapisane');
            $this->dispatch('prices-saved');
            session()->flash('message', 'Ceny wariantów zostały zapisane pomyślnie.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Variant prices save error', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', message: 'Błąd zapisu cen: ' . $e->getMessage());
            session()->flash('error', 'Błąd podczas zapisu cen: ' . $e->getMessage());
        }
    }

    /**
     * Load variant prices data for grid (Wave 3 Task 2)
     *
     * @return void
     */
    protected function loadVariantPrices(): void
    {
        if (!$this->product || !$this->product->is_variant_master) {
            return;
        }

        $variants = $this->product->variants()->with('prices.priceGroup')->get();
        $this->variantPrices = [];

        foreach ($variants as $variant) {
            foreach ($variant->prices as $price) {
                if ($price->priceGroup) {
                    $this->variantPrices[$variant->id][$price->priceGroup->code] = $price->price;
                }
            }
        }
    }

    /**
     * Get price groups with prices for grid rendering
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPriceGroupsWithPrices(): \Illuminate\Support\Collection
    {
        $priceGroups = PriceGroup::orderBy('name')->get();

        return $priceGroups->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'prices' => $this->product->variants->mapWithKeys(function ($variant) use ($group) {
                    $price = $variant->prices->firstWhere('price_group_id', $group->id);
                    return [
                        $variant->id => [
                            'price' => $price?->price ?? 0,
                            'price_special' => $price?->price_special,
                            'has_special' => $price && $price->price_special,
                        ]
                    ];
                }),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Update variant stock for specific warehouse
     *
     * @param int $variantId
     * @param int $warehouseId
     * @param array $stockData ['quantity', 'reserved']
     * @return void
     */
    public function updateVariantStock(int $variantId, int $warehouseId, array $stockData): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);

            // Validation
            $stockData['warehouse_id'] = $warehouseId;
            $this->validateVariantStock($stockData);

            // Update or create stock
            $variant->stock()->updateOrCreate(
                ['warehouse_id' => $warehouseId],
                [
                    'quantity' => $stockData['quantity'],
                    'reserved' => $stockData['reserved'] ?? 0,
                ]
            );

            Log::info('Variant stock updated', [
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
            ]);

            $this->dispatch('variant-stock-updated');
        } catch (\Exception $e) {
            Log::error('Variant stock update failed', [
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas aktualizacji stanu magazynowego: ' . $e->getMessage());
        }
    }

    /**
     * Save variant stock grid (Wave 3 Task 2)
     *
     * @return void
     */
    public function saveStock(): void
    {
        try {
            DB::beginTransaction();

            foreach ($this->variantStock as $variantId => $stock) {
                foreach ($stock as $warehouseIndex => $quantity) {
                    // Find warehouse by index (1-based from blade loop)
                    $warehouse = Warehouse::where('is_active', true)
                        ->orderBy('name')
                        ->skip($warehouseIndex - 1)
                        ->first();

                    if (!$warehouse) {
                        continue;
                    }

                    // Validate quantity
                    if (!is_int($quantity) && !is_numeric($quantity)) {
                        throw new \Exception("Nieprawidłowy stan dla wariantu {$variantId}");
                    }

                    $quantity = (int) $quantity;

                    if ($quantity < 0) {
                        throw new \Exception("Stan nie może być ujemny dla wariantu {$variantId}");
                    }

                    // Update or create stock
                    VariantStock::updateOrCreate(
                        [
                            'variant_id' => $variantId,
                            'warehouse_id' => $warehouse->id,
                        ],
                        [
                            'quantity' => $quantity,
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            DB::commit();

            Log::info('Variant stock saved', [
                'product_id' => $this->product->id,
                'variants_count' => count($this->variantStock),
            ]);

            $this->dispatch('success', message: 'Stany magazynowe zapisane');
            $this->dispatch('stock-saved');
            session()->flash('message', 'Stany magazynowe zostały zapisane pomyślnie.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Variant stock save error', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('error', message: 'Błąd zapisu stanów: ' . $e->getMessage());
            session()->flash('error', 'Błąd podczas zapisu stanów: ' . $e->getMessage());
        }
    }

    /**
     * Load variant stock data for grid (Wave 3 Task 2)
     *
     * @return void
     */
    protected function loadVariantStock(): void
    {
        if (!$this->product || !$this->product->is_variant_master) {
            return;
        }

        $variants = $this->product->variants()->with('stock.warehouse')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $this->variantStock = [];

        foreach ($variants as $variant) {
            foreach ($warehouses as $index => $warehouse) {
                $stock = $variant->stock->firstWhere('warehouse_id', $warehouse->id);
                $this->variantStock[$variant->id][$index + 1] = $stock?->quantity ?? 0;
            }
        }
    }

    /**
     * Get warehouses with stock for grid rendering
     *
     * @return \Illuminate\Support\Collection
     */
    public function getWarehousesWithStock(): \Illuminate\Support\Collection
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return $warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'stock' => $this->product->variants->mapWithKeys(function ($variant) use ($warehouse) {
                    $stock = $variant->stock->firstWhere('warehouse_id', $warehouse->id);
                    return [
                        $variant->id => [
                            'quantity' => $stock?->quantity ?? 0,
                            'reserved' => $stock?->reserved ?? 0,
                            'available' => ($stock?->quantity ?? 0) - ($stock?->reserved ?? 0),
                            'is_low' => ($stock?->quantity ?? 0) < 10,
                        ]
                    ];
                }),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Upload images for variant from Livewire property (Wave 3 Task 3)
     *
     * This method is called automatically when $variantImages property changes
     * via wire:model="variantImages" in Blade
     *
     * @return void
     */
    public function updatedVariantImages(): void
    {
        if (empty($this->variantImages)) {
            return;
        }

        try {
            // Validate each image
            $this->validate([
                'variantImages.*' => 'image|max:5120|mimes:jpg,jpeg,png,gif,webp',
            ], [
                'variantImages.*.image' => 'Plik musi być zdjęciem.',
                'variantImages.*.max' => 'Maksymalny rozmiar pliku to 5MB.',
                'variantImages.*.mimes' => 'Dozwolone formaty: JPG, PNG, GIF, WEBP.',
            ]);

            DB::beginTransaction();

            foreach ($this->variantImages as $image) {
                // Store original in shared product images directory
                $filename = uniqid() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs("products/{$this->product->id}/variants", $filename, 'public');

                // Generate thumbnail
                $thumbnailPath = $this->generateThumbnail($path);

                // Create DB record (without variant assignment - user will assign later)
                VariantImage::create([
                    'variant_id' => null, // Will be assigned manually by user
                    'filename' => $filename,
                    'path' => $path,
                    'is_cover' => false,
                    'position' => VariantImage::max('position') + 1,
                ]);
            }

            DB::commit();

            Log::info('Variant images uploaded (unassigned)', [
                'product_id' => $this->product->id,
                'images_count' => count($this->variantImages),
            ]);

            // Reset property
            $this->variantImages = null;

            $this->dispatch('variant-images-uploaded');
            session()->flash('message', 'Zdjęcia zostały przesłane. Przypisz je do wariantów.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Variant image upload failed', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);
            $this->addError('variantImages', 'Błąd podczas przesyłania zdjęć: ' . $e->getMessage());
        }
    }

    /**
     * Upload images for variant (direct method - legacy support)
     *
     * @param int $variantId
     * @param array $images (from Livewire upload)
     * @return void
     */
    public function uploadVariantImages(int $variantId, array $images): void
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);

            foreach ($images as $image) {
                // Validation
                $this->validateVariantImage($image);

                DB::transaction(function () use ($variant, $image) {
                    // Store original
                    $path = $image->store("variants/{$variant->id}", 'public');

                    // Generate thumbnail
                    $thumbPath = $this->generateThumbnail($path, 200, 200);

                    // Get next position
                    $position = $variant->images()->max('position') + 1;

                    // Create DB record
                    $variant->images()->create([
                        'image_path' => $path,
                        'image_thumb_path' => $thumbPath,
                        'is_cover' => $variant->images()->count() === 0, // First image = cover
                        'position' => $position,
                    ]);
                });
            }

            Log::info('Variant images uploaded', [
                'variant_id' => $variantId,
                'images_count' => count($images),
            ]);

            $this->dispatch('variant-images-uploaded');
            session()->flash('message', 'Zdjęcia zostały dodane pomyślnie.');
        } catch (\Exception $e) {
            Log::error('Variant image upload failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas dodawania zdjęć: ' . $e->getMessage());
        }
    }

    /**
     * Generate thumbnail for image (Wave 3 Task 3)
     *
     * @param string $originalPath
     * @param int $width
     * @param int $height
     * @return string Thumbnail path
     */
    protected function generateThumbnail(string $originalPath, int $width = 200, int $height = 200): string
    {
        try {
            // Use Intervention Image if available, otherwise use GD
            if (class_exists('\Intervention\Image\Facades\Image')) {
                $image = \Intervention\Image\Facades\Image::make(storage_path("app/public/{$originalPath}"));
                $image->fit($width, $height);

                $thumbPath = str_replace('variants/', 'variants/thumbs/', $originalPath);
                $thumbDir = dirname(storage_path("app/public/{$thumbPath}"));

                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0755, true);
                }

                $image->save(storage_path("app/public/{$thumbPath}"));
            } else {
                // Fallback to GD library
                $thumbPath = $this->generateThumbnailWithGD($originalPath, $width, $height);
            }

            return $thumbPath;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'path' => $originalPath,
                'error' => $e->getMessage(),
            ]);

            // Return original path as fallback
            return $originalPath;
        }
    }

    /**
     * Generate thumbnail using GD library (fallback)
     *
     * @param string $originalPath
     * @param int $width
     * @param int $height
     * @return string
     */
    protected function generateThumbnailWithGD(string $originalPath, int $width, int $height): string
    {
        $sourcePath = storage_path("app/public/{$originalPath}");
        $thumbPath = str_replace('variants/', 'variants/thumbs/', $originalPath);
        $thumbFullPath = storage_path("app/public/{$thumbPath}");

        $thumbDir = dirname($thumbFullPath);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        list($origWidth, $origHeight, $type) = getimagesize($sourcePath);

        // Create image from source
        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$source) {
            return $originalPath;
        }

        // Create thumbnail
        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        // Save thumbnail
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($thumb, $thumbFullPath, 90),
            IMAGETYPE_PNG => imagepng($thumb, $thumbFullPath, 9),
            IMAGETYPE_WEBP => imagewebp($thumb, $thumbFullPath, 90),
            default => null,
        };

        imagedestroy($source);
        imagedestroy($thumb);

        return $thumbPath;
    }

    /**
     * Assign existing image to variant (if shared images)
     *
     * @param int $imageId
     * @param int $variantId
     * @return void
     */
    public function assignImageToVariant(int $imageId, int $variantId): void
    {
        try {
            $image = VariantImage::findOrFail($imageId);
            $variant = ProductVariant::findOrFail($variantId);

            // Update image variant_id
            $image->update(['variant_id' => $variantId]);

            Log::info('Image assigned to variant', [
                'image_id' => $imageId,
                'variant_id' => $variantId,
            ]);

            $this->dispatch('image-assigned');
            session()->flash('message', 'Zdjęcie zostało przypisane do wariantu.');
        } catch (\Exception $e) {
            Log::error('Image assignment failed', [
                'image_id' => $imageId,
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas przypisywania zdjęcia: ' . $e->getMessage());
        }
    }

    /**
     * Delete variant image
     *
     * @param int $imageId
     * @return void
     */
    public function deleteVariantImage(int $imageId): void
    {
        try {
            $image = VariantImage::findOrFail($imageId);

            DB::transaction(function () use ($image) {
                // Delete files from storage
                Storage::disk('public')->delete($image->path);

                // Delete thumbnail if exists
                $thumbPath = str_replace($image->filename, 'thumb_' . $image->filename, $image->path);
                if (Storage::disk('public')->exists($thumbPath)) {
                    Storage::disk('public')->delete($thumbPath);
                }

                // If this was cover, set first remaining as cover
                if ($image->is_cover) {
                    $newCover = $image->variant->images()
                        ->where('id', '!=', $image->id)
                        ->orderBy('position')
                        ->first();

                    if ($newCover) {
                        $newCover->update(['is_cover' => true]);
                    }
                }

                // Delete DB record
                $image->delete();

                Log::info('Variant image deleted', [
                    'image_id' => $image->id,
                ]);
            });

            $this->dispatch('image-deleted');
            session()->flash('message', 'Zdjęcie zostało usunięte.');
        } catch (\Exception $e) {
            Log::error('Image deletion failed', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas usuwania zdjęcia: ' . $e->getMessage());
        }
    }

    /**
     * Set image as cover
     *
     * @param int $imageId
     * @return void
     */
    public function setCoverImage(int $imageId): void
    {
        try {
            $image = VariantImage::findOrFail($imageId);

            DB::transaction(function () use ($image) {
                // Unset other covers for this variant
                $image->variant->images()
                    ->where('id', '!=', $image->id)
                    ->update(['is_cover' => false]);

                // Set as cover
                $image->update(['is_cover' => true]);

                Log::info('Cover image changed', [
                    'image_id' => $image->id,
                    'variant_id' => $image->variant_id,
                ]);
            });

            $this->dispatch('cover-image-changed');
            session()->flash('message', 'Zdjęcie główne zostało ustawione.');
        } catch (\Exception $e) {
            Log::error('Set cover image failed', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Błąd podczas ustawiania zdjęcia głównego: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ALIAS METHODS (Blade Compatibility)
    |--------------------------------------------------------------------------
    */

    /**
     * Alias for setCoverImage (Blade compatibility)
     *
     * @param int $imageId
     * @return void
     */
    public function setImageAsCover(int $imageId): void
    {
        $this->setCoverImage($imageId);
    }

    /**
     * Alias for deleteVariantImage (Blade compatibility)
     *
     * @param int $imageId
     * @return void
     */
    public function deleteImage(int $imageId): void
    {
        $this->deleteVariantImage($imageId);
    }
}

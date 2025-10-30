<?php

namespace App\Services\PrestaShop;

use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use App\Services\Product\AttributeTypeService;
use App\Services\Product\AttributeValueService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShopAttributeSyncService
 *
 * Synchronizes PPM AttributeType/AttributeValue ↔ PrestaShop attribute_groups/attributes
 *
 * FEATURES:
 * - Sync AttributeType → ps_attribute_group
 * - Sync AttributeValue → ps_attribute
 * - Create missing attributes in PrestaShop
 * - Verify sync status (synced/conflict/missing)
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since ETAP_05b Phase 2 (2025-10-24)
 */
class PrestaShopAttributeSyncService
{
    protected AttributeTypeService $typeService;
    protected AttributeValueService $valueService;

    public function __construct(
        AttributeTypeService $typeService,
        AttributeValueService $valueService
    ) {
        $this->typeService = $typeService;
        $this->valueService = $valueService;
    }

    /**
     * Sync AttributeType with PrestaShop attribute group
     *
     * @param int $attributeTypeId
     * @param int $shopId
     * @return array ['status' => string, 'ps_id' => int|null, 'message' => string]
     */
    public function syncAttributeGroup(int $attributeTypeId, int $shopId): array
    {
        return DB::transaction(function () use ($attributeTypeId, $shopId) {
            try {
                $attributeType = AttributeType::findOrFail($attributeTypeId);
                $shop = PrestaShopShop::findOrFail($shopId);
                $client = PrestaShopClientFactory::create($shop);

                // Query PrestaShop API
                $queryParams = http_build_query([
                    'display' => 'full',
                    'filter[name]' => '[' . $attributeType->name . ']',
                    'output_format' => 'JSON',
                ]);

                $response = $client->makeRequest('GET', "/product_options?{$queryParams}");

                if (isset($response['product_options']) && !empty($response['product_options'])) {
                    $psGroup = is_array($response['product_options']) ? $response['product_options'][0] : $response['product_options'];
                    $psGroupId = (int) $psGroup['id'];

                    // Verify group_type compatibility
                    $expectedGroupType = $attributeType->display_type === 'color' ? 'color' : 'select';
                    $actualGroupType = $psGroup['group_type'] ?? 'select';

                    if ($expectedGroupType !== $actualGroupType) {
                        $this->updateMapping($attributeType->id, $shop->id, [
                            'prestashop_attribute_group_id' => $psGroupId,
                            'prestashop_label' => $psGroup['name'][0]['value'] ?? $attributeType->name,
                            'sync_status' => 'conflict',
                            'sync_notes' => "Group type mismatch: PPM={$expectedGroupType}, PS={$actualGroupType}",
                            'is_synced' => false,
                            'last_synced_at' => now(),
                        ]);

                        return ['status' => 'conflict', 'ps_id' => $psGroupId, 'message' => 'Group type mismatch'];
                    }

                    // SUCCESS
                    $this->updateMapping($attributeType->id, $shop->id, [
                        'prestashop_attribute_group_id' => $psGroupId,
                        'prestashop_label' => $psGroup['name'][0]['value'] ?? $attributeType->name,
                        'sync_status' => 'synced',
                        'sync_notes' => null,
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);

                    return ['status' => 'synced', 'ps_id' => $psGroupId, 'message' => 'Successfully synced'];

                } else {
                    // NOT FOUND
                    $this->updateMapping($attributeType->id, $shop->id, [
                        'prestashop_attribute_group_id' => null,
                        'prestashop_label' => null,
                        'sync_status' => 'missing',
                        'sync_notes' => 'Not found in PrestaShop',
                        'is_synced' => false,
                        'last_synced_at' => now(),
                    ]);

                    return ['status' => 'missing', 'ps_id' => null, 'message' => 'Not found in PrestaShop'];
                }

            } catch (\Exception $e) {
                Log::error('PrestaShopAttributeSyncService::syncAttributeGroup FAILED', [
                    'attribute_type_id' => $attributeTypeId,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create attribute group in PrestaShop
     *
     * @param int $attributeTypeId
     * @param int $shopId
     * @return int PrestaShop attribute_group_id
     * @throws \Exception
     */
    public function createAttributeGroupInPS(int $attributeTypeId, int $shopId): int
    {
        return DB::transaction(function () use ($attributeTypeId, $shopId) {
            try {
                $attributeType = AttributeType::findOrFail($attributeTypeId);
                $shop = PrestaShopShop::findOrFail($shopId);
                $client = PrestaShopClientFactory::create($shop);

                // Generate XML for PrestaShop API
                $xml = $this->generateAttributeGroupXML($attributeType);

                // POST to PrestaShop API
                // IMPORTANT: Pass body in $options (4th param), not $data (3rd param)
                $response = $client->makeRequest('POST', '/product_options', [], [
                    'body' => $xml,
                    'headers' => ['Content-Type' => 'application/xml'],
                ]);

                $psGroupId = (int) $response['product_option']['id'];

                Log::info('Created attribute group in PrestaShop', [
                    'attribute_type_id' => $attributeTypeId,
                    'shop_id' => $shopId,
                    'ps_attribute_group_id' => $psGroupId,
                ]);

                // Update mapping
                $this->updateMapping($attributeType->id, $shop->id, [
                    'prestashop_attribute_group_id' => $psGroupId,
                    'prestashop_label' => $attributeType->name,
                    'sync_status' => 'synced',
                    'sync_notes' => 'Created in PrestaShop',
                    'is_synced' => true,
                    'last_synced_at' => now(),
                ]);

                return $psGroupId;

            } catch (\Exception $e) {
                Log::error('PrestaShopAttributeSyncService::createAttributeGroupInPS FAILED', [
                    'attribute_type_id' => $attributeTypeId,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Generate PrestaShop XML for product_option
     *
     * PrestaShop API XML requirements:
     * - Must include xmlns:xlink namespace
     * - Field order: is_color_group, group_type, name, public_name
     * - CDATA for text values
     *
     * @param AttributeType $type
     * @return string XML
     */
    protected function generateAttributeGroupXML(AttributeType $type): string
    {
        $groupType = $type->display_type === 'color' ? 'color' : 'select';
        $isColorGroup = $type->display_type === 'color' ? '1' : '0';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
  <product_option>
    <is_color_group><![CDATA[{$isColorGroup}]]></is_color_group>
    <group_type><![CDATA[{$groupType}]]></group_type>
    <name>
      <language id="1"><![CDATA[{$type->name}]]></language>
    </name>
    <public_name>
      <language id="1"><![CDATA[{$type->name}]]></language>
    </public_name>
  </product_option>
</prestashop>
XML;
    }

    /**
     * Sync AttributeValue with PrestaShop attribute
     *
     * @param int $attributeValueId
     * @param int $shopId
     * @return array ['status' => string, 'ps_id' => int|null, 'message' => string]
     */
    public function syncAttributeValue(int $attributeValueId, int $shopId): array
    {
        return DB::transaction(function () use ($attributeValueId, $shopId) {
            try {
                $attributeValue = AttributeValue::with('attributeType')->findOrFail($attributeValueId);
                $shop = PrestaShopShop::findOrFail($shopId);
                $client = PrestaShopClientFactory::create($shop);

                // Check if AttributeType is mapped to PrestaShop
                $groupMapping = DB::table('prestashop_attribute_group_mapping')
                    ->where('attribute_type_id', $attributeValue->attribute_type_id)
                    ->where('prestashop_shop_id', $shopId)
                    ->first();

                if (!$groupMapping || !$groupMapping->prestashop_attribute_group_id) {
                    $this->updateValueMapping($attributeValue->id, $shop->id, [
                        'prestashop_attribute_id' => null,
                        'prestashop_label' => null,
                        'prestashop_color' => null,
                        'sync_status' => 'conflict',
                        'sync_notes' => 'Parent AttributeType not mapped to PrestaShop',
                        'is_synced' => false,
                        'last_synced_at' => now(),
                    ]);

                    return ['status' => 'conflict', 'ps_id' => null, 'message' => 'Parent AttributeType not mapped'];
                }

                $psGroupId = $groupMapping->prestashop_attribute_group_id;

                // Query PrestaShop API for existing attribute
                $queryParams = http_build_query([
                    'display' => 'full',
                    'filter[id_attribute_group]' => $psGroupId,
                    'filter[name]' => '[' . $attributeValue->label . ']',
                    'output_format' => 'JSON',
                ]);

                $response = $client->makeRequest('GET', "/product_option_values?{$queryParams}");

                if (isset($response['product_option_values']) && !empty($response['product_option_values'])) {
                    $psAttribute = is_array($response['product_option_values']) ? $response['product_option_values'][0] : $response['product_option_values'];
                    $psAttributeId = (int) $psAttribute['id'];

                    // Compare color if AttributeType is color
                    if ($attributeValue->attributeType->display_type === 'color') {
                        $psColor = $psAttribute['color'] ?? null;
                        $ppmColor = $attributeValue->color_hex;

                        if ($psColor && $ppmColor && strtolower($psColor) !== strtolower($ppmColor)) {
                            $this->updateValueMapping($attributeValue->id, $shop->id, [
                                'prestashop_attribute_id' => $psAttributeId,
                                'prestashop_label' => $psAttribute['name'][0]['value'] ?? $attributeValue->label,
                                'prestashop_color' => $psColor,
                                'sync_status' => 'conflict',
                                'sync_notes' => "Color mismatch: PPM={$ppmColor}, PS={$psColor}",
                                'is_synced' => false,
                                'last_synced_at' => now(),
                            ]);

                            return ['status' => 'conflict', 'ps_id' => $psAttributeId, 'message' => 'Color mismatch'];
                        }
                    }

                    // SUCCESS
                    $this->updateValueMapping($attributeValue->id, $shop->id, [
                        'prestashop_attribute_id' => $psAttributeId,
                        'prestashop_label' => $psAttribute['name'][0]['value'] ?? $attributeValue->label,
                        'prestashop_color' => $psAttribute['color'] ?? null,
                        'sync_status' => 'synced',
                        'sync_notes' => null,
                        'is_synced' => true,
                        'last_synced_at' => now(),
                    ]);

                    return ['status' => 'synced', 'ps_id' => $psAttributeId, 'message' => 'Successfully synced'];

                } else {
                    // NOT FOUND
                    $this->updateValueMapping($attributeValue->id, $shop->id, [
                        'prestashop_attribute_id' => null,
                        'prestashop_label' => null,
                        'prestashop_color' => null,
                        'sync_status' => 'missing',
                        'sync_notes' => 'Not found in PrestaShop',
                        'is_synced' => false,
                        'last_synced_at' => now(),
                    ]);

                    return ['status' => 'missing', 'ps_id' => null, 'message' => 'Not found in PrestaShop'];
                }

            } catch (\Exception $e) {
                Log::error('PrestaShopAttributeSyncService::syncAttributeValue FAILED', [
                    'attribute_value_id' => $attributeValueId,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    protected function updateMapping(int $attributeTypeId, int $shopId, array $data): void
    {
        DB::table('prestashop_attribute_group_mapping')->updateOrInsert(
            ['attribute_type_id' => $attributeTypeId, 'prestashop_shop_id' => $shopId],
            array_merge($data, ['updated_at' => now()])
        );
    }

    protected function updateValueMapping(int $attributeValueId, int $shopId, array $data): void
    {
        DB::table('prestashop_attribute_value_mapping')->updateOrInsert(
            ['attribute_value_id' => $attributeValueId, 'prestashop_shop_id' => $shopId],
            array_merge($data, ['updated_at' => now()])
        );
    }
}

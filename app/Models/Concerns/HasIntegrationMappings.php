<?php

namespace App\Models\Concerns;

use App\Models\IntegrationMapping;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasIntegrationMappings Trait - Universal Integration Mapping Support
 *
 * Responsibility: Polymorphic IntegrationMapping relation for any entity
 *
 * Features:
 * - integrationMappings() morphMany relationship
 * - Helper methods for finding/creating mappings
 * - Support for Baselinker, PrestaShop, Subiekt GT, Dynamics
 *
 * Usage: Use in any model that needs ERP/integration sync
 * - Product (via HasSyncStatus which has its own implementation)
 * - ProductVariant (ETAP_08.6 - variant sync to Baselinker)
 * - Media (ETAP_08.6 - image sync to Baselinker)
 *
 * @package App\Models\Concerns
 * @version 1.0
 * @since ETAP_08.6 - ERP Full Feature Parity
 */
trait HasIntegrationMappings
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Integration mappings polymorphic relationship (1:many)
     *
     * Universal mapping: PrestaShop, Baselinker, Subiekt GT, Dynamics
     * Performance: Optimized for sync operations with ordering
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function integrationMappings(): MorphMany
    {
        return $this->morphMany(IntegrationMapping::class, 'mappable')
                    ->orderBy('integration_type', 'asc')
                    ->orderBy('integration_identifier', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get integration mapping for specific type and identifier
     *
     * @param string $integrationType baselinker|prestashop|subiekt_gt|dynamics
     * @param string $integrationIdentifier Instance name or shop ID
     * @return IntegrationMapping|null
     */
    public function getIntegrationMapping(string $integrationType, string $integrationIdentifier): ?IntegrationMapping
    {
        return $this->integrationMappings()
            ->where('integration_type', $integrationType)
            ->where('integration_identifier', $integrationIdentifier)
            ->first();
    }

    /**
     * Get or create integration mapping
     *
     * @param string $integrationType
     * @param string $integrationIdentifier
     * @param array $attributes Additional attributes for creation
     * @return IntegrationMapping
     */
    public function findOrCreateIntegrationMapping(
        string $integrationType,
        string $integrationIdentifier,
        array $attributes = []
    ): IntegrationMapping {
        $mapping = $this->getIntegrationMapping($integrationType, $integrationIdentifier);

        if (!$mapping) {
            $mapping = new IntegrationMapping(array_merge([
                'integration_type' => $integrationType,
                'integration_identifier' => $integrationIdentifier,
                'sync_status' => 'pending',
                'sync_direction' => 'both',
            ], $attributes));

            $this->integrationMappings()->save($mapping);
        }

        return $mapping;
    }

    /**
     * Check if entity has mapping for specific integration
     *
     * @param string $integrationType
     * @param string|null $integrationIdentifier
     * @return bool
     */
    public function hasIntegrationMapping(string $integrationType, ?string $integrationIdentifier = null): bool
    {
        $query = $this->integrationMappings()
            ->where('integration_type', $integrationType);

        if ($integrationIdentifier !== null) {
            $query->where('integration_identifier', $integrationIdentifier);
        }

        return $query->exists();
    }

    /**
     * Get external ID for specific integration
     *
     * @param string $integrationType
     * @param string $integrationIdentifier
     * @return string|null
     */
    public function getExternalId(string $integrationType, string $integrationIdentifier): ?string
    {
        $mapping = $this->getIntegrationMapping($integrationType, $integrationIdentifier);
        return $mapping?->external_id;
    }

    /**
     * Set external ID for specific integration
     *
     * @param string $integrationType
     * @param string $integrationIdentifier
     * @param string $externalId
     * @return IntegrationMapping
     */
    public function setExternalId(
        string $integrationType,
        string $integrationIdentifier,
        string $externalId
    ): IntegrationMapping {
        $mapping = $this->findOrCreateIntegrationMapping($integrationType, $integrationIdentifier);
        $mapping->external_id = $externalId;
        $mapping->save();

        return $mapping;
    }

    /**
     * Get all Baselinker mappings
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBaselinkerMappings()
    {
        return $this->integrationMappings()
            ->where('integration_type', 'baselinker')
            ->get();
    }

    /**
     * Check if synced to Baselinker
     *
     * @param string $instanceName Baselinker instance name
     * @return bool
     */
    public function isSyncedToBaselinker(string $instanceName): bool
    {
        $mapping = $this->getIntegrationMapping('baselinker', $instanceName);
        return $mapping && $mapping->sync_status === 'synced';
    }
}

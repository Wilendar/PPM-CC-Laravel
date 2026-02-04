<?php

namespace App\Http\Livewire\Admin\Scan\Traits;

use App\Models\ERPConnection;
use App\Models\PrestaShopShop;
use Illuminate\Support\Collection;

/**
 * Trait for managing scan sources (ERP connections and PrestaShop shops)
 *
 * ETAP_10 FAZA 3: Product Scan System - Sources Management
 *
 * @package App\Http\Livewire\Admin\Scan\Traits
 */
trait ScanSourcesTrait
{
    /**
     * Get available sources based on selected type
     *
     * @return Collection<int, array>
     */
    public function getAvailableSources(): Collection
    {
        $sources = collect();

        // Add ERP connections
        $erpConnections = ERPConnection::active()
            ->orderBy('priority')
            ->get(['id', 'erp_type', 'instance_name', 'is_default']);

        foreach ($erpConnections as $connection) {
            $sources->push([
                'id' => $connection->id,
                'type' => $connection->erp_type,
                'name' => $connection->instance_name,
                'is_default' => $connection->is_default,
                'group' => 'ERP',
                'icon' => $this->getSourceIcon($connection->erp_type),
            ]);
        }

        // Add PrestaShop shops
        $shops = PrestaShopShop::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'url']);

        foreach ($shops as $shop) {
            $sources->push([
                'id' => $shop->id,
                'type' => 'prestashop',
                'name' => $shop->name,
                'is_default' => false,
                'group' => 'PrestaShop',
                'icon' => 'prestashop',
                'url' => $shop->url,
            ]);
        }

        return $sources;
    }

    /**
     * Get grouped sources for dropdown
     *
     * @return array<string, Collection>
     */
    public function getGroupedSources(): array
    {
        return $this->getAvailableSources()
            ->groupBy('group')
            ->toArray();
    }

    /**
     * Get icon for source type
     *
     * @param string $sourceType
     * @return string
     */
    protected function getSourceIcon(string $sourceType): string
    {
        return match ($sourceType) {
            'subiekt_gt' => 'subiekt',
            'baselinker' => 'baselinker',
            'dynamics' => 'dynamics',
            'prestashop' => 'prestashop',
            default => 'database',
        };
    }

    /**
     * Get source name by type and ID
     *
     * @param string $sourceType
     * @param int $sourceId
     * @return string
     */
    public function getSourceName(string $sourceType, int $sourceId): string
    {
        if ($sourceType === 'prestashop') {
            $shop = PrestaShopShop::find($sourceId);
            return $shop ? $shop->name : 'Unknown Shop';
        }

        $connection = ERPConnection::find($sourceId);
        return $connection ? $connection->instance_name : 'Unknown Connection';
    }

    /**
     * Validate selected source
     *
     * @return bool
     */
    protected function validateSelectedSource(): bool
    {
        if (empty($this->selectedSourceType) || empty($this->selectedSourceId)) {
            session()->flash('error', 'Wybierz zrodlo skanowania');
            return false;
        }

        if ($this->selectedSourceType === 'prestashop') {
            $shop = PrestaShopShop::find($this->selectedSourceId);
            if (!$shop || !$shop->is_active) {
                session()->flash('error', 'Wybrany sklep PrestaShop jest nieaktywny');
                return false;
            }
        } else {
            $connection = ERPConnection::find($this->selectedSourceId);
            if (!$connection || !$connection->is_active) {
                session()->flash('error', 'Wybrane polaczenie ERP jest nieaktywne');
                return false;
            }
        }

        return true;
    }
}

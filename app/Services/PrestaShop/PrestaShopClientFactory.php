<?php

namespace App\Services\PrestaShop;

use App\Models\PrestaShopShop;
use InvalidArgumentException;

/**
 * Factory for creating PrestaShop API clients
 *
 * Automatically selects correct client version (8 or 9) based on shop configuration
 */
class PrestaShopClientFactory
{
    /**
     * Create PrestaShop client for given shop
     *
     * @param PrestaShopShop $shop Shop configuration
     * @return BasePrestaShopClient PrestaShop8Client or PrestaShop9Client
     * @throws InvalidArgumentException If unsupported version
     */
    public static function create(PrestaShopShop $shop): BasePrestaShopClient
    {
        return match($shop->version) {
            '8' => new PrestaShop8Client($shop),
            '9' => new PrestaShop9Client($shop),
            default => throw new InvalidArgumentException(
                "Unsupported PrestaShop version: {$shop->version}. Supported versions: 8, 9"
            )
        };
    }

    /**
     * Create multiple clients for multiple shops
     *
     * @param array $shops Array of PrestaShopShop models
     * @return array Associative array [shop_id => client]
     */
    public static function createMultiple(array $shops): array
    {
        $clients = [];

        foreach ($shops as $shop) {
            $clients[$shop->id] = self::create($shop);
        }

        return $clients;
    }

    /**
     * Create clients for all active shops
     *
     * @return array Associative array [shop_id => client]
     */
    public static function createForAllActiveShops(): array
    {
        $activeShops = PrestaShopShop::where('is_active', true)->get();

        return self::createMultiple($activeShops->all());
    }
}

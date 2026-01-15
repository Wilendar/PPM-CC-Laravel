<?php

declare(strict_types=1);

namespace App\Services\VisualEditor\Styleset;

use App\Contracts\VisualEditor\ShopStylesetDefinitionInterface;
use App\Models\PrestaShopShop;
use App\Services\VisualEditor\Styleset\ShopStylesets\B2BStyleset;
use App\Services\VisualEditor\Styleset\ShopStylesets\KayoStyleset;
use App\Services\VisualEditor\Styleset\ShopStylesets\MrfStyleset;
use App\Services\VisualEditor\Styleset\ShopStylesets\PitgangStyleset;
use App\Services\VisualEditor\Styleset\ShopStylesets\YcfStyleset;
use Illuminate\Support\Str;

/**
 * Factory for creating shop-specific styleset definitions.
 *
 * Determines the appropriate styleset based on shop name/URL
 * and returns the corresponding definition class.
 */
class StylesetFactory
{
    /**
     * Registered styleset definitions.
     */
    private array $definitions = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * Get styleset definition for a shop.
     *
     * @param PrestaShopShop|int|null $shop Shop model or ID
     * @return ShopStylesetDefinitionInterface
     */
    public function getForShop(PrestaShopShop|int|null $shop): ShopStylesetDefinitionInterface
    {
        if ($shop instanceof PrestaShopShop) {
            $type = $this->detectShopType($shop);
        } elseif (is_int($shop)) {
            $shopModel = PrestaShopShop::find($shop);
            $type = $shopModel ? $this->detectShopType($shopModel) : 'b2b';
        } else {
            $type = 'b2b';
        }

        return $this->get($type);
    }

    /**
     * Get styleset definition by type.
     *
     * @param string $type b2b, kayo, ycf, pitgang, mrf
     * @return ShopStylesetDefinitionInterface
     */
    public function get(string $type): ShopStylesetDefinitionInterface
    {
        $type = strtolower($type);

        if (isset($this->definitions[$type])) {
            return new $this->definitions[$type]();
        }

        // Fallback to B2B
        return new B2BStyleset();
    }

    /**
     * Get all registered styleset types.
     *
     * @return array ['type' => 'Name', ...]
     */
    public function getAvailableTypes(): array
    {
        $types = [];

        foreach ($this->definitions as $type => $class) {
            $instance = new $class();
            $types[$type] = $instance->getName();
        }

        return $types;
    }

    /**
     * Register a styleset definition.
     *
     * @param string $type Type identifier
     * @param string $class Class name implementing ShopStylesetDefinitionInterface
     */
    public function register(string $type, string $class): void
    {
        $this->definitions[strtolower($type)] = $class;
    }

    /**
     * Check if a type is registered.
     */
    public function has(string $type): bool
    {
        return isset($this->definitions[strtolower($type)]);
    }

    /**
     * Detect shop type from shop model.
     */
    private function detectShopType(PrestaShopShop $shop): string
    {
        $name = strtolower($shop->name ?? '');
        $url = strtolower($shop->url ?? '');

        // KAYO detection
        if (Str::contains($name, 'kayo') || Str::contains($url, 'kayo')) {
            return 'kayo';
        }

        // YCF detection
        if (Str::contains($name, 'ycf') || Str::contains($url, 'ycf')) {
            return 'ycf';
        }

        // Pitgang detection
        if (Str::contains($name, 'pitgang') || Str::contains($url, 'pitgang')) {
            return 'pitgang';
        }

        // MRF detection
        if (Str::contains($name, 'mrf') || Str::contains($url, 'pitbikemrf') || Str::contains($url, 'mrf')) {
            return 'mrf';
        }

        // B2B detection (default for professional/wholesale shops)
        if (Str::contains($name, 'b2b') || Str::contains($url, 'b2b') || Str::contains($name, 'hurt')) {
            return 'b2b';
        }

        // Default to B2B
        return 'b2b';
    }

    /**
     * Register default styleset definitions.
     */
    private function registerDefaults(): void
    {
        $this->definitions = [
            'b2b' => B2BStyleset::class,
            'kayo' => KayoStyleset::class,
            'ycf' => YcfStyleset::class,
            'pitgang' => PitgangStyleset::class,
            'mrf' => MrfStyleset::class,
        ];
    }
}

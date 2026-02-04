<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Data Transfer Object for product status aggregation in Product List.
 *
 * Contains all issues/warnings for a single product across:
 * - Global issues (price, stock, images, PrestaShop presence)
 * - Per-shop issues (data discrepancies)
 * - Per-ERP issues (data discrepancies)
 * - Variant issues
 *
 * @package App\DTOs
 * @since 2026-02-04
 * @see Plan_Projektu/synthetic-mixing-thunder.md
 */
class ProductStatusDTO
{
    /**
     * Issue type constants for global issues
     */
    public const ISSUE_ZERO_PRICE = 'zero_price';
    public const ISSUE_LOW_STOCK = 'low_stock';
    public const ISSUE_NO_IMAGES = 'no_images';
    public const ISSUE_NOT_IN_PRESTASHOP = 'not_in_prestashop';

    /**
     * Issue type constants for per-integration discrepancies
     */
    public const ISSUE_BASIC_DATA = 'basic';
    public const ISSUE_DESCRIPTIONS = 'desc';
    public const ISSUE_PHYSICAL = 'physical';
    public const ISSUE_IMAGES_MAPPING = 'images';
    public const ISSUE_ATTRIBUTES = 'attributes';
    public const ISSUE_COMPATIBILITY = 'compatibility';

    /**
     * Variant issue constants
     */
    public const VARIANT_NO_IMAGES = 'no_images';
    public const VARIANT_ZERO_PRICE = 'zero_price';
    public const VARIANT_LOW_STOCK = 'low_stock';

    /**
     * Severity levels
     */
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_OK = 'ok';

    public int $productId;

    /**
     * Global issues (not per-integration)
     * @var array<string, bool>
     */
    public array $globalIssues = [
        self::ISSUE_ZERO_PRICE => false,
        self::ISSUE_LOW_STOCK => false,
        self::ISSUE_NO_IMAGES => false,
        self::ISSUE_NOT_IN_PRESTASHOP => false,
    ];

    /**
     * Per-shop issues
     * @var array<int, array<string>> [shop_id => ['basic', 'desc', 'physical', 'images']]
     */
    public array $shopIssues = [];

    /**
     * Per-ERP issues
     * @var array<int, array<string>> [erp_connection_id => ['basic', 'desc', 'physical']]
     */
    public array $erpIssues = [];

    /**
     * Variant issues
     * @var array<int, array<string>> [variant_id => ['no_images', 'zero_price', 'low_stock']]
     */
    public array $variantIssues = [];

    /**
     * Extra metadata for display
     * @var array<string, mixed>
     */
    public array $metadata = [];

    /**
     * All connected PrestaShop shops (regardless of issues)
     * @var array<int, array{name: string, color: string, icon: string, hasIssues: bool}>
     */
    public array $connectedShops = [];

    /**
     * All connected ERP systems (regardless of issues)
     * @var array<int, array{name: string, color: string, icon: string, hasIssues: bool}>
     */
    public array $connectedErps = [];

    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    /**
     * Set a global issue
     */
    public function setGlobalIssue(string $issue, bool $hasIssue = true): self
    {
        if (array_key_exists($issue, $this->globalIssues)) {
            $this->globalIssues[$issue] = $hasIssue;
        }
        return $this;
    }

    /**
     * Add shop issue
     */
    public function addShopIssue(int $shopId, string $issueType): self
    {
        if (!isset($this->shopIssues[$shopId])) {
            $this->shopIssues[$shopId] = [];
        }
        if (!in_array($issueType, $this->shopIssues[$shopId])) {
            $this->shopIssues[$shopId][] = $issueType;
        }
        return $this;
    }

    /**
     * Add ERP issue
     */
    public function addErpIssue(int $erpConnectionId, string $issueType): self
    {
        if (!isset($this->erpIssues[$erpConnectionId])) {
            $this->erpIssues[$erpConnectionId] = [];
        }
        if (!in_array($issueType, $this->erpIssues[$erpConnectionId])) {
            $this->erpIssues[$erpConnectionId][] = $issueType;
        }
        return $this;
    }

    /**
     * Add variant issue
     */
    public function addVariantIssue(int $variantId, string $issueType): self
    {
        if (!isset($this->variantIssues[$variantId])) {
            $this->variantIssues[$variantId] = [];
        }
        if (!in_array($issueType, $this->variantIssues[$variantId])) {
            $this->variantIssues[$variantId][] = $issueType;
        }
        return $this;
    }

    /**
     * Set metadata value
     */
    public function setMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Add connected shop (for showing all integrations in status column)
     */
    public function addConnectedShop(int $shopId, string $name, string $color = '06b6d4', string $icon = 'shopping-cart'): self
    {
        $this->connectedShops[$shopId] = [
            'name' => $name,
            'color' => $color,
            'icon' => $icon,
            'hasIssues' => isset($this->shopIssues[$shopId]),
        ];
        return $this;
    }

    /**
     * Add connected ERP (for showing all integrations in status column)
     */
    public function addConnectedErp(int $erpId, string $name, string $color = 'f97316', string $icon = 'database'): self
    {
        $this->connectedErps[$erpId] = [
            'name' => $name,
            'color' => $color,
            'icon' => $icon,
            'hasIssues' => isset($this->erpIssues[$erpId]),
        ];
        return $this;
    }

    /**
     * Update hasIssues flag for all connected integrations
     * (call after all issues have been added)
     */
    public function finalizeConnectedIntegrations(): self
    {
        foreach ($this->connectedShops as $shopId => &$shop) {
            $shop['hasIssues'] = isset($this->shopIssues[$shopId]);
        }
        foreach ($this->connectedErps as $erpId => &$erp) {
            $erp['hasIssues'] = isset($this->erpIssues[$erpId]);
        }
        return $this;
    }

    /**
     * Get shops without any issues
     * @return array<int, array>
     */
    public function getShopsWithoutIssues(): array
    {
        return array_filter($this->connectedShops, fn($shop) => !$shop['hasIssues']);
    }

    /**
     * Get ERPs without any issues
     * @return array<int, array>
     */
    public function getErpsWithoutIssues(): array
    {
        return array_filter($this->connectedErps, fn($erp) => !$erp['hasIssues']);
    }

    /**
     * Check if product has any connected integrations
     */
    public function hasConnectedIntegrations(): bool
    {
        return !empty($this->connectedShops) || !empty($this->connectedErps);
    }

    /**
     * Check if product has any issues at all
     */
    public function hasAnyIssues(): bool
    {
        // Check global issues
        if (in_array(true, $this->globalIssues, true)) {
            return true;
        }

        // Check shop issues
        if (!empty($this->shopIssues)) {
            return true;
        }

        // Check ERP issues
        if (!empty($this->erpIssues)) {
            return true;
        }

        // Check variant issues
        if (!empty($this->variantIssues)) {
            return true;
        }

        return false;
    }

    /**
     * Check if product has global issues only
     */
    public function hasGlobalIssues(): bool
    {
        return in_array(true, $this->globalIssues, true);
    }

    /**
     * Get overall severity level
     */
    public function getSeverity(): string
    {
        // Critical: zero price or not in any shop
        if ($this->globalIssues[self::ISSUE_ZERO_PRICE] ||
            $this->globalIssues[self::ISSUE_NOT_IN_PRESTASHOP]) {
            return self::SEVERITY_CRITICAL;
        }

        // Warning: any other issue
        if ($this->hasAnyIssues()) {
            return self::SEVERITY_WARNING;
        }

        return self::SEVERITY_OK;
    }

    /**
     * Get total count of all issues
     */
    public function getIssueCount(): int
    {
        $count = 0;

        // Count global issues
        $count += count(array_filter($this->globalIssues));

        // Count shop issues (each shop's issues count as one)
        $count += count($this->shopIssues);

        // Count ERP issues
        $count += count($this->erpIssues);

        // Count variant issues
        $count += count($this->variantIssues);

        return $count;
    }

    /**
     * Get flat list of all global issue types that are active
     * @return array<string>
     */
    public function getActiveGlobalIssues(): array
    {
        return array_keys(array_filter($this->globalIssues));
    }

    /**
     * Get summary for display/debugging
     */
    public function getSummary(): array
    {
        return [
            'product_id' => $this->productId,
            'severity' => $this->getSeverity(),
            'issue_count' => $this->getIssueCount(),
            'has_issues' => $this->hasAnyIssues(),
            'global_issues' => $this->getActiveGlobalIssues(),
            'shop_issues_count' => count($this->shopIssues),
            'erp_issues_count' => count($this->erpIssues),
            'variant_issues_count' => count($this->variantIssues),
        ];
    }

    /**
     * Get issue labels in Polish for display
     */
    public static function getIssueLabels(): array
    {
        return [
            // Global
            self::ISSUE_ZERO_PRICE => 'Cena 0,00 zł',
            self::ISSUE_LOW_STOCK => 'Poniżej stanu min.',
            self::ISSUE_NO_IMAGES => 'Brak zdjęć',
            self::ISSUE_NOT_IN_PRESTASHOP => 'Brak w PrestaShop',
            // Per-integration
            self::ISSUE_BASIC_DATA => 'Dane podstawowe',
            self::ISSUE_DESCRIPTIONS => 'Opisy',
            self::ISSUE_PHYSICAL => 'Wymiary/waga',
            self::ISSUE_IMAGES_MAPPING => 'Zdjęcia',
            self::ISSUE_ATTRIBUTES => 'Atrybuty',
            self::ISSUE_COMPATIBILITY => 'Dopasowania',
            // Variants
            self::VARIANT_NO_IMAGES => 'Brak zdjęć',
            self::VARIANT_ZERO_PRICE => 'Cena 0,00 zł',
            self::VARIANT_LOW_STOCK => 'Poniżej min.',
        ];
    }

    /**
     * Get issue colors for UI
     */
    public static function getIssueColors(): array
    {
        return [
            self::ISSUE_ZERO_PRICE => 'red',
            self::ISSUE_LOW_STOCK => 'yellow',
            self::ISSUE_NO_IMAGES => 'orange',
            self::ISSUE_NOT_IN_PRESTASHOP => 'gray',
            self::ISSUE_BASIC_DATA => 'orange',
            self::ISSUE_DESCRIPTIONS => 'yellow',
            self::ISSUE_PHYSICAL => 'yellow',
            self::ISSUE_IMAGES_MAPPING => 'orange',
            self::ISSUE_ATTRIBUTES => 'purple',
            self::ISSUE_COMPATIBILITY => 'blue',
        ];
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'global_issues' => $this->globalIssues,
            'shop_issues' => $this->shopIssues,
            'erp_issues' => $this->erpIssues,
            'variant_issues' => $this->variantIssues,
            'connected_shops' => $this->connectedShops,
            'connected_erps' => $this->connectedErps,
            'metadata' => $this->metadata,
            'severity' => $this->getSeverity(),
            'issue_count' => $this->getIssueCount(),
        ];
    }
}

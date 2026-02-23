<?php

namespace App\Services\Compatibility;

use App\Models\SmartKeywordRule;
use App\Models\Product;
use App\Services\Compatibility\Results\KeywordMatchResult;
use Illuminate\Support\Facades\Cache;

class KeywordRuleMatcher
{
    protected const CACHE_KEY = 'smart_keyword_rules_active';
    protected const CACHE_TTL = 3600;

    public function match(Product $product, Product $vehicle): KeywordMatchResult
    {
        $rules = $this->getActiveRules();

        if ($rules->isEmpty()) {
            return KeywordMatchResult::noMatch();
        }

        $productName = mb_strtolower(trim($product->name ?? ''));
        $productSku = mb_strtolower(trim($product->sku ?? ''));
        $matchedRules = [];
        $highestBonus = 0.0;
        $matchedKeyword = null;

        foreach ($rules as $rule) {
            $keyword = $rule->keyword_normalized;

            if (!$this->keywordMatchesField($keyword, $rule->match_field, $rule->match_type, $productName, $productSku)) {
                continue;
            }

            if (!$this->vehicleMatchesTarget($vehicle, $rule)) {
                continue;
            }

            $matchedRules[] = $rule;

            if ($rule->score_bonus > $highestBonus) {
                $highestBonus = $rule->score_bonus;
                $matchedKeyword = $rule->keyword;
            }
        }

        if (empty($matchedRules)) {
            return KeywordMatchResult::noMatch();
        }

        return new KeywordMatchResult(true, $highestBonus, $matchedRules, $matchedKeyword);
    }

    protected function keywordMatchesField(string $keyword, string $matchField, string $matchType, string $name, string $sku): bool
    {
        $fields = match ($matchField) {
            'name' => [$name],
            'sku' => [$sku],
            'any' => [$name, $sku],
            default => [$name, $sku],
        };

        foreach ($fields as $fieldValue) {
            if (empty($fieldValue)) continue;

            $matched = match ($matchType) {
                'exact' => $fieldValue === $keyword,
                'starts_with' => str_starts_with($fieldValue, $keyword),
                'regex' => (bool) @preg_match("/{$keyword}/i", $fieldValue),
                default => str_contains($fieldValue, $keyword),
            };

            if ($matched) return true;
        }

        return false;
    }

    protected function vehicleMatchesTarget(Product $vehicle, SmartKeywordRule $rule): bool
    {
        if (empty($rule->target_vehicle_type) && empty($rule->target_brand)) {
            return true;
        }

        if (!empty($rule->target_vehicle_type)) {
            $targetType = $rule->target_vehicle_type;

            // Primary: check if vehicle belongs to category by slug
            $matchesCategory = $vehicle->categories()
                ->where('slug', $targetType)
                ->exists();

            // Fallback: check vehicle name contains target type (backward compat)
            if (!$matchesCategory) {
                $vehicleName = mb_strtolower($vehicle->name ?? '');
                $targetTypeLower = mb_strtolower($targetType);
                if (!str_contains($vehicleName, $targetTypeLower)) {
                    return false;
                }
            }
        }

        if (!empty($rule->target_brand)) {
            $vehicleBrand = mb_strtolower(trim($vehicle->manufacturer ?? ''));
            $targetBrand = mb_strtolower($rule->target_brand);

            if ($vehicleBrand !== $targetBrand) {
                return false;
            }
        }

        return true;
    }

    protected function getActiveRules()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SmartKeywordRule::active()
                ->orderByPriority()
                ->get();
        });
    }
}

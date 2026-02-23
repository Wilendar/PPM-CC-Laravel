<?php

namespace App\Services\Compatibility;

use App\Models\SmartVehicleAlias;
use App\Models\Product;
use App\Services\Compatibility\Results\ModelDetectionResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class VehicleModelDetector
{
    protected const ALIAS_CACHE_TTL = 3600;

    /**
     * Legacy constants - kept as reference. Actual values from AiScoringConfig.
     * @see AiScoringConfig::DEFAULTS
     */
    // protected const MIN_TOKEN_LENGTH = 3;
    // protected const MIN_TOKEN_MATCHES = 2;

    protected const STOP_WORDS = [
        'quad', 'buggy', 'motocykl', 'cross', 'enduro', 'pit', 'bike', 'dirt',
        'dla', 'do', 'na', 'ze', 'the', 'and', 'with', 'for',
        'nowy', 'nowa', 'nowe', 'mini', 'maxi',
    ];

    public function __construct(
        protected AiScoringConfig $config,
    ) {}

    public function detect(Product $product, Product $vehicle): ModelDetectionResult
    {
        $productName = mb_strtolower(trim($product->name ?? ''));
        $productSku = mb_strtolower(trim($product->sku ?? ''));

        if (empty($productName) && empty($productSku)) {
            return ModelDetectionResult::noMatch();
        }

        $aliasResult = $this->checkAliases($vehicle, $productName, $productSku);
        if ($aliasResult->hasMatch) {
            return $aliasResult;
        }

        return $this->tokenMatch($vehicle, $productName);
    }

    protected function checkAliases(Product $vehicle, string $productName, string $productSku): ModelDetectionResult
    {
        $aliases = $this->getVehicleAliases($vehicle->id);

        foreach ($aliases as $alias) {
            $normalized = $alias->alias_normalized;

            if (!empty($productName) && str_contains($productName, $normalized)) {
                return new ModelDetectionResult(
                    true,
                    $this->config->get('weight_model_alias_exact'),
                    'alias_exact',
                    $alias->alias
                );
            }

            if (!empty($productSku) && str_contains($productSku, $normalized)) {
                return new ModelDetectionResult(
                    true,
                    $this->config->get('weight_model_alias_sku'),
                    'alias_sku',
                    $alias->alias
                );
            }
        }

        return ModelDetectionResult::noMatch();
    }

    protected function tokenMatch(Product $vehicle, string $productName): ModelDetectionResult
    {
        $vehicleName = mb_strtolower(trim($vehicle->name ?? ''));

        if (empty($vehicleName)) {
            return ModelDetectionResult::noMatch();
        }

        $vehicleTokens = $this->tokenize($vehicleName);
        $productTokens = $this->tokenize($productName);

        if (empty($vehicleTokens) || empty($productTokens)) {
            return ModelDetectionResult::noMatch();
        }

        $matches = 0;
        foreach ($vehicleTokens as $vToken) {
            if (in_array($vToken, $productTokens, true)) {
                $matches++;
            }
        }

        $minTknMatches = $this->config->get('min_tkn_matches');

        if ($matches >= $minTknMatches) {
            return new ModelDetectionResult(
                true,
                $this->config->get('weight_model_tkn_match'),
                'token_match',
                null,
                $matches
            );
        }

        $vehicleNgrams = $this->generateNgrams($vehicleTokens, 2);
        foreach ($vehicleNgrams as $ngram) {
            if (str_contains($productName, $ngram)) {
                return new ModelDetectionResult(
                    true,
                    $this->config->get('weight_model_tkn_ngram'),
                    'token_match',
                    $ngram,
                    2
                );
            }
        }

        return ModelDetectionResult::noMatch();
    }

    protected function tokenize(string $text): array
    {
        $tokens = preg_split('/[\s\-_\/\.]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $minLength = $this->config->get('min_tkn_length');

        return array_values(array_filter($tokens, function ($token) use ($minLength) {
            return mb_strlen($token) >= $minLength
                && !in_array($token, self::STOP_WORDS, true);
        }));
    }

    protected function generateNgrams(array $tokens, int $n): array
    {
        $ngrams = [];
        $count = count($tokens);

        for ($i = 0; $i <= $count - $n; $i++) {
            $ngrams[] = implode(' ', array_slice($tokens, $i, $n));
        }

        return $ngrams;
    }

    public function generateAliases(Product $vehicle): array
    {
        $name = trim($vehicle->name ?? '');
        $brand = trim($vehicle->manufacturer ?? '');

        if (empty($name)) return [];

        $tokens = preg_split('/[\s\-_\/\.]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $aliases = [];

        foreach ($tokens as $token) {
            if (preg_match('/\d/', $token) && mb_strlen($token) >= 2) {
                $clean = rtrim($token, 'cc');
                $aliases[] = [
                    'alias' => $clean,
                    'alias_type' => 'model_code',
                ];
            }
        }

        if (!empty($brand)) {
            foreach ($aliases as $alias) {
                if ($alias['alias_type'] === 'model_code') {
                    $aliases[] = [
                        'alias' => $brand . ' ' . $alias['alias'],
                        'alias_type' => 'common_name',
                    ];
                }
            }
        }

        $nameNorm = mb_strtolower($name);
        $brandNorm = mb_strtolower($brand);
        if (!empty($name) && $nameNorm !== $brandNorm) {
            $aliases[] = [
                'alias' => $name,
                'alias_type' => 'common_name',
            ];
        }

        return $aliases;
    }

    public function saveGeneratedAliases(Product $vehicle, ?int $userId = null): int
    {
        $aliases = $this->generateAliases($vehicle);
        $saved = 0;

        foreach ($aliases as $aliasData) {
            $normalized = mb_strtolower(trim($aliasData['alias']));

            $exists = SmartVehicleAlias::where('vehicle_product_id', $vehicle->id)
                ->where('alias_normalized', $normalized)
                ->exists();

            if ($exists) continue;

            SmartVehicleAlias::create([
                'vehicle_product_id' => $vehicle->id,
                'alias' => $aliasData['alias'],
                'alias_type' => $aliasData['alias_type'],
                'is_auto_generated' => true,
                'is_active' => true,
                'created_by' => $userId,
            ]);

            $saved++;
        }

        Cache::forget("smart_aliases_vehicle_{$vehicle->id}");

        return $saved;
    }

    protected function getVehicleAliases(int $vehicleId): Collection
    {
        return Cache::remember(
            "smart_aliases_vehicle_{$vehicleId}",
            self::ALIAS_CACHE_TTL,
            fn() => SmartVehicleAlias::active()->forVehicle($vehicleId)->get()
        );
    }
}

<?php

namespace App\Services\PrestaShop;

use App\Models\Manufacturer;
use Illuminate\Support\Str;

/**
 * ManufacturerTransformer - Transform manufacturer data between PPM and PrestaShop formats
 *
 * ETAP 07g: Manufacturer Sync System
 *
 * Handles bidirectional data transformation:
 * - PPM Manufacturer -> PrestaShop XML format
 * - PrestaShop API response -> PPM format
 *
 * PrestaShop Manufacturer Fields:
 * - name (string, NOT multilang)
 * - active (0|1)
 * - description (multilang)
 * - short_description (multilang)
 * - meta_title (multilang)
 * - meta_description (multilang)
 * - meta_keywords (multilang)
 * - link_rewrite (string, auto-generated if not provided)
 *
 * @package App\Services\PrestaShop
 */
class ManufacturerTransformer
{
    /**
     * Transform PPM Manufacturer to PrestaShop format
     *
     * @param Manufacturer $manufacturer PPM Manufacturer model
     * @param int $langId Default language ID (default: 1 = Polish)
     * @param array $additionalLangIds Additional language IDs for multilang fields
     * @return array Data ready for PrestaShop API
     */
    public function transformForPrestaShop(
        Manufacturer $manufacturer,
        int $langId = 1,
        array $additionalLangIds = []
    ): array {
        $allLangIds = array_merge([$langId], $additionalLangIds);

        $data = [
            'name' => $manufacturer->name,
            'active' => $manufacturer->is_active ? '1' : '0',
        ];

        // Link rewrite (SEO-friendly URL)
        if (!empty($manufacturer->ps_link_rewrite)) {
            $data['link_rewrite'] = $manufacturer->ps_link_rewrite;
        } else {
            $data['link_rewrite'] = Str::slug($manufacturer->name);
        }

        // Multilang fields
        if (!empty($manufacturer->description)) {
            $data['description'] = $this->buildMultilangField($manufacturer->description, $allLangIds);
        }

        if (!empty($manufacturer->short_description)) {
            $data['short_description'] = $this->buildMultilangField($manufacturer->short_description, $allLangIds);
        }

        if (!empty($manufacturer->meta_title)) {
            $data['meta_title'] = $this->buildMultilangField($manufacturer->meta_title, $allLangIds);
        }

        if (!empty($manufacturer->meta_description)) {
            $data['meta_description'] = $this->buildMultilangField($manufacturer->meta_description, $allLangIds);
        }

        if (!empty($manufacturer->meta_keywords)) {
            $data['meta_keywords'] = $this->buildMultilangField($manufacturer->meta_keywords, $allLangIds);
        }

        return $data;
    }

    /**
     * Transform PrestaShop API response to PPM format
     *
     * @param array $psData PrestaShop manufacturer data from API
     * @param int $preferredLangId Preferred language ID for multilang extraction
     * @return array Data suitable for Manufacturer model
     */
    public function transformFromPrestaShop(array $psData, int $preferredLangId = 1): array
    {
        return [
            'name' => $psData['name'] ?? '',
            'code' => Str::slug($psData['name'] ?? '', '_'),
            'ps_link_rewrite' => $psData['link_rewrite'] ?? null,
            'description' => $this->extractMultilangValue($psData['description'] ?? null, $preferredLangId),
            'short_description' => $this->extractMultilangValue($psData['short_description'] ?? null, $preferredLangId),
            'meta_title' => $this->extractMultilangValue($psData['meta_title'] ?? null, $preferredLangId),
            'meta_description' => $this->extractMultilangValue($psData['meta_description'] ?? null, $preferredLangId),
            'meta_keywords' => $this->extractMultilangValue($psData['meta_keywords'] ?? null, $preferredLangId),
            'is_active' => ($psData['active'] ?? '0') === '1',
        ];
    }

    /**
     * Build multilang field structure for PrestaShop API
     *
     * PrestaShop expects:
     * ['language' => [['@attributes' => ['id' => 1], '#' => 'Text'], ...]]
     *
     * Simplified format (for arrayToXml):
     * [['id' => 1, 'value' => 'Text'], ...]
     *
     * @param string $value Value to set for all languages
     * @param array $langIds Array of language IDs
     * @return array Multilang structure
     */
    public function buildMultilangField(string $value, array $langIds = [1]): array
    {
        $languages = [];

        foreach ($langIds as $langId) {
            $languages[] = [
                'id' => $langId,
                'value' => $value,
            ];
        }

        return ['language' => $languages];
    }

    /**
     * Extract value from multilang field structure
     *
     * Handles various PrestaShop response formats:
     * - Simple string: "value"
     * - Object with language array: {'language': [{'id': 1, 'value': 'text'}]}
     * - Object with language object: {'language': {'id': 1, 'value': 'text'}}
     *
     * @param mixed $multilang Multilang data from PrestaShop
     * @param int $preferredLangId Preferred language ID
     * @return string|null Extracted value
     */
    public function extractMultilangValue($multilang, int $preferredLangId = 1): ?string
    {
        if ($multilang === null) {
            return null;
        }

        // Simple string
        if (is_string($multilang)) {
            return !empty($multilang) ? $multilang : null;
        }

        if (!is_array($multilang)) {
            return null;
        }

        // Format: ['language' => [...]]
        if (isset($multilang['language'])) {
            $languages = $multilang['language'];

            // Single language object
            if (isset($languages['id'])) {
                return $this->extractValueFromLanguageEntry($languages);
            }

            // Array of languages
            if (is_array($languages)) {
                // Try to find preferred language
                foreach ($languages as $entry) {
                    if (is_array($entry) && (int)($entry['id'] ?? 0) === $preferredLangId) {
                        return $this->extractValueFromLanguageEntry($entry);
                    }
                }

                // Fallback to first language
                $first = reset($languages);
                if (is_array($first)) {
                    return $this->extractValueFromLanguageEntry($first);
                }
            }
        }

        // Direct array format [['id' => 1, 'value' => '...']]
        if (isset($multilang[0])) {
            foreach ($multilang as $entry) {
                if (is_array($entry) && (int)($entry['id'] ?? 0) === $preferredLangId) {
                    return $this->extractValueFromLanguageEntry($entry);
                }
            }

            // Fallback to first
            return $this->extractValueFromLanguageEntry($multilang[0]);
        }

        return null;
    }

    /**
     * Extract value from single language entry
     *
     * @param array $entry Language entry ['id' => x, 'value' => '...'] or ['id' => x, '#' => '...']
     * @return string|null
     */
    private function extractValueFromLanguageEntry(array $entry): ?string
    {
        // XML format with '#' key
        if (isset($entry['#'])) {
            return !empty($entry['#']) ? (string)$entry['#'] : null;
        }

        // JSON format with 'value' key
        if (isset($entry['value'])) {
            return !empty($entry['value']) ? (string)$entry['value'] : null;
        }

        return null;
    }

    /**
     * Merge PPM manufacturer data with existing PrestaShop data
     *
     * Used for GET-MODIFY-PUT pattern to preserve fields not managed by PPM
     *
     * @param array $existingPsData Current data from PrestaShop
     * @param array $newData New data from PPM transformer
     * @return array Merged data
     */
    public function mergeForUpdate(array $existingPsData, array $newData): array
    {
        // Fields to preserve from PrestaShop (not managed by PPM)
        $preserveFields = [
            'date_add',
            'date_upd',
            'id_shop_default',
        ];

        $merged = $newData;

        foreach ($preserveFields as $field) {
            if (isset($existingPsData[$field]) && !isset($newData[$field])) {
                $merged[$field] = $existingPsData[$field];
            }
        }

        return $merged;
    }

    /**
     * Validate manufacturer data before sync
     *
     * @param Manufacturer $manufacturer
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateForSync(Manufacturer $manufacturer): array
    {
        $errors = [];

        if (empty($manufacturer->name)) {
            $errors[] = 'Manufacturer name is required';
        }

        if (strlen($manufacturer->name) > 64) {
            $errors[] = 'Manufacturer name exceeds 64 characters (PrestaShop limit)';
        }

        if (!empty($manufacturer->meta_title) && strlen($manufacturer->meta_title) > 255) {
            $errors[] = 'Meta title exceeds 255 characters';
        }

        if (!empty($manufacturer->meta_description) && strlen($manufacturer->meta_description) > 512) {
            $errors[] = 'Meta description exceeds 512 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

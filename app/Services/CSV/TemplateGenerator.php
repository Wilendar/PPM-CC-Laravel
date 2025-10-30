<?php

namespace App\Services\CSV;

use App\Models\AttributeType;
use App\Models\FeatureType;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * CSV Template Generator Service
 *
 * Generates CSV templates for import operations:
 * - Variants template (SKU, attributes, prices, stock, images)
 * - Features template (SKU, feature types, values, units)
 * - Compatibility template (SKU, vehicle models, attributes, sources)
 *
 * Templates include Polish headers and dynamic columns based on DB data.
 */
class TemplateGenerator
{
    /**
     * Generate variants template CSV
     *
     * @return array Header row with Polish column names
     */
    public function generateVariantsTemplate(): array
    {
        Log::info('TemplateGenerator: Generating variants template');

        $headers = [
            'SKU', // Variant SKU (required, unique)
            'Rodzic SKU', // Parent product SKU (required, must exist)
            'Nazwa wariantu', // Variant name
            'Aktywny', // Is active (TAK/NIE)
            'Domyslny', // Is default (TAK/NIE)
            'Pozycja', // Display position (integer)
        ];

        // Add dynamic attribute type columns
        $attributeTypes = AttributeType::active()->ordered()->get();
        foreach ($attributeTypes as $attributeType) {
            $headers[] = 'Atrybut: ' . $attributeType->name; // e.g., "Atrybut: Rozmiar"
        }

        // Add price group columns
        $priceGroups = PriceGroup::active()->ordered()->get();
        foreach ($priceGroups as $priceGroup) {
            $headers[] = 'Cena: ' . $priceGroup->name; // e.g., "Cena: Detaliczna"
        }

        // Add warehouse stock columns
        $warehouses = Warehouse::active()->ordered()->get();
        foreach ($warehouses as $warehouse) {
            $headers[] = 'Stan: ' . $warehouse->name; // e.g., "Stan: MPPTRADE"
        }

        $headers[] = 'Zdjecie glowne'; // Cover image URL or path

        Log::info('TemplateGenerator: Variants template generated', [
            'column_count' => count($headers),
            'attribute_types_count' => $attributeTypes->count(),
            'price_groups_count' => $priceGroups->count(),
            'warehouses_count' => $warehouses->count(),
        ]);

        return $headers;
    }

    /**
     * Generate features template CSV
     *
     * @return array Header row with Polish column names
     */
    public function generateFeaturesTemplate(): array
    {
        Log::info('TemplateGenerator: Generating features template');

        $headers = [
            'SKU', // Product/Variant SKU (required, must exist)
        ];

        // Add dynamic feature type columns
        $featureTypes = FeatureType::active()->ordered()->get();
        foreach ($featureTypes as $featureType) {
            $columnName = 'Cecha: ' . $featureType->name;

            // Add unit to column name if feature has unit
            if ($featureType->unit) {
                $columnName .= ' (' . $featureType->unit . ')';
            }

            // Add value type hint for select/bool types
            if ($featureType->value_type === FeatureType::VALUE_TYPE_SELECT) {
                $columnName .= ' [lista]';
            } elseif ($featureType->value_type === FeatureType::VALUE_TYPE_BOOL) {
                $columnName .= ' [TAK/NIE]';
            } elseif ($featureType->value_type === FeatureType::VALUE_TYPE_NUMBER) {
                $columnName .= ' [liczba]';
            }

            $headers[] = $columnName;
        }

        Log::info('TemplateGenerator: Features template generated', [
            'column_count' => count($headers),
            'feature_types_count' => $featureTypes->count(),
        ]);

        return $headers;
    }

    /**
     * Generate compatibility template CSV
     *
     * @return array Header row with Polish column names
     */
    public function generateCompatibilityTemplate(): array
    {
        Log::info('TemplateGenerator: Generating compatibility template');

        $headers = [
            'SKU', // Product/Variant SKU (required, must exist)
            'Marka pojazdu', // Vehicle brand (Honda, Yamaha, etc.)
            'Model pojazdu', // Vehicle model (CBR 600 RR, MT-09, etc.)
            'Rok od', // Year from (integer, 1900-2100)
            'Rok do', // Year to (integer, 1900-2100, może być puste)
            'SKU pojazdu', // Vehicle SKU (optional, for SKU-first lookup)
            'Typ dopasowania', // Compatibility attribute (Oryginal, Zamiennik, Performance)
            'Zrodlo', // Source (Producent, Manual, Import)
            'Zweryfikowane', // Verified (TAK/NIE)
            'Uwagi', // Notes (optional text)
        ];

        Log::info('TemplateGenerator: Compatibility template generated', [
            'column_count' => count($headers),
        ]);

        return $headers;
    }

    /**
     * Generate products template CSV (complete product data)
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateProductsTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        Log::info('TemplateGenerator: Generating products template');

        $headers = [
            'SKU',
            'Nazwa produktu',
            'Kategoria główna',
            'Status [ACTIVE/INACTIVE]',
            'Stan [IN_STOCK/OUT_OF_STOCK/PREORDER]',
        ];

        // Dynamic price groups
        $priceGroups = PriceGroup::orderBy('name')->get();
        foreach ($priceGroups as $group) {
            $headers[] = "Cena: {$group->name}";
        }

        // Dynamic warehouses
        $warehouses = Warehouse::orderBy('name')->get();
        foreach ($warehouses as $warehouse) {
            $headers[] = "Stan mag.: {$warehouse->name} [liczba]";
        }

        $headers = array_merge($headers, [
            'Opis krótki',
            'Opis długi',
            'Cechy produktu (;)',
            'Producent',
            'Data utworzenia',
            'Data aktualizacji',
        ]);

        $exampleRow = $this->generateProductExampleRow($priceGroups->count(), $warehouses->count());

        Log::info('TemplateGenerator: Products template generated', [
            'column_count' => count($headers),
            'price_groups_count' => $priceGroups->count(),
            'warehouses_count' => $warehouses->count(),
        ]);

        return $this->generateTemplateWithExamples('products_import_template.csv', $headers, [$exampleRow]);
    }

    /**
     * Generate example row for products template
     *
     * @param int $priceGroupsCount Number of price groups
     * @param int $warehousesCount Number of warehouses
     * @return array Example data row
     */
    private function generateProductExampleRow(int $priceGroupsCount, int $warehousesCount): array
    {
        $row = [
            'PROD-001',
            'Przykładowy produkt testowy',
            'Elektronika > Smartfony',
            'ACTIVE',
            'IN_STOCK',
        ];

        // Example prices
        for ($i = 0; $i < $priceGroupsCount; $i++) {
            $row[] = number_format(rand(100, 1000), 2, '.', '');
        }

        // Example stock
        for ($i = 0; $i < $warehousesCount; $i++) {
            $row[] = rand(0, 100);
        }

        $row = array_merge($row, [
            'Krótki opis produktu przykładowego',
            'Szczegółowy opis produktu z informacjami technicznymi',
            'Kolor: Czarny; Rozmiar: Large; Materiał: Bawełna',
            'Example Manufacturer',
            now()->format('Y-m-d H:i:s'),
            now()->format('Y-m-d H:i:s'),
        ]);

        return $row;
    }

    /**
     * Generate template with examples (generic helper)
     *
     * @param string $filename Output filename
     * @param array $headers Column headers
     * @param array $exampleRows Example data rows
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function generateTemplateWithExamples(string $filename, array $headers, array $exampleRows): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $csvContent = [];
        $csvContent[] = $headers;
        foreach ($exampleRows as $row) {
            $csvContent[] = $row;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        $handle = fopen($tempFile, 'w');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        foreach ($csvContent as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }

        fclose($handle);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate template with example rows
     *
     * @param string $templateType Type: variants, features, compatibility
     * @param int $exampleRowsCount Number of example rows to add
     * @return array Array of rows (first row = headers, rest = examples)
     */
    public function generateTemplateWithExamples(string $templateType, int $exampleRowsCount = 3): array
    {
        Log::info('TemplateGenerator: Generating template with examples', [
            'template_type' => $templateType,
            'example_rows_count' => $exampleRowsCount,
        ]);

        $rows = [];

        switch ($templateType) {
            case 'variants':
                $headers = $this->generateVariantsTemplate();
                $rows[] = $headers;

                // Add example rows
                for ($i = 1; $i <= $exampleRowsCount; $i++) {
                    $exampleRow = $this->generateVariantExampleRow($headers, $i);
                    $rows[] = $exampleRow;
                }
                break;

            case 'features':
                $headers = $this->generateFeaturesTemplate();
                $rows[] = $headers;

                // Add example rows
                for ($i = 1; $i <= $exampleRowsCount; $i++) {
                    $exampleRow = $this->generateFeatureExampleRow($headers, $i);
                    $rows[] = $exampleRow;
                }
                break;

            case 'compatibility':
                $headers = $this->generateCompatibilityTemplate();
                $rows[] = $headers;

                // Add example rows
                for ($i = 1; $i <= $exampleRowsCount; $i++) {
                    $exampleRow = $this->generateCompatibilityExampleRow($headers, $i);
                    $rows[] = $exampleRow;
                }
                break;

            default:
                Log::warning('TemplateGenerator: Unknown template type', ['template_type' => $templateType]);
                throw new \InvalidArgumentException("Unknown template type: {$templateType}");
        }

        Log::info('TemplateGenerator: Template with examples generated', [
            'total_rows' => count($rows),
            'header_columns' => count($rows[0]),
        ]);

        return $rows;
    }

    /**
     * Generate example row for variants template
     *
     * @param array $headers Column headers
     * @param int $rowIndex Row index (for unique values)
     * @return array Example data row
     */
    protected function generateVariantExampleRow(array $headers, int $rowIndex): array
    {
        $row = [];

        foreach ($headers as $header) {
            // Basic fields
            if ($header === 'SKU') {
                $row[] = 'VAR-EXAMPLE-' . str_pad($rowIndex, 3, '0', STR_PAD_LEFT);
            } elseif ($header === 'Rodzic SKU') {
                $row[] = 'PROD-EXAMPLE-001';
            } elseif ($header === 'Nazwa wariantu') {
                $row[] = 'Przyklad wariantu ' . $rowIndex;
            } elseif ($header === 'Aktywny') {
                $row[] = 'TAK';
            } elseif ($header === 'Domyslny') {
                $row[] = $rowIndex === 1 ? 'TAK' : 'NIE';
            } elseif ($header === 'Pozycja') {
                $row[] = $rowIndex;
            }
            // Attribute columns
            elseif (str_starts_with($header, 'Atrybut: ')) {
                $attributeName = substr($header, strlen('Atrybut: '));
                if (str_contains($attributeName, 'Rozmiar')) {
                    $row[] = ['S', 'M', 'L', 'XL'][$rowIndex % 4];
                } elseif (str_contains($attributeName, 'Kolor')) {
                    $row[] = ['Czerwony', 'Niebieski', 'Zielony'][$rowIndex % 3];
                } else {
                    $row[] = 'Wartosc ' . $rowIndex;
                }
            }
            // Price columns
            elseif (str_starts_with($header, 'Cena: ')) {
                $row[] = number_format(100 + ($rowIndex * 10), 2, ',', '');
            }
            // Stock columns
            elseif (str_starts_with($header, 'Stan: ')) {
                $row[] = 10 * $rowIndex;
            }
            // Cover image
            elseif ($header === 'Zdjecie glowne') {
                $row[] = 'https://example.com/images/variant-' . $rowIndex . '.jpg';
            }
            // Unknown column
            else {
                $row[] = '';
            }
        }

        return $row;
    }

    /**
     * Generate example row for features template
     *
     * @param array $headers Column headers
     * @param int $rowIndex Row index (for unique values)
     * @return array Example data row
     */
    protected function generateFeatureExampleRow(array $headers, int $rowIndex): array
    {
        $row = [];

        foreach ($headers as $header) {
            if ($header === 'SKU') {
                $row[] = 'PROD-EXAMPLE-' . str_pad($rowIndex, 3, '0', STR_PAD_LEFT);
            }
            // Feature columns
            elseif (str_starts_with($header, 'Cecha: ')) {
                // Extract feature type info from header
                if (str_contains($header, '[TAK/NIE]')) {
                    $row[] = $rowIndex % 2 === 0 ? 'TAK' : 'NIE';
                } elseif (str_contains($header, '[liczba]')) {
                    $row[] = 100 * $rowIndex;
                } else {
                    $row[] = 'Wartosc ' . $rowIndex;
                }
            }
            // Unknown column
            else {
                $row[] = '';
            }
        }

        return $row;
    }

    /**
     * Generate example row for compatibility template
     *
     * @param array $headers Column headers
     * @param int $rowIndex Row index (for unique values)
     * @return array Example data row
     */
    protected function generateCompatibilityExampleRow(array $headers, int $rowIndex): array
    {
        $brands = ['Honda', 'Yamaha', 'Kawasaki'];
        $models = ['CBR 600 RR', 'MT-09', 'Ninja 650'];
        $types = ['Oryginal', 'Zamiennik', 'Performance'];
        $sources = ['Producent', 'Manual', 'Import'];

        $row = [];

        foreach ($headers as $header) {
            if ($header === 'SKU') {
                $row[] = 'PROD-EXAMPLE-' . str_pad($rowIndex, 3, '0', STR_PAD_LEFT);
            } elseif ($header === 'Marka pojazdu') {
                $row[] = $brands[$rowIndex % count($brands)];
            } elseif ($header === 'Model pojazdu') {
                $row[] = $models[$rowIndex % count($models)];
            } elseif ($header === 'Rok od') {
                $row[] = 2010 + $rowIndex;
            } elseif ($header === 'Rok do') {
                $row[] = 2020 + $rowIndex;
            } elseif ($header === 'SKU pojazdu') {
                $row[] = 'VEH-' . str_pad($rowIndex, 3, '0', STR_PAD_LEFT);
            } elseif ($header === 'Typ dopasowania') {
                $row[] = $types[$rowIndex % count($types)];
            } elseif ($header === 'Zrodlo') {
                $row[] = $sources[$rowIndex % count($sources)];
            } elseif ($header === 'Zweryfikowane') {
                $row[] = $rowIndex % 2 === 0 ? 'TAK' : 'NIE';
            } elseif ($header === 'Uwagi') {
                $row[] = 'Przykladowa uwaga dla wiersza ' . $rowIndex;
            } else {
                $row[] = '';
            }
        }

        return $row;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VehicleCompatibility;
use App\Services\CSV\ExportFormatter;
use App\Services\CSV\TemplateGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CSV Export Controller
 *
 * Handles CSV/Excel export and template download endpoints.
 *
 * Routes:
 * - GET /admin/csv/templates/{type} - Download template
 * - GET /admin/products/{id}/export/variants - Export variants
 * - GET /admin/products/{id}/export/features - Export features
 * - GET /admin/products/{id}/export/compatibility - Export compatibility
 */
class CSVExportController extends Controller
{
    protected TemplateGenerator $templateGenerator;
    protected ExportFormatter $exportFormatter;

    public function __construct(
        TemplateGenerator $templateGenerator,
        ExportFormatter $exportFormatter
    ) {
        $this->templateGenerator = $templateGenerator;
        $this->exportFormatter = $exportFormatter;
    }

    /**
     * Download CSV template
     *
     * @param string $type Template type (variants, features, compatibility)
     * @return BinaryFileResponse
     */
    public function downloadTemplate(string $type): BinaryFileResponse
    {
        Log::info('CSVExportController: Template download requested', ['type' => $type]);

        // Validate template type
        if (!in_array($type, ['variants', 'features', 'compatibility'])) {
            abort(404, 'Unknown template type');
        }

        // Generate template with example rows
        $rows = $this->templateGenerator->generateTemplateWithExamples($type, 3);

        // Create CSV file
        $filename = 'szablon_' . $type . '_' . date('Y-m-d');
        $filePath = $this->exportFormatter->formatForCsv($rows, $filename);

        Log::info('CSVExportController: Template generated', [
            'type' => $type,
            'file_path' => $filePath,
        ]);

        // Download file
        return response()->download($filePath, $filename . '.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export product variants to CSV/Excel
     *
     * @param int $productId Product ID
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportVariants(int $productId, Request $request): BinaryFileResponse
    {
        Log::info('CSVExportController: Variants export requested', ['product_id' => $productId]);

        $product = Product::findOrFail($productId);

        // Get all variants with relationships
        $variants = ProductVariant::where('product_id', $productId)
            ->with(['attributes.attributeType', 'prices.priceGroup', 'stock.warehouse', 'images'])
            ->get();

        if ($variants->isEmpty()) {
            abort(404, 'No variants found for this product');
        }

        // Format variants for export
        $rows = [];

        // Add header row
        $rows[] = $this->templateGenerator->generateVariantsTemplate();

        // Add variant data rows
        foreach ($variants as $variant) {
            $rows[] = $this->exportFormatter->formatVariantForExport($variant);
        }

        // Determine format (CSV or Excel)
        $format = $request->get('format', 'xlsx');
        $filename = 'warianty_' . $product->sku . '_' . date('Y-m-d');

        if ($format === 'xlsx') {
            $filePath = $this->exportFormatter->formatForExcel(
                ['Warianty' => $rows],
                $filename
            );

            return response()->download($filePath, $filename . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } else {
            $filePath = $this->exportFormatter->formatForCsv($rows, $filename);

            return response()->download($filePath, $filename . '.csv', [
                'Content-Type' => 'text/csv; charset=utf-8',
            ])->deleteFileAfterSend(true);
        }
    }

    /**
     * Export product features to CSV/Excel
     *
     * @param int $productId Product ID
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportFeatures(int $productId, Request $request): BinaryFileResponse
    {
        Log::info('CSVExportController: Features export requested', ['product_id' => $productId]);

        $product = Product::with(['features.featureType'])->findOrFail($productId);

        if ($product->features->isEmpty()) {
            abort(404, 'No features found for this product');
        }

        // Format features for export
        $rows = [];

        // Add header row
        $rows[] = $this->templateGenerator->generateFeaturesTemplate();

        // Add feature data row
        $rows[] = $this->exportFormatter->formatFeaturesForExport($product);

        // Determine format
        $format = $request->get('format', 'xlsx');
        $filename = 'cechy_' . $product->sku . '_' . date('Y-m-d');

        if ($format === 'xlsx') {
            $filePath = $this->exportFormatter->formatForExcel(
                ['Cechy' => $rows],
                $filename
            );

            return response()->download($filePath, $filename . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } else {
            $filePath = $this->exportFormatter->formatForCsv($rows, $filename);

            return response()->download($filePath, $filename . '.csv', [
                'Content-Type' => 'text/csv; charset=utf-8',
            ])->deleteFileAfterSend(true);
        }
    }

    /**
     * Export product compatibility to CSV/Excel
     *
     * @param int $productId Product ID
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportCompatibility(int $productId, Request $request): BinaryFileResponse
    {
        Log::info('CSVExportController: Compatibility export requested', ['product_id' => $productId]);

        $product = Product::findOrFail($productId);

        // Get all compatibility records
        $compatibilityRecords = VehicleCompatibility::where('part_sku', $product->sku)
            ->with(['vehicleModel', 'compatibilityAttribute', 'compatibilitySource'])
            ->get();

        if ($compatibilityRecords->isEmpty()) {
            abort(404, 'No compatibility records found for this product');
        }

        // Format compatibility for export
        $rows = [];

        // Add header row
        $rows[] = $this->templateGenerator->generateCompatibilityTemplate();

        // Add compatibility data rows
        foreach ($compatibilityRecords as $compatibility) {
            $rows[] = $this->exportFormatter->formatCompatibilityForExport($compatibility);
        }

        // Determine format
        $format = $request->get('format', 'xlsx');
        $filename = 'dopasowania_' . $product->sku . '_' . date('Y-m-d');

        if ($format === 'xlsx') {
            $filePath = $this->exportFormatter->formatForExcel(
                ['Dopasowania' => $rows],
                $filename
            );

            return response()->download($filePath, $filename . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } else {
            $filePath = $this->exportFormatter->formatForCsv($rows, $filename);

            return response()->download($filePath, $filename . '.csv', [
                'Content-Type' => 'text/csv; charset=utf-8',
            ])->deleteFileAfterSend(true);
        }
    }

    /**
     * Export multiple products' data to multi-sheet Excel
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function exportMultipleProducts(Request $request): BinaryFileResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
            'include_variants' => 'boolean',
            'include_features' => 'boolean',
            'include_compatibility' => 'boolean',
        ]);

        Log::info('CSVExportController: Multiple products export requested', [
            'product_count' => count($request->product_ids),
        ]);

        $products = Product::whereIn('id', $request->product_ids)->get();

        $sheets = [];

        // Export variants
        if ($request->get('include_variants', true)) {
            $variantsRows = [];
            $variantsRows[] = $this->templateGenerator->generateVariantsTemplate();

            foreach ($products as $product) {
                $variants = ProductVariant::where('product_id', $product->id)
                    ->with(['attributes.attributeType', 'prices.priceGroup', 'stock.warehouse', 'images'])
                    ->get();

                foreach ($variants as $variant) {
                    $variantsRows[] = $this->exportFormatter->formatVariantForExport($variant);
                }
            }

            $sheets['Warianty'] = $variantsRows;
        }

        // Export features
        if ($request->get('include_features', true)) {
            $featuresRows = [];
            $featuresRows[] = $this->templateGenerator->generateFeaturesTemplate();

            foreach ($products as $product) {
                $product->load('features.featureType');
                if ($product->features->isNotEmpty()) {
                    $featuresRows[] = $this->exportFormatter->formatFeaturesForExport($product);
                }
            }

            $sheets['Cechy'] = $featuresRows;
        }

        // Export compatibility
        if ($request->get('include_compatibility', true)) {
            $compatibilityRows = [];
            $compatibilityRows[] = $this->templateGenerator->generateCompatibilityTemplate();

            foreach ($products as $product) {
                $compatibilityRecords = VehicleCompatibility::where('part_sku', $product->sku)
                    ->with(['vehicleModel', 'compatibilityAttribute', 'compatibilitySource'])
                    ->get();

                foreach ($compatibilityRecords as $compatibility) {
                    $compatibilityRows[] = $this->exportFormatter->formatCompatibilityForExport($compatibility);
                }
            }

            $sheets['Dopasowania'] = $compatibilityRows;
        }

        // Generate Excel file
        $filename = 'eksport_produktow_' . date('Y-m-d');
        $filePath = $this->exportFormatter->formatForExcel($sheets, $filename);

        Log::info('CSVExportController: Multiple products export completed', [
            'file_path' => $filePath,
            'sheet_count' => count($sheets),
        ]);

        return response()->download($filePath, $filename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}

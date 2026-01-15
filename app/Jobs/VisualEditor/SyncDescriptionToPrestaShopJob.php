<?php

declare(strict_types=1);

namespace App\Jobs\VisualEditor;

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\VisualEditor\DescriptionRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sync Product Description to PrestaShop Job.
 *
 * Renders visual editor blocks to HTML and updates
 * the product description in PrestaShop via API.
 *
 * Features:
 * - Renders blocks using DescriptionRenderer
 * - Updates PrestaShop product description field
 * - Progress tracking via JobProgressService
 * - Supports single product or batch sync
 *
 * @package App\Jobs\VisualEditor
 * @since ETAP_07f Faza 8.2 - Description Sync Integration
 */
class SyncDescriptionToPrestaShopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run.
     */
    public int $timeout = 120;

    /**
     * Delay between retries in seconds.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @param int $productId PPM Product ID
     * @param int $shopId PrestaShop shop ID
     * @param int $userId User who initiated the sync
     * @param int|null $progressId Pre-created progress ID (optional)
     */
    public function __construct(
        public int $productId,
        public int $shopId,
        public int $userId,
        public ?int $progressId = null
    ) {
        // Set queue for description sync jobs
        $this->onQueue('prestashop');
    }

    /**
     * Execute the job.
     */
    public function handle(
        DescriptionRenderer $renderer,
        JobProgressService $progressService
    ): void {
        Log::info('SyncDescriptionToPrestaShopJob: rozpoczeto', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
        ]);

        try {
            // Load required models
            $product = Product::find($this->productId);
            if (!$product) {
                throw new \RuntimeException("Produkt o ID {$this->productId} nie istnieje");
            }

            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                throw new \RuntimeException("Sklep o ID {$this->shopId} nie istnieje");
            }

            // Check if product is synced to this shop
            $shopData = $product->getShopData($this->shopId);
            if (!$shopData || !$shopData->prestashop_id) {
                throw new \RuntimeException(
                    "Produkt {$product->sku} nie jest zsynchronizowany ze sklepem {$shop->name}"
                );
            }

            $prestashopProductId = $shopData->prestashop_id;

            // Get or create ProductDescription
            $description = ProductDescription::getOrCreate($this->productId, $this->shopId);

            // Render HTML from blocks
            $html = $renderer->renderAndCache($description, minify: true);

            if (empty($html) || empty($description->blocks)) {
                Log::warning('SyncDescriptionToPrestaShopJob: brak blokow do synchronizacji', [
                    'product_id' => $this->productId,
                    'shop_id' => $this->shopId,
                ]);
                // Update progress and exit gracefully
                $this->updateProgressCompleted($progressService, 'Brak blokow do synchronizacji');
                return;
            }

            // Create PrestaShop API client
            $client = PrestaShopClientFactory::create($shop);

            // Build description update payload
            $languageId = $shop->default_language_id ?? 1;
            $descriptionPayload = $this->buildDescriptionPayload($html, $languageId);

            // Update product in PrestaShop
            $client->updateProduct($prestashopProductId, $descriptionPayload);

            // Mark sync as completed
            $this->updateProgressCompleted($progressService, 'Opis zsynchronizowany poprawnie');

            Log::info('SyncDescriptionToPrestaShopJob: zakonczono sukcesem', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'prestashop_id' => $prestashopProductId,
                'html_length' => strlen($html),
            ]);

        } catch (Throwable $e) {
            $this->handleFailure($progressService, $e);
            throw $e;
        }
    }

    /**
     * Build PrestaShop description payload.
     *
     * @param string $html Rendered HTML description
     * @param int $languageId PrestaShop language ID
     * @return array Payload for API update
     */
    private function buildDescriptionPayload(string $html, int $languageId): array
    {
        return [
            'description' => [
                'language' => [
                    [
                        '@attributes' => ['id' => $languageId],
                        '@value' => $html,
                    ],
                ],
            ],
        ];
    }

    /**
     * Update progress service on completion.
     */
    private function updateProgressCompleted(JobProgressService $progressService, string $message): void
    {
        if ($this->progressId) {
            $progressService->markCompleted($this->progressId, [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'message' => $message,
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    private function handleFailure(JobProgressService $progressService, Throwable $e): void
    {
        Log::error('SyncDescriptionToPrestaShopJob: blad synchronizacji', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'error' => $e->getMessage(),
        ]);

        if ($this->progressId) {
            $progressService->markFailed($this->progressId, $e->getMessage(), [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
            ]);
        }
    }

    /**
     * Handle permanent failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('SyncDescriptionToPrestaShopJob: trwaly blad', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}

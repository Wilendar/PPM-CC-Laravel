<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * MediaTest - FAZA C: Unit tests dla Media Model
 * 
 * Testuje funkcjonalności:
 * - Polymorphic relationships
 * - Primary image logic
 * - File handling methods
 * - PrestaShop mapping
 * - Sync status management
 * 
 * @package Tests\Unit\Models
 * @since FAZA C - Media & Relations Implementation
 */
class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Additional setup if needed
    }

    /** @test */
    public function it_can_belong_to_a_product()
    {
        $product = Product::factory()->create();
        $media = Media::factory()->forProduct($product)->create();

        $this->assertInstanceOf(Product::class, $media->mediable);
        $this->assertEquals($product->id, $media->mediable->id);
        $this->assertEquals('App\\Models\\Product', $media->mediable_type);
    }

    /** @test */
    public function it_can_belong_to_a_product_variant()
    {
        $variant = ProductVariant::factory()->create();
        $media = Media::factory()->forVariant($variant)->create();

        $this->assertInstanceOf(ProductVariant::class, $media->mediable);
        $this->assertEquals($variant->id, $media->mediable->id);
        $this->assertEquals('App\\Models\\ProductVariant', $media->mediable_type);
    }

    /** @test */
    public function it_ensures_only_one_primary_image_per_mediable()
    {
        $product = Product::factory()->create();
        
        // Create first primary image
        $media1 = Media::factory()->forProduct($product)->primaryImage()->create();
        $this->assertTrue($media1->fresh()->is_primary);

        // Create second image marked as primary
        $media2 = Media::factory()->forProduct($product)->primaryImage()->create();
        
        // Refresh from database
        $media1->refresh();
        $media2->refresh();

        // Only the second one should be primary
        $this->assertFalse($media1->is_primary);
        $this->assertTrue($media2->is_primary);
    }

    /** @test */
    public function it_generates_unique_file_names()
    {
        $media1 = Media::factory()->make();
        $media2 = Media::factory()->make();

        $this->assertNotEquals($media1->file_name, $media2->file_name);
        $this->assertStringContainsString('.jpg', $media1->file_name);
    }

    /** @test */
    public function it_returns_correct_url_attribute()
    {
        $media = Media::factory()->create([
            'file_path' => 'media/products/test.jpg'
        ]);

        $url = $media->url;
        
        // Should return storage URL or placeholder
        $this->assertIsString($url);
        $this->assertTrue(
            str_contains($url, 'test.jpg') || str_contains($url, 'placeholder')
        );
    }

    /** @test */
    public function it_returns_correct_thumbnail_url()
    {
        $media = Media::factory()->create([
            'file_path' => 'media/products/test.jpg'
        ]);

        $thumbnailUrl = $media->thumbnail_url;
        
        $this->assertIsString($thumbnailUrl);
        // Should return thumbnail URL or fallback to main URL
    }

    /** @test */
    public function it_formats_file_size_correctly()
    {
        $media = Media::factory()->create(['file_size' => 1048576]); // 1MB
        $this->assertEquals('1.00 MB', $media->formatted_size);

        $media = Media::factory()->create(['file_size' => 1024]); // 1KB
        $this->assertEquals('1.00 KB', $media->formatted_size);

        $media = Media::factory()->create(['file_size' => 500]); // 500B
        $this->assertEquals('500 B', $media->formatted_size);
    }

    /** @test */
    public function it_generates_intelligent_alt_text()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        $media = Media::factory()->forProduct($product)->create(['alt_text' => null]);

        $altText = $media->display_alt_text;
        
        $this->assertStringContainsString('Test Product', $altText);
    }

    /** @test */
    public function it_uses_custom_alt_text_when_provided()
    {
        $media = Media::factory()->create(['alt_text' => 'Custom Alt Text']);

        $this->assertEquals('Custom Alt Text', $media->display_alt_text);
    }

    /** @test */
    public function it_correctly_identifies_images()
    {
        $imageMedia = Media::factory()->create(['mime_type' => 'image/jpeg']);
        $this->assertTrue($imageMedia->is_image);

        $documentMedia = Media::factory()->create(['mime_type' => 'application/pdf']);
        $this->assertFalse($documentMedia->is_image);
    }

    /** @test */
    public function it_calculates_image_dimensions_correctly()
    {
        $media = Media::factory()->create([
            'width' => 1920,
            'height' => 1080
        ]);

        $dimensions = $media->dimensions;
        
        $this->assertEquals(1920, $dimensions['width']);
        $this->assertEquals(1080, $dimensions['height']);
        $this->assertEquals(1.78, $dimensions['ratio']); // 16:9 ratio
    }

    /** @test */
    public function it_scopes_active_media()
    {
        $activeMedia = Media::factory()->create(['is_active' => true]);
        $inactiveMedia = Media::factory()->inactive()->create();

        $activeResults = Media::active()->get();
        
        $this->assertTrue($activeResults->contains($activeMedia));
        $this->assertFalse($activeResults->contains($inactiveMedia));
    }

    /** @test */
    public function it_scopes_primary_images()
    {
        $primaryMedia = Media::factory()->primaryImage()->create();
        $regularMedia = Media::factory()->create(['is_primary' => false]);

        $primaryResults = Media::primary()->get();
        
        $this->assertTrue($primaryResults->contains($primaryMedia));
        $this->assertFalse($primaryResults->contains($regularMedia));
    }

    /** @test */
    public function it_scopes_by_mediable_type()
    {
        $productMedia = Media::factory()->forProduct()->create();
        $variantMedia = Media::factory()->forVariant()->create();

        $productResults = Media::byType('App\\Models\\Product')->get();
        
        $this->assertTrue($productResults->contains($productMedia));
        $this->assertFalse($productResults->contains($variantMedia));
    }

    /** @test */
    public function it_can_be_marked_as_synced()
    {
        $media = Media::factory()->create(['sync_status' => 'pending']);

        $result = $media->markAsSynced('shop_1', ['external_id' => 123]);
        
        $this->assertTrue($result);
        $this->assertEquals('synced', $media->fresh()->sync_status);
        
        $mapping = $media->fresh()->prestashop_mapping;
        $this->assertEquals(123, $mapping['shop_1']['external_id']);
        $this->assertEquals('synced', $mapping['shop_1']['status']);
    }

    /** @test */
    public function it_can_be_marked_with_sync_error()
    {
        $media = Media::factory()->create(['sync_status' => 'pending']);

        $result = $media->markSyncError('shop_1', 'Upload failed');
        
        $this->assertTrue($result);
        $this->assertEquals('error', $media->fresh()->sync_status);
        
        $mapping = $media->fresh()->prestashop_mapping;
        $this->assertEquals('Upload failed', $mapping['shop_1']['error_message']);
        $this->assertEquals('error', $mapping['shop_1']['status']);
    }

    /** @test */
    public function it_generates_different_image_sizes()
    {
        $media = Media::factory()->create();

        $sizes = $media->generateSizes();
        
        $this->assertIsArray($sizes);
        $this->assertArrayHasKey('thumbnail', $sizes);
        $this->assertArrayHasKey('small', $sizes);
        $this->assertArrayHasKey('medium', $sizes);
        $this->assertArrayHasKey('large', $sizes);
        
        $this->assertEquals(150, $sizes['thumbnail']['width']);
        $this->assertEquals(150, $sizes['thumbnail']['height']);
    }

    /** @test */
    public function it_can_check_if_deletion_is_allowed()
    {
        $product = Product::factory()->create();
        
        // Create non-primary image
        $media = Media::factory()->forProduct($product)->create(['is_primary' => false]);
        $this->assertTrue($media->canDelete());
        
        // Create primary image (only image)
        $primaryMedia = Media::factory()->forProduct($product)->primaryImage()->create();
        // Should not be deletable if it's the only image
        $this->assertFalse($primaryMedia->canDelete());
    }

    /** @test */
    public function it_can_get_prestashop_mapping_for_specific_store()
    {
        $media = Media::factory()->syncedToPrestaShop(2)->create();

        $mapping = $media->getPrestaShopMapping(2);
        
        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('synced_at', $mapping);
        $this->assertEquals('synced', $mapping['status']);
    }

    /** @test */
    public function it_can_set_prestashop_mapping_for_specific_store()
    {
        $media = Media::factory()->create();

        $result = $media->setPrestaShopMapping(3, [
            'external_id' => 456,
            'position' => 2,
        ]);
        
        $this->assertTrue($result);
        
        $mapping = $media->fresh()->getPrestaShopMapping(3);
        $this->assertEquals(456, $mapping['external_id']);
        $this->assertEquals(2, $mapping['position']);
    }

    /** @test */
    public function it_orders_gallery_correctly()
    {
        $product = Product::factory()->create();
        
        $media1 = Media::factory()->forProduct($product)->create(['sort_order' => 2, 'is_primary' => false]);
        $media2 = Media::factory()->forProduct($product)->primaryImage()->create(['sort_order' => 1]);
        $media3 = Media::factory()->forProduct($product)->create(['sort_order' => 3, 'is_primary' => false]);

        $galleryOrder = Media::where('mediable_id', $product->id)
                             ->galleryOrder()
                             ->get();
        
        // Primary should be first, regardless of sort_order
        $this->assertEquals($media2->id, $galleryOrder->first()->id);
        $this->assertEquals($media1->id, $galleryOrder->get(1)->id);
        $this->assertEquals($media3->id, $galleryOrder->get(2)->id);
    }

    /** @test */
    public function it_uses_id_as_route_key()
    {
        $media = new Media();
        $this->assertEquals('id', $media->getRouteKeyName());
    }

    /** @test */
    public function it_uses_correct_table_name()
    {
        $media = new Media();
        $this->assertEquals('media', $media->getTable());
    }
}
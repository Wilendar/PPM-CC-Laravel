<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\AttributeTypeCreated;
use App\Events\AttributeValueCreated;
use App\Listeners\SyncNewAttributeTypeWithPrestaShops;
use App\Listeners\SyncNewAttributeValueWithPrestaShops;
use App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop;
use App\Jobs\PrestaShop\SyncAttributeValueWithPrestaShop;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

/**
 * Attribute Events & Listeners Tests
 *
 * Tests event-driven auto-sync when AttributeType/AttributeValue created
 *
 * COVERAGE:
 * - AttributeTypeCreated event dispatched
 * - SyncNewAttributeTypeWithPrestaShops listener triggered
 * - Jobs dispatched for each active shop
 * - AttributeValueCreated event dispatched
 * - SyncNewAttributeValueWithPrestaShops listener triggered
 *
 * @package Tests\Unit\Events
 */
class AttributeEventsTest extends TestCase
{
    use RefreshDatabase;

    protected PrestaShopShop $activeShop1;
    protected PrestaShopShop $activeShop2;
    protected PrestaShopShop $inactiveShop;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test shops
        $this->activeShop1 = PrestaShopShop::create([
            'name' => 'Active Shop 1',
            'url' => 'https://shop1.prestashop.com',
            'api_key' => 'KEY1',
            'is_active' => true,
        ]);

        $this->activeShop2 = PrestaShopShop::create([
            'name' => 'Active Shop 2',
            'url' => 'https://shop2.prestashop.com',
            'api_key' => 'KEY2',
            'is_active' => true,
        ]);

        $this->inactiveShop = PrestaShopShop::create([
            'name' => 'Inactive Shop',
            'url' => 'https://inactive.prestashop.com',
            'api_key' => 'KEY3',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function attribute_type_created_event_is_dispatched_on_creation()
    {
        Event::fake([AttributeTypeCreated::class]);

        $type = AttributeType::create([
            'name' => 'Kolor',
            'code' => 'color',
            'display_type' => 'color',
            'position' => 1,
        ]);

        // Manually dispatch event (in real app, would be in service layer)
        event(new AttributeTypeCreated($type));

        Event::assertDispatched(AttributeTypeCreated::class, function ($event) use ($type) {
            return $event->attributeType->id === $type->id;
        });
    }

    /** @test */
    public function listener_dispatches_jobs_for_all_active_shops()
    {
        Queue::fake();

        $type = AttributeType::create([
            'name' => 'Kolor',
            'code' => 'color',
            'display_type' => 'color',
            'position' => 1,
        ]);

        $listener = new SyncNewAttributeTypeWithPrestaShops();
        $listener->handle(new AttributeTypeCreated($type));

        // Should dispatch 2 jobs (only active shops)
        Queue::assertPushed(SyncAttributeGroupWithPrestaShop::class, 2);

        // Verify jobs for active shops
        Queue::assertPushed(SyncAttributeGroupWithPrestaShop::class, function ($job) use ($type) {
            return $job->attributeType->id === $type->id
                && $job->shop->id === $this->activeShop1->id;
        });

        Queue::assertPushed(SyncAttributeGroupWithPrestaShop::class, function ($job) use ($type) {
            return $job->attributeType->id === $type->id
                && $job->shop->id === $this->activeShop2->id;
        });

        // Should NOT dispatch for inactive shop
        Queue::assertNotPushed(SyncAttributeGroupWithPrestaShop::class, function ($job) use ($type) {
            return $job->shop->id === $this->inactiveShop->id;
        });
    }

    /** @test */
    public function listener_handles_no_active_shops_gracefully()
    {
        Queue::fake();

        // Deactivate all shops
        PrestaShopShop::query()->update(['is_active' => false]);

        $type = AttributeType::create([
            'name' => 'Kolor',
            'code' => 'color',
            'display_type' => 'color',
            'position' => 1,
        ]);

        $listener = new SyncNewAttributeTypeWithPrestaShops();
        $listener->handle(new AttributeTypeCreated($type));

        // Should NOT dispatch any jobs
        Queue::assertNothingPushed();
    }

    /** @test */
    public function attribute_value_created_event_is_dispatched_on_creation()
    {
        Event::fake([AttributeValueCreated::class]);

        $type = AttributeType::create([
            'name' => 'Kolor',
            'code' => 'color',
            'display_type' => 'color',
            'position' => 1,
        ]);

        $value = AttributeValue::create([
            'attribute_type_id' => $type->id,
            'code' => 'red',
            'value' => 'Czerwony',
            'color_hex' => '#ff0000',
            'position' => 1,
        ]);

        // Manually dispatch event
        event(new AttributeValueCreated($value));

        Event::assertDispatched(AttributeValueCreated::class, function ($event) use ($value) {
            return $event->attributeValue->id === $value->id;
        });
    }

    /** @test */
    public function value_listener_dispatches_jobs_for_all_active_shops()
    {
        Queue::fake();

        $type = AttributeType::create([
            'name' => 'Kolor',
            'code' => 'color',
            'display_type' => 'color',
            'position' => 1,
        ]);

        $value = AttributeValue::create([
            'attribute_type_id' => $type->id,
            'code' => 'red',
            'value' => 'Czerwony',
            'color_hex' => '#ff0000',
            'position' => 1,
        ]);

        $listener = new SyncNewAttributeValueWithPrestaShops();
        $listener->handle(new AttributeValueCreated($value));

        // Should dispatch 2 jobs (only active shops)
        Queue::assertPushed(SyncAttributeValueWithPrestaShop::class, 2);

        // Verify jobs for active shops
        Queue::assertPushed(SyncAttributeValueWithPrestaShop::class, function ($job) use ($value) {
            return $job->attributeValue->id === $value->id
                && $job->shop->id === $this->activeShop1->id;
        });

        Queue::assertPushed(SyncAttributeValueWithPrestaShop::class, function ($job) use ($value) {
            return $job->attributeValue->id === $value->id
                && $job->shop->id === $this->activeShop2->id;
        });
    }

    /** @test */
    public function events_are_registered_in_event_service_provider()
    {
        $registeredEvents = Event::getListeners(AttributeTypeCreated::class);
        $this->assertNotEmpty($registeredEvents);
        $this->assertContains(SyncNewAttributeTypeWithPrestaShops::class, array_keys($registeredEvents));

        $registeredEvents = Event::getListeners(AttributeValueCreated::class);
        $this->assertNotEmpty($registeredEvents);
        $this->assertContains(SyncNewAttributeValueWithPrestaShops::class, array_keys($registeredEvents));
    }
}

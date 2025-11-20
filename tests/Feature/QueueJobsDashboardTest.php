<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QueueJobsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user for tests
        $this->user = User::factory()->create();
    }

    /** @test */
    public function test_dashboard_renders_for_authenticated_user()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('admin.queue-jobs'));

        $response->assertStatus(200);
        $response->assertSee('Oczekujące');
        $response->assertSee('W trakcie');
        $response->assertSee('Błędy');
        $response->assertSee('Utknięte');
    }

    /** @test */
    public function test_dashboard_requires_authentication()
    {
        $response = $this->get(route('admin.queue-jobs'));

        // Should redirect to login or show 403
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 403
        );
    }

    /** @test */
    public function test_route_exists()
    {
        $this->assertTrue(route('admin.queue-jobs') !== null);
        $this->assertEquals('http://localhost/admin/queue-jobs', route('admin.queue-jobs'));
    }

    /** @test */
    public function test_component_class_exists()
    {
        $this->assertTrue(class_exists(\App\Http\Livewire\Admin\QueueJobsDashboard::class));
    }

    /** @test */
    public function test_service_class_exists()
    {
        $this->assertTrue(class_exists(\App\Services\QueueJobsService::class));
    }

    /** @test */
    public function test_component_has_required_properties()
    {
        $component = new \App\Http\Livewire\Admin\QueueJobsDashboard();

        $this->assertObjectHasProperty('filter', $component);
        $this->assertObjectHasProperty('selectedQueue', $component);
    }

    /** @test */
    public function test_component_has_required_methods()
    {
        $component = new \App\Http\Livewire\Admin\QueueJobsDashboard();

        $this->assertTrue(method_exists($component, 'render'));
        $this->assertTrue(method_exists($component, 'retryJob'));
        $this->assertTrue(method_exists($component, 'cancelJob'));
        $this->assertTrue(method_exists($component, 'deleteFailedJob'));
        $this->assertTrue(method_exists($component, 'retryAllFailed'));
        $this->assertTrue(method_exists($component, 'clearAllFailed'));
    }

    /** @test */
    public function test_view_file_exists()
    {
        $this->assertTrue(
            view()->exists('livewire.admin.queue-jobs-dashboard')
        );
    }
}

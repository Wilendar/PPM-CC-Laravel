<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QueueJobsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Unit Tests for QueueJobsService
 *
 * Tests comprehensive queue job monitoring and management functionality
 */
class QueueJobsServiceTest extends TestCase
{
    use DatabaseTransactions;

    private QueueJobsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QueueJobsService();
    }

    /**
     * Test getActiveJobs returns collection of active jobs
     */
    public function test_get_active_jobs_returns_collection(): void
    {
        // Create sample job
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\TestJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $jobs = $this->service->getActiveJobs();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $jobs);
        $this->assertCount(1, $jobs);
        $this->assertEquals('default', $jobs->first()['queue']);
        $this->assertEquals('pending', $jobs->first()['status']);
    }

    /**
     * Test getFailedJobs returns collection of failed jobs
     */
    public function test_get_failed_jobs_returns_collection(): void
    {
        // Create sample failed job
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-123',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\FailedJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'exception' => "Test Exception\nStack trace...",
            'failed_at' => now(),
        ]);

        $jobs = $this->service->getFailedJobs();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $jobs);
        $this->assertCount(1, $jobs);
        $this->assertEquals('test-uuid-123', $jobs->first()['uuid']);
        $this->assertEquals('Test Exception', $jobs->first()['exception_message']);
    }

    /**
     * Test getStuckJobs filters jobs processing > 5 minutes
     */
    public function test_get_stuck_jobs_filters_correctly(): void
    {
        $sixMinutesAgo = now()->subMinutes(6)->timestamp;
        $twoMinutesAgo = now()->subMinutes(2)->timestamp;

        // Create stuck job (6 minutes ago)
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\StuckJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'attempts' => 1,
            'reserved_at' => $sixMinutesAgo,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        // Create recent job (2 minutes ago - should NOT be stuck)
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\RecentJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'attempts' => 1,
            'reserved_at' => $twoMinutesAgo,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $stuckJobs = $this->service->getStuckJobs();

        $this->assertCount(1, $stuckJobs);
        $this->assertEquals('App\\Jobs\\StuckJob', $stuckJobs->first()['job_name']);
    }

    /**
     * Test parseJob extracts correct data structure
     */
    public function test_parse_job_extracts_data(): void
    {
        $jobData = (object)[
            'id' => 1,
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\TestJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ];

        $parsed = $this->service->parseJob($jobData);

        $this->assertIsArray($parsed);
        $this->assertArrayHasKey('id', $parsed);
        $this->assertArrayHasKey('queue', $parsed);
        $this->assertArrayHasKey('job_name', $parsed);
        $this->assertArrayHasKey('status', $parsed);
        $this->assertArrayHasKey('attempts', $parsed);
        $this->assertArrayHasKey('data', $parsed);
        $this->assertArrayHasKey('created_at', $parsed);
        $this->assertEquals('App\\Jobs\\TestJob', $parsed['job_name']);
        $this->assertEquals('pending', $parsed['status']);
    }

    /**
     * Test extractJobData extracts product information
     */
    public function test_extract_job_data_for_product(): void
    {
        $commandData = (object)[
            'product' => (object)[
                'id' => 123,
                'sku' => 'TEST-SKU-001',
            ],
        ];

        $extracted = $this->service->extractJobData($commandData);

        $this->assertIsArray($extracted);
        $this->assertArrayHasKey('product_id', $extracted);
        $this->assertArrayHasKey('sku', $extracted);
        $this->assertEquals(123, $extracted['product_id']);
        $this->assertEquals('TEST-SKU-001', $extracted['sku']);
    }

    /**
     * Test extractJobData extracts shop information
     */
    public function test_extract_job_data_for_shop(): void
    {
        $commandData = (object)[
            'shop' => (object)[
                'id' => 5,
                'name' => 'Test Shop',
            ],
        ];

        $extracted = $this->service->extractJobData($commandData);

        $this->assertIsArray($extracted);
        $this->assertArrayHasKey('shop_id', $extracted);
        $this->assertArrayHasKey('shop_name', $extracted);
        $this->assertEquals(5, $extracted['shop_id']);
        $this->assertEquals('Test Shop', $extracted['shop_name']);
    }

    /**
     * Test extractJobData handles missing data gracefully
     */
    public function test_extract_job_data_handles_empty_data(): void
    {
        $commandData = (object)['random' => 'data'];

        $extracted = $this->service->extractJobData($commandData);

        $this->assertIsArray($extracted);
        $this->assertEmpty($extracted);
    }

    /**
     * Test retryFailedJob calls Artisan command
     */
    public function test_retry_failed_job_calls_artisan(): void
    {
        // Create a failed job first
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-123',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\TestJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'exception' => 'Test Exception',
            'failed_at' => now(),
        ]);

        $result = $this->service->retryFailedJob('test-uuid-123');

        // Artisan command returns 0 on success
        $this->assertEquals(0, $result);
    }

    /**
     * Test cancelPendingJob deletes from database
     */
    public function test_cancel_pending_job_deletes_from_db(): void
    {
        // Create pending job
        DB::table('jobs')->insert([
            'id' => 999,
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\TestJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->assertDatabaseHas('jobs', ['id' => 999]);

        $deleted = $this->service->cancelPendingJob(999);

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('jobs', ['id' => 999]);
    }

    /**
     * Test deleteFailedJob removes from failed_jobs table
     */
    public function test_delete_failed_job_removes_from_table(): void
    {
        // Create failed job
        DB::table('failed_jobs')->insert([
            'uuid' => 'test-uuid-delete',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\FailedJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'exception' => 'Test Exception',
            'failed_at' => now(),
        ]);

        $this->assertDatabaseHas('failed_jobs', ['uuid' => 'test-uuid-delete']);

        $deleted = $this->service->deleteFailedJob('test-uuid-delete');

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => 'test-uuid-delete']);
    }

    /**
     * Test parseJob correctly identifies processing vs pending status
     */
    public function test_parse_job_identifies_status_correctly(): void
    {
        // Pending job (reserved_at = null)
        $pendingJob = (object)[
            'id' => 1,
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\PendingJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ];

        // Processing job (reserved_at != null)
        $processingJob = (object)[
            'id' => 2,
            'queue' => 'default',
            'payload' => json_encode([
                'displayName' => 'App\\Jobs\\ProcessingJob',
                'data' => ['command' => serialize((object)['test' => 'data'])],
            ]),
            'attempts' => 1,
            'reserved_at' => now()->subMinute()->timestamp,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ];

        $parsedPending = $this->service->parseJob($pendingJob);
        $parsedProcessing = $this->service->parseJob($processingJob);

        $this->assertEquals('pending', $parsedPending['status']);
        $this->assertNull($parsedPending['reserved_at']);

        $this->assertEquals('processing', $parsedProcessing['status']);
        $this->assertNotNull($parsedProcessing['reserved_at']);
        $this->assertInstanceOf(Carbon::class, $parsedProcessing['reserved_at']);
    }
}

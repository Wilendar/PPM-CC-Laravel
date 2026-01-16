<?php

namespace App\Jobs\ERP;

use App\Models\SyncJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * DynamicsSyncJob - Placeholder
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * PLACEHOLDER dla przyszlej integracji z Microsoft Dynamics.
 * Job zawsze failuje z informacja o braku implementacji.
 */
class DynamicsSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SyncJob $syncJob
    ) {
        $this->onQueue('erp_default');
    }

    public function handle(): void
    {
        $this->syncJob->start();

        $this->syncJob->fail(
            'Microsoft Dynamics integration is not yet implemented',
            'This is a placeholder job. Dynamics requires OAuth2 and OData API configuration.',
            null
        );
    }
}

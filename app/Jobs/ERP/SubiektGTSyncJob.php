<?php

namespace App\Jobs\ERP;

use App\Models\SyncJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * SubiektGTSyncJob - Placeholder
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * PLACEHOLDER dla przyszlej integracji z Subiekt GT.
 * Job zawsze failuje z informacja o braku implementacji.
 */
class SubiektGTSyncJob implements ShouldQueue
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
            'Subiekt GT integration is not yet implemented',
            'This is a placeholder job. Subiekt GT requires Windows Server with DLL bridge.',
            null
        );
    }
}

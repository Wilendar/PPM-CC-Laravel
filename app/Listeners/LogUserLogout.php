<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\AuditLog;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user) {
            AuditLog::logLogout();
        }
    }
}

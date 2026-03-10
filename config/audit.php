<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Logging Enabled
    |--------------------------------------------------------------------------
    |
    | Global switch to enable/disable automatic audit logging via the
    | Auditable trait. Can also be controlled at runtime via AuditContext.
    |
    */

    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Audit Logs
    |--------------------------------------------------------------------------
    |
    | When enabled, audit log entries will be dispatched to a queue
    | instead of being written synchronously. Reduces request latency
    | at the cost of slight delay in log visibility.
    |
    */

    'queue' => env('AUDIT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Batch Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of audit entries per model class within a single
    | request/job lifecycle. Prevents runaway logging during bulk
    | operations (e.g. mass imports updating thousands of rows).
    | After hitting this limit, one summary entry is logged.
    |
    */

    'batch_limit' => 100,

    /*
    |--------------------------------------------------------------------------
    | Globally Excluded Fields
    |--------------------------------------------------------------------------
    |
    | Fields listed here will never be included in audit log old_values
    | or new_values, regardless of model configuration. Prevents
    | logging of timestamps, tokens, and sensitive data.
    |
    */

    'global_exclude' => [
        'updated_at',
        'created_at',
        'deleted_at',
        'remember_token',
        'password',
        'email_verified_at',
    ],

];

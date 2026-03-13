<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStatusTransition extends Model
{
    protected $fillable = [
        'product_id',
        'from_status_id',
        'to_status_id',
        'trigger',
        'stock_at_transition',
        'warehouse_id',
        'sync_results',
        'transitioned_at',
    ];

    protected $casts = [
        'sync_results' => 'array',
        'transitioned_at' => 'datetime',
        'stock_at_transition' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class, 'to_status_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}

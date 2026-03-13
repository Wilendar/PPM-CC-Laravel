<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStatusIntegrationMapping extends Model
{
    public const TYPE_PRESTASHOP = 'prestashop';
    public const TYPE_BASELINKER = 'baselinker';
    public const TYPE_SUBIEKT_GT = 'subiekt_gt';

    public const TYPES = [
        self::TYPE_PRESTASHOP => 'PrestaShop',
        self::TYPE_BASELINKER => 'BaseLinker',
        self::TYPE_SUBIEKT_GT => 'Subiekt GT',
    ];

    protected $fillable = [
        'product_status_id',
        'integration_type',
        'maps_to_active',
        'description',
    ];

    protected $casts = [
        'maps_to_active' => 'boolean',
    ];

    public function productStatus(): BelongsTo
    {
        return $this->belongsTo(ProductStatus::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sync Log Model
 *
 * Audit trail for all PrestaShop API synchronization operations
 *
 * Operations: sync_product, sync_category, sync_image, sync_stock, sync_price, webhook
 * Directions: ppm_to_ps (FAZA 1), ps_to_ppm (FAZA 2)
 * Statuses: started, success, error, warning
 *
 * @property int $id
 * @property int $shop_id
 * @property int|null $product_id
 * @property string $operation sync_product|sync_category|sync_image|sync_stock|sync_price|webhook
 * @property string $direction ppm_to_ps|ps_to_ppm
 * @property string $status started|success|error|warning
 * @property string|null $message
 * @property array|null $request_data
 * @property array|null $response_data
 * @property int|null $execution_time_ms
 * @property string|null $api_endpoint
 * @property int|null $http_status_code
 * @property \Carbon\Carbon $created_at
 *
 * @property-read PrestaShopShop $shop
 * @property-read Product|null $product
 */
class SyncLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sync_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; // Only created_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'shop_id',
        'product_id',
        'operation',
        'direction',
        'status',
        'message',
        'request_data',
        'response_data',
        'execution_time_ms',
        'api_endpoint',
        'http_status_code',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'execution_time_ms' => 'integer',
        'http_status_code' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Operation type constants
     */
    public const OPERATION_SYNC_PRODUCT = 'sync_product';
    public const OPERATION_SYNC_CATEGORY = 'sync_category';
    public const OPERATION_SYNC_IMAGE = 'sync_image';
    public const OPERATION_SYNC_STOCK = 'sync_stock';
    public const OPERATION_SYNC_PRICE = 'sync_price';
    public const OPERATION_WEBHOOK = 'webhook';

    /**
     * Direction constants
     */
    public const DIRECTION_PPM_TO_PS = 'ppm_to_ps';
    public const DIRECTION_PS_TO_PPM = 'ps_to_ppm';

    /**
     * Status constants
     */
    public const STATUS_STARTED = 'started';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_WARNING = 'warning';

    /**
     * Get the shop that this log belongs to
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Get the product that this log belongs to
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: Filter by operation type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $operation
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    /**
     * Scope: Product sync logs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductSync($query)
    {
        return $query->where('operation', self::OPERATION_SYNC_PRODUCT);
    }

    /**
     * Scope: Category sync logs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategorySync($query)
    {
        return $query->where('operation', self::OPERATION_SYNC_CATEGORY);
    }

    /**
     * Scope: Filter by direction
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope: PPM to PrestaShop direction
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePpmToPs($query)
    {
        return $query->where('direction', self::DIRECTION_PPM_TO_PS);
    }

    /**
     * Scope: PrestaShop to PPM direction
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePsToPpm($query)
    {
        return $query->where('direction', self::DIRECTION_PS_TO_PPM);
    }

    /**
     * Scope: Filter by status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Successful logs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope: Error logs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeError($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Scope: Warning logs
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWarning($query)
    {
        return $query->where('status', self::STATUS_WARNING);
    }

    /**
     * Scope: Filter by shop
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by product
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Filter by HTTP status code
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByHttpStatus($query, int $code)
    {
        return $query->where('http_status_code', $code);
    }

    /**
     * Scope: Successful HTTP responses (2xx)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHttpSuccess($query)
    {
        return $query->whereBetween('http_status_code', [200, 299]);
    }

    /**
     * Scope: Client errors (4xx)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHttpClientError($query)
    {
        return $query->whereBetween('http_status_code', [400, 499]);
    }

    /**
     * Scope: Server errors (5xx)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHttpServerError($query)
    {
        return $query->whereBetween('http_status_code', [500, 599]);
    }

    /**
     * Scope: Recent logs (last N days)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Slow operations (execution time > threshold ms)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $thresholdMs
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSlow($query, int $thresholdMs = 5000)
    {
        return $query->where('execution_time_ms', '>', $thresholdMs);
    }

    /**
     * Create success log
     *
     * @param int $shopId
     * @param string $operation
     * @param string $direction
     * @param array $data Additional data
     * @return SyncLog
     */
    public static function logSuccess(
        int $shopId,
        string $operation,
        string $direction = self::DIRECTION_PPM_TO_PS,
        array $data = []
    ): SyncLog {
        return static::create(array_merge([
            'shop_id' => $shopId,
            'operation' => $operation,
            'direction' => $direction,
            'status' => self::STATUS_SUCCESS,
            'created_at' => now(),
        ], $data));
    }

    /**
     * Create error log
     *
     * @param int $shopId
     * @param string $operation
     * @param string $errorMessage
     * @param string $direction
     * @param array $data Additional data
     * @return SyncLog
     */
    public static function logError(
        int $shopId,
        string $operation,
        string $errorMessage,
        string $direction = self::DIRECTION_PPM_TO_PS,
        array $data = []
    ): SyncLog {
        return static::create(array_merge([
            'shop_id' => $shopId,
            'operation' => $operation,
            'direction' => $direction,
            'status' => self::STATUS_ERROR,
            'message' => $errorMessage,
            'created_at' => now(),
        ], $data));
    }

    /**
     * Check if log is successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if log is error
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    /**
     * Check if log is warning
     *
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->status === self::STATUS_WARNING;
    }
}

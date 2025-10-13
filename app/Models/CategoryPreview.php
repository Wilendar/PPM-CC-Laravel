<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Category Preview Model
 *
 * ETAP_07 FAZA 3D: Category Import Preview System
 *
 * Purpose: Temporary storage dla category preview data przed bulk import
 * Lifecycle: Created → User reviews → Approved/Rejected → Auto-expires (1h)
 * Business Logic: Preview missing categories → user approval → bulk create
 *
 * Workflow:
 * 1. AnalyzeMissingCategories job creates preview record
 * 2. CategoryPreviewModal displays tree dla user
 * 3. User approves/rejects → status updated
 * 4. BulkCreateCategories job processes approved categories
 * 5. Cleanup cron removes expired records
 *
 * @property int $id
 * @property string $job_id UUID linking to JobProgress
 * @property int $shop_id PrestaShop shop ID
 * @property array $category_tree_json Hierarchical category tree
 * @property int $total_categories Total category count
 * @property array|null $user_selection_json User-selected category IDs
 * @property string $status pending|approved|rejected|expired
 * @property Carbon $expires_at Auto-expiration timestamp
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read PrestaShopShop $shop
 * @property-read JobProgress|null $jobProgress
 *
 * @method static Builder active()
 * @method static Builder expired()
 * @method static Builder forShop(int $shopId)
 * @method static Builder forJob(string $jobId)
 * @method static Builder pending()
 *
 * @package App\Models
 * @version 1.0
 * @since 2025-10-08
 */
class CategoryPreview extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_preview';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Preview expiration time (1 hour)
     */
    public const EXPIRATION_HOURS = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'job_id',
        'shop_id',
        'category_tree_json',
        'total_categories',
        'user_selection_json',
        'import_context_json',  // Original import options (mode, category_id, product_ids)
        'status',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * Performance: JSON casting dla efficient tree manipulation
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shop_id' => 'integer',
            'total_categories' => 'integer',
            'category_tree_json' => 'array',
            'user_selection_json' => 'array',
            'import_context_json' => 'array',  // Original import context casting
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * Business Logic: Auto-set expires_at przy tworzeniu
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($preview) {
            if (!$preview->expires_at) {
                $preview->expires_at = Carbon::now()->addHours(self::EXPIRATION_HOURS);
            }

            if (!$preview->total_categories && isset($preview->category_tree_json['total_count'])) {
                $preview->total_categories = $preview->category_tree_json['total_count'];
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the PrestaShop shop this preview belongs to
     *
     * Performance: Foreign key relationship dla eager loading
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(PrestaShopShop::class, 'shop_id');
    }

    /**
     * Get the job progress for this preview
     *
     * Business Logic: Track import progress via job_id
     * Performance: UUID matching on job_progress table
     *
     * @return BelongsTo
     */
    public function jobProgress(): BelongsTo
    {
        return $this->belongsTo(JobProgress::class, 'job_id', 'job_id');
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active (pending) previews only
     *
     * Business Logic: Nie expired, nie approved/rejected
     * Performance: Status index dla fast filtering
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope: Expired previews
     *
     * Business Logic: Cleanup candidates
     * Performance: expires_at index dla CRON query
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<', Carbon::now())
              ->orWhere('status', self::STATUS_EXPIRED);
        });
    }

    /**
     * Scope: Filter by shop
     *
     * Performance: shop_id index
     *
     * @param Builder $query
     * @param int $shopId
     * @return Builder
     */
    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Filter by job ID
     *
     * Performance: job_id unique index dla single record lookup
     *
     * @param Builder $query
     * @param string $jobId UUID
     * @return Builder
     */
    public function scopeForJob(Builder $query, string $jobId): Builder
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope: Pending previews only
     *
     * Business Logic: Awaiting user approval
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Mark preview as approved
     *
     * Business Logic: User accepted category tree → trigger bulk create
     *
     * @return bool
     */
    public function markApproved(): bool
    {
        return $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark preview as rejected
     *
     * Business Logic: User cancelled import → no categories created
     *
     * @return bool
     */
    public function markRejected(): bool
    {
        return $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Mark preview as expired
     *
     * Business Logic: Timeout occurred → require new analysis
     *
     * @return bool
     */
    public function markExpired(): bool
    {
        return $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Check if preview is expired
     *
     * Business Logic: Validation before displaying modal
     * Performance: Simple timestamp comparison
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if preview is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    /**
     * Check if preview is approved
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if preview is rejected
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get category tree array
     *
     * Business Logic: Access hierarchical tree structure
     * Performance: Direct JSON column access (already cast)
     *
     * @return array
     */
    public function getCategoryTree(): array
    {
        return $this->category_tree_json['categories'] ?? [];
    }

    /**
     * Get total category count
     *
     * Business Logic: Display count in UI
     * Performance: Cached in column dla instant access
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->total_categories;
    }

    /**
     * Get max tree depth
     *
     * Business Logic: Tree complexity indicator
     *
     * @return int
     */
    public function getMaxDepth(): int
    {
        return $this->category_tree_json['max_depth'] ?? 0;
    }

    /**
     * Get user-selected category IDs
     *
     * Business Logic: Filter tree dla bulk creation
     *
     * @return array
     */
    public function getSelectedCategoryIds(): array
    {
        return $this->user_selection_json ?? [];
    }

    /**
     * Set user selection
     *
     * Business Logic: Store user's category choices before approval
     *
     * @param array $categoryIds PrestaShop category IDs
     * @return bool
     */
    public function setUserSelection(array $categoryIds): bool
    {
        return $this->update(['user_selection_json' => $categoryIds]);
    }

    /**
     * Get flattened category list from tree
     *
     * Business Logic: Convert hierarchical tree to flat array
     * Performance: Recursive traversal dla bulk operations
     *
     * @return array
     */
    public function getFlattenedCategories(): array
    {
        return $this->flattenTree($this->getCategoryTree());
    }

    /**
     * Flatten category tree recursively
     *
     * Private helper method dla tree traversal
     *
     * @param array $categories
     * @return array
     */
    private function flattenTree(array $categories): array
    {
        $flattened = [];

        foreach ($categories as $category) {
            $children = $category['children'] ?? [];
            unset($category['children']); // Remove children key

            $flattened[] = $category;

            if (!empty($children)) {
                $flattened = array_merge($flattened, $this->flattenTree($children));
            }
        }

        return $flattened;
    }

    /**
     * Validate business rules
     *
     * Enterprise validation dla preview data integrity
     *
     * @return array Validation errors
     */
    public function validateBusinessRules(): array
    {
        $errors = [];

        // Job ID validation
        if (empty($this->job_id)) {
            $errors[] = 'Job ID is required';
        }

        // Shop validation
        if (!$this->shop_id || !$this->shop) {
            $errors[] = 'Invalid shop reference';
        }

        // Category tree validation
        if (empty($this->category_tree_json)) {
            $errors[] = 'Category tree cannot be empty';
        }

        // Expiration validation
        if ($this->isExpired() && $this->status === self::STATUS_PENDING) {
            $errors[] = 'Preview has expired';
        }

        return $errors;
    }

    /**
     * Extend expiration time
     *
     * Business Logic: Allow user more time dla review
     *
     * @param int $hours Additional hours
     * @return bool
     */
    public function extendExpiration(int $hours = 1): bool
    {
        return $this->update([
            'expires_at' => $this->expires_at->addHours($hours)
        ]);
    }

    /**
     * Get status badge class dla UI
     *
     * UI Helper method dla consistent styling
     *
     * @return string
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_EXPIRED => 'badge-secondary',
            default => 'badge-secondary'
        };
    }

    /**
     * Get status label dla UI
     *
     * UI Helper method dla user-friendly display
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Oczekuje',
            self::STATUS_APPROVED => 'Zatwierdzono',
            self::STATUS_REJECTED => 'Odrzucono',
            self::STATUS_EXPIRED => 'Wygasło',
            default => 'Nieznany'
        };
    }
}

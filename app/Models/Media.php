<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Media Model - FAZA C: Polymorphic Media Management System
 * 
 * Zarządza zdjęciami dla różnych encji w systemie PPM-CC-Laravel:
 * - Product (główny produkt - do 20 zdjęć)
 * - ProductVariant (wariant produktu - dedykowane zdjęcia)
 * 
 * Funkcjonalności klasy enterprise:
 * - Polymorphic relationships dla uniwersalności
 * - Multi-size image generation (thumbnails, watermarks)
 * - PrestaShop multi-store mapping w JSONB
 * - CDN integration ready z storage abstraction
 * - Performance optimization z strategic indexing
 * - SEO-friendly alt text z intelligent fallback
 * 
 * Performance optimizations:
 * - Strategic eager loading dla gallery views
 * - Efficient primary image selection
 * - Cached URL generation
 * - Optimized query scopes
 * 
 * @property int $id
 * @property string $mediable_type Product|ProductVariant
 * @property int $mediable_id ID powiązanego obiektu
 * @property string $file_name Nazwa pliku w storage
 * @property string|null $original_name Oryginalna nazwa uploadowanego pliku
 * @property string $file_path Ścieżka do pliku w storage
 * @property int $file_size Rozmiar w bajtach
 * @property string $mime_type MIME type (image/jpeg, etc.)
 * @property int|null $width Szerokość obrazu w pikselach
 * @property int|null $height Wysokość obrazu w pikselach
 * @property string|null $alt_text Tekst alternatywny dla SEO
 * @property int $sort_order Kolejność wyświetlania (0-pierwsza)
 * @property bool $is_primary Główne zdjęcie produktu/wariantu
 * @property array|null $prestashop_mapping Mapowanie per sklep PrestaShop
 * @property string $sync_status pending|synced|error|ignored
 * @property bool $is_active Status aktywności
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read \App\Models\Product|\App\Models\ProductVariant $mediable
 * @property-read string $url
 * @property-read string $thumbnail_url
 * @property-read string $formatted_size
 * @property-read string $display_alt_text
 * @property-read bool $is_image
 * @property-read array $dimensions
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder primary()
 * @method static \Illuminate\Database\Eloquent\Builder byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder images()
 * @method static \Illuminate\Database\Eloquent\Builder synced()
 * @method static \Illuminate\Database\Eloquent\Builder needsSync()
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA C - Media & Relations Implementation
 */
class Media extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'width',
        'height',
        'alt_text',
        'sort_order',
        'is_primary',
        'prestashop_mapping',
        'sync_status',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'deleted_at',
        'prestashop_mapping', // Może zawierać wrażliwe dane mapowania
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mediable_id' => 'integer',
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'prestashop_mapping' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * Business Logic: Auto-generation unique file names i primary logic
     */
    protected static function boot(): void
    {
        parent::boot();

        // Ensure only one primary image per mediable
        static::saving(function ($media) {
            if ($media->is_primary) {
                // Remove primary flag from other media for same mediable
                static::where('mediable_type', $media->mediable_type)
                      ->where('mediable_id', $media->mediable_id)
                      ->where('id', '!=', $media->id ?? 0)
                      ->update(['is_primary' => false]);
            }
        });

        // Generate unique file name if not provided
        static::creating(function ($media) {
            if (empty($media->file_name) && !empty($media->original_name)) {
                $media->file_name = $media->generateUniqueFileName($media->original_name);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Laravel Eloquent Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Get the parent mediable model (Product or ProductVariant).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Laravel 12.x Attribute Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full URL to the media file
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (Storage::exists($this->file_path)) {
                    return Storage::url($this->file_path);
                }
                
                // Fallback to placeholder based on mediable type
                return $this->getPlaceholderUrl();
            }
        );
    }

    /**
     * Get the thumbnail URL (150x150 square crop)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // Generate thumbnail path
                $thumbnailPath = $this->getThumbnailPath();
                
                if (Storage::exists($thumbnailPath)) {
                    return Storage::url($thumbnailPath);
                }
                
                // Try to generate thumbnail (placeholder implementation)
                // TODO: Implement actual thumbnail generation in FAZA C
                return $this->url;
            }
        );
    }

    /**
     * Get formatted file size (human readable)
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedSize(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $bytes = $this->file_size;
                
                if ($bytes >= 1073741824) {
                    return number_format($bytes / 1073741824, 2) . ' GB';
                } elseif ($bytes >= 1048576) {
                    return number_format($bytes / 1048576, 2) . ' MB';
                } elseif ($bytes >= 1024) {
                    return number_format($bytes / 1024, 2) . ' KB';
                }
                
                return $bytes . ' B';
            }
        );
    }

    /**
     * Get display-ready alt text with intelligent fallback
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function displayAltText(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (!empty($this->alt_text)) {
                    return $this->alt_text;
                }
                
                // Generate intelligent alt text based on mediable
                if ($this->mediable) {
                    $baseName = '';
                    
                    if ($this->mediable instanceof Product) {
                        $baseName = $this->mediable->display_name;
                    } elseif ($this->mediable instanceof ProductVariant) {
                        $baseName = $this->mediable->product->display_name . ' - ' . $this->mediable->variant_name;
                    }
                    
                    return $baseName . ($this->is_primary ? '' : ' - zdjęcie ' . ($this->sort_order + 1));
                }
                
                return 'Zdjęcie produktu';
            }
        );
    }

    /**
     * Check if file is an image
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isImage(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => str_starts_with($this->mime_type, 'image/')
        );
    }

    /**
     * Get image dimensions as array
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function dimensions(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [
                'width' => $this->width,
                'height' => $this->height,
                'ratio' => $this->width && $this->height ? round($this->width / $this->height, 2) : null,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Business Logic Filters
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active media only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Primary images only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Filter by mediable type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('mediable_type', $type);
    }

    /**
     * Scope: Images only (exclude other file types if any)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'LIKE', 'image/%');
    }

    /**
     * Scope: Successfully synced media
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSynced(Builder $query): Builder
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope: Media that needs synchronization
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsSync(Builder $query): Builder
    {
        return $query->whereIn('sync_status', ['pending', 'error']);
    }

    /**
     * Scope: Gallery order (primary first, then by sort_order)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGalleryOrder(Builder $query): Builder
    {
        return $query->orderBy('is_primary', 'desc')
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Enterprise Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Generate unique file name for storage
     *
     * @param string $originalName
     * @return string
     */
    private function generateUniqueFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        
        $uniqueName = $baseName . '_' . uniqid() . '_' . time();
        
        return $uniqueName . ($extension ? '.' . strtolower($extension) : '');
    }

    /**
     * Get placeholder URL based on mediable type
     *
     * @return string
     */
    private function getPlaceholderUrl(): string
    {
        if ($this->mediable instanceof Product) {
            $productType = $this->mediable->product_type;
            
            $placeholders = [
                'vehicle' => '/images/placeholders/vehicle-placeholder.jpg',
                'spare_part' => '/images/placeholders/spare-part-placeholder.jpg',
                'clothing' => '/images/placeholders/clothing-placeholder.jpg',
                'other' => '/images/placeholders/default-placeholder.jpg',
            ];
            
            return asset($placeholders[$productType] ?? $placeholders['other']);
        }
        
        return asset('/images/placeholders/default-placeholder.jpg');
    }

    /**
     * Get thumbnail path for storage
     *
     * @return string
     */
    private function getThumbnailPath(): string
    {
        $pathInfo = pathinfo($this->file_path);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';
        
        return $directory . '/thumbs/' . $filename . '_thumb.' . $extension;
    }

    /**
     * Mark media as synced with external system
     *
     * @param string $system
     * @param array $data
     * @return bool
     */
    public function markAsSynced(string $system, array $data = []): bool
    {
        $mapping = $this->prestashop_mapping ?? [];
        $mapping[$system] = array_merge($mapping[$system] ?? [], $data, [
            'synced_at' => now()->toISOString(),
            'status' => 'synced',
        ]);

        $this->prestashop_mapping = $mapping;
        $this->sync_status = 'synced';
        
        return $this->save();
    }

    /**
     * Mark media as sync error
     *
     * @param string $system
     * @param string $error
     * @return bool
     */
    public function markSyncError(string $system, string $error): bool
    {
        $mapping = $this->prestashop_mapping ?? [];
        $mapping[$system] = array_merge($mapping[$system] ?? [], [
            'error_at' => now()->toISOString(),
            'error_message' => $error,
            'status' => 'error',
        ]);

        $this->prestashop_mapping = $mapping;
        $this->sync_status = 'error';
        
        return $this->save();
    }

    /**
     * Generate different image sizes (placeholder implementation)
     * 
     * TODO: Implement actual image processing in FAZA C
     *
     * @param array $sizes
     * @return array
     */
    public function generateSizes(array $sizes = []): array
    {
        $defaultSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];
        
        $sizes = array_merge($defaultSizes, $sizes);
        $generatedSizes = [];
        
        foreach ($sizes as $sizeName => $dimensions) {
            // Placeholder - actual image processing would go here
            $generatedSizes[$sizeName] = [
                'url' => $this->url, // Would be actual resized image URL
                'width' => $dimensions['width'],
                'height' => $dimensions['height'],
                'path' => $this->file_path, // Would be actual resized image path
            ];
        }
        
        return $generatedSizes;
    }

    /**
     * Check if media can be deleted
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        // Cannot delete primary image if it's the only image
        if ($this->is_primary) {
            $otherMedia = static::where('mediable_type', $this->mediable_type)
                               ->where('mediable_id', $this->mediable_id)
                               ->where('id', '!=', $this->id)
                               ->active()
                               ->count();
            
            if ($otherMedia === 0) {
                return false; // Cannot delete the only image
            }
        }
        
        // TODO: Add checks for:
        // - Active sync processes
        // - External system dependencies
        
        return true;
    }

    /**
     * Get media for specific PrestaShop store
     *
     * @param int $storeId
     * @return array|null
     */
    public function getPrestaShopMapping(int $storeId): ?array
    {
        $mappings = $this->prestashop_mapping ?? [];
        
        return $mappings["store_{$storeId}"] ?? null;
    }

    /**
     * Set PrestaShop mapping for specific store
     *
     * @param int $storeId
     * @param array $data
     * @return bool
     */
    public function setPrestaShopMapping(int $storeId, array $data): bool
    {
        $mappings = $this->prestashop_mapping ?? [];
        $mappings["store_{$storeId}"] = array_merge($mappings["store_{$storeId}"] ?? [], $data, [
            'updated_at' => now()->toISOString(),
        ]);
        
        $this->prestashop_mapping = $mappings;
        
        return $this->save();
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return 'media';
    }
}
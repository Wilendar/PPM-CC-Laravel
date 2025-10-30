<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Variant Image Model
 *
 * Zdjęcie wariantu produktu
 * Wspiera cover image i pozycjonowanie
 *
 * @property int $id
 * @property int $variant_id
 * @property string $filename Nazwa pliku
 * @property string $path Ścieżka do pliku
 * @property bool $is_cover Czy główne zdjęcie
 * @property int|null $position Kolejność wyświetlania
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VariantImage extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'variant_images';

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'variant_id',
        'filename',
        'path',
        'is_cover',
        'position',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'variant_id' => 'integer',
        'is_cover' => 'boolean',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Storage disk
     */
    protected const STORAGE_DISK = 'public';

    /**
     * Base path for variant images
     */
    protected const BASE_PATH = 'variants';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only cover images
     */
    public function scopeCover($query)
    {
        return $query->where('is_cover', true);
    }

    /**
     * Scope: Ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('id', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get full file path
     */
    public function getFullPath(): string
    {
        return Storage::disk(self::STORAGE_DISK)->path($this->path);
    }

    /**
     * Get public URL
     */
    public function getUrl(): string
    {
        return Storage::disk(self::STORAGE_DISK)->url($this->path);
    }

    /**
     * Get thumbnail path (if exists)
     */
    public function getThumbPath(): ?string
    {
        $thumbPath = str_replace(
            $this->filename,
            'thumb_' . $this->filename,
            $this->path
        );

        if (Storage::disk(self::STORAGE_DISK)->exists($thumbPath)) {
            return $thumbPath;
        }

        return null;
    }

    /**
     * Get thumbnail URL (if exists)
     */
    public function getThumbUrl(): ?string
    {
        $thumbPath = $this->getThumbPath();

        if ($thumbPath) {
            return Storage::disk(self::STORAGE_DISK)->url($thumbPath);
        }

        return null;
    }

    /**
     * Delete image file from storage
     */
    public function deleteFile(): bool
    {
        $deleted = Storage::disk(self::STORAGE_DISK)->delete($this->path);

        // Delete thumbnail if exists
        $thumbPath = $this->getThumbPath();
        if ($thumbPath) {
            Storage::disk(self::STORAGE_DISK)->delete($thumbPath);
        }

        return $deleted;
    }

    /**
     * Set as cover image (unset others)
     */
    public function setAsCover(): bool
    {
        // Unset other cover images for this variant
        static::where('variant_id', $this->variant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_cover' => false]);

        $this->is_cover = true;
        return $this->save();
    }
}

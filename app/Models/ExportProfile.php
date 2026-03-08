<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * ExportProfile Model - Export & Feed Profile Configuration
 *
 * Przechowuje konfiguracje profili eksportu produktow:
 * - Formaty: CSV, XLSX, JSON, XML (Google, Ceneo, PrestaShop)
 * - Konfigurowalne pola, filtry, grupy cenowe, magazyny, sklepy
 * - Harmonogram generowania: reczny lub automatyczny (1h-24h)
 * - Publiczny dostep przez token (dla feedow zewnetrznych)
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $token
 * @property string $format
 * @property array $field_config
 * @property array $filter_config
 * @property array $price_groups
 * @property array $warehouses
 * @property array $shop_ids
 * @property string $schedule
 * @property bool $is_active
 * @property bool $is_public
 * @property string|null $file_path
 * @property int|null $file_size
 * @property int|null $product_count
 * @property int|null $generation_duration
 * @property \Carbon\Carbon|null $last_generated_at
 * @property \Carbon\Carbon|null $next_generation_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ExportProfile extends Model
{
    use HasFactory, SoftDeletes;

    // === CONSTANTS ===

    const FORMATS = ['csv', 'xlsx', 'json', 'xml_google', 'xml_ceneo', 'xml_prestashop'];

    const SCHEDULES = ['manual', '1h', '6h', '12h', '24h'];

    const SCHEDULE_MINUTES = [
        'manual' => null,
        '1h'     => 60,
        '6h'     => 360,
        '12h'    => 720,
        '24h'    => 1440,
    ];

    // === FILLABLE ===

    protected $fillable = [
        'name',
        'slug',
        'token',
        'format',
        'field_config',
        'filter_config',
        'price_groups',
        'warehouses',
        'shop_ids',
        'schedule',
        'is_active',
        'is_public',
        'file_path',
        'file_size',
        'product_count',
        'generation_duration',
        'last_generated_at',
        'next_generation_at',
        'created_by',
        'updated_by',
    ];

    // === CASTS ===

    protected $casts = [
        'field_config'        => 'array',
        'filter_config'       => 'array',
        'price_groups'        => 'array',
        'warehouses'          => 'array',
        'shop_ids'            => 'array',
        'is_active'           => 'boolean',
        'is_public'           => 'boolean',
        'file_size'           => 'integer',
        'product_count'       => 'integer',
        'generation_duration' => 'integer',
        'last_generated_at'   => 'datetime',
        'next_generation_at'  => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    // === BOOT ===

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->token)) {
                $model->token = Str::random(64);
            }

            if (empty($model->slug)) {
                $model->slug = $model->generateUniqueSlug($model->name);
            }
        });
    }

    // === RELATIONSHIPS ===

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExportProfileLog::class, 'export_profile_id');
    }

    // === METHODS ===

    public function getFeedUrl(): string
    {
        return url('/feed/' . $this->token);
    }

    public function getDownloadUrl(): string
    {
        return url('/feed/' . $this->token . '/download');
    }

    public function isFeedFresh(): bool
    {
        if (!$this->last_generated_at) {
            return false;
        }

        $minutes = self::SCHEDULE_MINUTES[$this->schedule] ?? null;

        if ($minutes === null) {
            return true;
        }

        return $this->last_generated_at->diffInMinutes(now()) < $minutes;
    }

    public function needsGeneration(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return !$this->isFeedFresh();
    }

    // === SCOPES ===

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeNeedsGeneration(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('schedule', '!=', 'manual')
            ->where(function (Builder $q) {
                $q->whereNull('last_generated_at')
                  ->orWhere('next_generation_at', '<=', now());
            });
    }

    // === HELPERS ===

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

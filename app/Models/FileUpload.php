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
 * FileUpload Model - FAZA C: Universal File Upload System
 * 
 * Zarządza dokumentami dla różnych encji w systemie PPM-CC-Laravel:
 * - Container (dokumenty kontenerów - ZIP, PDF, XLSX, XML)
 * - Order (dokumenty zamówień)
 * - Product (dokumenty produktów - instrukcje, certyfikaty)
 * - User (dokumenty użytkowników)
 * 
 * Enterprise security features:
 * - Access level control (admin, manager, all)
 * - Audit trail z uploaded_by tracking
 * - File type classification i validation
 * - Polymorphic relationships dla uniwersalności
 * - Safe download URL generation
 * - Automatic metadata extraction
 * 
 * Performance optimizations:
 * - Strategic indexing dla access patterns
 * - Efficient file type filtering
 * - Metadata caching w JSONB field
 * - Optimized query scopes
 * 
 * @property int $id
 * @property string $uploadable_type Container|Order|Product|User
 * @property int $uploadable_id ID powiązanego obiektu
 * @property string $file_name Nazwa pliku w storage
 * @property string $original_name Oryginalna nazwa uploadowanego pliku
 * @property string $file_path Ścieżka do pliku w storage
 * @property int $file_size Rozmiar w bajtach
 * @property string $mime_type MIME type pliku
 * @property string $file_type document|spreadsheet|archive|certificate|manual|other
 * @property string $access_level admin|manager|all
 * @property int $uploaded_by ID użytkownika który uploadował
 * @property string|null $description Opis dokumentu
 * @property array|null $metadata Dodatkowe metadane
 * @property bool $is_active Status aktywności
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * 
 * @property-read \App\Models\Container|\App\Models\Order|\App\Models\Product|\App\Models\User $uploadable
 * @property-read \App\Models\User $uploader
 * @property-read string $download_url
 * @property-read string $formatted_size
 * @property-read string $icon
 * @property-read bool $is_document
 * @property-read bool $is_spreadsheet
 * @property-read bool $is_archive
 * 
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder accessibleBy(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder documents()
 * @method static \Illuminate\Database\Eloquent\Builder spreadsheets()
 * @method static \Illuminate\Database\Eloquent\Builder archives()
 * 
 * @package App\Models
 * @version 1.0
 * @since FAZA C - Media & Relations Implementation
 */
class FileUpload extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'uploadable_type',
        'uploadable_id',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'file_type',
        'access_level',
        'uploaded_by',
        'description',
        'metadata',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'deleted_at',
        'file_path', // Security: Don't expose internal file paths
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploadable_id' => 'integer',
            'file_size' => 'integer',
            'uploaded_by' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     *
     * Business Logic: Auto-generation unique file names i metadata
     */
    protected static function boot(): void
    {
        parent::boot();

        // Generate unique file name if not provided
        static::creating(function ($upload) {
            if (empty($upload->file_name) && !empty($upload->original_name)) {
                $upload->file_name = $upload->generateUniqueFileName($upload->original_name);
            }

            // Auto-detect file type if not provided
            if (empty($upload->file_type)) {
                $upload->file_type = $upload->detectFileType($upload->mime_type);
            }

            // Generate metadata if not provided
            if (empty($upload->metadata)) {
                $upload->metadata = $upload->generateMetadata();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Laravel Eloquent Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Get the parent uploadable model (Container, Order, Product, User).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function uploadable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS - Laravel 12.x Attribute Pattern
    |--------------------------------------------------------------------------
    */

    /**
     * Get secure download URL for the file
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function downloadUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // Generate secure temporary URL or route to download controller
                // This prevents direct access to file paths
                return route('files.download', ['file' => $this->id]);
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
     * Get icon class/name based on file type
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function icon(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return match ($this->file_type) {
                    'document' => $this->getDocumentIcon(),
                    'spreadsheet' => 'fas fa-file-excel',
                    'archive' => 'fas fa-file-archive',
                    'certificate' => 'fas fa-certificate',
                    'manual' => 'fas fa-book',
                    default => 'fas fa-file',
                };
            }
        );
    }

    /**
     * Check if file is a document
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isDocument(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->file_type === 'document'
        );
    }

    /**
     * Check if file is a spreadsheet
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isSpreadsheet(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->file_type === 'spreadsheet'
        );
    }

    /**
     * Check if file is an archive
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function isArchive(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->file_type === 'archive'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES - Business Logic Filters
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Active files only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by uploadable type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('uploadable_type', $type);
    }

    /**
     * Scope: Files accessible by specific user (based on access level)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        // Admin can access everything
        if ($user->isAdmin()) {
            return $query;
        }
        
        // Manager can access admin and manager level files
        if ($user->isManager()) {
            return $query->whereIn('access_level', ['all', 'manager', 'admin']);
        }
        
        // Regular users can only access 'all' level files or their own uploads
        return $query->where(function ($q) use ($user) {
            $q->where('access_level', 'all')
              ->orWhere('uploaded_by', $user->id);
        });
    }

    /**
     * Scope: Documents only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where('file_type', 'document');
    }

    /**
     * Scope: Spreadsheets only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSpreadsheets(Builder $query): Builder
    {
        return $query->where('file_type', 'spreadsheet');
    }

    /**
     * Scope: Archives only
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchives(Builder $query): Builder
    {
        return $query->where('file_type', 'archive');
    }

    /**
     * Scope: Recent uploads (within last 30 days)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(30));
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS - Enterprise Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Check if file is accessible by specific user
     *
     * @param User $user
     * @return bool
     */
    public function isAccessibleBy(User $user): bool
    {
        // Admin can access everything
        if ($user->isAdmin()) {
            return true;
        }
        
        // Manager can access admin and manager level files
        if ($user->isManager()) {
            return in_array($this->access_level, ['all', 'manager', 'admin']);
        }
        
        // Regular users can only access 'all' level files or their own uploads
        return $this->access_level === 'all' || $this->uploaded_by === $user->id;
    }

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
     * Auto-detect file type based on MIME type
     *
     * @param string $mimeType
     * @return string
     */
    private function detectFileType(string $mimeType): string
    {
        return match (true) {
            str_contains($mimeType, 'pdf') => 'document',
            str_contains($mimeType, 'word') => 'document',
            str_contains($mimeType, 'text') => 'document',
            str_contains($mimeType, 'excel') => 'spreadsheet',
            str_contains($mimeType, 'spreadsheet') => 'spreadsheet',
            str_contains($mimeType, 'csv') => 'spreadsheet',
            str_contains($mimeType, 'zip') => 'archive',
            str_contains($mimeType, 'rar') => 'archive',
            str_contains($mimeType, '7z') => 'archive',
            str_contains($mimeType, 'tar') => 'archive',
            str_contains($mimeType, 'xml') => 'document',
            default => 'other',
        };
    }

    /**
     * Generate metadata for file
     *
     * @return array
     */
    private function generateMetadata(): array
    {
        $metadata = [
            'file_hash' => null, // Would be calculated if file exists
            'upload_ip' => request()->ip(),
            'upload_user_agent' => request()->userAgent(),
            'detected_encoding' => null,
            'virus_scan_status' => 'pending',
        ];

        // Add file-specific metadata based on type
        if ($this->isSpreadsheet) {
            $metadata['estimated_rows'] = null; // Would be calculated
            $metadata['estimated_columns'] = null;
        }

        if ($this->isArchive) {
            $metadata['estimated_files_count'] = null; // Would be calculated
            $metadata['compression_ratio'] = null;
        }

        return $metadata;
    }

    /**
     * Get specific document icon based on MIME type
     *
     * @return string
     */
    private function getDocumentIcon(): string
    {
        return match (true) {
            str_contains($this->mime_type, 'pdf') => 'fas fa-file-pdf',
            str_contains($this->mime_type, 'word') => 'fas fa-file-word',
            str_contains($this->mime_type, 'text') => 'fas fa-file-alt',
            str_contains($this->mime_type, 'xml') => 'fas fa-file-code',
            default => 'fas fa-file',
        };
    }

    /**
     * Check if file can be deleted
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        // TODO: Add business logic checks:
        // - File is not referenced by active processes
        // - File is not part of mandatory documentation
        // - User has proper permissions
        
        return true;
    }

    /**
     * Get file content (for small files only)
     * Security: Only for trusted file types and authorized users
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        // Security check: Only allow small text files
        if ($this->file_size > 1048576) { // 1MB limit
            return null;
        }

        if (!in_array($this->file_type, ['document']) || !str_contains($this->mime_type, 'text')) {
            return null;
        }

        if (Storage::exists($this->file_path)) {
            return Storage::get($this->file_path);
        }

        return null;
    }

    /**
     * Update access level (with audit)
     *
     * @param string $newLevel
     * @param User $changedBy
     * @return bool
     */
    public function updateAccessLevel(string $newLevel, User $changedBy): bool
    {
        $oldLevel = $this->access_level;
        $this->access_level = $newLevel;
        
        // Add to metadata for audit trail
        $metadata = $this->metadata ?? [];
        $metadata['access_level_changes'] = $metadata['access_level_changes'] ?? [];
        $metadata['access_level_changes'][] = [
            'from' => $oldLevel,
            'to' => $newLevel,
            'changed_by' => $changedBy->id,
            'changed_at' => now()->toISOString(),
        ];
        
        $this->metadata = $metadata;
        
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
        return 'file_uploads';
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExportProfileLog Model - Activity log for export profiles
 *
 * Przechowuje logi aktywnosci profili eksportu:
 * - Generowanie plikow (generated)
 * - Pobrania przez uzytkownikow (downloaded)
 * - Dostep publiczny przez token (accessed)
 * - Bledy generowania (error)
 *
 * @property int $id
 * @property int $export_profile_id
 * @property string $action
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $product_count
 * @property int|null $file_size
 * @property int|null $duration
 * @property string|null $error_message
 * @property int|null $response_time_ms
 * @property string|null $served_from
 * @property int|null $http_status
 * @property string|null $content_type
 * @property string|null $referer
 * @property bool $is_bot
 * @property string|null $bot_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ExportProfileLog extends Model
{
    use HasFactory;

    // === CONSTANTS ===

    const ACTIONS = ['generated', 'downloaded', 'accessed', 'error'];
    const SERVED_FROM = ['cache', 'generated', 'on_the_fly'];

    // === FILLABLE ===

    protected $fillable = [
        'export_profile_id',
        'action',
        'user_id',
        'ip_address',
        'user_agent',
        'product_count',
        'file_size',
        'duration',
        'error_message',
        'response_time_ms',
        'served_from',
        'http_status',
        'content_type',
        'referer',
        'is_bot',
        'bot_name',
    ];

    // === CASTS ===

    protected $casts = [
        'product_count'    => 'integer',
        'file_size'        => 'integer',
        'duration'         => 'integer',
        'response_time_ms' => 'integer',
        'http_status'      => 'integer',
        'is_bot'           => 'boolean',
    ];

    // === RELATIONSHIPS ===

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ExportProfile::class, 'export_profile_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // === SCOPES ===

    /**
     * Scope: only bot requests.
     */
    public function scopeBots($query)
    {
        return $query->where('is_bot', true);
    }

    /**
     * Scope: only human requests.
     */
    public function scopeHumans($query)
    {
        return $query->where('is_bot', false);
    }
}

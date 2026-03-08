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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ExportProfileLog extends Model
{
    use HasFactory;

    // === CONSTANTS ===

    const ACTIONS = ['generated', 'downloaded', 'accessed', 'error'];

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
    ];

    // === CASTS ===

    protected $casts = [
        'product_count' => 'integer',
        'file_size'     => 'integer',
        'duration'      => 'integer',
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

/**
 * ETAP_04 FAZA A: PasswordHistory Model
 *
 * Stores hashed password history to prevent password reuse.
 *
 * @property int $id
 * @property int $user_id
 * @property string $password_hash
 * @property \Carbon\Carbon $created_at
 *
 * @property-read User $user
 */
class PasswordHistory extends Model
{
    protected $table = 'password_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'password_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password_hash',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user that owns this password history entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to a specific user's password history.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get recent password history entries.
     */
    public function scopeRecent(Builder $query, int $count): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($count);
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * Record a new password in history.
     */
    public static function recordPassword(User $user, string $hashedPassword): static
    {
        return static::create([
            'user_id' => $user->id,
            'password_hash' => $hashedPassword,
            'created_at' => now(),
        ]);
    }

    /**
     * Check if a password was used recently.
     *
     * @param User $user
     * @param string $plainPassword
     * @param int $count Number of recent passwords to check
     * @return bool True if password was used recently
     */
    public static function wasUsedRecently(User $user, string $plainPassword, int $count = 3): bool
    {
        $recentPasswords = static::forUser($user->id)
            ->recent($count)
            ->pluck('password_hash');

        foreach ($recentPasswords as $hash) {
            if (Hash::check($plainPassword, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cleanup old password history entries.
     *
     * @param User $user
     * @param int $keep Number of entries to keep
     */
    public static function cleanup(User $user, int $keep = 10): int
    {
        $idsToKeep = static::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->limit($keep)
            ->pluck('id');

        return static::forUser($user->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}

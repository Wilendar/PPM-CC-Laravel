<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * ETAP_04 FAZA A: PasswordPolicy Model
 *
 * Defines password requirements and expiration policies
 * that can be assigned to users or roles.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $min_length
 * @property int $max_length
 * @property bool $require_uppercase
 * @property bool $require_lowercase
 * @property bool $require_numbers
 * @property bool $require_symbols
 * @property int $expire_days
 * @property int $warning_days_before_expire
 * @property int $history_count
 * @property int $lockout_attempts
 * @property int $lockout_duration_minutes
 * @property bool $is_default
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<User> $users
 */
class PasswordPolicy extends Model
{
    protected $table = 'password_policies';

    protected $fillable = [
        'name',
        'description',
        'min_length',
        'max_length',
        'require_uppercase',
        'require_lowercase',
        'require_numbers',
        'require_symbols',
        'expire_days',
        'warning_days_before_expire',
        'history_count',
        'lockout_attempts',
        'lockout_duration_minutes',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'require_uppercase' => 'boolean',
        'require_lowercase' => 'boolean',
        'require_numbers' => 'boolean',
        'require_symbols' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get users with this password policy.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'password_policy_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to active policies only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to default policy.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * Get the default password policy.
     */
    public static function getDefault(): ?static
    {
        return static::active()->default()->first();
    }

    /**
     * Get effective policy for a user.
     */
    public static function getForUser(User $user): ?static
    {
        // User-specific policy takes precedence
        if ($user->password_policy_id) {
            return static::find($user->password_policy_id);
        }

        // Fall back to default policy
        return static::getDefault();
    }

    // ==========================================
    // VALIDATION METHODS
    // ==========================================

    /**
     * Validate a password against this policy.
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validatePassword(string $password): array
    {
        $errors = [];

        // Length check
        if (strlen($password) < $this->min_length) {
            $errors[] = "Haslo musi miec minimum {$this->min_length} znakow.";
        }

        if (strlen($password) > $this->max_length) {
            $errors[] = "Haslo moze miec maksimum {$this->max_length} znakow.";
        }

        // Uppercase check
        if ($this->require_uppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Haslo musi zawierac co najmniej jedna wielka litere.';
        }

        // Lowercase check
        if ($this->require_lowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Haslo musi zawierac co najmniej jedna mala litere.';
        }

        // Numbers check
        if ($this->require_numbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Haslo musi zawierac co najmniej jedna cyfre.';
        }

        // Symbols check
        if ($this->require_symbols && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Haslo musi zawierac co najmniej jeden znak specjalny.';
        }

        return $errors;
    }

    /**
     * Check if password is valid.
     */
    public function isPasswordValid(string $password): bool
    {
        return empty($this->validatePassword($password));
    }

    /**
     * Get policy requirements as human-readable list.
     */
    public function getRequirementsList(): array
    {
        $requirements = [];

        $requirements[] = "Minimum {$this->min_length} znakow";

        if ($this->max_length < 128) {
            $requirements[] = "Maksimum {$this->max_length} znakow";
        }

        if ($this->require_uppercase) {
            $requirements[] = 'Co najmniej jedna wielka litera (A-Z)';
        }

        if ($this->require_lowercase) {
            $requirements[] = 'Co najmniej jedna mala litera (a-z)';
        }

        if ($this->require_numbers) {
            $requirements[] = 'Co najmniej jedna cyfra (0-9)';
        }

        if ($this->require_symbols) {
            $requirements[] = 'Co najmniej jeden znak specjalny (!@#$%^&*...)';
        }

        if ($this->expire_days > 0) {
            $requirements[] = "Haslo wygasa po {$this->expire_days} dniach";
        }

        if ($this->history_count > 0) {
            $requirements[] = "Nie mozna uzyc {$this->history_count} poprzednich hasel";
        }

        return $requirements;
    }

    /**
     * Get password strength indicator for a given password.
     *
     * @return array ['score' => int 0-100, 'label' => string, 'color' => string]
     */
    public function getPasswordStrength(string $password): array
    {
        $score = 0;

        // Length score (up to 30 points)
        $lengthScore = min(30, strlen($password) * 2);
        $score += $lengthScore;

        // Character variety (up to 40 points)
        if (preg_match('/[a-z]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 10;
        }

        // Penalty for common patterns (up to -30 points)
        $commonPatterns = ['123456', 'password', 'qwerty', 'admin', '111111', 'abc123'];
        foreach ($commonPatterns as $pattern) {
            if (stripos($password, $pattern) !== false) {
                $score -= 30;
                break;
            }
        }

        // Normalize score
        $score = max(0, min(100, $score));

        // Determine label and color
        if ($score >= 80) {
            return ['score' => $score, 'label' => 'Bardzo silne', 'color' => 'green'];
        } elseif ($score >= 60) {
            return ['score' => $score, 'label' => 'Silne', 'color' => 'blue'];
        } elseif ($score >= 40) {
            return ['score' => $score, 'label' => 'Srednie', 'color' => 'yellow'];
        } elseif ($score >= 20) {
            return ['score' => $score, 'label' => 'Slabe', 'color' => 'orange'];
        }

        return ['score' => $score, 'label' => 'Bardzo slabe', 'color' => 'red'];
    }

    // ==========================================
    // INSTANCE METHODS
    // ==========================================

    /**
     * Set this policy as the default.
     */
    public function setAsDefault(): void
    {
        // Clear other defaults
        static::where('is_default', true)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }
}

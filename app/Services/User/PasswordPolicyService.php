<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\PasswordPolicy;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ETAP_04 FAZA A: Password Policy Service
 *
 * Handles password validation, expiration, and history management.
 */
class PasswordPolicyService
{
    /**
     * Validate a password against the user's policy.
     *
     * @return array Array of validation errors (empty if valid)
     */
    public function validatePassword(string $password, ?User $user = null): array
    {
        $policy = $this->getEffectivePolicy($user);

        if (!$policy) {
            // No policy - just check minimum length
            if (strlen($password) < 8) {
                return ['Haslo musi miec minimum 8 znakow.'];
            }
            return [];
        }

        $errors = $policy->validatePassword($password);

        // Check password history if user provided
        if ($user && $policy->history_count > 0) {
            if (PasswordHistory::wasUsedRecently($user, $password, $policy->history_count)) {
                $errors[] = "Nie mozesz uzyc jednego z {$policy->history_count} poprzednich hasel.";
            }
        }

        return $errors;
    }

    /**
     * Check if password is valid.
     */
    public function isPasswordValid(string $password, ?User $user = null): bool
    {
        return empty($this->validatePassword($password, $user));
    }

    /**
     * Require user to change password at next login.
     */
    public function requirePasswordChange(User $user): void
    {
        $user->update([
            'force_password_change' => true,
        ]);
    }

    /**
     * Change user's password.
     */
    public function changePassword(User $user, string $newPassword): void
    {
        // Validate against policy
        $errors = $this->validatePassword($newPassword, $user);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        // Record current password in history
        PasswordHistory::recordPassword($user, $user->password);

        // Clean up old history entries
        $policy = $this->getEffectivePolicy($user);
        $keepCount = $policy ? max($policy->history_count, 10) : 10;
        PasswordHistory::cleanup($user, $keepCount);

        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        // Terminate all other sessions (security measure)
        $sessionService = app(SessionManagementService::class);
        $sessionService->terminateAllUserSessions($user, session()->getId());
    }

    /**
     * Check if user's password is expired.
     */
    public function isPasswordExpired(User $user): bool
    {
        $policy = $this->getEffectivePolicy($user);

        if (!$policy || $policy->expire_days <= 0) {
            return false;
        }

        if (!$user->password_changed_at) {
            // If no password change date, assume expired
            return true;
        }

        return $user->password_changed_at->addDays($policy->expire_days)->isPast();
    }

    /**
     * Check if password will expire soon.
     *
     * @return int|null Days until expiration, or null if no expiration
     */
    public function getDaysUntilExpiration(User $user): ?int
    {
        $policy = $this->getEffectivePolicy($user);

        if (!$policy || $policy->expire_days <= 0) {
            return null;
        }

        if (!$user->password_changed_at) {
            return 0;
        }

        $expirationDate = $user->password_changed_at->addDays($policy->expire_days);
        $daysLeft = now()->diffInDays($expirationDate, false);

        return max(0, (int) $daysLeft);
    }

    /**
     * Check if user should see password expiration warning.
     */
    public function shouldShowExpirationWarning(User $user): bool
    {
        $policy = $this->getEffectivePolicy($user);

        if (!$policy || $policy->expire_days <= 0) {
            return false;
        }

        $daysLeft = $this->getDaysUntilExpiration($user);

        return $daysLeft !== null && $daysLeft <= $policy->warning_days_before_expire;
    }

    /**
     * Reset password and return temporary password.
     */
    public function resetPassword(User $user): string
    {
        $tempPassword = Str::random(12);

        // Record current password in history
        if ($user->password) {
            PasswordHistory::recordPassword($user, $user->password);
        }

        $user->update([
            'password' => Hash::make($tempPassword),
            'password_changed_at' => null, // Force expiration check
            'force_password_change' => true,
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        return $tempPassword;
    }

    /**
     * Handle failed login attempt.
     *
     * @return bool True if account is now locked
     */
    public function handleFailedLogin(User $user): bool
    {
        $policy = $this->getEffectivePolicy($user);
        $lockoutAttempts = $policy ? $policy->lockout_attempts : 5;
        $lockoutDuration = $policy ? $policy->lockout_duration_minutes : 30;

        $attempts = $user->failed_login_attempts + 1;

        $updates = [
            'failed_login_attempts' => $attempts,
        ];

        // Check if should be locked
        if ($attempts >= $lockoutAttempts) {
            $updates['locked_until'] = now()->addMinutes($lockoutDuration);
        }

        $user->update($updates);

        return isset($updates['locked_until']);
    }

    /**
     * Check if user account is locked.
     */
    public function isAccountLocked(User $user): bool
    {
        if (!$user->locked_until) {
            return false;
        }

        if ($user->locked_until->isPast()) {
            // Lock expired, reset
            $user->update([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get remaining lockout minutes.
     */
    public function getLockoutMinutesRemaining(User $user): int
    {
        if (!$user->locked_until || $user->locked_until->isPast()) {
            return 0;
        }

        return (int) now()->diffInMinutes($user->locked_until);
    }

    /**
     * Unlock user account.
     */
    public function unlockAccount(User $user): void
    {
        $user->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);
    }

    /**
     * Clear failed login attempts on successful login.
     */
    public function clearFailedAttempts(User $user): void
    {
        if ($user->failed_login_attempts > 0) {
            $user->update([
                'failed_login_attempts' => 0,
            ]);
        }
    }

    /**
     * Get password requirements for display.
     */
    public function getRequirements(?User $user = null): array
    {
        $policy = $this->getEffectivePolicy($user);

        if (!$policy) {
            return ['Minimum 8 znakow'];
        }

        return $policy->getRequirementsList();
    }

    /**
     * Get password strength indicator.
     */
    public function getPasswordStrength(string $password, ?User $user = null): array
    {
        $policy = $this->getEffectivePolicy($user);

        if (!$policy) {
            // Basic strength calculation
            $score = min(100, strlen($password) * 5);
            return [
                'score' => $score,
                'label' => $score >= 60 ? 'Akceptowalne' : 'Slabe',
                'color' => $score >= 60 ? 'green' : 'red',
            ];
        }

        return $policy->getPasswordStrength($password);
    }

    /**
     * Get the effective password policy for a user.
     */
    public function getEffectivePolicy(?User $user = null): ?PasswordPolicy
    {
        if ($user) {
            return PasswordPolicy::getForUser($user);
        }

        return PasswordPolicy::getDefault();
    }

    /**
     * Assign a password policy to a user.
     */
    public function assignPolicy(User $user, PasswordPolicy $policy): void
    {
        $user->update([
            'password_policy_id' => $policy->id,
        ]);
    }

    /**
     * Remove password policy from user (use default).
     */
    public function removePolicy(User $user): void
    {
        $user->update([
            'password_policy_id' => null,
        ]);
    }

    /**
     * Get users with expiring passwords.
     *
     * @param int $daysThreshold Number of days to check
     */
    public function getUsersWithExpiringPasswords(int $daysThreshold = 7): \Illuminate\Support\Collection
    {
        $users = User::whereNotNull('password_changed_at')
            ->whereNotNull('password_policy_id')
            ->with('passwordPolicy')
            ->get();

        return $users->filter(function ($user) use ($daysThreshold) {
            $daysLeft = $this->getDaysUntilExpiration($user);
            return $daysLeft !== null && $daysLeft <= $daysThreshold && $daysLeft > 0;
        });
    }

    /**
     * Get users with expired passwords.
     */
    public function getUsersWithExpiredPasswords(): \Illuminate\Support\Collection
    {
        return User::where('is_active', true)
            ->get()
            ->filter(function ($user) {
                return $this->isPasswordExpired($user);
            });
    }
}

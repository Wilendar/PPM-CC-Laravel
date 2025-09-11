<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\User;
use App\Jobs\SendNotificationJob;
use App\Events\NotificationCreated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a new admin notification
     */
    public function create(
        string $title,
        string $message,
        string $type = AdminNotification::TYPE_SYSTEM,
        string $priority = AdminNotification::PRIORITY_NORMAL,
        string $channel = AdminNotification::CHANNEL_WEB,
        ?array $recipients = null,
        ?object $relatedModel = null,
        ?array $metadata = null,
        ?User $createdBy = null
    ): AdminNotification {
        $notification = AdminNotification::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
            'channel' => $channel,
            'recipients' => $recipients,
            'related_type' => $relatedModel ? get_class($relatedModel) : null,
            'related_id' => $relatedModel?->id,
            'metadata' => $metadata,
            'created_by' => $createdBy?->id,
        ]);

        // Dispatch notification event for real-time updates
        event(new NotificationCreated($notification));

        // Queue email sending if needed
        if ($notification->shouldSendEmail()) {
            SendNotificationJob::dispatch($notification);
        }

        Log::info('Admin notification created', [
            'notification_id' => $notification->id,
            'type' => $type,
            'priority' => $priority,
        ]);

        return $notification;
    }

    /**
     * Create system error notification
     */
    public function systemError(string $title, string $message, ?object $relatedModel = null, ?array $metadata = null): AdminNotification
    {
        return $this->create(
            title: $title,
            message: $message,
            type: AdminNotification::TYPE_SYSTEM,
            priority: AdminNotification::PRIORITY_HIGH,
            channel: AdminNotification::CHANNEL_BOTH,
            recipients: $this->getAdminRecipients(),
            relatedModel: $relatedModel,
            metadata: $metadata
        );
    }

    /**
     * Create security alert notification
     */
    public function securityAlert(string $title, string $message, ?object $relatedModel = null, ?array $metadata = null): AdminNotification
    {
        return $this->create(
            title: $title,
            message: $message,
            type: AdminNotification::TYPE_SECURITY,
            priority: AdminNotification::PRIORITY_CRITICAL,
            channel: AdminNotification::CHANNEL_BOTH,
            recipients: $this->getSecurityRecipients(),
            relatedModel: $relatedModel,
            metadata: $metadata
        );
    }

    /**
     * Create integration failure notification
     */
    public function integrationFailure(string $integration, string $error, ?object $relatedModel = null): AdminNotification
    {
        return $this->create(
            title: "Błąd integracji: {$integration}",
            message: $error,
            type: AdminNotification::TYPE_INTEGRATION,
            priority: AdminNotification::PRIORITY_HIGH,
            channel: AdminNotification::CHANNEL_BOTH,
            recipients: $this->getIntegrationRecipients(),
            relatedModel: $relatedModel,
            metadata: [
                'integration' => $integration,
                'error_type' => 'connection_failure',
            ]
        );
    }

    /**
     * Create user activity notification
     */
    public function userActivity(string $title, string $message, User $user, ?object $relatedModel = null): AdminNotification
    {
        return $this->create(
            title: $title,
            message: $message,
            type: AdminNotification::TYPE_USER,
            priority: AdminNotification::PRIORITY_NORMAL,
            channel: AdminNotification::CHANNEL_WEB,
            recipients: $this->getAdminRecipients(),
            relatedModel: $relatedModel,
            metadata: [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
            ],
            createdBy: $user
        );
    }

    /**
     * Create maintenance reminder notification
     */
    public function maintenanceReminder(string $task, \DateTime $dueDate): AdminNotification
    {
        return $this->create(
            title: "Przypomnienie konserwacyjne: {$task}",
            message: "Zadanie konserwacyjne '{$task}' jest zaplanowane na {$dueDate->format('Y-m-d H:i')}",
            type: AdminNotification::TYPE_SYSTEM,
            priority: AdminNotification::PRIORITY_NORMAL,
            channel: AdminNotification::CHANNEL_BOTH,
            recipients: $this->getMaintenanceRecipients(),
            metadata: [
                'task' => $task,
                'due_date' => $dueDate->format('Y-m-d H:i:s'),
                'type' => 'maintenance_reminder',
            ]
        );
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        $notification = AdminNotification::find($notificationId);
        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(array $notificationIds): int
    {
        return AdminNotification::whereIn('id', $notificationIds)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Mark all notifications as read for a specific type
     */
    public function markAllAsReadByType(string $type): int
    {
        return AdminNotification::where('type', $type)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Acknowledge notification
     */
    public function acknowledge(int $notificationId, User $user): bool
    {
        $notification = AdminNotification::find($notificationId);
        if (!$notification) {
            return false;
        }

        $notification->acknowledge($user);
        return true;
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(): int
    {
        return AdminNotification::unread()->count();
    }

    /**
     * Get unread notifications by type
     */
    public function getUnreadByType(string $type): Collection
    {
        return AdminNotification::unread()
            ->byType($type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get critical notifications
     */
    public function getCriticalNotifications(): Collection
    {
        return AdminNotification::critical()
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent notifications
     */
    public function getRecent(int $limit = 10): Collection
    {
        return AdminNotification::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Cleanup old notifications
     */
    public function cleanup(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return AdminNotification::where('created_at', '<', $cutoffDate)
            ->where('is_read', true)
            ->where('priority', '!=', AdminNotification::PRIORITY_CRITICAL)
            ->delete();
    }

    /**
     * Get admin recipients
     */
    protected function getAdminRecipients(): array
    {
        return User::whereIn('role', ['admin', 'manager'])
            ->pluck('email')
            ->toArray();
    }

    /**
     * Get security recipients
     */
    protected function getSecurityRecipients(): array
    {
        return User::where('role', 'admin')
            ->pluck('email')
            ->toArray();
    }

    /**
     * Get integration recipients
     */
    protected function getIntegrationRecipients(): array
    {
        return User::whereIn('role', ['admin', 'manager'])
            ->pluck('email')
            ->toArray();
    }

    /**
     * Get maintenance recipients
     */
    protected function getMaintenanceRecipients(): array
    {
        return User::whereIn('role', ['admin', 'manager', 'warehouseman'])
            ->pluck('email')
            ->toArray();
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => AdminNotification::count(),
            'unread' => AdminNotification::unread()->count(),
            'critical' => AdminNotification::critical()->unread()->count(),
            'by_type' => AdminNotification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_priority' => AdminNotification::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
            'recent_activity' => AdminNotification::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }
}
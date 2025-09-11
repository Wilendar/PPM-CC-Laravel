<?php

namespace App\Http\Livewire\Admin\Notifications;

use App\Models\AdminNotification;
use App\Services\NotificationService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

class NotificationCenter extends Component
{
    use WithPagination;

    public $showDropdown = false;
    public $activeTab = 'all';
    public $selectedType = '';
    public $selectedPriority = '';
    public $showOnlyUnread = false;
    
    // Real-time notification data
    public $unreadCount = 0;
    public $recentNotifications;
    public $criticalNotifications;

    protected $listeners = [
        'notificationCreated' => 'handleNewNotification',
        'refreshNotifications' => 'loadNotifications',
        'echo-private:admin-notifications,notification.created' => 'handleRealtimeNotification',
    ];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function render()
    {
        $notifications = $this->getFilteredNotifications();
        
        return view('livewire.admin.notifications.notification-center', [
            'notifications' => $notifications,
            'statistics' => $this->getStatistics(),
        ]);
    }

    /**
     * Load notification data
     */
    public function loadNotifications()
    {
        $notificationService = app(NotificationService::class);
        
        $this->unreadCount = $notificationService->getUnreadCount();
        $this->recentNotifications = $notificationService->getRecent(5);
        $this->criticalNotifications = $notificationService->getCriticalNotifications();
    }

    /**
     * Get filtered notifications for main list
     */
    protected function getFilteredNotifications()
    {
        $query = AdminNotification::query()
            ->with(['creator', 'acknowledger'])
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($this->showOnlyUnread) {
            $query->unread();
        }

        // Filter by type
        if ($this->selectedType) {
            $query->where('type', $this->selectedType);
        }

        // Filter by priority
        if ($this->selectedPriority) {
            $query->where('priority', $this->selectedPriority);
        }

        // Tab filtering
        switch ($this->activeTab) {
            case 'critical':
                $query->where('priority', AdminNotification::PRIORITY_CRITICAL);
                break;
            case 'unread':
                $query->unread();
                break;
            case 'system':
                $query->where('type', AdminNotification::TYPE_SYSTEM);
                break;
            case 'security':
                $query->where('type', AdminNotification::TYPE_SECURITY);
                break;
            case 'integration':
                $query->where('type', AdminNotification::TYPE_INTEGRATION);
                break;
        }

        return $query->paginate(15);
    }

    /**
     * Toggle dropdown visibility
     */
    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
        
        if ($this->showDropdown) {
            $this->loadNotifications();
        }
    }

    /**
     * Set active tab
     */
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        $notificationService = app(NotificationService::class);
        
        if ($notificationService->markAsRead($notificationId)) {
            $this->loadNotifications();
            $this->emit('notificationRead', $notificationId);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        AdminNotification::where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $this->loadNotifications();
        $this->emit('allNotificationsRead');
    }

    /**
     * Mark all in current tab as read
     */
    public function markTabAsRead()
    {
        $notificationService = app(NotificationService::class);
        
        switch ($this->activeTab) {
            case 'system':
                $notificationService->markAllAsReadByType(AdminNotification::TYPE_SYSTEM);
                break;
            case 'security':
                $notificationService->markAllAsReadByType(AdminNotification::TYPE_SECURITY);
                break;
            case 'integration':
                $notificationService->markAllAsReadByType(AdminNotification::TYPE_INTEGRATION);
                break;
            case 'user':
                $notificationService->markAllAsReadByType(AdminNotification::TYPE_USER);
                break;
        }

        $this->loadNotifications();
    }

    /**
     * Acknowledge notification (for critical notifications)
     */
    public function acknowledge($notificationId)
    {
        $notificationService = app(NotificationService::class);
        
        if ($notificationService->acknowledge($notificationId, auth()->user())) {
            $this->loadNotifications();
            $this->emit('notificationAcknowledged', $notificationId);
        }
    }

    /**
     * Handle real-time notification
     */
    public function handleRealtimeNotification($data)
    {
        $this->loadNotifications();
        
        // Show browser notification for critical alerts
        if ($data['priority'] === AdminNotification::PRIORITY_CRITICAL) {
            $this->emit('showBrowserNotification', [
                'title' => $data['title'],
                'body' => $data['message'],
                'icon' => '/favicon.ico',
            ]);
        }
        
        // Show toast notification
        $this->emit('showToast', [
            'type' => $this->getPriorityToastType($data['priority']),
            'title' => $data['title'],
            'message' => $data['message'],
        ]);
    }

    /**
     * Handle new notification event
     */
    public function handleNewNotification($notification)
    {
        $this->loadNotifications();
    }

    /**
     * Get notification statistics
     */
    protected function getStatistics(): array
    {
        $notificationService = app(NotificationService::class);
        return $notificationService->getStatistics();
    }

    /**
     * Get toast type based on priority
     */
    protected function getPriorityToastType(string $priority): string
    {
        return match ($priority) {
            AdminNotification::PRIORITY_CRITICAL => 'error',
            AdminNotification::PRIORITY_HIGH => 'warning',
            AdminNotification::PRIORITY_NORMAL => 'info',
            AdminNotification::PRIORITY_LOW => 'success',
        };
    }

    /**
     * Filter by type
     */
    public function filterByType($type)
    {
        $this->selectedType = $type === $this->selectedType ? '' : $type;
        $this->resetPage();
    }

    /**
     * Filter by priority
     */
    public function filterByPriority($priority)
    {
        $this->selectedPriority = $priority === $this->selectedPriority ? '' : $priority;
        $this->resetPage();
    }

    /**
     * Toggle unread filter
     */
    public function toggleUnreadFilter()
    {
        $this->showOnlyUnread = !$this->showOnlyUnread;
        $this->resetPage();
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->selectedType = '';
        $this->selectedPriority = '';
        $this->showOnlyUnread = false;
        $this->resetPage();
    }

    /**
     * Get available notification types
     */
    public function getNotificationTypes(): array
    {
        return [
            AdminNotification::TYPE_SYSTEM => 'System',
            AdminNotification::TYPE_SECURITY => 'Bezpieczeństwo',
            AdminNotification::TYPE_INTEGRATION => 'Integracje',
            AdminNotification::TYPE_USER => 'Użytkownicy',
        ];
    }

    /**
     * Get available priorities
     */
    public function getPriorities(): array
    {
        return [
            AdminNotification::PRIORITY_CRITICAL => 'Krytyczny',
            AdminNotification::PRIORITY_HIGH => 'Wysoki',
            AdminNotification::PRIORITY_NORMAL => 'Normalny',
            AdminNotification::PRIORITY_LOW => 'Niski',
        ];
    }
}
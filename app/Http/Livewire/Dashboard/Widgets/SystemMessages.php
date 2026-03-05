<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\AdminNotification;

class SystemMessages extends Component
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $messages;

    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        $this->messages = AdminNotification::where('channel', '!=', AdminNotification::CHANNEL_EMAIL)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $this->unreadCount = AdminNotification::where('channel', '!=', AdminNotification::CHANNEL_EMAIL)
            ->unread()
            ->count();
    }

    public function markAsRead(int $id): void
    {
        $notification = AdminNotification::find($id);

        if ($notification) {
            $notification->markAsRead();
            $this->loadMessages();
        }
    }

    protected function getPriorityConfig(string $priority): array
    {
        return match ($priority) {
            AdminNotification::PRIORITY_CRITICAL => [
                'dot' => 'bg-red-500',
                'bg' => 'bg-red-500/10',
                'text' => 'text-red-400',
                'icon' => 'fas fa-exclamation-triangle',
            ],
            AdminNotification::PRIORITY_HIGH => [
                'dot' => 'bg-orange-500',
                'bg' => 'bg-orange-500/10',
                'text' => 'text-orange-400',
                'icon' => 'fas fa-exclamation-circle',
            ],
            AdminNotification::PRIORITY_NORMAL => [
                'dot' => 'bg-blue-500',
                'bg' => 'bg-blue-500/10',
                'text' => 'text-blue-400',
                'icon' => 'fas fa-info-circle',
            ],
            default => [
                'dot' => 'bg-gray-500',
                'bg' => 'bg-gray-500/10',
                'text' => 'text-gray-400',
                'icon' => 'fas fa-bell',
            ],
        };
    }

    public function render()
    {
        $messagesWithConfig = $this->messages->map(function ($msg) {
            $msg->priorityConfig = $this->getPriorityConfig($msg->priority);
            return $msg;
        });

        return view('livewire.dashboard.widgets.system-messages', [
            'messagesWithConfig' => $messagesWithConfig,
        ]);
    }
}

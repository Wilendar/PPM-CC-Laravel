<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\AdminNotification;
use Illuminate\Support\Str;

class SystemMessages extends Component
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $notifications;

    public int $unreadCount = 0;

    public bool $isAdmin = false;
    public bool $showEditor = false;
    public ?int $editingId = null;
    public string $editTitle = '';
    public string $editMessage = '';
    public string $editPriority = 'normal';

    protected $rules = [
        'editTitle' => 'required|string|max:255',
        'editMessage' => 'nullable|string|max:5000',
        'editPriority' => 'required|in:low,normal,high,critical',
    ];

    public function mount(): void
    {
        $this->loadMessages();
        $this->isAdmin = auth()->user()?->hasRole('Admin') ?? false;
    }

    public function loadMessages(): void
    {
        $this->notifications = AdminNotification::where('channel', '!=', AdminNotification::CHANNEL_EMAIL)
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

    public function createMessage(): void
    {
        $this->resetEditor();
        $this->showEditor = true;
    }

    public function startEdit(int $id): void
    {
        $msg = AdminNotification::find($id);
        if (!$msg) return;

        $this->editingId = $msg->id;
        $this->editTitle = $msg->title;
        $this->editMessage = $msg->message ?? '';
        $this->editPriority = $msg->priority;
        $this->showEditor = true;
    }

    public function saveMessage(): void
    {
        $this->validate();

        $data = [
            'title' => $this->editTitle,
            'message' => $this->editMessage,
            'priority' => $this->editPriority,
            'channel' => AdminNotification::CHANNEL_WEB,
            'type' => AdminNotification::TYPE_SYSTEM,
        ];

        if ($this->editingId) {
            AdminNotification::where('id', $this->editingId)->update($data);
        } else {
            $data['created_by'] = auth()->id();
            AdminNotification::create($data);
        }

        $this->cancelEdit();
        $this->loadMessages();
    }

    public function cancelEdit(): void
    {
        $this->resetEditor();
    }

    public function deleteMessage(int $id): void
    {
        AdminNotification::where('id', $id)->delete();
        $this->loadMessages();
    }

    protected function resetEditor(): void
    {
        $this->showEditor = false;
        $this->editingId = null;
        $this->editTitle = '';
        $this->editMessage = '';
        $this->editPriority = 'normal';
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
        $messagesWithConfig = $this->notifications->map(function ($msg) {
            $msg->priorityConfig = $this->getPriorityConfig($msg->priority);
            $msg->renderedMessage = $msg->message ? Str::markdown($msg->message, ['renderer' => ['soft_break' => "<br>\n"]]) : '';
            return $msg;
        });

        return view('livewire.dashboard.widgets.system-messages', [
            'messagesWithConfig' => $messagesWithConfig,
        ]);
    }
}

<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\BugReport;
use Illuminate\Support\Facades\Auth;

class MyBugReports extends Component
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $reports;

    public int $totalCount = 0;
    public int $openCount = 0;
    public array $statusCounts = [];

    public function mount(): void
    {
        $this->loadReports();
    }

    public function loadReports(): void
    {
        $userId = Auth::id();

        $this->reports = BugReport::reportedBy($userId)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $statusCounts = BugReport::reportedBy($userId)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $this->totalCount = array_sum($statusCounts);

        $closedStatuses = [BugReport::STATUS_CLOSED, BugReport::STATUS_REJECTED];
        $this->openCount = $this->totalCount - collect($closedStatuses)->sum(fn ($s) => $statusCounts[$s] ?? 0);

        $this->statusCounts = [
            'new' => $statusCounts[BugReport::STATUS_NEW] ?? 0,
            'in_progress' => $statusCounts[BugReport::STATUS_IN_PROGRESS] ?? 0,
            'resolved' => $statusCounts[BugReport::STATUS_RESOLVED] ?? 0,
            'closed' => $statusCounts[BugReport::STATUS_CLOSED] ?? 0,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.my-bug-reports');
    }
}

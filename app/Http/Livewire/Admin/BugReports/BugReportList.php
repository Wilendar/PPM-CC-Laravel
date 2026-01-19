<?php

namespace App\Http\Livewire\Admin\BugReports;

use App\Models\BugReport;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * BugReportList Component (Admin)
 *
 * Admin panel for managing all bug reports.
 * Features: filtering, bulk actions, statistics, quick preview.
 *
 * @package App\Http\Livewire\Admin\BugReports
 */
#[Layout('layouts.admin')]
#[Title('Zgloszenia - Panel Admina')]
class BugReportList extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    // Filters
    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';
    public string $severityFilter = '';
    public ?int $assignedToFilter = null;

    // Bulk actions
    public array $selectedReports = [];
    public bool $selectAll = false;

    // Quick preview
    public ?int $previewReportId = null;

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        // Skip auth check if user not logged in (dev mode)
        if (Auth::check()) {
            Gate::authorize('viewAny', BugReport::class);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAssignedToFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedReports = $this->getFilteredReportsQuery()
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedReports = [];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Quick preview a report
     */
    public function preview(int $reportId): void
    {
        $this->previewReportId = $reportId;
    }

    /**
     * Close preview
     */
    public function closePreview(): void
    {
        $this->previewReportId = null;
    }

    /**
     * Navigate to report detail
     */
    public function viewReport(int $reportId): void
    {
        $this->redirect(route('admin.bug-reports.show', $reportId), navigate: true);
    }

    /**
     * Bulk change status
     */
    public function bulkChangeStatus(string $status): void
    {
        Gate::authorize('manage', BugReport::class);

        $count = BugReport::whereIn('id', $this->selectedReports)
            ->update(['status' => $status]);

        $this->selectedReports = [];
        $this->selectAll = false;

        $this->dispatch('success', message: "Status zmieniony dla {$count} zgloszen.");
    }

    /**
     * Bulk assign to user
     */
    public function bulkAssign(?int $userId): void
    {
        Gate::authorize('manage', BugReport::class);

        $count = BugReport::whereIn('id', $this->selectedReports)
            ->update(['assigned_to' => $userId]);

        $this->selectedReports = [];
        $this->selectAll = false;

        $userName = $userId ? User::find($userId)?->name : 'nikogo';
        $this->dispatch('success', message: "Przypisano do {$userName} ({$count} zgloszen).");
    }

    /**
     * Quick status change
     */
    public function quickStatusChange(int $reportId, string $status): void
    {
        $report = BugReport::findOrFail($reportId);
        Gate::authorize('manage', $report);

        $report->update(['status' => $status]);

        $this->dispatch('success', message: 'Status zmieniony.');
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->severityFilter = '';
        $this->assignedToFilter = null;
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get statistics
     */
    public function getStatsProperty(): array
    {
        return [
            'total' => BugReport::count(),
            'new' => BugReport::byStatus(BugReport::STATUS_NEW)->count(),
            'in_progress' => BugReport::byStatus(BugReport::STATUS_IN_PROGRESS)->count(),
            'waiting' => BugReport::byStatus(BugReport::STATUS_WAITING)->count(),
            'resolved' => BugReport::byStatus(BugReport::STATUS_RESOLVED)->count(),
            'unassigned' => BugReport::whereNull('assigned_to')->unresolved()->count(),
            'critical' => BugReport::bySeverity(BugReport::SEVERITY_CRITICAL)->unresolved()->count(),
        ];
    }

    /**
     * Get preview report
     */
    public function getPreviewReportProperty(): ?BugReport
    {
        if (!$this->previewReportId) {
            return null;
        }

        return BugReport::with(['reporter', 'assignee'])->find($this->previewReportId);
    }

    /**
     * Get admins/managers for assignment
     */
    public function getAssigneesProperty(): \Illuminate\Support\Collection
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Admin', 'Manager']);
        })->orderBy('name')->get(['id', 'name']);
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Build filtered query
     */
    private function getFilteredReportsQuery()
    {
        $query = BugReport::with(['reporter', 'assignee']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
                  ->orWhere('id', $this->search);
            });
        }

        if ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->byType($this->typeFilter);
        }

        if ($this->severityFilter) {
            $query->bySeverity($this->severityFilter);
        }

        if ($this->assignedToFilter !== null) {
            if ($this->assignedToFilter === 0) {
                $query->whereNull('assigned_to');
            } else {
                $query->assignedTo($this->assignedToFilter);
            }
        }

        return $query->orderByRaw("
            CASE status
                WHEN 'new' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'waiting' THEN 3
                WHEN 'resolved' THEN 4
                WHEN 'closed' THEN 5
                WHEN 'rejected' THEN 6
                ELSE 7
            END
        ")->orderByRaw("
            CASE severity
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END
        ")->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.bug-reports.bug-report-list', [
            'reports' => $this->getFilteredReportsQuery()->paginate(15),
            'types' => BugReport::getTypes(),
            'statuses' => BugReport::getStatuses(),
            'severities' => BugReport::getSeverities(),
        ]);
    }
}

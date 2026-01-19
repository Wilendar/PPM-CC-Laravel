<?php

namespace App\Http\Livewire\BugReports;

use App\Models\BugReport;
use App\Models\BugReportComment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UserBugReports Component
 *
 * User's personal bug report history page.
 * Shows reports submitted by the current user with status filtering.
 *
 * @package App\Http\Livewire\BugReports
 */
#[Layout('layouts.admin')]
#[Title('Moje Zgloszenia')]
class UserBugReports extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    public string $statusFilter = '';
    public string $typeFilter = '';
    public ?int $selectedReportId = null;
    public string $newComment = '';

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            $this->redirect(route('login'));
        }
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * View report details
     */
    public function viewReport(int $reportId): void
    {
        $report = BugReport::where('id', $reportId)
            ->where('reporter_id', Auth::id())
            ->first();

        if ($report) {
            $this->selectedReportId = $reportId;
        }
    }

    /**
     * Close details panel
     */
    public function closeDetails(): void
    {
        $this->selectedReportId = null;
        $this->newComment = '';
    }

    /**
     * Add a comment to selected report
     */
    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|min:3|max:2000',
        ]);

        $report = BugReport::where('id', $this->selectedReportId)
            ->where('reporter_id', Auth::id())
            ->first();

        if (!$report || !$report->isOpen()) {
            $this->dispatch('error', message: 'Nie mozesz dodac komentarza do tego zgloszenia.');
            return;
        }

        BugReportComment::create([
            'bug_report_id' => $report->id,
            'user_id' => Auth::id(),
            'content' => $this->newComment,
            'is_internal' => false,
        ]);

        $this->newComment = '';
        $this->dispatch('success', message: 'Komentarz dodany.');
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get selected report with comments
     */
    public function getSelectedReportProperty(): ?BugReport
    {
        if (!$this->selectedReportId) {
            return null;
        }

        return BugReport::with(['publicComments.user'])
            ->where('id', $this->selectedReportId)
            ->where('reporter_id', Auth::id())
            ->first();
    }

    /**
     * Get counts by status
     */
    public function getStatusCountsProperty(): array
    {
        $userId = Auth::id();

        return [
            'all' => BugReport::where('reporter_id', $userId)->count(),
            'new' => BugReport::where('reporter_id', $userId)->byStatus(BugReport::STATUS_NEW)->count(),
            'in_progress' => BugReport::where('reporter_id', $userId)->byStatus(BugReport::STATUS_IN_PROGRESS)->count(),
            'resolved' => BugReport::where('reporter_id', $userId)->byStatus(BugReport::STATUS_RESOLVED)->count(),
            'closed' => BugReport::where('reporter_id', $userId)->byStatus(BugReport::STATUS_CLOSED)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        $query = BugReport::where('reporter_id', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->byType($this->typeFilter);
        }

        return view('livewire.bug-reports.user-bug-reports', [
            'reports' => $query->paginate(10),
            'types' => BugReport::getTypes(),
            'statuses' => BugReport::getStatuses(),
        ]);
    }
}

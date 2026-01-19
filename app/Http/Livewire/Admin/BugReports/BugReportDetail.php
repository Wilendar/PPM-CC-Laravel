<?php

namespace App\Http\Livewire\Admin\BugReports;

use App\Models\BugReport;
use App\Models\BugReportComment;
use App\Models\User;
use App\Services\BugReportNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * BugReportDetail Component (Admin)
 *
 * Detailed view and management of a single bug report.
 * Features: status changes, assignment, comments (public/internal), resolution.
 *
 * @package App\Http\Livewire\Admin\BugReports
 */
#[Layout('layouts.admin')]
class BugReportDetail extends Component
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    public BugReport $report;

    // Status change
    public string $newStatus = '';

    // Assignment
    public ?int $assignedTo = null;

    // Comments
    public string $newComment = '';
    public bool $isInternalComment = false;

    // Resolution
    public string $resolution = '';
    public bool $showResolutionForm = false;

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(BugReport $report): void
    {
        // Skip auth check if user not logged in (dev mode)
        if (Auth::check()) {
            Gate::authorize('manage', $report);
        }

        $this->report = $report->load(['reporter', 'assignee', 'comments.user']);
        $this->newStatus = $report->status;
        $this->assignedTo = $report->assigned_to;
        $this->resolution = $report->resolution ?? '';
    }

    public function getTitle(): string
    {
        return "Zgloszenie #{$this->report->id} - Panel Admina";
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Update report status
     */
    public function updateStatus(): void
    {
        Gate::authorize('changeStatus', $this->report);

        $oldStatus = $this->report->status;

        $this->report->update(['status' => $this->newStatus]);

        // Send notification to reporter about status change
        try {
            app(BugReportNotificationService::class)->notifyStatusChanged($this->report, $oldStatus);
        } catch (\Exception $e) {
            Log::warning('Failed to send status change notification', ['error' => $e->getMessage()]);
        }

        // Dispatch event for notifications
        $this->dispatch('bug-report-status-changed', [
            'reportId' => $this->report->id,
            'oldStatus' => $oldStatus,
            'newStatus' => $this->newStatus,
        ]);

        $this->dispatch('success', message: 'Status zmieniony.');
    }

    /**
     * Update assignment
     */
    public function updateAssignment(): void
    {
        Gate::authorize('assign', $this->report);

        $this->report->update([
            'assigned_to' => $this->assignedTo ?: null,
        ]);

        // If assigning and status is 'new', auto-change to 'in_progress'
        if ($this->assignedTo && $this->report->status === BugReport::STATUS_NEW) {
            $this->report->update(['status' => BugReport::STATUS_IN_PROGRESS]);
            $this->newStatus = BugReport::STATUS_IN_PROGRESS;
        }

        $this->report->refresh();
        $this->dispatch('success', message: 'Przypisanie zaktualizowane.');
    }

    /**
     * Add comment
     */
    public function addComment(): void
    {
        $this->validate([
            'newComment' => 'required|string|min:3|max:5000',
        ]);

        if ($this->isInternalComment) {
            Gate::authorize('addInternalComment', $this->report);
        }

        BugReportComment::create([
            'bug_report_id' => $this->report->id,
            'user_id' => Auth::id(),
            'content' => $this->newComment,
            'is_internal' => $this->isInternalComment,
        ]);

        $this->newComment = '';
        $this->isInternalComment = false;
        $this->report->refresh();

        $this->dispatch('success', message: 'Komentarz dodany.');
    }

    /**
     * Mark as resolved
     */
    public function markResolved(): void
    {
        $this->validate([
            'resolution' => 'required|string|min:10|max:5000',
        ]);

        Gate::authorize('resolve', $this->report);

        $this->report->markResolved($this->resolution);
        $this->newStatus = BugReport::STATUS_RESOLVED;
        $this->showResolutionForm = false;

        // Send notification to reporter
        try {
            app(BugReportNotificationService::class)->notifyResolved($this->report);
        } catch (\Exception $e) {
            Log::warning('Failed to send resolution notification', ['error' => $e->getMessage()]);
        }

        // Dispatch for notifications
        $this->dispatch('bug-report-resolved', ['reportId' => $this->report->id]);

        $this->dispatch('success', message: 'Zgloszenie oznaczone jako rozwiazane.');
    }

    /**
     * Mark as rejected
     */
    public function markRejected(): void
    {
        $this->validate([
            'resolution' => 'required|string|min:10|max:5000',
        ]);

        Gate::authorize('reject', $this->report);

        $this->report->markRejected($this->resolution);
        $this->newStatus = BugReport::STATUS_REJECTED;
        $this->showResolutionForm = false;

        // Send notification to reporter
        try {
            app(BugReportNotificationService::class)->notifyResolved($this->report);
        } catch (\Exception $e) {
            Log::warning('Failed to send rejection notification', ['error' => $e->getMessage()]);
        }

        $this->dispatch('success', message: 'Zgloszenie odrzucone.');
    }

    /**
     * Close report
     */
    public function closeReport(): void
    {
        Gate::authorize('manage', $this->report);

        $this->report->markClosed();
        $this->newStatus = BugReport::STATUS_CLOSED;

        $this->dispatch('success', message: 'Zgloszenie zamkniete.');
    }

    /**
     * Reopen report
     */
    public function reopenReport(): void
    {
        Gate::authorize('manage', $this->report);

        $this->report->update([
            'status' => BugReport::STATUS_IN_PROGRESS,
            'closed_at' => null,
        ]);
        $this->newStatus = BugReport::STATUS_IN_PROGRESS;

        $this->dispatch('success', message: 'Zgloszenie ponownie otwarte.');
    }

    /**
     * Go back to list
     */
    public function backToList(): void
    {
        $this->redirect(route('admin.bug-reports.index'), navigate: true);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get admins/managers for assignment
     */
    public function getAssigneesProperty(): \Illuminate\Support\Collection
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Admin', 'Manager']);
        })->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Get all comments (public + internal for admins)
     */
    public function getCommentsProperty(): \Illuminate\Support\Collection
    {
        return $this->report->comments()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.bug-reports.bug-report-detail', [
            'statuses' => BugReport::getStatuses(),
        ]);
    }
}

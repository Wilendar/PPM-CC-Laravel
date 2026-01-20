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
    public string $resolutionMode = 'resolve'; // 'resolve' or 'reject'

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
     * Open resolution form in resolve mode
     */
    public function openResolveForm(): void
    {
        $this->resolutionMode = 'resolve';
        $this->showResolutionForm = true;
    }

    /**
     * Open resolution form in reject mode
     */
    public function openRejectForm(): void
    {
        $this->resolutionMode = 'reject';
        $this->showResolutionForm = true;
    }

    /**
     * Update report status
     */
    public function updateStatus(): void
    {
        if (Auth::check()) {
            Gate::authorize('changeStatus', $this->report);
        }

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
        if (Auth::check()) {
            Gate::authorize('assign', $this->report);
        }

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

        if ($this->isInternalComment && Auth::check()) {
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

        // Skip auth check if user not logged in (dev mode)
        if (Auth::check()) {
            Gate::authorize('resolve', $this->report);
        }

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

        if (Auth::check()) {
            Gate::authorize('reject', $this->report);
        }

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
        if (Auth::check()) {
            Gate::authorize('manage', $this->report);
        }

        $this->report->markClosed();
        $this->newStatus = BugReport::STATUS_CLOSED;

        $this->dispatch('success', message: 'Zgloszenie zamkniete.');
    }

    /**
     * Reopen report
     */
    public function reopenReport(): void
    {
        if (Auth::check()) {
            Gate::authorize('manage', $this->report);
        }

        $this->report->update([
            'status' => BugReport::STATUS_IN_PROGRESS,
            'closed_at' => null,
        ]);
        $this->newStatus = BugReport::STATUS_IN_PROGRESS;

        $this->dispatch('success', message: 'Zgloszenie ponownie otwarte.');
    }

    /**
     * Export resolution to Markdown file for Claude Code knowledge base
     */
    public function exportToMarkdown(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $report = $this->report;
        $filename = sprintf(
            'ISSUE_%s_%s_%s.md',
            $report->id,
            \Str::slug($report->title, '_'),
            now()->format('Y-m-d')
        );

        $markdown = $this->generateMarkdownContent($report);

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, $filename, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    /**
     * Generate enhanced Markdown content for knowledge base
     */
    protected function generateMarkdownContent(BugReport $report): string
    {
        $typeLabels = BugReport::getTypes();
        $severityLabels = BugReport::getSeverities();
        $statusLabels = BugReport::getStatuses();

        $md = "# {$report->title}\n\n";

        // Metadata section (tabela)
        $md .= "## Metadane zgloszenia\n\n";
        $md .= "| Pole | Wartosc |\n";
        $md .= "|------|--------|\n";
        $md .= "| **ID** | #{$report->id} |\n";
        $md .= "| **Typ** | {$typeLabels[$report->type]} |\n";
        $md .= "| **Priorytet** | {$severityLabels[$report->severity]} |\n";
        $md .= "| **Status** | {$statusLabels[$report->status]} |\n";
        $md .= "| **Zglaszajacy** | " . ($report->reporter?->name ?? 'Nieznany') . " |\n";
        $md .= "| **Przypisany do** | " . ($report->assignee?->name ?? 'Brak') . " |\n";
        $md .= "| **Data zgloszenia** | {$report->created_at->format('Y-m-d H:i')} |\n";

        if ($report->resolved_at) {
            $md .= "| **Data rozwiazania** | {$report->resolved_at->format('Y-m-d H:i')} |\n";
            $resolutionTime = $report->created_at->diffForHumans($report->resolved_at, true);
            $md .= "| **Czas rozwiazania** | {$resolutionTime} |\n";
        }

        if ($report->closed_at) {
            $md .= "| **Data zamkniecia** | {$report->closed_at->format('Y-m-d H:i')} |\n";
        }

        $md .= "\n---\n\n";

        // Problem description
        $md .= "## Opis problemu\n\n";
        $md .= $report->description . "\n\n";

        // Steps to reproduce
        if ($report->steps_to_reproduce) {
            $md .= "## Kroki do odtworzenia\n\n";
            $md .= $report->steps_to_reproduce . "\n\n";
        }

        // Technical context
        $md .= "## Dane techniczne\n\n";

        if ($report->context_url) {
            $md .= "### URL kontekstu\n\n";
            $md .= "`{$report->context_url}`\n\n";
        }

        if ($report->browser_info || $report->os_info) {
            $md .= "### Srodowisko\n\n";
            if ($report->browser_info) {
                $md .= "- **Przegladarka:** {$report->browser_info}\n";
            }
            if ($report->os_info) {
                $md .= "- **System operacyjny:** {$report->os_info}\n";
            }
            $md .= "\n";
        }

        // Console errors
        if ($report->console_errors && count($report->console_errors) > 0) {
            $md .= "### Bledy konsoli\n\n";
            $md .= "```javascript\n";
            foreach ($report->console_errors as $error) {
                if (is_array($error)) {
                    $md .= ($error['message'] ?? json_encode($error)) . "\n";
                } else {
                    $md .= $error . "\n";
                }
            }
            $md .= "```\n\n";
        }

        // User actions
        if ($report->user_actions && count($report->user_actions) > 0) {
            $md .= "### Akcje uzytkownika\n\n";
            foreach ($report->user_actions as $index => $action) {
                $actionNum = $index + 1;
                if (is_array($action)) {
                    $type = $action['type'] ?? $action['action'] ?? 'unknown';
                    $target = $action['target'] ?? '';
                    $md .= "{$actionNum}. **{$type}** - {$target}\n";
                } else {
                    $md .= "{$actionNum}. {$action}\n";
                }
            }
            $md .= "\n";
        }

        // Screenshot
        if ($report->screenshot_path) {
            $md .= "### Zrzut ekranu\n\n";
            $screenshotUrl = asset('storage/' . $report->screenshot_path);
            $md .= "![Screenshot]({$screenshotUrl})\n\n";
            $md .= "Sciezka lokalna: `{$report->screenshot_path}`\n\n";
        }

        $md .= "---\n\n";

        // Resolution - THE MAIN PART
        if ($report->resolution) {
            $sectionTitle = $report->status === 'rejected' ? 'Powod odrzucenia' : 'Rozwiazanie';
            $md .= "## {$sectionTitle}\n\n";
            $md .= $report->resolution . "\n\n";
        }

        $md .= "---\n\n";

        // Tags for easy searching
        $md .= "## Tagi\n\n";
        $md .= "`{$report->type}` `{$report->severity}` ";

        // Add contextual tags based on description keywords
        $keywords = ['Laravel', 'Livewire', 'PrestaShop', 'API', 'CSS', 'JavaScript', 'Database', 'Cache', 'Redis', 'Queue'];
        $descLower = strtolower($report->description . ' ' . ($report->resolution ?? ''));
        foreach ($keywords as $keyword) {
            if (str_contains($descLower, strtolower($keyword))) {
                $md .= "`{$keyword}` ";
            }
        }
        $md .= "`PPM`\n\n";

        // Footer
        $md .= "---\n\n";
        $md .= "_Wygenerowano automatycznie z systemu Bug Report PPM_\n";
        $md .= "_Data eksportu: " . now()->format('Y-m-d H:i:s') . "_\n";

        return $md;
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

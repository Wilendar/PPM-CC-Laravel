<?php

namespace App\Http\Livewire\BugReports;

use App\Models\BugReport;
use App\Services\BugReportNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * BugReportModal Component
 *
 * Global modal for submitting bug reports and feature requests.
 * Available from any page via floating button.
 *
 * Features:
 * - Bug report submission with type/severity
 * - Screenshot upload (optional)
 * - Auto-capture diagnostics (URL, browser, OS, console errors, user actions)
 * - Form validation
 * - Success/error feedback
 *
 * @package App\Http\Livewire\BugReports
 */
class BugReportModal extends Component
{
    use WithFileUploads;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    public bool $isOpen = false;

    #[Validate('required|string|min:5|max:255')]
    public string $title = '';

    #[Validate('required|string|min:10|max:5000')]
    public string $description = '';

    #[Validate('nullable|string|max:3000')]
    public ?string $stepsToReproduce = null;

    #[Validate('required|in:bug,feature_request,improvement,question,support')]
    public string $type = 'bug';

    #[Validate('required|in:low,medium,high,critical')]
    public string $severity = 'medium';

    #[Validate('nullable|image|max:5120')]
    public $screenshot = null;

    // Auto-captured diagnostics
    public ?string $contextUrl = null;
    public ?string $browserInfo = null;
    public ?string $osInfo = null;
    public ?array $consoleErrors = null;
    public ?array $userActions = null;

    public bool $isSubmitting = false;

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE METHODS
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        // Pre-fill context if available
        $this->contextUrl = request()->header('referer');
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT LISTENERS
    |--------------------------------------------------------------------------
    */

    /**
     * Open modal triggered by Alpine event
     */
    #[On('open-bug-report-modal')]
    public function open(?array $data = null): void
    {
        $this->reset(['title', 'description', 'stepsToReproduce', 'screenshot', 'isSubmitting']);
        $this->type = 'bug';
        $this->severity = 'medium';

        // Accept pre-filled data if provided
        if ($data) {
            $this->contextUrl = $data['url'] ?? null;
            $this->browserInfo = $data['browser'] ?? null;
            $this->osInfo = $data['os'] ?? null;
            $this->consoleErrors = $data['consoleErrors'] ?? null;
            $this->userActions = $data['userActions'] ?? null;
        }

        $this->isOpen = true;
    }

    /**
     * Close modal
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['title', 'description', 'stepsToReproduce', 'screenshot', 'isSubmitting']);
    }

    /*
    |--------------------------------------------------------------------------
    | FORM ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Submit bug report
     */
    public function submit(): void
    {
        // Dev mode: allow without auth, use user ID 1 as fallback
        $reporterId = Auth::id() ?? 1;

        $this->isSubmitting = true;

        try {
            $this->validate();

            // Process screenshot if uploaded
            $screenshotPath = null;
            if ($this->screenshot) {
                $screenshotPath = $this->screenshot->store('bug-reports/screenshots', 'public');
            }

            // Create bug report
            $report = BugReport::create([
                'title' => $this->title,
                'description' => $this->description,
                'steps_to_reproduce' => $this->stepsToReproduce,
                'type' => $this->type,
                'severity' => $this->severity,
                'context_url' => $this->contextUrl,
                'browser_info' => $this->browserInfo,
                'os_info' => $this->osInfo,
                'console_errors' => $this->consoleErrors,
                'user_actions' => $this->userActions,
                'screenshot_path' => $screenshotPath,
                'reporter_id' => $reporterId,
                'status' => BugReport::STATUS_NEW,
            ]);

            Log::info('BugReport created', [
                'id' => $report->id,
                'type' => $report->type,
                'reporter_id' => $reporterId,
            ]);

            // Send notifications to admins
            try {
                app(BugReportNotificationService::class)->notifyNewReport($report);
            } catch (\Exception $e) {
                Log::warning('Failed to send bug report notification', ['error' => $e->getMessage()]);
            }

            // Dispatch notification event (for BugReportNotificationService)
            $this->dispatch('bug-report-created', reportId: $report->id);

            // Show success message
            $this->dispatch('success', message: 'Zgloszenie zostalo wyslane. Dziekujemy!');

            // Close modal
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isSubmitting = false;
            throw $e;
        } catch (\Exception $e) {
            Log::error('BugReport creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            $this->dispatch('error', message: 'Nie udalo sie wyslac zgloszenia. Sprobuj ponownie.');
            $this->isSubmitting = false;
        }
    }

    /**
     * Remove uploaded screenshot
     */
    public function removeScreenshot(): void
    {
        $this->screenshot = null;
    }

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get available types for form select
     */
    public function getTypesProperty(): array
    {
        return BugReport::getTypes();
    }

    /**
     * Get available severities for form select
     */
    public function getSeveritiesProperty(): array
    {
        return BugReport::getSeverities();
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.bug-reports.bug-report-modal');
    }
}

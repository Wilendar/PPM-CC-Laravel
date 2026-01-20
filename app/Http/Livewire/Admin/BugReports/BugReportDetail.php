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

        // Auto-change status from NEW to IN_PROGRESS when admin opens the report
        if ($this->report->status === BugReport::STATUS_NEW) {
            $this->report->markInProgress();
            $this->report->refresh();
        }

        $this->newStatus = $this->report->status;
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

        $this->report->update([
            'status' => $this->newStatus,
            'status_updated_at' => now(),
        ]);

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
     * Export bug report as AI Agent Task for Claude Code CLI
     * Generates detailed instructions for fixing the issue
     */
    public function exportForAIAgent(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $report = $this->report;
        $filename = sprintf(
            'AI_TASK_%s_%s_%s.md',
            $report->id,
            \Str::slug($report->title, '_'),
            now()->format('Y-m-d')
        );

        $markdown = $this->generateAIAgentTaskMarkdown($report);

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, $filename, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    /**
     * Generate AI Agent Task Markdown for Claude Code CLI
     */
    protected function generateAIAgentTaskMarkdown(BugReport $report): string
    {
        $typeLabels = BugReport::getTypes();
        $severityLabels = BugReport::getSeverities();
        $statusLabels = BugReport::getStatuses();

        // Extract keywords
        $keywords = $this->extractKeywordsForAI($report);
        $problemSummary = \Str::limit(
            preg_replace('/\s+/', ' ', strip_tags($report->description)),
            300
        );

        // AI-FRIENDLY HEADER (YAML frontmatter)
        $md = "---\n";
        $md .= "# AI AGENT TASK - PPM Bug Report Fix Request\n";
        $md .= "# Ten plik zawiera zadanie do wykonania przez Claude Code CLI\n";
        $md .= "task_type: bug_fix\n";
        $md .= "id: {$report->id}\n";
        $md .= "type: {$report->type}\n";
        $md .= "severity: {$report->severity}\n";
        $md .= "status: {$report->status}\n";
        $md .= "priority: " . $this->mapSeverityToPriority($report->severity) . "\n";
        $md .= "keywords: [" . implode(', ', $keywords) . "]\n";
        $md .= "context_url: " . ($report->context_url ?? 'null') . "\n";
        $md .= "project: PPM-CC-Laravel\n";
        $md .= "created_at: {$report->created_at->format('Y-m-d H:i')}\n";
        $md .= "---\n\n";

        // AI TASK SUMMARY
        $md .= "## AI Task Summary\n\n";
        $md .= "> **ZADANIE:** Napraw problem opisany ponizej w projekcie PPM-CC-Laravel\n>\n";
        $md .= "> **PROBLEM:** {$problemSummary}\n>\n";
        $md .= "> **PRIORYTET:** " . $this->mapSeverityToPriority($report->severity) . "\n>\n";
        $md .= "> **TECHNOLOGIE:** " . implode(', ', $keywords) . "\n\n";

        // AI WORKFLOW INSTRUCTIONS
        $md .= "## AI Agent Workflow\n\n";
        $md .= "<!-- AI_WORKFLOW\n";
        $md .= "WYKONAJ PONIZSZE KROKI W KOLEJNOSCI:\n\n";

        $md .= "### KROK 1: ANALIZA PROBLEMU\n";
        $md .= "1. Przeczytaj dokladnie sekcje 'Opis problemu' i 'Kroki do odtworzenia'\n";
        $md .= "2. Przeanalizuj bledy konsoli (jesli dostepne)\n";
        $md .= "3. Sprawdz akcje uzytkownika ktore doprowadzily do bledu\n";
        $md .= "4. Zidentyfikuj potencjalne pliki zrodlowe do modyfikacji\n\n";

        $md .= "### KROK 2: LOKALIZACJA KODU\n";
        $md .= "1. Uzyj URL kontekstu: " . ($report->context_url ?? 'brak') . "\n";
        $md .= "2. Znajdz odpowiednie pliki: routes, controllers, Livewire components, views\n";
        $md .= "3. Przeszukaj codebase pod katem slow kluczowych z bledu\n\n";

        $md .= "### KROK 3: IMPLEMENTACJA POPRAWKI\n";
        $md .= "1. Wprowadz minimalne zmiany naprawiajace problem\n";
        $md .= "2. NIE refaktoryzuj kodu poza zakresem naprawy\n";
        $md .= "3. Zachowaj zgodnosc z CLAUDE.md i standardami projektu\n";
        $md .= "4. Uzyj Context7 do weryfikacji wzorcow Laravel/Livewire\n\n";

        $md .= "### KROK 4: DEPLOYMENT\n";
        $md .= "1. Zbuduj assety: npm run build\n";
        $md .= "2. Upload plikow na produkcje via pscp\n";
        $md .= "3. Wyczysc cache: php artisan cache:clear\n\n";

        $md .= "### KROK 5: WERYFIKACJA (MANDATORY)\n";
        $md .= "1. Uzyj Claude in Chrome MCP do weryfikacji\n";
        $md .= "2. Nawiguj do URL: " . ($report->context_url ?? 'strona z bledem') . "\n";
        $md .= "3. Odtworz kroki z sekcji 'Kroki do odtworzenia'\n";
        $md .= "4. Sprawdz konsole przegladarki pod katem bledow\n";
        $md .= "5. Zrob screenshot potwierdzajacy naprawe\n\n";

        $md .= "### KROK 6: RAPORT ROZWIAZANIA\n";
        $md .= "Jesli weryfikacja POZYTYWNA, napisz rozwiazanie w formacie:\n";
        $md .= "```\n";
        $md .= "## Rozwiazanie\n\n";
        $md .= "### Przyczyna problemu\n";
        $md .= "[Opisz root cause - dlaczego blad wystepował]\n\n";
        $md .= "### Wprowadzone zmiany\n";
        $md .= "[Lista zmienionych plikow z opisem]\n\n";
        $md .= "### Weryfikacja\n";
        $md .= "[Potwierdzenie ze problem zostal naprawiony]\n";
        $md .= "```\n";
        $md .= "AI_WORKFLOW -->\n\n";

        $md .= "---\n\n";

        // MAIN CONTENT - Problem Details
        $md .= "# {$report->title}\n\n";

        // Metadata table
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
        $md .= "\n---\n\n";

        // Problem description
        $md .= "## Opis problemu\n\n";
        $md .= $report->description . "\n\n";

        // Steps to reproduce - CRITICAL for debugging
        if ($report->steps_to_reproduce) {
            $md .= "## Kroki do odtworzenia (WAZNE!)\n\n";
            $md .= $report->steps_to_reproduce . "\n\n";
        } else {
            $md .= "## Kroki do odtworzenia\n\n";
            $md .= "_Brak szczegolowych krokow - sprawdz opis problemu_\n\n";
        }

        // Technical context - CRITICAL for fixing
        $md .= "## Dane techniczne\n\n";

        if ($report->context_url) {
            $md .= "### URL kontekstu (NAWIGUJ TUTAJ)\n\n";
            $md .= "```\n{$report->context_url}\n```\n\n";
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

        // Console errors - CRITICAL for debugging
        if ($report->console_errors && count($report->console_errors) > 0) {
            $md .= "### Bledy konsoli (PRZEANALIZUJ!)\n\n";
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
            $md .= "### Akcje uzytkownika przed bledem\n\n";
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
            $md .= "### Zrzut ekranu bledu\n\n";
            $screenshotUrl = asset('storage/' . $report->screenshot_path);
            $md .= "![Screenshot]({$screenshotUrl})\n\n";
            $md .= "Sciezka lokalna: `{$report->screenshot_path}`\n\n";
        }

        $md .= "---\n\n";

        // Suggested files to check
        $md .= "## Sugerowane pliki do sprawdzenia\n\n";
        $md .= $this->suggestFilesToCheck($report);
        $md .= "\n---\n\n";

        // Tags
        $md .= "## Tagi\n\n";
        foreach ($keywords as $keyword) {
            $md .= "`{$keyword}` ";
        }
        $md .= "\n\n";

        // Footer
        $md .= "---\n\n";
        $md .= "_Wygenerowano automatycznie z systemu Bug Report PPM_\n";
        $md .= "_Data eksportu: " . now()->format('Y-m-d H:i:s') . "_\n";
        $md .= "_Przeznaczenie: Claude Code CLI AI Agent Task_\n";

        return $md;
    }

    /**
     * Extract keywords for AI context
     */
    protected function extractKeywordsForAI(BugReport $report): array
    {
        $allKeywords = [
            'Laravel', 'Livewire', 'PrestaShop', 'API', 'CSS', 'JavaScript',
            'Database', 'Cache', 'Redis', 'Queue', 'Blade', 'Alpine.js',
            'Eloquent', 'Migration', 'Route', 'Middleware', 'Controller',
            'Component', 'Model', 'View', 'Vite', 'Tailwind', 'PHP',
            'MySQL', 'SQL', 'JSON', 'HTTP', 'AJAX', 'Fetch', 'Axios',
            'Auth', 'Session', 'Cookie', 'Storage', 'Upload', 'File',
            'Validation', 'Form', 'Input', 'Modal', 'Dropdown', 'Table',
            'Pagination', 'Search', 'Filter', 'Sort', 'Export', 'Import',
        ];

        $urlPatterns = [
            '/admin/' => 'Admin',
            '/products' => 'Product',
            '/categories' => 'Category',
            '/shops' => 'Shop',
            '/orders' => 'Order',
            '/settings' => 'Settings',
            '/sync' => 'Sync',
            '/bug-reports' => 'BugReport',
            '/edit' => 'Edit',
            '/create' => 'Create',
        ];

        $content = strtolower(
            $report->title . ' ' .
            $report->description . ' ' .
            ($report->context_url ?? '')
        );

        $found = [];

        foreach ($allKeywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                $found[] = $keyword;
            }
        }

        $contextUrl = $report->context_url ?? '';
        foreach ($urlPatterns as $pattern => $keyword) {
            if (str_contains(strtolower($contextUrl), $pattern)) {
                $found[] = $keyword;
            }
        }

        $found[] = ucfirst(str_replace('_', ' ', $report->type));
        $found[] = 'PPM';

        return array_unique($found);
    }

    /**
     * Map severity to priority label
     */
    protected function mapSeverityToPriority(string $severity): string
    {
        return match ($severity) {
            'critical' => 'KRYTYCZNY - natychmiast',
            'high' => 'WYSOKI - pilne',
            'medium' => 'SREDNI - normalne',
            'low' => 'NISKI - moze czekac',
            default => 'SREDNI',
        };
    }

    /**
     * Suggest files to check based on context URL and keywords
     */
    protected function suggestFilesToCheck(BugReport $report): string
    {
        $suggestions = [];
        $url = $report->context_url ?? '';
        $content = strtolower($report->description);

        // Route-based suggestions
        if (str_contains($url, '/admin/products')) {
            $suggestions[] = "- `app/Http/Livewire/Admin/Products/` - komponenty Livewire produktow";
            $suggestions[] = "- `resources/views/livewire/admin/products/` - widoki Blade";
        }
        if (str_contains($url, '/admin/categories')) {
            $suggestions[] = "- `app/Http/Livewire/Admin/Categories/` - komponenty kategorii";
        }
        if (str_contains($url, '/admin/shops')) {
            $suggestions[] = "- `app/Http/Livewire/Admin/Shops/` - komponenty sklepow";
        }
        if (str_contains($url, '/admin/bug-reports')) {
            $suggestions[] = "- `app/Http/Livewire/Admin/BugReports/` - komponenty bug reportow";
        }

        // Content-based suggestions
        if (str_contains($content, 'css') || str_contains($content, 'style') || str_contains($content, 'layout')) {
            $suggestions[] = "- `resources/css/admin/components.css` - style admin UI";
            $suggestions[] = "- `resources/css/admin/layout.css` - layout grid";
        }
        if (str_contains($content, 'livewire') || str_contains($content, 'wire:')) {
            $suggestions[] = "- Sprawdz dyrektywy wire: w odpowiednim widoku Blade";
            $suggestions[] = "- Sprawdz _ISSUES_FIXES/ pod katem znanych problemow Livewire";
        }
        if (str_contains($content, 'modal') || str_contains($content, 'dropdown')) {
            $suggestions[] = "- Sprawdz z-index i stacking context w CSS";
        }

        if (empty($suggestions)) {
            $suggestions[] = "- Przeszukaj codebase pod katem slow kluczowych z opisu";
            $suggestions[] = "- Sprawdz routes/web.php pod katem matchujacych route'ow";
        }

        return implode("\n", $suggestions);
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

<?php

namespace App\Http\Livewire\Admin\BugReports;

use App\Models\BugReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * SolutionsLibrary Component (Admin)
 *
 * Library of all resolved bug reports and their solutions.
 * Useful as knowledge base for recurring issues.
 *
 * @package App\Http\Livewire\Admin\BugReports
 */
#[Layout('layouts.admin')]
#[Title('Biblioteka Rozwiazan - Panel Admina')]
class SolutionsLibrary extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | PUBLIC PROPERTIES
    |--------------------------------------------------------------------------
    */

    public string $search = '';
    public string $typeFilter = '';
    public string $severityFilter = '';

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        if (Auth::check()) {
            Gate::authorize('viewAny', BugReport::class);
        }
    }

    public function updatingSearch(): void
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

    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * View report detail
     */
    public function viewReport(int $reportId): void
    {
        $this->redirect(route('admin.bug-reports.show', $reportId), navigate: true);
    }

    /**
     * Export single solution to Markdown
     */
    public function exportToMarkdown(int $reportId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $report = BugReport::with(['reporter', 'assignee'])->findOrFail($reportId);

        $filename = sprintf(
            'SOLUTION_%s_%s_%s.md',
            $report->id,
            Str::slug($report->title, '_'),
            now()->format('Y-m-d')
        );

        $markdown = $this->generateEnhancedMarkdown($report);

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, $filename, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    /**
     * Clear filters
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->severityFilter = '';
        $this->resetPage();
    }

    /**
     * Export all solutions to ZIP archive
     */
    public function exportAllToZip(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $reports = BugReport::with(['reporter', 'assignee'])
            ->whereIn('status', [BugReport::STATUS_RESOLVED, BugReport::STATUS_CLOSED])
            ->whereNotNull('resolution')
            ->orderBy('resolved_at', 'desc')
            ->get();

        if ($reports->isEmpty()) {
            $this->dispatch('error', message: 'Brak rozwiazan do eksportu.');
            return response()->noContent();
        }

        $zipFilename = 'PPM_Solutions_Library_' . now()->format('Y-m-d_His') . '.zip';

        return response()->streamDownload(function () use ($reports) {
            $zip = new \ZipArchive();
            $tempFile = tempnam(sys_get_temp_dir(), 'solutions_');

            if ($zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create ZIP archive');
            }

            foreach ($reports as $report) {
                $filename = sprintf(
                    'SOLUTION_%s_%s.md',
                    $report->id,
                    Str::slug($report->title, '_')
                );

                $markdown = $this->generateEnhancedMarkdown($report);
                $zip->addFromString($filename, $markdown);
            }

            // Add index file
            $indexContent = $this->generateIndexMarkdown($reports);
            $zip->addFromString('INDEX.md', $indexContent);

            $zip->close();

            readfile($tempFile);
            unlink($tempFile);
        }, $zipFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Generate index markdown for ZIP archive
     * Includes AI-friendly summary for Claude Code CLI
     */
    protected function generateIndexMarkdown($reports): string
    {
        $typeLabels = BugReport::getTypes();
        $severityLabels = BugReport::getSeverities();

        // Count by type
        $byType = $reports->groupBy('type')->map->count();
        $bySeverity = $reports->groupBy('severity')->map->count();

        // Collect all unique keywords
        $allKeywords = [];
        foreach ($reports as $report) {
            $allKeywords = array_merge($allKeywords, $this->extractKeywords($report));
        }
        $uniqueKeywords = array_unique($allKeywords);

        // AI-FRIENDLY HEADER
        $md = "---\n";
        $md .= "# AI AGENT CONTEXT - PPM Solutions Library Index\n";
        $md .= "# Ten plik jest indeksem wszystkich rozwiazan w bibliotece PPM\n";
        $md .= "total_solutions: " . $reports->count() . "\n";
        $md .= "exported_at: " . now()->format('Y-m-d H:i:s') . "\n";
        $md .= "project: PPM-CC-Laravel\n";
        $md .= "keywords: [" . implode(', ', array_slice($uniqueKeywords, 0, 20)) . "]\n";
        $md .= "---\n\n";

        // AI QUICK SUMMARY
        $md .= "## AI Summary\n\n";
        $md .= "> **BIBLIOTEKA:** Zbiór {$reports->count()} rozwiązanych problemów z projektu PPM-CC-Laravel\n>\n";
        $md .= "> **TYPY:** " . $byType->map(fn($count, $type) => ($typeLabels[$type] ?? $type) . " ({$count})")->implode(', ') . "\n>\n";
        $md .= "> **PRIORYTETY:** " . $bySeverity->map(fn($count, $sev) => ($severityLabels[$sev] ?? $sev) . " ({$count})")->implode(', ') . "\n\n";

        // AI INSTRUCTION BLOCK
        $md .= "<!-- AI_INSTRUCTION\n";
        $md .= "Jak korzystac z tej biblioteki rozwiazan:\n";
        $md .= "1. Przeszukaj INDEX.md po slowach kluczowych zwiazanych z Twoim problemem\n";
        $md .= "2. Otworz odpowiedni plik SOLUTION_*.md\n";
        $md .= "3. Przeczytaj sekcje 'AI Summary' dla szybkiego kontekstu\n";
        $md .= "4. Zastosuj rozwiazanie z sekcji 'Rozwiazanie'\n";
        $md .= "5. Uzyj sekcji 'Tagi' do znalezienia powiazanych rozwiazan\n";
        $md .= "AI_INSTRUCTION -->\n\n";

        $md .= "---\n\n";

        // MAIN CONTENT
        $md .= "# PPM Solutions Library - Index\n\n";
        $md .= "**Wygenerowano:** " . now()->format('Y-m-d H:i:s') . "\n\n";
        $md .= "**Liczba rozwiazan:** " . $reports->count() . "\n\n";
        $md .= "---\n\n";

        $md .= "## Lista rozwiazan\n\n";
        $md .= "| ID | Tytul | Typ | Priorytet | Rozwiazano |\n";
        $md .= "|---|---|---|---|---|\n";

        foreach ($reports as $report) {
            $filename = sprintf('SOLUTION_%s_%s.md', $report->id, Str::slug($report->title, '_'));
            $md .= sprintf(
                "| #%s | [%s](%s) | %s | %s | %s |\n",
                $report->id,
                Str::limit($report->title, 50),
                $filename,
                $typeLabels[$report->type] ?? $report->type,
                $severityLabels[$report->severity] ?? $report->severity,
                $report->resolved_at?->format('Y-m-d') ?? '-'
            );
        }

        $md .= "\n---\n\n";

        // Keywords section for AI search
        $md .= "## Slowa kluczowe (dla AI)\n\n";
        $md .= implode(', ', $uniqueKeywords) . "\n\n";

        $md .= "---\n\n";
        $md .= "_Eksport z systemu Bug Report PPM_\n";

        return $md;
    }

    /*
    |--------------------------------------------------------------------------
    | MARKDOWN GENERATION
    |--------------------------------------------------------------------------
    */

    /**
     * Generate enhanced Markdown with full metadata
     * Includes AI-friendly summary for Claude Code CLI
     */
    protected function generateEnhancedMarkdown(BugReport $report): string
    {
        $typeLabels = BugReport::getTypes();
        $severityLabels = BugReport::getSeverities();
        $statusLabels = BugReport::getStatuses();

        // Extract keywords for AI context
        $keywords = $this->extractKeywords($report);
        // Clean summaries - remove markdown/HTML and newlines for single-line AI summary
        $problemSummary = Str::limit(
            preg_replace('/\s+/', ' ', strip_tags($report->description)),
            200
        );
        $solutionSummary = $report->resolution
            ? Str::limit(preg_replace('/\s+/', ' ', strip_tags($report->resolution)), 200)
            : 'Brak rozwiazania';

        // AI-FRIENDLY HEADER (YAML-like frontmatter for Claude Code CLI)
        $md = "---\n";
        $md .= "# AI AGENT CONTEXT - PPM Bug Report Solution\n";
        $md .= "# Ten blok jest przeznaczony dla agentow AI (Claude Code CLI)\n";
        $md .= "id: {$report->id}\n";
        $md .= "type: {$report->type}\n";
        $md .= "severity: {$report->severity}\n";
        $md .= "status: {$report->status}\n";
        $md .= "resolved_at: " . ($report->resolved_at?->format('Y-m-d') ?? 'null') . "\n";
        $md .= "keywords: [" . implode(', ', $keywords) . "]\n";
        $md .= "context_url: " . ($report->context_url ?? 'null') . "\n";
        $md .= "---\n\n";

        // AI QUICK SUMMARY
        $md .= "## AI Summary\n\n";
        $md .= "> **PROBLEM:** {$problemSummary}\n>\n";
        $md .= "> **ROZWIAZANIE:** {$solutionSummary}\n>\n";
        $md .= "> **TECHNOLOGIE:** " . implode(', ', $keywords) . "\n\n";

        // AI INSTRUCTION BLOCK
        $md .= "<!-- AI_INSTRUCTION\n";
        $md .= "Jesli napotykasz podobny problem w projekcie PPM-CC-Laravel:\n";
        $md .= "1. Sprawdz czy symptomy sa zgodne z opisem ponizej\n";
        $md .= "2. Zastosuj rozwiazanie z sekcji 'Rozwiazanie'\n";
        $md .= "3. Zweryfikuj poprawke zgodnie z krokami odtworzenia\n";
        $md .= "4. Jesli problem sie powtarza, sprawdz sekcje 'Dane techniczne'\n";
        $md .= "AI_INSTRUCTION -->\n\n";

        $md .= "---\n\n";

        // MAIN CONTENT
        $md .= "# {$report->title}\n\n";

        // Metadata section
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
                    $type = $action['type'] ?? 'unknown';
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

        // Tags for searching
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

    /*
    |--------------------------------------------------------------------------
    | COMPUTED PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Get statistics for solutions
     */
    public function getStatsProperty(): array
    {
        $resolvedQuery = BugReport::whereIn('status', [
            BugReport::STATUS_RESOLVED,
            BugReport::STATUS_CLOSED,
        ])->whereNotNull('resolution');

        return [
            'total_solutions' => $resolvedQuery->count(),
            'this_month' => $resolvedQuery->where('resolved_at', '>=', now()->startOfMonth())->count(),
            'by_type' => [
                'bug' => $resolvedQuery->clone()->where('type', 'bug')->count(),
                'feature' => $resolvedQuery->clone()->where('type', 'feature_request')->count(),
                'improvement' => $resolvedQuery->clone()->where('type', 'improvement')->count(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Extract technology keywords from bug report for AI context
     */
    private function extractKeywords(BugReport $report): array
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
            'Sync', 'Webhook', 'Cron', 'Job', 'Event', 'Listener',
            'BaseLinker', 'ERP', 'Subiekt', 'FTP', 'SSH', 'Deploy',
        ];

        // URL path patterns -> keywords mapping
        $urlPatterns = [
            '/admin/' => 'Admin',
            '/products' => 'Product',
            '/categories' => 'Category',
            '/shops' => 'Shop',
            '/orders' => 'Order',
            '/users' => 'User',
            '/settings' => 'Settings',
            '/import' => 'Import',
            '/export' => 'Export',
            '/sync' => 'Sync',
            '/bug-reports' => 'BugReport',
            '/dashboard' => 'Dashboard',
            '/media' => 'Media',
            '/edit' => 'Edit',
            '/create' => 'Create',
        ];

        $content = strtolower(
            $report->title . ' ' .
            $report->description . ' ' .
            ($report->resolution ?? '') . ' ' .
            ($report->context_url ?? '')
        );

        $found = [];

        // Match technology keywords
        foreach ($allKeywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                $found[] = $keyword;
            }
        }

        // Match URL patterns
        $contextUrl = $report->context_url ?? '';
        foreach ($urlPatterns as $pattern => $keyword) {
            if (str_contains(strtolower($contextUrl), $pattern)) {
                $found[] = $keyword;
            }
        }

        // Always add type and PPM
        $found[] = ucfirst(str_replace('_', ' ', $report->type));
        $found[] = 'PPM';

        return array_unique($found);
    }

    /**
     * Build filtered query for resolved reports only
     */
    private function getResolvedReportsQuery()
    {
        $query = BugReport::with(['reporter', 'assignee'])
            ->whereIn('status', [
                BugReport::STATUS_RESOLVED,
                BugReport::STATUS_CLOSED,
            ])
            ->whereNotNull('resolution');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%")
                  ->orWhere('resolution', 'like', "%{$this->search}%")
                  ->orWhere('id', $this->search);
            });
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->severityFilter) {
            $query->where('severity', $this->severityFilter);
        }

        return $query->orderBy('resolved_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        return view('livewire.admin.bug-reports.solutions-library', [
            'solutions' => $this->getResolvedReportsQuery()->paginate(12),
            'types' => BugReport::getTypes(),
            'severities' => BugReport::getSeverities(),
        ]);
    }
}

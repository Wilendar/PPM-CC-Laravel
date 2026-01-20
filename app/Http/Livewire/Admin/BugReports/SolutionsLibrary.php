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
     */
    protected function generateIndexMarkdown($reports): string
    {
        $md = "# PPM Solutions Library - Index\n\n";
        $md .= "Wygenerowano: " . now()->format('Y-m-d H:i:s') . "\n\n";
        $md .= "Liczba rozwiazan: " . $reports->count() . "\n\n";
        $md .= "---\n\n";

        $md .= "## Lista rozwiazan\n\n";
        $md .= "| ID | Tytul | Typ | Priorytet | Rozwiazano |\n";
        $md .= "|---|---|---|---|---|\n";

        $typeLabels = BugReport::getTypes();
        $severityLabels = BugReport::getSeverities();

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
     */
    protected function generateEnhancedMarkdown(BugReport $report): string
    {
        $typeLabels = BugReport::getTypes();
        $severityLabels = BugReport::getSeverities();
        $statusLabels = BugReport::getStatuses();

        $md = "# {$report->title}\n\n";

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

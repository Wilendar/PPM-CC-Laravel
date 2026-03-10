<?php

namespace App\Http\Livewire\Profile;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Activity History Component
 *
 * Shows the authenticated user's activity history from audit_logs.
 * Provides filtering by event type, date range, and quick period shortcuts.
 *
 * Uses AuditLog scopes: forUser(), forEvent(), dateRange()
 * Uses AuditLog accessors: event_display, short_model_type, getChanges()
 */
class ActivityHistory extends Component
{
    use WithPagination;

    /**
     * Filter by event type (e.g. 'created', 'updated', 'login').
     */
    public string $eventFilter = '';

    /**
     * Date range start (Y-m-d format).
     */
    public string $dateFrom = '';

    /**
     * Date range end (Y-m-d format).
     */
    public string $dateTo = '';

    /**
     * Items per page.
     */
    public int $perPage = 25;

    /**
     * Initialize default filter values.
     */
    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = '';
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    /**
     * Paginated audit logs for the current user with filters applied.
     *
     * According to Livewire 3.x docs (/livewire/livewire):
     * Computed properties ensure Eloquent constraints are reapplied on each request.
     */
    #[Computed]
    public function logs()
    {
        $query = AuditLog::forUser(Auth::id());

        if ($this->eventFilter !== '') {
            $query->forEvent($this->eventFilter);
        }

        if ($this->dateFrom !== '') {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }

        if ($this->dateTo !== '') {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Activity statistics for the current user.
     */
    #[Computed]
    public function stats(): array
    {
        $userId = Auth::id();

        return [
            'today' => AuditLog::forUser($userId)
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'week' => AuditLog::forUser($userId)
                ->where('created_at', '>=', now()->startOfWeek())
                ->count(),
            'total' => AuditLog::forUser($userId)->count(),
        ];
    }

    /**
     * Distinct event types that exist for the current user.
     */
    #[Computed]
    public function availableEvents(): \Illuminate\Support\Collection
    {
        return AuditLog::forUser(Auth::id())
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');
    }

    // ==========================================
    // FILTER METHODS
    // ==========================================

    /**
     * Reset all filters to defaults.
     */
    public function clearFilters(): void
    {
        $this->eventFilter = '';
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = '';
        $this->resetPage();
    }

    /**
     * Apply a quick date filter shortcut.
     */
    public function setQuickFilter(string $period): void
    {
        $this->dateTo = now()->format('Y-m-d');

        $this->dateFrom = match ($period) {
            'today' => now()->format('Y-m-d'),
            'week' => now()->startOfWeek()->format('Y-m-d'),
            'month' => now()->subDays(30)->format('Y-m-d'),
            default => now()->subDays(30)->format('Y-m-d'),
        };

        $this->resetPage();
    }

    /**
     * Reset page when event filter changes.
     */
    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset page when dateFrom changes.
     */
    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    /**
     * Reset page when dateTo changes.
     */
    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Get the CSS classes for an event type icon.
     */
    public function getEventIconClasses(string $event): string
    {
        return match ($event) {
            'created' => 'text-emerald-400 bg-emerald-500/20',
            'updated' => 'text-amber-400 bg-amber-500/20',
            'deleted' => 'text-red-400 bg-red-500/20',
            'restored' => 'text-purple-400 bg-purple-500/20',
            'login' => 'text-blue-400 bg-blue-500/20',
            'login_failed' => 'text-red-400 bg-red-500/20',
            'logout' => 'text-gray-400 bg-gray-500/20',
            'bulk_delete' => 'text-red-400 bg-red-500/20',
            'bulk_update' => 'text-amber-400 bg-amber-500/20',
            'bulk_export' => 'text-blue-400 bg-blue-500/20',
            'synced' => 'text-cyan-400 bg-cyan-500/20',
            'imported' => 'text-indigo-400 bg-indigo-500/20',
            'exported' => 'text-teal-400 bg-teal-500/20',
            'matched' => 'text-yellow-400 bg-yellow-500/20',
            default => 'text-gray-400 bg-gray-500/20',
        };
    }

    /**
     * Get the SVG icon name for an event type.
     */
    public function getEventIconName(string $event): string
    {
        return match ($event) {
            'created' => 'plus-circle',
            'updated' => 'pencil-square',
            'deleted' => 'trash',
            'restored' => 'arrow-uturn-left',
            'login' => 'arrow-right-on-rectangle',
            'login_failed' => 'exclamation-triangle',
            'logout' => 'arrow-left-on-rectangle',
            'bulk_delete' => 'trash',
            'bulk_update' => 'pencil-square',
            'bulk_export' => 'arrow-down-tray',
            'synced' => 'arrow-path',
            'imported' => 'arrow-down-on-square',
            'exported' => 'arrow-up-on-square',
            'matched' => 'link',
            default => 'information-circle',
        };
    }

    /**
     * Get human-readable label for an event type (used in filter dropdown).
     */
    public function getEventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Utworzono',
            'updated' => 'Zaktualizowano',
            'deleted' => 'Usunieto',
            'restored' => 'Przywrocono',
            'login' => 'Logowanie',
            'login_failed' => 'Nieudane logowanie',
            'logout' => 'Wylogowanie',
            'bulk_delete' => 'Masowe usuwanie',
            'bulk_update' => 'Masowa aktualizacja',
            'bulk_export' => 'Masowy eksport',
            'synced' => 'Zsynchronizowano',
            'imported' => 'Zaimportowano',
            'exported' => 'Wyeksportowano',
            'matched' => 'Dopasowano',
            default => ucfirst(str_replace('_', ' ', $event)),
        };
    }

    /**
     * Get human-readable Polish label for a model class name.
     */
    public function getModelLabel(string $auditableType): string
    {
        return match ($auditableType) {
            'App\\Models\\Product' => 'Produkt',
            'App\\Models\\Category' => 'Kategoria',
            'App\\Models\\User' => 'Uzytkownik',
            'App\\Models\\BusinessPartner' => 'Kontrahent',
            'App\\Models\\FeatureType' => 'Cecha',
            'App\\Models\\FeatureValue' => 'Wartosc cechy',
            'App\\Models\\FeatureTemplate' => 'Szablon cech',
            'App\\Models\\FeatureGroup' => 'Grupa cech',
            'App\\Models\\PriceGroup' => 'Grupa cenowa',
            'App\\Models\\PrestaShopShop' => 'Sklep',
            'App\\Models\\ERPConnection' => 'Polaczenie ERP',
            'App\\Models\\ProductVariant' => 'Wariant produktu',
            'App\\Models\\Warehouse' => 'Magazyn',
            'App\\Models\\Manufacturer' => 'Producent',
            'App\\Models\\AuditLog' => 'Log audytu',
            default => class_basename($auditableType),
        };
    }

    /**
     * Get a short summary of changed fields for display in the list.
     */
    public function getChangeSummary(AuditLog $log): string
    {
        $newValues = $log->new_values ?? [];
        $oldValues = $log->old_values ?? [];

        // For created events, show count of fields set
        if ($log->event === 'created' && !empty($newValues)) {
            $fields = array_keys($newValues);
            $count = count($fields);
            if ($count === 0) {
                return '';
            }
            $shown = array_slice($fields, 0, 3);
            $summary = implode(', ', $shown);
            if ($count > 3) {
                $summary .= ' (+' . ($count - 3) . ' wiecej)';
            }
            return $summary;
        }

        // For updated events, show changed fields
        if (in_array($log->event, ['updated', 'bulk_update'])) {
            $changes = $log->getChanges();
            $fields = array_keys($changes);
            $count = count($fields);
            if ($count === 0) {
                return '';
            }
            $shown = array_slice($fields, 0, 3);
            $summary = implode(', ', $shown);
            if ($count > 3) {
                $summary .= ' (+' . ($count - 3) . ' wiecej)';
            }
            return $summary;
        }

        // For deleted events, show what was deleted
        if ($log->event === 'deleted' && !empty($oldValues)) {
            $nameField = $oldValues['name'] ?? $oldValues['sku'] ?? $oldValues['email'] ?? null;
            if ($nameField) {
                return \Illuminate\Support\Str::limit((string) $nameField, 40);
            }
        }

        return '';
    }

    // ==========================================
    // RENDER
    // ==========================================

    public function render()
    {
        return view('livewire.profile.activity-history')
            ->layout('layouts.admin', [
                'title' => 'Historia aktywnosci - Admin PPM',
                'breadcrumb' => 'Historia aktywnosci',
            ]);
    }
}

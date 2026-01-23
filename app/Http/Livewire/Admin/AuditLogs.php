<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

/**
 * Audit Logs Management Interface
 * 
 * FAZA C: Comprehensive audit trail dla existing audit_logs table
 * 
 * Features:
 * - Filterable log table (user, action, model_type, date range)
 * - JSON diff viewer dla old_values vs new_values
 * - Activity charts (daily/weekly activity) 
 * - Suspicious activity detection alerts
 * - Export audit logs dla compliance (Excel, PDF)
 * - Real-time log streaming preparation
 * - Advanced search z full-text capabilities na JSONB fields
 * - User activity timeline generator
 * - System event correlation
 */
class AuditLogs extends Component
{
    use WithPagination;

    // ==========================================
    // CORE PROPERTIES
    // ==========================================

    public $selectedLogId = null;
    public $selectedLog = null;
    public $showDetailsModal = false;
    public $showDiffModal = false;
    
    // ==========================================
    // FILTERING PROPERTIES
    // ==========================================

    public $search = '';
    public $userFilter = 'all';
    public $actionFilter = 'all';
    public $modelFilter = 'all';
    public $dateFromFilter = '';
    public $dateToFilter = '';
    public $ipFilter = '';
    public $suspiciousOnly = false;
    
    // ==========================================
    // SORTING & DISPLAY
    // ==========================================
    
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 50;
    public $showFilters = false;
    public $viewMode = 'table'; // table, timeline, chart
    
    // ==========================================
    // EXPORT PROPERTIES
    // ==========================================
    
    public $showExportModal = false;
    public $exportFormat = 'excel';
    public $exportDateFrom = '';
    public $exportDateTo = '';
    public $exportFields = [
        'created_at' => true,
        'user_name' => true,
        'action' => true,
        'model_type' => true,
        'model_id' => true,
        'ip_address' => true,
        'user_agent' => true,
        'changes' => false,
        'old_values' => false,
        'new_values' => false
    ];
    
    // ==========================================
    // ANALYTICS PROPERTIES
    // ==========================================
    
    public $chartPeriod = 'week'; // day, week, month
    public $showSuspiciousAlerts = true;
    public $activityStats = [];
    public $topUsers = [];
    public $topActions = [];
    public $suspiciousActivities = [];

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount()
    {
        $this->authorize('viewAny', AuditLog::class);
        
        // Set default date filters (last 30 days)
        $this->dateFromFilter = now()->subDays(30)->format('Y-m-d');
        $this->dateToFilter = now()->format('Y-m-d');
        $this->exportDateFrom = $this->dateFromFilter;
        $this->exportDateTo = $this->dateToFilter;
        
        $this->calculateStats();
        $this->detectSuspiciousActivity();
    }

    // ==========================================
    // SEARCH & FILTERING
    // ==========================================

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedActionFilter()
    {
        $this->resetPage();
    }

    public function updatedModelFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFromFilter()
    {
        $this->resetPage();
        $this->calculateStats();
    }

    public function updatedDateToFilter()
    {
        $this->resetPage();
        $this->calculateStats();
    }

    public function updatedSuspiciousOnly()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->userFilter = 'all';
        $this->actionFilter = 'all';
        $this->modelFilter = 'all';
        $this->dateFromFilter = now()->subDays(30)->format('Y-m-d');
        $this->dateToFilter = now()->format('Y-m-d');
        $this->ipFilter = '';
        $this->suspiciousOnly = false;
        $this->resetPage();
        $this->calculateStats();
    }

    public function setQuickFilter($period)
    {
        switch ($period) {
            case 'today':
                $this->dateFromFilter = now()->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
            case 'week':
                $this->dateFromFilter = now()->subWeek()->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
            case 'month':
                $this->dateFromFilter = now()->subMonth()->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
            case 'quarter':
                $this->dateFromFilter = now()->subQuarter()->format('Y-m-d');
                $this->dateToFilter = now()->format('Y-m-d');
                break;
        }
        
        $this->resetPage();
        $this->calculateStats();
    }

    // ==========================================
    // SORTING
    // ==========================================

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    // ==========================================
    // LOG DETAILS & DIFF VIEWER
    // ==========================================

    public function showLogDetails($logId)
    {
        $this->selectedLogId = $logId;
        $this->selectedLog = AuditLog::with('user')->findOrFail($logId);
        $this->showDetailsModal = true;
    }

    public function showLogDiff($logId)
    {
        $this->selectedLogId = $logId;
        $this->selectedLog = AuditLog::with('user')->findOrFail($logId);
        $this->showDiffModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedLog = null;
        $this->selectedLogId = null;
    }

    public function closeDiffModal()
    {
        $this->showDiffModal = false;
        $this->selectedLog = null;
        $this->selectedLogId = null;
    }

    // ==========================================
    // EXPORT FUNCTIONALITY
    // ==========================================

    public function openExportModal()
    {
        $this->authorize('export', AuditLog::class);
        $this->showExportModal = true;
    }

    public function closeExportModal()
    {
        $this->showExportModal = false;
        $this->exportFormat = 'excel';
    }

    public function exportLogs()
    {
        $this->authorize('export', AuditLog::class);
        
        $query = $this->getLogsQuery();
        
        // Apply export date range if different from filter
        if ($this->exportDateFrom && $this->exportDateFrom !== $this->dateFromFilter) {
            $query->where('created_at', '>=', Carbon::parse($this->exportDateFrom)->startOfDay());
        }
        
        if ($this->exportDateTo && $this->exportDateTo !== $this->dateToFilter) {
            $query->where('created_at', '<=', Carbon::parse($this->exportDateTo)->endOfDay());
        }
        
        $logs = $query->with('user')->get();
        
        switch ($this->exportFormat) {
            case 'excel':
                return $this->exportToExcel($logs);
            case 'csv':
                return $this->exportToCsv($logs);
            case 'pdf':
                return $this->exportToPdf($logs);
            default:
                session()->flash('error', 'Nieznany format eksportu.');
        }
        
        $this->closeExportModal();
    }

    protected function exportToExcel($logs)
    {
        $data = $this->formatLogsForExport($logs);
        
        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        // TODO: Implement Excel export using Laravel-Excel
        session()->flash('info', 'Eksport do Excel zostanie wkrótce zaimplementowany.');
        
        return response()->streamDownload(function() use ($data) {
            // Excel generation logic will be implemented
        }, $filename);
    }

    protected function exportToCsv($logs)
    {
        $data = $this->formatLogsForExport($logs);
        
        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        return response()->streamDownload(function() use ($data) {
            $handle = fopen('php://output', 'w');
            
            // CSV headers
            $headers = array_keys($this->exportFields);
            $headers = array_filter($headers, function($key) {
                return $this->exportFields[$key];
            });
            fputcsv($handle, $headers);
            
            // CSV data
            foreach ($data as $row) {
                $csvRow = [];
                foreach ($headers as $header) {
                    $csvRow[] = $row[$header] ?? '';
                }
                fputcsv($handle, $csvRow);
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function formatLogsForExport($logs)
    {
        return $logs->map(function ($log) {
            $data = [
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'user_name' => $log->user ? $log->user->full_name : 'System',
                'action' => $log->action,
                'model_type' => class_basename($log->model_type),
                'model_id' => $log->model_id,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'changes' => $this->formatChangesForExport($log),
                'old_values' => json_encode($log->old_values),
                'new_values' => json_encode($log->new_values)
            ];
            
            // Filter only selected fields
            return array_filter($data, function($key) {
                return $this->exportFields[$key] ?? false;
            }, ARRAY_FILTER_USE_KEY);
        })->toArray();
    }

    protected function formatChangesForExport($log)
    {
        if (empty($log->old_values) && empty($log->new_values)) {
            return '';
        }
        
        $changes = [];
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];
        
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        
        foreach ($allKeys as $key) {
            $old = $oldValues[$key] ?? null;
            $new = $newValues[$key] ?? null;
            
            if ($old !== $new) {
                $changes[] = "{$key}: '{$old}' → '{$new}'";
            }
        }
        
        return implode('; ', $changes);
    }

    // ==========================================
    // ANALYTICS & STATS
    // ==========================================

    protected function calculateStats()
    {
        $query = AuditLog::query();
        
        if ($this->dateFromFilter) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFromFilter)->startOfDay());
        }
        
        if ($this->dateToFilter) {
            $query->where('created_at', '<=', Carbon::parse($this->dateToFilter)->endOfDay());
        }
        
        // Activity stats
        $this->activityStats = [
            'total_logs' => $query->count(),
            'unique_users' => $query->distinct('user_id')->whereNotNull('user_id')->count(),
            'unique_ips' => $query->distinct('ip_address')->whereNotNull('ip_address')->count(),
            'system_actions' => $query->whereNull('user_id')->count(),
        ];
        
        // Top users by activity
        $this->topUsers = $query->select('user_id', DB::raw('count(*) as activity_count'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('activity_count', 'desc')
            ->limit(10)
            ->with('user')
            ->get()
            ->map(function ($item) {
                return [
                    'user' => User::find($item->user_id),
                    'count' => $item->activity_count
                ];
            })
            ->filter(function ($item) {
                return $item['user'] !== null;
            });
        
        // Top actions (event column in DB, 'action' alias for display)
        $this->topActions = $query->select('event', DB::raw('count(*) as action_count'))
            ->groupBy('event')
            ->orderBy('action_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'action' => $item->event,
                    'count' => $item->action_count
                ];
            });
    }

    protected function detectSuspiciousActivity()
    {
        if (!$this->showSuspiciousAlerts) {
            return;
        }
        
        $this->suspiciousActivities = [];
        
        // Detect multiple failed login attempts
        $failedLogins = AuditLog::where('event', 'login_failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->select('ip_address', DB::raw('count(*) as attempts'))
            ->groupBy('ip_address')
            ->having('attempts', '>', 5)
            ->get();
            
        foreach ($failedLogins as $failedLogin) {
            $this->suspiciousActivities[] = [
                'type' => 'multiple_failed_logins',
                'severity' => 'high',
                'message' => "Wielokrotne nieudane próby logowania z IP: {$failedLogin->ip_address} ({$failedLogin->attempts} prób)",
                'ip_address' => $failedLogin->ip_address,
                'count' => $failedLogin->attempts
            ];
        }
        
        // Detect unusual login times (outside business hours)
        $unusualLogins = AuditLog::where('event', 'login')
            ->where('created_at', '>=', now()->subDays(7))
            ->get()
            ->filter(function ($log) {
                $hour = $log->created_at->hour;
                return $hour < 6 || $hour > 22; // Before 6 AM or after 10 PM
            });
            
        if ($unusualLogins->count() > 10) {
            $this->suspiciousActivities[] = [
                'type' => 'unusual_login_times',
                'severity' => 'medium',
                'message' => "Nietypowe godziny logowania: {$unusualLogins->count()} logowań poza godzinami pracy",
                'count' => $unusualLogins->count()
            ];
        }
        
        // Detect bulk operations
        $bulkOperations = AuditLog::whereIn('event', ['bulk_delete', 'bulk_update', 'bulk_export'])
            ->where('created_at', '>=', now()->subDays(7))
            ->with('user')
            ->get()
            ->groupBy('user_id');
            
        foreach ($bulkOperations as $userId => $operations) {
            if ($operations->count() > 20) {
                $user = $operations->first()->user;
                $this->suspiciousActivities[] = [
                    'type' => 'excessive_bulk_operations',
                    'severity' => 'medium',
                    'message' => "Nadmierne operacje masowe przez użytkownika: {$user->full_name} ({$operations->count()} operacji)",
                    'user' => $user,
                    'count' => $operations->count()
                ];
            }
        }
    }

    public function dismissSuspiciousActivity($index)
    {
        unset($this->suspiciousActivities[$index]);
        $this->suspiciousActivities = array_values($this->suspiciousActivities);
    }

    // ==========================================
    // VIEW MODE MANAGEMENT
    // ==========================================

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        
        if ($mode === 'chart') {
            $this->calculateChartData();
        }
    }

    protected function calculateChartData()
    {
        // Chart data calculation will be implemented for timeline view
        // This would generate data for activity charts by day/week/month
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getLogsProperty()
    {
        return $this->getLogsQuery()->paginate($this->perPage);
    }

    protected function getLogsQuery()
    {
        $query = AuditLog::with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('event', 'like', '%' . $this->search . '%')
                      ->orWhere('auditable_type', 'like', '%' . $this->search . '%')
                      ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function ($userQuery) {
                          $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                                   ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                   ->orWhere('email', 'like', '%' . $this->search . '%');
                      })
                      // JSON search in old_values and new_values
                      ->orWhereRaw('JSON_SEARCH(old_values, "one", ?) IS NOT NULL', ['%' . $this->search . '%'])
                      ->orWhereRaw('JSON_SEARCH(new_values, "one", ?) IS NOT NULL', ['%' . $this->search . '%']);
                });
            })
            ->when($this->userFilter !== 'all', function ($query) {
                if ($this->userFilter === 'system') {
                    $query->whereNull('user_id');
                } else {
                    $query->where('user_id', $this->userFilter);
                }
            })
            ->when($this->actionFilter !== 'all', function ($query) {
                $query->where('event', $this->actionFilter);
            })
            ->when($this->modelFilter !== 'all', function ($query) {
                $query->where('auditable_type', 'like', '%' . $this->modelFilter);
            })
            ->when($this->dateFromFilter, function ($query) {
                $query->where('created_at', '>=', Carbon::parse($this->dateFromFilter)->startOfDay());
            })
            ->when($this->dateToFilter, function ($query) {
                $query->where('created_at', '<=', Carbon::parse($this->dateToFilter)->endOfDay());
            })
            ->when($this->ipFilter, function ($query) {
                $query->where('ip_address', 'like', '%' . $this->ipFilter . '%');
            })
            ->when($this->suspiciousOnly, function ($query) {
                $query->where(function ($q) {
                    $q->where('event', 'like', '%failed%')
                      ->orWhere('event', 'like', '%bulk_%')
                      ->orWhereRaw('HOUR(created_at) < 6 OR HOUR(created_at) > 22');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function getUsersProperty()
    {
        return User::orderBy('first_name')->get();
    }

    public function getActionsProperty()
    {
        return AuditLog::select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');
    }

    public function getModelsProperty()
    {
        return AuditLog::select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->get()
            ->map(function ($item) {
                return [
                    'full' => $item->auditable_type,
                    'short' => class_basename($item->auditable_type)
                ];
            })
            ->unique('short')
            ->values();
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    public function getFormattedChanges($log)
    {
        if (empty($log->old_values) && empty($log->new_values)) {
            return [];
        }
        
        $changes = [];
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];
        
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        
        foreach ($allKeys as $key) {
            $old = $oldValues[$key] ?? null;
            $new = $newValues[$key] ?? null;
            
            if ($old !== $new) {
                $changes[$key] = [
                    'old' => $old,
                    'new' => $new,
                    'field' => $this->humanizeFieldName($key)
                ];
            }
        }
        
        return $changes;
    }

    protected function humanizeFieldName($field)
    {
        $mapping = [
            'first_name' => 'Imię',
            'last_name' => 'Nazwisko',
            'email' => 'Email',
            'company' => 'Firma',
            'is_active' => 'Status aktywności',
            'name' => 'Nazwa',
            'description' => 'Opis',
            'price' => 'Cena',
            'stock' => 'Stan magazynowy'
        ];
        
        return $mapping[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.audit-logs', [
            'logs' => $this->logs,
            'users' => $this->users,
            'actions' => $this->actions,
            'models' => $this->models,
        ])->layout('layouts.admin', [
            'title' => 'Logi Audytu - Admin PPM',
            'breadcrumb' => 'Logi audytu'
        ]);
    }
}
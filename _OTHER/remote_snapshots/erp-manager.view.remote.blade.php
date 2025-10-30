<div>
    <!-- ERP Integration Management Dashboard -->
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">ERP Integration Management</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <button wire:click="$refresh" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addERPModal">
                        <i class="bi bi-plus-circle"></i> Add ERP Connection
                    </button>
                </div>
            </div>
        </div>

        <!-- Connection Status Overview -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Connections</h6>
                                <h2 class="mb-0">{{ count($connections) }}</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-diagram-3 fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Active Connections</h6>
                                <h2 class="mb-0">{{ collect($connections)->where('status', 'connected')->count() }}</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-check-circle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Failed Connections</h6>
                                <h2 class="mb-0">{{ collect($connections)->where('status', 'error')->count() }}</h2>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERP Connections List -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ERP Connections</h5>
                    <div class="input-group" style="width: 300px;">
                        <input wire:model="search" type="text" class="form-control" placeholder="Search connections...">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if(empty($connections))
                    <div class="text-center py-4">
                        <i class="bi bi-diagram-3 text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">No ERP connections configured</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addERPModal">
                            <i class="bi bi-plus-circle"></i> Add Your First ERP Connection
                        </button>
                    </div>
                @else
                    <div class="row">
                        @foreach($connections as $connection)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 border-{{ $connection['status_color'] ?? 'secondary' }}">
                                    <div class="card-header bg-{{ $connection['status_color'] ?? 'secondary' }} text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">{{ $connection['name'] ?? 'Unknown ERP' }}</h6>
                                            <span class="badge bg-light text-dark">{{ $connection['type'] ?? 'Unknown' }}</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="status-indicator bg-{{ $connection['status_color'] ?? 'secondary' }} me-2"></div>
                                            <small class="text-muted">{{ ucfirst($connection['status'] ?? 'Unknown') }}</small>
                                        </div>
                                        <p class="card-text small">
                                            <strong>Last Sync:</strong> {{ $connection['last_sync'] ?? 'Never' }}<br>
                                            <strong>Success Rate:</strong> {{ $connection['success_rate'] ?? 'N/A' }}<br>
                                            <strong>API Version:</strong> {{ $connection['api_version'] ?? 'Unknown' }}
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="btn-group w-100" role="group">
                                            <button wire:click="testConnection({{ $connection['id'] }})" 
                                                    class="btn btn-sm btn-outline-primary"
                                                    @if($connection['status'] === 'testing') disabled @endif>
                                                @if($connection['status'] === 'testing')
                                                    <span class="spinner-border spinner-border-sm me-1"></span>
                                                @else
                                                    <i class="bi bi-wifi"></i>
                                                @endif
                                                Test
                                            </button>
                                            <button wire:click="syncERP({{ $connection['id'] }})" 
                                                    class="btn btn-sm btn-outline-success"
                                                    @if($connection['status'] !== 'connected') disabled @endif>
                                                <i class="bi bi-arrow-repeat"></i> Sync
                                            </button>
                                            <button wire:click="showDetails({{ $connection['id'] }})" 
                                                    class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-gear"></i> Config
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Sync Activity -->
        @if(!empty($recentActivity))
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Recent Sync Activity</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ERP System</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Records</th>
                                <th>Duration</th>
                                <th>Started</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentActivity as $activity)
                                <tr>
                                    <td>{{ $activity['erp_name'] ?? 'Unknown' }}</td>
                                    <td>{{ $activity['type'] ?? 'Unknown' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $activity['status_color'] ?? 'secondary' }}">
                                            {{ ucfirst($activity['status'] ?? 'Unknown') }}
                                        </span>
                                    </td>
                                    <td>{{ $activity['records_processed'] ?? 0 }}</td>
                                    <td>{{ $activity['duration'] ?? 'N/A' }}</td>
                                    <td>{{ $activity['started_at'] ?? 'N/A' }}</td>
                                    <td>
                                        @if($activity['status'] === 'failed')
                                            <button wire:click="syncERP({{ $activity['id'] }})" 
                                                    class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-arrow-clockwise"></i> Retry
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Add ERP Connection Modal -->
    <div class="modal fade" id="addERPModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add ERP Connection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">ERP System Type</label>
                            <select wire:model="connectionForm.erp_type" class="form-select">
                                <option value="">Select ERP Type...</option>
                                <option value="baselinker">Baselinker</option>
                                <option value="subiekt_gt">Subiekt GT</option>
                                <option value="dynamics">Microsoft Dynamics</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Connection Name</label>
                            <input wire:model="connectionForm.instance_name" type="text" class="form-control" 
                                   placeholder="e.g., Main Baselinker Account">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API URL / Host</label>
                            <input wire:model="dynamicsConfig.odata_url" type="url" class="form-control" 
                                   placeholder="https://api.baselinker.com">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">API Key / Username</label>
                                    <input wire:model="baselinkerConfig.api_token" type="text" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">API Secret / Password</label>
                                    <input wire:model="dynamicsConfig.client_secret" type="password" autocomplete="current-password" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select wire:model="connectionForm.priority" class="form-select">
                                <option value="1">High Priority</option>
                                <option value="2" selected>Normal Priority</option>
                                <option value="3">Low Priority</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input wire:model="connectionForm.auto_sync_products" class="form-check-input" type="checkbox" id="autoSync">
                            <label class="form-check-label" for="autoSync">
                                Enable automatic synchronization
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button wire:click="testAuthentication" class="btn btn-outline-primary">Test Connection</button>
                    <button wire:click="completeWizard" class="btn btn-primary">Save Connection</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</div>


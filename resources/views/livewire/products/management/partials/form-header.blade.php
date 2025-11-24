{{-- Header Section --}}
<div class="mb-6 px-4 xl:px-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-dark-primary mb-2">
                @if($isEditMode)
                    <i class="fas fa-edit text-mpp-orange mr-2"></i>
                    Edytuj produkt
                @else
                    <i class="fas fa-plus-circle text-green-400 mr-2"></i>
                    Nowy produkt
                @endif
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb-dark flex items-center space-x-2 text-sm">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-mpp-orange">
                            <i class="fas fa-home"></i> Panel administracyjny
                        </a>
                    </li>
                    <li class="text-dark-muted">></li>
                    <li>
                        <a href="{{ route('admin.products.index') }}" class="hover:text-mpp-orange">
                            <i class="fas fa-box"></i> Produkty
                        </a>
                    </li>
                    <li class="text-dark-muted">></li>
                    <li class="text-dark-secondary">
                        @if($isEditMode)
                            Edycja: {{ $name ?? 'Produkt' }}
                        @else
                            Nowy produkt
                        @endif
                    </li>
                </ol>
            </nav>
        </div>
        <div class="flex gap-4">
            @if($hasUnsavedChanges)
                <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Niezapisane zmiany
                </span>
            @endif
            <a href="{{ route('admin.products.index') }}"
               class="btn-enterprise-secondary">
                <i class="fas fa-times"></i>
                Anuluj
            </a>
        </div>
    </div>
</div>

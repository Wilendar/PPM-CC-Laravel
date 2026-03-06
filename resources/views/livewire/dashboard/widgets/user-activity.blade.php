<div class="dashboard-widget" role="region" aria-label="Moja aktywnosc">
    {{-- Header --}}
    <div class="dashboard-widget__header">
        <span class="dashboard-widget__title">Moja aktywnosc</span>
        <div class="dashboard-widget__icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>

    {{-- Activity feed --}}
    <div class="max-h-64 overflow-y-auto">
        @forelse ($activities as $activity)
            <div wire:key="activity-{{ $activity->id }}" class="activity-item">
                <div class="activity-item__icon {{ $this->getEventIconClass($activity->event) }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="{{ $this->getEventIconPath($activity->event) }}" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-200 truncate">
                        {{ $this->getActivityDescription($activity) }}
                    </p>
                    <p class="activity-item__time">
                        {{ $activity->created_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        @empty
            <div class="text-center py-6">
                <svg class="w-8 h-8 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-sm text-gray-500">Brak aktywnosci</p>
            </div>
        @endforelse
    </div>
</div>

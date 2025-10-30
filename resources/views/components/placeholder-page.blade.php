@props(['title', 'message', 'etap' => null, 'instructions' => null, 'steps' => [], 'features' => []])

<x-admin-layout>
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="enterprise-card max-w-3xl w-full text-center">
            <!-- Icon -->
            <div class="mb-6">
                @if(count($steps) > 0 || count($features) > 0)
                    <svg class="w-20 h-20 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @else
                    <svg class="w-24 h-24 mx-auto text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                @endif
            </div>

            <!-- Title -->
            <h1 class="text-h1 font-bold mb-4" style="color: #e0ac7e;">{{ $title }}</h1>

            <!-- Message -->
            <p class="text-lg text-gray-300 mb-6">{{ $message }}</p>

            <!-- ETAP Info (if provided) -->
            @if($etap)
                <div class="inline-block px-4 py-2 rounded-lg mb-6" style="background: rgba(224, 172, 126, 0.1); border: 1px solid rgba(224, 172, 126, 0.3);">
                    <p class="text-sm font-semibold" style="color: #e0ac7e;">{{ $etap }}</p>
                </div>
            @endif

            <!-- Instructions & Steps (if provided) -->
            @if($instructions || count($steps) > 0)
                <div class="mt-8 mb-8 text-left">
                    @if($instructions)
                        <h3 class="text-h3 font-semibold mb-4 text-gray-200">{{ $instructions }}</h3>
                    @endif

                    @if(count($steps) > 0)
                        <ol class="space-y-3 text-gray-300">
                            @foreach($steps as $step)
                                <li class="flex items-start">
                                    <span class="mr-3">{!! $step !!}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            @endif

            <!-- Features (if provided) -->
            @if(count($features) > 0)
                <div class="mt-6 mb-8">
                    <h4 class="text-h4 font-semibold mb-3 text-gray-200">Dostępne funkcje:</h4>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-3 text-left text-gray-300">
                        @foreach($features as $feature)
                            <li class="flex items-start">
                                <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Back Button -->
            <div class="mt-8">
                <a href="/admin" class="btn-enterprise-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Powrót do Dashboard
                </a>
            </div>
        </div>
    </div>
</x-admin-layout>

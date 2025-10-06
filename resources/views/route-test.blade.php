<!DOCTYPE html>
<html>
<head>
    <title>Route Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .test-box { background: white; padding: 20px; border-radius: 8px; margin: 10px 0; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🔍 Route Test - admin/products/categories/create</h1>

    <div class="test-box">
        <h2>📍 Current Route Info</h2>
        <p><strong>Current URL:</strong> {{ url()->current() }}</p>
        <p><strong>Current Route:</strong> {{ Route::currentRouteName() ?? 'N/A' }}</p>
        <p><strong>Route Parameters:</strong> {{ json_encode(request()->route()?->parameters() ?? []) }}</p>
        <p><strong>Request Method:</strong> {{ request()->method() }}</p>
        <p><strong>User Agent:</strong> {{ request()->userAgent() }}</p>
    </div>

    <div class="test-box">
        <h2>🔧 Middleware Info</h2>
        <p><strong>Route Middleware:</strong>
            @php
                $middleware = request()->route()?->gatherMiddleware() ?? [];
            @endphp
            {{ implode(', ', $middleware) }}
        </p>
    </div>

    <div class="test-box">
        <h2>✅ Expected vs Actual</h2>
        <p><strong>Expected:</strong> Strona z CategoryForm (5 zakładek, MPP TRADE admin panel)</p>
        <p><strong>Actual:</strong> {{ request()->path() === 'admin/products/categories/create' ? 'PPM-CC-Laravel route' : 'Unknown route' }}</p>
    </div>

    <div class="test-box">
        <h2>🚀 Direct Component Test</h2>
        <p>Jeśli to się ładuje, to route działa:</p>
        @livewire('products.categories.category-form')
    </div>

    @livewireStyles
    @livewireScripts
</body>
</html>